<?php
const STALE_SECS = 7200;

const COUNTY_CENTROIDS = [
    'nairobi'=>[-1.2921,36.8219],'mombasa'=>[-4.0435,39.6682],'kisumu'=>[-0.0917,34.7679],
    'nakuru'=>[-0.3031,36.0800],'eldoret'=>[0.5143,35.2698],'uasin gishu'=>[0.5500,35.2700],
    'kakamega'=>[0.2827,34.7519],'meru'=>[0.0476,37.6490],'nyeri'=>[-0.4167,36.9500],
    'machakos'=>[-1.5177,37.2634],'kitui'=>[-1.3671,38.0106],'kiambu'=>[-1.0312,36.8318],
    'muranga'=>[-0.7167,37.1500],'kirinyaga'=>[-0.5600,37.3300],'embu'=>[-0.5330,37.4500],
    'tharaka nithi'=>[-0.3000,38.0000],'isiolo'=>[0.3542,37.5821],'marsabit'=>[2.3284,37.9899],
    'garissa'=>[-0.4532,39.6461],'wajir'=>[1.7471,40.0573],'mandera'=>[3.9366,41.8670],
    'turkana'=>[3.1000,35.6000],'west pokot'=>[1.7167,35.1167],'samburu'=>[1.2000,36.9000],
    'trans nzoia'=>[1.0566,35.0020],'baringo'=>[0.8000,35.9500],'laikipia'=>[-0.2000,36.7000],
    'nyandarua'=>[-0.1833,36.5000],'bungoma'=>[0.5635,34.5606],'busia'=>[0.4608,34.1110],
    'siaya'=>[-0.0617,34.2422],'homa bay'=>[-0.5177,34.4569],'migori'=>[-1.0634,34.4731],
    'kisii'=>[-0.6817,34.7660],'nyamira'=>[-0.5667,34.9333],'bomet'=>[-0.7833,35.3500],
    'kericho'=>[-0.3690,35.2863],'nandi'=>[0.1833,35.1000],'vihiga'=>[0.0833,34.7167],
    'narok'=>[-1.0834,35.8699],'kajiado'=>[-1.8520,36.7820],'makueni'=>[-1.8036,37.6236],
    'taita taveta'=>[-3.3167,38.4833],'kwale'=>[-4.1736,39.4525],'kilifi'=>[-3.5107,39.8499],
    'tana river'=>[-1.5000,40.1000],'lamu'=>[-2.2717,40.9022],
];

function county_coords(string $c): ?array {
    $k=strtolower(trim($c)); if($k==='') return null;
    foreach(COUNTY_CENTROIDS as $ck=>$v){ if(str_starts_with($k,$ck)||str_starts_with($ck,$k)) return $v; }
    return null;
}

$m=@new mysqli('localhost','uvyzhdzt_mtti','mtti_sync_2026!','uvyzhdzt_mtti');
$dbError=$m->connect_errno?$m->connect_error:null;
if(!$dbError)$m->set_charset('utf8mb4');

function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function num($n,$dp=0){return $n===null?'—':number_format((float)$n,$dp);}
function pct($n,$dp=1){return($n===null||$n==='')?'—':number_format((float)$n,$dp).'%';}
function lastSeen(?int $s):string{
    if($s===null)return 'never';
    if($s<120)return 'just now';
    if($s<3600)return floor($s/60).' min ago';
    if($s<86400)return floor($s/3600).' hr ago';
    $d=floor($s/86400);return $d===1.0?'yesterday':((int)$d).' days ago';
}
function milestone(int $n):?string{
    if($n>=100)return '🏆';if($n>=50)return '🌳';if($n>=25)return '🌿';if($n>=10)return '🌱';return null;
}

$groups=[];$markers=[];$nowTs=time();
$totalDevices=0;$totalStudents=0;$totalLessons=0;$totalCerts=0;$onlineCount=0;
$weightedScoreNum=0.0;$weightedScoreDen=0;

if(!$dbError){
    $r=$m->query("SELECT device_id,name,county,region,lat,lng,
                         student_count,student_count_prev,lesson_completions,
                         quiz_attempts,avg_score,cert_count,active_last_30,
                         avg_sync_interval_secs,last_sync_at,is_active
                  FROM devices WHERE is_active=1 AND last_sync_at IS NOT NULL
                  ORDER BY region,name");
    if($r) while($row=$r->fetch_assoc()){
        $totalDevices++;
        $totalStudents+=(int)$row['student_count'];
        $totalLessons+=(int)$row['lesson_completions'];
        $totalCerts+=(int)$row['cert_count'];
        if((int)$row['quiz_attempts']>0&&$row['avg_score']!==null){
            $weightedScoreNum+=(float)$row['avg_score']*(int)$row['quiz_attempts'];
            $weightedScoreDen+=(int)$row['quiz_attempts'];
        }

        $syncTs=$row['last_sync_at']?strtotime($row['last_sync_at']):null;
        $ageSec=$syncTs?max(0,$nowTs-$syncTs):null;
        $isStale=$ageSec===null||$ageSec>STALE_SECS;
        if(!$isStale)$onlineCount++;

        $offlineLabel=null;
        if($isStale&&$ageSec!==null){
            $days=floor($ageSec/86400);$hrs=floor(($ageSec%86400)/3600);
            $offlineLabel=$days>0?"Offline {$days}d".($hrs>0?" {$hrs}h":''):"Offline {$hrs}h";
        } elseif($isStale){$offlineLabel='Never synced';}

        $certRate=($row['student_count']>0&&$row['cert_count']>0)
            ?round($row['cert_count']/$row['student_count']*100,1):null;

        $row['_age']=$ageSec;$row['_stale']=$isStale;
        $row['_offline_label']=$offlineLabel;$row['_cert_rate']=$certRate;

        $group=trim((string)($row['region']??''))?:(trim((string)$row['county'])?:'Unassigned');
        if(!isset($groups[$group]))$groups[$group]=[];
        $groups[$group][]=$row;

        $hasGps=!empty($row['lat'])&&!empty($row['lng']);
        $lat=$hasGps?(float)$row['lat']:null;
        $lng=$hasGps?(float)$row['lng']:null;
        if(!$hasGps){$c=county_coords((string)($row['county']?:$row['region']?:''));if($c)[$lat,$lng]=$c;}

        if($lat&&$lng){
            $markers[]=[
                'name'=>$row['name'],'group'=>$group,'county'=>$row['county'],
                'lat'=>$lat,'lng'=>$lng,'has_gps'=>$hasGps,
                'students'=>(int)$row['student_count'],
                'lessons'=>(int)$row['lesson_completions'],
                'quizzes'=>(int)$row['quiz_attempts'],
                'avg_score'=>$row['avg_score']===null?null:(float)$row['avg_score'],
                'certs'=>(int)$row['cert_count'],'cert_rate'=>$certRate,
                'active_30'=>(int)$row['active_last_30'],
                'stale'=>$isStale,'last_seen'=>lastSeen($ageSec),
                'offline_label'=>$offlineLabel,'device_id'=>$row['device_id'],
                'sync_interval'=>$row['avg_sync_interval_secs']?round($row['avg_sync_interval_secs']/60,1):null,
                'student_delta'=>$row['student_count_prev']!==null?((int)$row['student_count']-(int)$row['student_count_prev']):null,
                'milestone'=>milestone((int)$row['student_count']),
            ];
        }
    }
}

$globalAvgScore=$weightedScoreDen>0?round($weightedScoreNum/$weightedScoreDen,1):null;
$globalCertRate=$totalStudents>0?round($totalCerts/$totalStudents*100,1):null;

// Region rollup
$regionRollup=[];
foreach($groups as $gName=>$devs){
    $rl=['students'=>0,'certs'=>0,'devices'=>count($devs),'online'=>0,'score_sum'=>0,'score_n'=>0];
    foreach($devs as $d){
        $rl['students']+=(int)$d['student_count'];$rl['certs']+=(int)$d['cert_count'];
        if(!$d['_stale'])$rl['online']++;
        if($d['avg_score']!==null&&(int)$d['quiz_attempts']>0){$rl['score_sum']+=(float)$d['avg_score']*(int)$d['quiz_attempts'];$rl['score_n']+=(int)$d['quiz_attempts'];}
    }
    $rl['cert_rate']=$rl['students']>0?round($rl['certs']/$rl['students']*100,1):null;
    $rl['avg_score']=$rl['score_n']>0?round($rl['score_sum']/$rl['score_n'],1):null;
    $regionRollup[$gName]=$rl;
}
ksort($groups);
$markersJson=json_encode($markers,JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MTTI LMS — Field Devices Map</title>
<link rel="stylesheet" href="/leaflet.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Roboto,Arial,sans-serif;background:#f5f7fa;color:#1f2937;line-height:1.5;}
header{background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;padding:24px 20px;text-align:center;}
header h1{font-size:1.6rem;margin-bottom:4px;}
header p{font-size:.9rem;opacity:.9;}
.kpis{display:flex;gap:12px;max-width:1100px;margin:20px auto;padding:0 16px;flex-wrap:wrap;}
.kpi{background:#fff;border-radius:12px;padding:16px;flex:1 1 140px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.kpi .num{font-size:1.7rem;font-weight:800;color:#1e3a8a;}
.kpi .lbl{font-size:.75rem;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-top:4px;}
#map{height:480px;max-width:1100px;margin:16px auto;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);background:#e5e7eb;}
.map-cap{max-width:1100px;margin:6px auto 0;padding:0 16px;font-size:.78rem;color:#6b7280;text-align:right;}
.map-cap a{color:#6b7280;text-decoration:none;}
/* toolbar */
.toolbar{max-width:1100px;margin:16px auto 0;padding:0 16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
.toolbar input{flex:1;min-width:180px;padding:8px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem;outline:none;}
.toolbar input:focus{border-color:#2563eb;}
.view-btns{display:flex;border:1px solid #d1d5db;border-radius:8px;overflow:hidden;}
.view-btn{padding:7px 16px;font-size:.82rem;font-weight:600;background:#fff;border:none;cursor:pointer;color:#374151;}
.view-btn.active{background:#1e3a8a;color:#fff;}
/* groups */
.groups{max-width:1100px;margin:16px auto 60px;padding:0 16px;}
.group{margin-bottom:28px;}
.group-head{display:flex;align-items:center;gap:10px;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #dbeafe;}
.group-head h2{font-size:1.1rem;color:#1e3a8a;}
.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px;}
/* card */
.card{background:#fff;border-radius:10px;padding:16px;border-left:4px solid #2563eb;box-shadow:0 1px 3px rgba(0,0,0,.05);transition:box-shadow .15s;}
.card:hover{box-shadow:0 4px 12px rgba(0,0,0,.1);}
.card.stale{border-left-color:#9ca3af;background:#fafafa;}
.card.stale h3{color:#6b7280;}
.card-title{display:flex;align-items:flex-start;justify-content:space-between;gap:6px;margin-bottom:4px;}
.card h3{font-size:1rem;color:#111827;flex:1;}
.milestone-badge{font-size:1.2rem;line-height:1;}
.card .sub{font-size:.78rem;color:#6b7280;margin-bottom:10px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;}
.tag-region{background:#dbeafe;color:#1e40af;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:8px;}
.cert-rate{display:inline-flex;align-items:center;gap:4px;background:#eff6ff;color:#1d4ed8;font-size:.75rem;font-weight:700;padding:2px 8px;border-radius:8px;}
.metric{display:flex;justify-content:space-between;padding:3px 0;font-size:.85rem;}
.metric .lbl{color:#555;}
.metric .val{font-weight:700;color:#1e3a8a;}
.val.fresh{background:#dcfce7;color:#166534;font-size:.8rem;padding:1px 8px;border-radius:10px;}
.val.stale-lbl{background:#f3f4f6;color:#6b7280;font-size:.8rem;padding:1px 8px;border-radius:10px;}
/* score bar */
.score-wrap{margin:8px 0 4px;}
.score-labels{display:flex;justify-content:space-between;font-size:.75rem;color:#6b7280;margin-bottom:3px;}
.score-val{font-weight:700;}
.score-track{height:8px;background:#f3f4f6;border-radius:4px;overflow:hidden;}
.score-bar{height:100%;border-radius:4px;transition:width .3s;}
/* rollup */
.rollup-card{background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.05);border-top:4px solid #1e3a8a;}
.rollup-card h3{font-size:1.05rem;color:#1e3a8a;margin-bottom:12px;}
.rollup-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px;}
.rs{text-align:center;padding:8px;background:#f9fafb;border-radius:8px;}
.rs .rv{font-size:1.2rem;font-weight:800;color:#1e3a8a;}
.rs .rl{font-size:.7rem;color:#6b7280;text-transform:uppercase;}
.empty{text-align:center;padding:40px;color:#6b7280;}
footer{text-align:center;font-size:.78rem;color:#6b7280;padding:18px;}
.hidden{display:none!important;}
/* popup */
.leaflet-popup-content{margin:10px 14px;font-family:'Segoe UI',Roboto,Arial,sans-serif;min-width:220px;}
.leaflet-popup-content h4{font-size:1rem;color:#1e3a8a;margin-bottom:6px;}
.pop-row{display:flex;justify-content:space-between;gap:14px;font-size:.84rem;padding:2px 0;}
.pop-row .l{color:#555;}.pop-row .v{font-weight:700;color:#1e3a8a;}
.pop-score{margin:6px 0 2px;padding:6px 8px;background:#eff6ff;border-radius:6px;font-size:.82rem;}
@media(max-width:600px){#map{height:340px;}.kpi .num{font-size:1.4rem;}.rollup-stats{grid-template-columns:repeat(2,1fr);}}
</style>
</head>
<body>
<header>
  <h1>📡 MTTI LMS — Field Devices</h1>
  <p>Masomotele Technical Training Institute · Live sync dashboard</p>
</header>

<section class="kpis">
  <div class="kpi"><div class="num"><?= $totalDevices ?></div><div class="lbl">Devices</div></div>
  <div class="kpi"><div class="num"><?= $onlineCount ?>/<?= $totalDevices ?></div><div class="lbl">Online &lt;2h</div></div>
  <div class="kpi"><div class="num"><?= num($totalStudents) ?></div><div class="lbl">Students</div></div>
  <div class="kpi"><div class="num"><?= $globalCertRate!==null?pct($globalCertRate):'—' ?></div><div class="lbl">Cert Rate</div></div>
  <div class="kpi"><div class="num"><?= $globalAvgScore!==null?pct($globalAvgScore):'—' ?></div><div class="lbl">Avg Quiz Score</div></div>
  <div class="kpi"><div class="num"><?= num($totalLessons) ?></div><div class="lbl">Lessons Done</div></div>
  <div class="kpi"><div class="num"><?= num($totalCerts) ?></div><div class="lbl">Certificates</div></div>
</section>

<div id="map"></div>
<div class="map-cap">
  🔵 Online (GPS) &nbsp; 🟣 Online (approx.) &nbsp; ⚫ Offline &nbsp;·&nbsp;
  &copy; <a href="https://openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>
  &copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>
</div>

<div class="toolbar">
  <input type="search" id="search" placeholder="🔍  Search devices..." oninput="filterCards()">
  <div class="view-btns">
    <button class="view-btn active" id="btn-devices" onclick="setView('devices')">Devices</button>
    <button class="view-btn" id="btn-regions" onclick="setView('regions')">Regions</button>
  </div>
</div>

<!-- Devices view -->
<section class="groups" id="view-devices">
<?php if(!$groups): ?>
  <div class="empty">No devices have synced yet.</div>
<?php else: foreach($groups as $groupName=>$devs): ?>
  <div class="group" data-group="<?= h($groupName) ?>">
    <div class="group-head">
      <h2><?= h($groupName) ?></h2>
      <span style="font-size:.8rem;color:#9ca3af;">· <?= count($devs) ?> device<?= count($devs)!==1?'s':'' ?></span>
    </div>
    <div class="cards">
    <?php foreach($devs as $d):
        $ms=milestone((int)$d['student_count']);
        $cr=$d['_cert_rate'];
        $score=$d['avg_score']!==null&&(int)$d['quiz_attempts']>0?(float)$d['avg_score']:null;
        $scoreColor=$score===null?'#9ca3af':($score>=70?'#16a34a':($score>=50?'#d97706':'#dc2626'));
    ?>
      <div class="card<?= $d['_stale']?' stale':'' ?>" data-name="<?= h(strtolower($d['name'])) ?>" data-group="<?= h(strtolower($groupName)) ?>">
        <div class="card-title">
          <h3><?= h($d['name']) ?></h3>
          <?php if($ms): ?><span class="milestone-badge" title="<?= $ms==='🏆'?'100+':($ms==='🌳'?'50+':($ms==='🌿'?'25+':'10+')) ?> students"><?= $ms ?></span><?php endif; ?>
        </div>
        <div class="sub">
          <?php if(!empty($d['region'])): ?><span class="tag-region">📍 <?= h($d['region']) ?></span><?php endif; ?>
          <?= h($d['county']?:'—') ?>
          <?php if($cr!==null): ?><span class="cert-rate">🎓 <?= pct($cr) ?> certified</span><?php endif; ?>
        </div>

        <?php if($score!==null): ?>
        <div class="score-wrap">
          <div class="score-labels">
            <span>Quiz avg score</span>
            <span class="score-val" style="color:<?= $scoreColor ?>"><?= pct($score) ?></span>
          </div>
          <div class="score-track">
            <div class="score-bar" style="width:<?= min(100,$score) ?>%;background:<?= $scoreColor ?>"></div>
          </div>
        </div>
        <?php endif; ?>

        <div class="metric"><span class="lbl">📟 Device</span><span class="val" style="font-family:monospace;font-size:.76rem;color:#6b7280;"><?= h($d['device_id']) ?></span></div>
        <div class="metric"><span class="lbl">👥 Students</span><span class="val"><?= num($d['student_count']) ?>
          <?php if($d['student_count_prev']!==null):$delta=(int)$d['student_count']-(int)$d['student_count_prev'];if($delta>0):?><span style="color:#16a34a;font-size:.78rem"> +<?= $delta ?></span><?php elseif($delta<0):?><span style="color:#dc2626;font-size:.78rem"> <?= $delta ?></span><?php endif;endif;?></span></div>
        <div class="metric"><span class="lbl">📚 Lessons done</span><span class="val"><?= num($d['lesson_completions']) ?></span></div>
        <div class="metric"><span class="lbl">🎓 Certificates</span><span class="val"><?= num($d['cert_count']) ?></span></div>
        <?php if($d['active_last_30']>0):?>
        <div class="metric"><span class="lbl">🟢 Active (30d)</span><span class="val"><?= num($d['active_last_30']) ?></span></div>
        <?php endif;?>
        <?php if(!empty($d['avg_sync_interval_secs'])):$mins=round($d['avg_sync_interval_secs']/60,1);$col=$mins<=2?'#166534':($mins<=10?'#92400e':'#991b1b');?>
        <div class="metric"><span class="lbl">🔁 Sync rate</span><span class="val" style="color:<?= $col ?>;font-size:.82rem;">every ~<?= $mins ?> min</span></div>
        <?php endif;?>
        <div class="metric"><span class="lbl">📡 Last sync</span><span class="val <?= $d['_stale']?'stale-lbl':'fresh' ?>"><?= lastSeen($d['_age']) ?></span></div>
        <?php if($d['_stale']&&$d['_offline_label']):?>
        <div class="metric"><span class="lbl">🔴 Status</span><span class="val" style="color:#dc2626;"><?= h($d['_offline_label']) ?></span></div>
        <?php endif;?>
      </div>
    <?php endforeach;?>
    </div>
  </div>
<?php endforeach;endif;?>
</section>

<!-- Region rollup view -->
<section class="groups hidden" id="view-regions">
  <div class="cards">
  <?php foreach($regionRollup as $rName=>$rl):
      $sc=$rl['avg_score'];
      $scColor=$sc===null?'#9ca3af':($sc>=70?'#16a34a':($sc>=50?'#d97706':'#dc2626'));
  ?>
  <div class="rollup-card">
    <h3>📍 <?= h($rName) ?> <span style="font-size:.8rem;color:#6b7280;font-weight:400;"><?= $rl['devices'] ?> device<?= $rl['devices']!==1?'s':'' ?> · <?= $rl['online'] ?> online</span></h3>
    <div class="rollup-stats">
      <div class="rs"><div class="rv"><?= num($rl['students']) ?></div><div class="rl">Students</div></div>
      <div class="rs"><div class="rv"><?= $rl['cert_rate']!==null?pct($rl['cert_rate']):'—' ?></div><div class="rl">Cert rate</div></div>
      <div class="rs"><div class="rv"><?= $sc!==null?pct($sc):'—' ?></div><div class="rl">Quiz avg</div></div>
    </div>
    <?php if($sc!==null):?>
    <div class="score-wrap">
      <div class="score-labels"><span>Quiz average</span><span class="score-val" style="color:<?= $scColor ?>"><?= pct($sc) ?></span></div>
      <div class="score-track"><div class="score-bar" style="width:<?= min(100,$sc) ?>%;background:<?= $scColor ?>"></div></div>
    </div>
    <?php endif;?>
  </div>
  <?php endforeach;?>
  </div>
</section>

<footer>MTTI LMS · live data from field devices · updates every minute</footer>

<script src="/leaflet.js"></script>
<script>
(function(){
  if(typeof L==='undefined'){document.getElementById('map').innerHTML='<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#6b7280;">Map unavailable</div>';return;}
  var markers=<?= $markersJson?:'[]' ?>;
  var map=L.map('map',{scrollWheelZoom:true}).setView([0.5,37.5],6);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',{maxZoom:19,subdomains:'abcd',attribution:''}).addTo(map);
  function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function fmt(n){return n==null?'—':Number(n).toLocaleString();}
  function pc(n){return n==null?'—':Number(n).toFixed(1)+'%';}
  if(!markers.length)return;
  var grp=L.featureGroup();
  markers.forEach(function(m){
    var r=m.students>=100?13:(m.students>=50?11:(m.students>=25?10:(m.students>=10?9:8)));
    var sc=m.avg_score;
    var scColor=sc==null?'#9ca3af':(sc>=70?'#16a34a':(sc>=50?'#d97706':'#dc2626'));
    var scHtml='';
    if(sc!==null){
      scHtml='<div class="pop-score">📝 Quiz avg <b style="color:'+scColor+'">'+pc(sc)+'</b>'
        +'<div style="height:6px;background:#f3f4f6;border-radius:3px;margin-top:4px;overflow:hidden;">'
        +'<div style="height:100%;width:'+Math.min(100,sc)+'%;background:'+scColor+';border-radius:3px;"></div></div></div>';
    }
    var rows=[
      ['📟 Device','<span style="font-family:monospace;font-size:.78em;color:#6b7280;">'+esc(m.device_id)+'</span>'],
      ['👥 Students',fmt(m.students)+(m.milestone?' '+m.milestone:'')+(m.student_delta>0?' <span style="color:#16a34a">+'+m.student_delta+'</span>':(m.student_delta<0?' <span style="color:#dc2626">'+m.student_delta+'</span>':''))],
      m.cert_rate!==null?['🎓 Cert rate','<b style="color:#1d4ed8">'+pc(m.cert_rate)+'</b> ('+fmt(m.certs)+')']:null,
      ['📚 Lessons done',fmt(m.lessons)],
      m.active_30>0?['🟢 Active 30d',fmt(m.active_30)]:null,
      m.sync_interval?['🔁 Sync rate','every ~'+m.sync_interval+' min']:null,
      ['📡 Last sync',m.last_seen],
      m.stale&&m.offline_label?['🔴 Status','<span style="color:#dc2626;font-weight:700;">'+esc(m.offline_label)+'</span>']:null,
    ].filter(Boolean);
    var html='<h4>'+esc(m.name)+(m.milestone?' '+m.milestone:'')+'</h4>';
    html+='<div style="font-size:.72rem;color:#6b7280;margin-bottom:6px;">📍 '+esc(m.group)+(m.county&&m.county!==m.group?' · '+esc(m.county):'')+'</div>';
    html+='<div style="font-size:.7rem;margin-bottom:8px;"><span style="background:'+(m.has_gps?'#dcfce7':'#ede9fe')+';color:'+(m.has_gps?'#166534':'#5b21b6')+';padding:2px 8px;border-radius:8px;font-weight:700;">'+(m.has_gps?'🛰 GPS':'📍 Approx. ('+esc(m.county)+')')+'</span></div>';
    html+=scHtml;
    rows.forEach(function(r){html+='<div class="pop-row"><span class="l">'+r[0]+'</span><span class="v">'+r[1]+'</span></div>';});
    var color=m.stale?'#9ca3af':'#2563eb';
    if(!m.has_gps)color=m.stale?'#d1d5db':'#7c3aed';
    var marker=m.has_gps
      ?L.circleMarker([m.lat,m.lng],{radius:r,color:'#fff',weight:2,fillColor:color,fillOpacity:m.stale?.65:.95})
      :L.marker([m.lat,m.lng],{icon:L.divIcon({className:'',html:'<div style="background:'+color+';width:28px;height:28px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;font-size:14px;">📍</div>',iconSize:[28,28],iconAnchor:[14,14]})});
    marker.bindPopup(html,{maxWidth:300}).addTo(grp);
  });
  grp.addTo(map);
  if(markers.length===1){map.setView([markers[0].lat,markers[0].lng],10);}
  else{map.fitBounds(grp.getBounds().pad(0.2));}
})();

function filterCards(){
  var q=document.getElementById('search').value.toLowerCase().trim();
  document.querySelectorAll('#view-devices .card').forEach(function(c){
    c.style.display=(!q||c.dataset.name.includes(q)||c.dataset.group.includes(q))?'':'none';
  });
  document.querySelectorAll('#view-devices .group').forEach(function(g){
    g.style.display=[].slice.call(g.querySelectorAll('.card')).some(function(c){return c.style.display!=='none';})?'':'none';
  });
}
function setView(v){
  document.getElementById('view-devices').classList.toggle('hidden',v!=='devices');
  document.getElementById('view-regions').classList.toggle('hidden',v!=='regions');
  document.getElementById('btn-devices').classList.toggle('active',v==='devices');
  document.getElementById('btn-regions').classList.toggle('active',v==='regions');
}
</script>
</body></html>
