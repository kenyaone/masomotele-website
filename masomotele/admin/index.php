<?php
session_start();
define('ADMIN_USER','mtti_admin');
define('ADMIN_PASS','Masomotele@2026');
define('BASE_DIR',dirname(__DIR__));
define('LESSONS_JSON',BASE_DIR.'/lessons.json');
define('TOGGLES_JSON',BASE_DIR.'/toggles.json');
define('ANNOUNCE_JSON',BASE_DIR.'/announcements.json');
define('MAX_BYTES',524288000);

if(isset($_POST['login'])){
  if($_POST['u']===ADMIN_USER&&$_POST['p']===ADMIN_PASS)$_SESSION['admin']=true;
  else $loginErr='Wrong username or password.';
}
if(isset($_GET['logout'])){session_destroy();header('Location: index.php');exit;}

function loadJson($path=LESSONS_JSON){
  if(!file_exists($path))return[];
  $d=json_decode(file_get_contents($path),true);
  return is_array($d)?$d:[];
}
function saveJson($data,$path=LESSONS_JSON){
  $json = json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  if(!is_writable($path) && file_exists($path)) chmod($path, 0664);
  $result = file_put_contents($path, $json, LOCK_EX);
  return $result !== false;
}
function autoTitle($fn,$subj=''){
  $n=pathinfo($fn,PATHINFO_FILENAME);
  $n=preg_replace('/^[a-z][0-9]+[-_][a-z]+-[0-9]+[-_]/i','',$n);
  $n=preg_replace('/^[a-z][0-9]+[-_][a-z]+-/i','',$n);
  $n=preg_replace('/^[a-z0-9]+-[0-9]+[-_]/i','',$n);
  $n=preg_replace('/[-_](full|lesson|interactive|exam|quiz|test|exercise|revision)$/i','',$n);
  $n=str_replace(['-','_'],' ',$n);
  $n=ucwords(strtolower(trim($n)));
  return $n?:"$subj Lesson";
}

$SUBJECTS=['f4-math'=>['Form 4','Mathematics'],'f4-phys'=>['Form 4','Physics'],'f4-chem'=>['Form 4','Chemistry'],'f4-bio'=>['Form 4','Biology'],'f4-eng'=>['Form 4','English'],'f4-kisw'=>['Form 4','Kiswahili'],'f4-hist'=>['Form 4','History'],'f4-geo'=>['Form 4','Geography'],'f4-bs'=>['Form 4','Business Studies'],'f4-cre'=>['Form 4','CRE/IRE'],'f4-agri'=>['Form 4','Agriculture'],'f4-hs'=>['Form 4','Home Science'],'f3-math'=>['Form 3','Mathematics'],'f3-phys'=>['Form 3','Physics'],'f3-chem'=>['Form 3','Chemistry'],'f3-bio'=>['Form 3','Biology'],'f3-eng'=>['Form 3','English'],'f3-kisw'=>['Form 3','Kiswahili'],'f3-hist'=>['Form 3','History'],'f3-geo'=>['Form 3','Geography'],'f3-bs'=>['Form 3','Business Studies'],'f3-cre'=>['Form 3','CRE/IRE'],'f3-agri'=>['Form 3','Agriculture'],'f3-hs'=>['Form 3','Home Science'],'g12-math'=>['Grade 12','Mathematics'],'g12-phys'=>['Grade 12','Physics'],'g12-chem'=>['Grade 12','Chemistry'],'g12-bio'=>['Grade 12','Biology'],'g12-eng'=>['Grade 12','English'],'g12-kisw'=>['Grade 12','Kiswahili'],'g12-bs'=>['Grade 12','Business Studies'],'g12-cs'=>['Grade 12','Computer Science'],'g12-hist'=>['Grade 12','History'],'g12-geo'=>['Grade 12','Geography'],'g12-elec'=>['Grade 12','Electrical Tech'],'g12-agri'=>['Grade 12','Agriculture'],'g11-math'=>['Grade 11','Mathematics'],'g11-phys'=>['Grade 11','Physics'],'g11-chem'=>['Grade 11','Chemistry'],'g11-bio'=>['Grade 11','Biology'],'g11-eng'=>['Grade 11','English'],'g11-kisw'=>['Grade 11','Kiswahili'],'g11-bs'=>['Grade 11','Business Studies'],'g11-cs'=>['Grade 11','Computer Science'],'g11-hist'=>['Grade 11','History'],'g11-geo'=>['Grade 11','Geography'],'g10-math'=>['Grade 10','Mathematics'],'g10-phys'=>['Grade 10','Physics'],'g10-chem'=>['Grade 10','Chemistry'],'g10-bio'=>['Grade 10','Biology'],'g10-eng'=>['Grade 10','English'],'g10-kisw'=>['Grade 10','Kiswahili'],'g10-bs'=>['Grade 10','Business Studies'],'g10-cs'=>['Grade 10','Computer Studies'],'g10-hist'=>['Grade 10','History'],'g10-geo'=>['Grade 10','Geography'],'g10-elec'=>['Grade 10','Electrical Tech'],'g10-pm'=>['Grade 10','Power Mechanics'],'g9-math'=>['Grade 9','Mathematics'],'g9-sci'=>['Grade 9','Integrated Science'],'g9-eng'=>['Grade 9','English'],'g9-kisw'=>['Grade 9','Kiswahili'],'g9-sst'=>['Grade 9','Social Studies'],'g9-pre'=>['Grade 9','Pre-Technical'],'g9-agri'=>['Grade 9','Agriculture'],'g8-math'=>['Grade 8','Mathematics'],'g8-sci'=>['Grade 8','Integrated Science'],'g8-eng'=>['Grade 8','English'],'g8-kisw'=>['Grade 8','Kiswahili'],'g8-sst'=>['Grade 8','Social Studies'],'g8-pre'=>['Grade 8','Pre-Technical'],'g8-agri'=>['Grade 8','Agriculture'],'g7-math'=>['Grade 7','Mathematics'],'g7-sci'=>['Grade 7','Integrated Science'],'g7-eng'=>['Grade 7','English'],'g7-kisw'=>['Grade 7','Kiswahili'],'g7-sst'=>['Grade 7','Social Studies'],'g7-pre'=>['Grade 7','Pre-Technical'],'g7-agri'=>['Grade 7','Agriculture']];

// AJAX handlers

if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='del-suggestion'){
  header('Content-Type: application/json');
  $id=$_POST['id']??'';
  $sf=BASE_DIR.'/suggestions.json';
  $d=file_exists($sf)?json_decode(file_get_contents($sf),true):[];
  $d=array_values(array_filter($d,function($s)use($id){return ($s['id']??'')!==$id;}));
  file_put_contents($sf,json_encode($d,JSON_PRETTY_PRINT));
  echo json_encode(['ok'=>true]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='clear-suggestions'){
  header('Content-Type: application/json');
  file_put_contents(BASE_DIR.'/suggestions.json','[]');
  echo json_encode(['ok'=>true]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='savegen'){
  header('Content-Type: application/json');
  $sid=trim($_POST['sid']??'');$title=trim($_POST['title']??'');$file=trim($_POST['file']??'');
  if(!$sid||!$title||!$file){echo json_encode(['ok'=>false,'msg'=>'Missing fields']);exit;}
  $data=loadJson();
  if(!isset($data[$sid]))$data[$sid]=['inter'=>[],'vid'=>[],'pdf'=>[]];
  foreach($data[$sid]['inter']??[] as $it){if($it['file']===$file){echo json_encode(['ok'=>true,'msg'=>'Already registered']);exit;}}
  $data[$sid]['inter'][]=['title'=>$title,'file'=>$file];
  saveJson($data);echo json_encode(['ok'=>true,'msg'=>'Registered: '.$title]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='branding'){
  header('Content-Type: application/json');
  $b=json_decode(file_get_contents('php://input'),true);
  if(!is_array($b)){echo json_encode(['ok'=>false]);exit;}
  file_put_contents(BASE_DIR.'/branding.json',json_encode($b,JSON_PRETTY_PRINT));
  echo json_encode(['ok'=>true]);exit;
}

if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='upload'){
  header('Content-Type: application/json');
  $sid=trim($_POST['subject_id']??'');$type=trim($_POST['type']??'');
  $year=trim($_POST['year']??'');$dur=trim($_POST['duration']??'');
  $customTitle=trim($_POST['custom_title']??'');
  if(!$sid||!$type||!isset($_FILES['file'])||$_FILES['file']['error']!==0){echo json_encode(['ok'=>false,'msg'=>'Missing fields or file error.']);exit;}
  if(!array_key_exists($sid,$SUBJECTS)){echo json_encode(['ok'=>false,'msg'=>'Invalid subject.']);exit;}
  if($_FILES['file']['size']>MAX_BYTES){echo json_encode(['ok'=>false,'msg'=>'File exceeds 500MB.']);exit;}
  $ext=strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION));
  $allowed=['inter'=>['html','htm'],'vid'=>['mp4','webm','mov','mkv'],'pdf'=>['pdf']];
  if(!in_array($ext,$allowed[$type]??[])){echo json_encode(['ok'=>false,'msg'=>"Wrong file type."]);exit;}
  [,$subj]=$SUBJECTS[$sid];
  $dirs=['inter'=>'interactives','vid'=>'videos','pdf'=>'papers'];
  $dir=BASE_DIR.'/'.$dirs[$type].'/';
  if(!is_dir($dir))mkdir($dir,0755,true);
  $safe=preg_replace('/[^a-zA-Z0-9._-]/','_',basename($_FILES['file']['name']));
  if(!move_uploaded_file($_FILES['file']['tmp_name'],$dir.$safe)){echo json_encode(['ok'=>false,'msg'=>'Save failed. Check permissions.']);exit;}
  $title=$customTitle?:autoTitle($_FILES['file']['name'],$subj);
  $data=loadJson();
  if(!isset($data[$sid]))$data[$sid]=['inter'=>[],'vid'=>[],'pdf'=>[]];
  $entry=['title'=>$title,'file'=>$safe];
  if($type==='vid'&&$dur)$entry['duration']=$dur;
  if($type==='pdf'&&$year)$entry['year']=$year;
  $data[$sid][$type][]=$entry;
  saveJson($data);
  echo json_encode(['ok'=>true,'msg'=>"Saved: $title",'title'=>$title,'file'=>$safe]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='delete'){
  header('Content-Type: application/json');
  $sid=$_POST['sid']??'';$type=$_POST['type']??'';$idx=(int)($_POST['idx']??-1);
  $data=loadJson();
  if(isset($data[$sid][$type][$idx])){
    $file=$data[$sid][$type][$idx]['file'];
    $dirs=['inter'=>'interactives','vid'=>'videos','pdf'=>'papers'];
    $path=BASE_DIR.'/'.($dirs[$type]??'').'/'.$file;
    if(file_exists($path))unlink($path);
    array_splice($data[$sid][$type],$idx,1);
    if(empty($data[$sid]['inter'])&&empty($data[$sid]['vid'])&&empty($data[$sid]['pdf']))unset($data[$sid]);
    saveJson($data);echo json_encode(['ok'=>true]);
  }else echo json_encode(['ok'=>false,'msg'=>'Not found']);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='rename'){
  header('Content-Type: application/json');
  $sid=$_POST['sid']??'';$type=$_POST['type']??'';$idx=(int)($_POST['idx']??-1);$title=trim($_POST['title']??'');
  $data=loadJson();
  if($title&&isset($data[$sid][$type][$idx])){$data[$sid][$type][$idx]['title']=$title;saveJson($data);echo json_encode(['ok'=>true]);}
  else echo json_encode(['ok'=>false]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='toggles'){
  header('Content-Type: application/json');
  $b=json_decode(file_get_contents('php://input'),true);
  if(!is_array($b)){echo json_encode(['ok'=>false]);exit;}
  saveJson($b,TOGGLES_JSON);echo json_encode(['ok'=>true]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='announce'){
  header('Content-Type: application/json');
  $b=json_decode(file_get_contents('php://input'),true);
  if(!is_array($b)){echo json_encode(['ok'=>false]);exit;}
  saveJson($b,ANNOUNCE_JSON);echo json_encode(['ok'=>true]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='del-notice'){
  header('Content-Type: application/json');
  $id=$_POST['id']??'';
  $data=loadJson(ANNOUNCE_JSON);
  $data['notices']=array_values(array_filter($data['notices']??[],function($n)use($id){return $n['id']!==$id;}));
  $ok=saveJson($data,ANNOUNCE_JSON);
  echo json_encode(['ok'=>$ok,'path'=>ANNOUNCE_JSON,'writable'=>is_writable(ANNOUNCE_JSON)]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='hide-notice'){
  header('Content-Type: application/json');
  $id=$_POST['id']??'';
  $active=$_POST['active']??'false';
  $data=loadJson(ANNOUNCE_JSON);
  foreach($data['notices']??[] as &$n){
    if($n['id']===$id){$n['active']=($active==='true');break;}
  }
  $ok=saveJson($data,ANNOUNCE_JSON);
  echo json_encode(['ok'=>$ok]);exit;
}

$jsonData=loadJson();
$toggleData=loadJson(TOGGLES_JSON);
$announceData=loadJson(ANNOUNCE_JSON);
// Popup AJAX
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='save-popup-story'){
  header('Content-Type: application/json');
  $sf=BASE_DIR.'/popup-stories.json';
  $d=file_exists($sf)?json_decode(file_get_contents($sf),true):['stories'=>[],'settings'=>[]];
  $id=(int)($_POST['id']??0);
  $story=['id'=>$id?:$id=time(),'name'=>strip_tags(trim($_POST['name']??'')),'course'=>strip_tags(trim($_POST['course']??'')),'location'=>strip_tags(trim($_POST['loc']??'')),'year'=>strip_tags(trim($_POST['year']??date('Y'))),'emoji'=>strip_tags(trim($_POST['emoji']??'🎓')),'salary_min'=>(int)($_POST['smin']??0),'salary_max'=>(int)($_POST['smax']??0),'abroad'=>($_POST['abroad']??'')==='1','story'=>strip_tags(trim($_POST['story']??'')),'highlight'=>strip_tags(trim($_POST['highlight']??''))?:null,'active'=>($_POST['active']??'')==='1'];
  $found=false;foreach($d['stories'] as &$s){if((string)$s['id']===(string)$_POST['id']){$s=$story;$found=true;break;}}
  if(!$found)$d['stories'][]=$story;
  file_put_contents($sf,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX);echo json_encode(['ok'=>true]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='del-popup-story'){
  header('Content-Type: application/json');
  $sf=BASE_DIR.'/popup-stories.json';
  $d=file_exists($sf)?json_decode(file_get_contents($sf),true):['stories'=>[],'settings'=>[]];
  $id=(int)($_POST['id']??0);
  $d['stories']=array_values(array_filter($d['stories'],fn($s)=>$s['id']!=$id));
  file_put_contents($sf,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX);echo json_encode(['ok'=>true]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='toggle-popup-story'){
  header('Content-Type: application/json');
  $sf=BASE_DIR.'/popup-stories.json';
  $d=file_exists($sf)?json_decode(file_get_contents($sf),true):['stories'=>[],'settings'=>[]];
  $id=(int)($_POST['id']??0);
  foreach($d['stories'] as &$s){if($s['id']==$id){$s['active']=!($s['active']??true);break;}}
  file_put_contents($sf,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX);echo json_encode(['ok'=>true]);exit;
}
if(!empty($_SESSION['admin'])&&($_GET['ajax']??'')==='save-popup-settings'){
  header('Content-Type: application/json');
  $sf=BASE_DIR.'/popup-stories.json';
  $d=file_exists($sf)?json_decode(file_get_contents($sf),true):['stories'=>[],'settings'=>[]];
  $d['settings']=['show_after_seconds'=>(int)($_POST['show_after']??25),'rotate_every_seconds'=>(int)($_POST['rotate_every']??5),'fb_url'=>strip_tags(trim($_POST['fb_url']??'')),'tiktok_url'=>strip_tags(trim($_POST['tiktok_url']??'')),'whatsapp'=>strip_tags(trim($_POST['whatsapp']??'')),'website'=>strip_tags(trim($_POST['website']??''))];
  file_put_contents($sf,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX);echo json_encode(['ok'=>true]);exit;
}
$tab=$_GET['tab']??'upload';
$bf=BASE_DIR.'/branding.json';$brandData=file_exists($bf)?json_decode(file_get_contents($bf),true):[];
$learners=[];
if(!empty($_SESSION['admin'])&&$tab==='learners'){
  $ctx=stream_context_create(['http'=>['timeout'=>10]]);
  $raw=@file_get_contents('https://masomoteletraining.co.ke/wp-json/mtti/v1/free-learners-list',false,$ctx);
  if($raw)$learners=json_decode($raw,true)?:[];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Masomotele Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f0f4f1;color:#1a2e1f;min-height:100vh;}
a{text-decoration:none;color:inherit;}
.lw{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:linear-gradient(135deg,#064a1f,#0a5e2a);}
.lc{background:white;border-radius:18px;padding:36px 30px;width:100%;max-width:380px;box-shadow:0 12px 40px rgba(0,0,0,.25);}
.lc h1{font-size:1.3rem;color:#0a5e2a;margin-bottom:3px;}.lc p{font-size:.8rem;color:#6b7a6e;margin-bottom:22px;}
.lerr{background:#ffeaea;color:#c62828;padding:8px 12px;border-radius:8px;font-size:.8rem;margin-bottom:14px;}
.wrap{display:flex;min-height:100vh;}
.sb{width:225px;background:linear-gradient(180deg,#064a1f,#0a5e2a);display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto;}
.sb-brand{padding:18px 15px 13px;border-bottom:1px solid rgba(255,255,255,.1);}
.sb-brand h2{color:white;font-size:.92rem;font-weight:800;}.sb-brand p{color:rgba(255,255,255,.45);font-size:.62rem;margin-top:1px;}
.sb-nav{padding:8px;flex:1;}
.sn{display:flex;align-items:center;gap:8px;padding:9px 11px;color:rgba(255,255,255,.7);border-radius:8px;font-size:.8rem;font-weight:600;margin-bottom:2px;transition:all .15s;}
.sn:hover,.sn.on{background:rgba(255,255,255,.16);color:white;}.sn.on{font-weight:700;}
.sb-foot{padding:9px 8px;border-top:1px solid rgba(255,255,255,.1);}
.sb-foot a{display:flex;align-items:center;gap:7px;padding:7px 11px;color:rgba(255,255,255,.6);font-size:.76rem;border-radius:8px;margin-bottom:1px;}
.sb-foot a:hover{background:rgba(255,255,255,.1);color:white;}
.main{flex:1;padding:22px;overflow-x:hidden;max-width:980px;}
label{display:block;font-size:.68rem;font-weight:700;color:#0a5e2a;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;}
input[type=text],input[type=password],input[type=date],select{width:100%;padding:9px 11px;border:1.5px solid #d4e6d9;border-radius:8px;font-size:.86rem;font-family:'Segoe UI',sans-serif;outline:none;transition:border .2s;background:#f4f7f5;}
input:focus,select:focus{border-color:#0a5e2a;background:white;}
textarea{width:100%;padding:9px 11px;border:1.5px solid #d4e6d9;border-radius:8px;font-size:.86rem;font-family:'Segoe UI',sans-serif;outline:none;resize:vertical;min-height:70px;background:#f4f7f5;transition:border .2s;}
textarea:focus{border-color:#0a5e2a;background:white;}
.fg{margin-bottom:13px;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#0a5e2a;color:white;border:none;border-radius:8px;font-size:.83rem;font-weight:700;cursor:pointer;transition:background .2s;}
.btn:hover{background:#064a1f;}.btn:disabled{opacity:.55;cursor:not-allowed;}
.btn-o{background:#f5a623;color:#064a1f;}.btn-o:hover{background:#e0951a;}
.btn-r{background:#c62828;color:white;}.btn-r:hover{background:#b71c1c;}
.btn-g{background:rgba(10,94,42,.09);color:#0a5e2a;}.btn-g:hover{background:rgba(10,94,42,.18);}
.btn-sm{padding:5px 10px;font-size:.72rem;}
.card{background:white;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.ctitle{font-size:.95rem;font-weight:800;margin-bottom:16px;padding-left:10px;border-left:3px solid #f5a623;}
.ptitle{font-size:1.15rem;font-weight:800;margin-bottom:18px;color:#0a5e2a;}
.dz{border:2.5px dashed #b0d4bc;border-radius:12px;padding:30px 20px;text-align:center;cursor:pointer;background:#f8fdf9;position:relative;transition:all .2s;}
.dz.drag{border-color:#0a5e2a;background:#e8f5ed;}
.dz input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.dz-ico{font-size:2rem;margin-bottom:6px;}.dz-txt{font-size:.86rem;font-weight:700;color:#0a5e2a;margin-bottom:3px;}.dz-sub{font-size:.7rem;color:#6b7a6e;}
.qi{background:#f4f7f5;border-radius:10px;padding:11px 13px;margin-bottom:7px;border:1.5px solid #d4e6d9;}
.qi-top{display:flex;align-items:center;gap:9px;margin-bottom:5px;}
.qi-name{flex:1;font-size:.8rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.qi-sz{font-size:.68rem;color:#6b7a6e;flex-shrink:0;}
.qi-title{width:100%;padding:5px 9px;border:1.5px solid #d4e6d9;border-radius:7px;font-size:.78rem;font-family:'Segoe UI',sans-serif;background:white;outline:none;margin-bottom:5px;}
.qi-title:focus{border-color:#0a5e2a;}
.pb{height:5px;background:#d4e6d9;border-radius:3px;overflow:hidden;}
.pf{height:100%;background:#0a5e2a;border-radius:3px;width:0;transition:width .25s;}
.qs{font-size:.7rem;margin-top:4px;font-weight:600;}
.qs.wait{color:#6b7a6e;}.qs.up{color:#0a5e2a;}.qs.done{color:#2e7d32;}.qs.fail{color:#c62828;}
.qrm{background:none;border:none;color:#c62828;cursor:pointer;font-size:.95rem;flex-shrink:0;padding:1px 3px;}
.ft{width:100%;border-collapse:collapse;font-size:.8rem;}
.ft th{background:#f4f7f5;padding:7px 9px;text-align:left;font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#6b7a6e;border-bottom:2px solid #d4e6d9;}
.ft td{padding:7px 9px;border-bottom:1px solid #eef3ef;vertical-align:middle;}
.ft tr:hover td{background:#fafcfa;}
.badge{display:inline-block;padding:2px 7px;border-radius:5px;font-size:.62rem;font-weight:800;}
.bi{background:#e8f5ed;color:#0a5e2a;}.bv{background:#fff8e1;color:#e0951a;}.bp{background:#fce4ec;color:#c62828;}
.etitle{cursor:pointer;border-bottom:1px dashed #bbb;}.etitle:hover{border-bottom-color:#0a5e2a;color:#0a5e2a;}
.sr{display:grid;grid-template-columns:repeat(auto-fit,minmax(105px,1fr));gap:11px;margin-bottom:20px;}
.sc{background:white;border-radius:11px;padding:13px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.05);}
.sc strong{display:block;font-size:1.6rem;font-weight:900;color:#0a5e2a;}.sc span{font-size:.66rem;color:#6b7a6e;font-weight:600;}
.lb{display:inline-block;padding:2px 7px;border-radius:50px;font-size:.62rem;font-weight:700;background:#e8f5ed;color:#0a5e2a;}
#pm{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.88);flex-direction:column;}
#pm.open{display:flex;}
.pb2{background:#111;padding:9px 14px;display:flex;align-items:center;gap:9px;flex-shrink:0;}
.pt{flex:1;color:white;font-size:.83rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.pnb{background:#333;color:white;border:none;border-radius:6px;padding:5px 13px;font-size:.76rem;cursor:pointer;}
.pnb:hover{background:#555;}#pi{color:#aaa;font-size:.73rem;white-space:nowrap;}
.pcl{background:#c62828;color:white;border:none;border-radius:6px;padding:5px 13px;font-size:.76rem;font-weight:700;cursor:pointer;}
.pbdy{flex:1;overflow:auto;display:flex;justify-content:center;padding:14px;background:#2a2a2a;}
#pc{max-width:100%;box-shadow:0 4px 18px rgba(0,0,0,.5);background:white;}
/* TOGGLE SWITCHES */
.tg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:12px;margin-bottom:80px;}
.tg-subj{background:#f8fdf9;border:1.5px solid #d4e6d9;border-radius:12px;padding:14px;}
.tg-subj-hd{display:flex;align-items:center;gap:8px;margin-bottom:10px;padding-bottom:9px;border-bottom:1px solid #d4e6d9;}
.tg-subj-name{font-weight:800;font-size:.83rem;flex:1;}
.tg-cls-lbl{font-size:.62rem;color:#6b7a6e;background:#e8f5ed;padding:2px 8px;border-radius:50px;}
.tg-items{display:flex;flex-direction:column;gap:5px;}
.tg-item{display:flex;align-items:center;gap:9px;padding:7px 10px;background:white;border:1.5px solid #e8efe9;border-radius:9px;font-size:.78rem;}
.tg-item-info{flex:1;overflow:hidden;}
.tg-item-title{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.tg-item-meta{font-size:.65rem;color:#9b9;margin-top:1px;}
.tg-type{display:inline-block;padding:1px 6px;border-radius:4px;font-size:.6rem;font-weight:800;margin-right:5px;}
.tg-type.inter{background:#e8f5ed;color:#0a5e2a;}.tg-type.vid{background:#fff8e1;color:#e0951a;}.tg-type.pdf{background:#fce4ec;color:#c62828;}
.sw{position:relative;display:inline-flex;align-items:center;cursor:pointer;flex-shrink:0;}
.sw input{opacity:0;width:0;height:0;position:absolute;}
.sw-track{width:40px;height:22px;background:#d4e6d9;border-radius:11px;transition:background .2s;display:flex;align-items:center;padding:2px;}
.sw input:checked+.sw-track{background:#0a5e2a;}
.sw-knob{width:18px;height:18px;background:white;border-radius:50%;box-shadow:0 1px 4px rgba(0,0,0,.2);transition:transform .2s;flex-shrink:0;}
.sw input:checked+.sw-track .sw-knob{transform:translateX(18px);}
.tg-empty{font-size:.78rem;color:#9b9;font-style:italic;}
.tg-save-bar{position:fixed;bottom:0;left:225px;right:0;background:white;border-top:2px solid #d4e6d9;padding:12px 22px;display:flex;align-items:center;gap:12px;z-index:99;}
.tg-saved{font-size:.8rem;color:#2e7d32;font-weight:700;display:none;}
.tg-cls-filter{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:14px;}
.tcf{padding:5px 13px;border:1.5px solid #d4e6d9;border-radius:50px;font-size:.72rem;font-weight:700;cursor:pointer;background:white;color:#6b7a6e;transition:all .15s;}
.tcf.on{background:#0a5e2a;color:white;border-color:#0a5e2a;}
/* ANNOUNCEMENTS */
.an-card{background:#f8fdf9;border:1.5px solid #d4e6d9;border-radius:12px;padding:14px;display:flex;gap:12px;align-items:flex-start;margin-bottom:10px;}
.an-emoji{font-size:1.5rem;flex-shrink:0;}.an-body{flex:1;min-width:0;}
.an-title{font-weight:800;font-size:.88rem;margin-bottom:3px;}.an-text{font-size:.76rem;color:#4a5c50;line-height:1.6;margin-bottom:6px;}
.an-meta{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:7px;}
.an-tag{display:inline-block;padding:2px 8px;border-radius:50px;font-size:.62rem;font-weight:700;}
.an-tag.upcoming{background:#fff8e1;color:#b8760a;}.an-tag.notice{background:#eff6ff;color:#1d4ed8;}.an-tag.congrats{background:#f0fdf4;color:#166534;}
.an-tag.pinned{background:#fce4ec;color:#c62828;}.an-tag.inactive{background:#f0f0f0;color:#999;}
.an-form{background:#f0fdf4;border:1.5px solid #b0d4bc;border-radius:12px;padding:16px;margin-bottom:14px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
@media(max-width:700px){.sb{display:none;}.main{padding:12px;}.form-row{grid-template-columns:1fr;}.tg-grid{grid-template-columns:1fr;}.tg-save-bar{left:0;}}
</style>
</head>
<body>
<?php if(empty($_SESSION['admin'])): ?>
<div class="lw"><div class="lc">
  <h1>Masomotele Admin</h1><p>M.T.T.I Eldoret</p>
  <?php if(!empty($loginErr))echo'<div class="lerr">'.htmlspecialchars($loginErr).'</div>';?>
  <form method="post">
    <div class="fg"><label>Username</label><input name="u" type="text" autocomplete="username" required></div>
    <div class="fg"><label>Password</label><input name="p" type="password" autocomplete="current-password" required></div>
    <button class="btn" style="width:100%" name="login">Sign In</button>
  </form>
</div></div>
<?php else: ?>
<div class="wrap">
<aside class="sb">
  <div class="sb-brand"><h2>Masomotele</h2><p>Admin - M.T.T.I Eldoret</p></div>
  <nav class="sb-nav">
    <a href="?tab=upload"   class="sn <?=$tab==='upload'  ?'on':''?>">Upload</a>
    <a href="?tab=manage"   class="sn <?=$tab==='manage'  ?'on':''?>">Manage Files</a>
    <a href="?tab=toggles"  class="sn <?=$tab==='toggles' ?'on':''?>">Content Toggles</a>
    <a href="?tab=announce" class="sn <?=$tab==='announce'?'on':''?>">Noticeboard</a>
    <a href="?tab=learners" class="sn <?=$tab==='learners'?'on':''?>">Learners</a>
    <a href="?tab=stats"    class="sn <?=$tab==='stats'   ?'on':''?>">Stats</a>
    <a href="?tab=visitors" class="sn <?=$tab==='visitors'?'on':''?>">&#128100; Visitors</a>
    <a href="?tab=popup"    class="sn <?=$tab==='popup'   ?'on':''?>">&#127937; Popup Mgr</a>
  </nav>
  <div class="sb-foot">
    <a href="../" target="_blank">View Portal</a>
    <a href="?logout=1">Sign Out</a>
  </div>
</aside>
<main class="main">

<?php if($tab==='upload'): ?>
<div class="ptitle">Upload Content</div>
<div class="card">
  <div class="ctitle">Step 1 - Choose type and subject</div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:18px">
    <div class="fg" style="margin:0"><label>Content Type</label>
      <select id="upType" onchange="onTypeChange()">
        <option value="">-- Select --</option>
        <option value="inter">HTML Interactive Lesson</option>
        <option value="vid">Video (MP4/WEBM)</option>
        <option value="pdf">PDF Revision Paper</option>
      </select></div>
    <div class="fg" style="margin:0"><label>Class and Subject</label>
      <select id="upSubj"><option value="">-- Select --</option>
      <?php $grps=[];foreach($SUBJECTS as $id=>[$c,$s])$grps[$c][$id]=$s;ksort($grps);foreach($grps as $cls=>$subs){echo"<optgroup label=\"$cls\">";foreach($subs as $id=>$subj)echo"<option value=\"$id\">$subj</option>";echo"</optgroup>";}?>
      </select></div>
    <div class="fg" style="margin:0" id="ef"><label id="el">Extra</label><input type="text" id="ev" placeholder=""></div>
  </div>
  <div class="ctitle">Step 2 - Drop files</div>
  <div class="dz" id="dz">
    <input type="file" id="fi" multiple accept=".html,.htm,.mp4,.webm,.mov,.mkv,.pdf">
    <div class="dz-ico">📂</div>
    <div class="dz-txt">Drop files here or click to browse</div>
    <div class="dz-sub" id="dh">Select type first - up to 500MB per file</div>
  </div>
  <div id="queue" style="margin-top:12px"></div>
  <div style="margin-top:14px;display:flex;gap:9px;flex-wrap:wrap">
    <button class="btn btn-o" id="upAllBtn" onclick="uploadAll()" style="display:none">Upload All</button>
    <button class="btn btn-g" id="clrBtn" onclick="clearQ()" style="display:none">Clear</button>
  </div>
  <div id="summary" style="margin-top:10px;font-size:.8rem;color:#6b7a6e"></div>
</div>

<?php elseif($tab==='manage'): ?>
<div class="ptitle">Manage Files</div>
<?php if(empty($jsonData)): ?>
<div class="card"><p style="color:#6b7a6e">No content uploaded yet.</p></div>
<?php else:
  $byC=[];foreach($jsonData as $sid=>$con){if(!isset($SUBJECTS[$sid]))continue;[$c,$s]=$SUBJECTS[$sid];$byC[$c][$sid]=['s'=>$s,'c'=>$con];}ksort($byC);
  foreach($byC as $cls=>$subs):?>
<div class="card"><div class="ctitle"><?=htmlspecialchars($cls)?></div>
<?php foreach($subs as $sid=>$info):$con=$info['c'];foreach(['inter'=>['Lesson','bi'],'vid'=>['Video','bv'],'pdf'=>['Paper','bp']] as $type=>[$lbl,$bc]):if(empty($con[$type]))continue;?>
<p style="font-size:.73rem;font-weight:700;color:#6b7a6e;margin:10px 0 5px"><?=htmlspecialchars($info['s'])?> - <?=$lbl?>s</p>
<table class="ft" style="margin-bottom:10px">
<thead><tr><th>#</th><th>Title (click to rename)</th><th>File</th><th>Type</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($con[$type] as $i=>$item):?>
<tr id="row-<?=$sid?>-<?=$type?>-<?=$i?>">
  <td><?=$i+1?></td>
  <td><span class="etitle" onclick="renameTitle(this,'<?=htmlspecialchars($sid)?>','<?=$type?>',<?=$i?>)"><?=htmlspecialchars($item['title'])?></span></td>
  <td style="font-size:.68rem;color:#6b7a6e;max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=htmlspecialchars($item['file'])?></td>
  <td><span class="badge <?=$bc?>"><?=$lbl?></span></td>
  <td style="display:flex;gap:5px;flex-wrap:wrap">
    <?php $dirs=['inter'=>'interactives','vid'=>'videos','pdf'=>'papers'];$url='../'.$dirs[$type].'/'.rawurlencode($item['file']);
    if($type==='pdf'):?><button class="btn btn-g btn-sm" onclick="previewPdf('<?=$url?>','<?=htmlspecialchars(addslashes($item['title']))?>')">View</button>
    <?php elseif($type==='inter'):?><a href="<?=$url?>" target="_blank" class="btn btn-g btn-sm">Preview</a><?php endif;?>
    <button class="btn btn-r btn-sm" onclick="delFile('<?=htmlspecialchars($sid)?>','<?=$type?>',<?=$i?>,'<?=htmlspecialchars(addslashes($item['title']))?>')">Delete</button>
  </td>
</tr>
<?php endforeach;?>
</tbody></table>
<?php endforeach;endforeach;endforeach;?>
</div>
<?php endif;?>

<?php elseif($tab==='toggles'):
$byC2=[];foreach($jsonData as $sid=>$con){if(!isset($SUBJECTS[$sid]))continue;[$c,$s]=$SUBJECTS[$sid];$byC2[$c][$sid]=['s'=>$s,'c'=>$con];}ksort($byC2);?>
<div class="ptitle">Content Toggles
  <span style="font-size:.72rem;font-weight:400;color:#6b7a6e;display:block;margin-top:3px">Hidden items show as <strong>Coming Soon</strong> to students. Drip-release weekly topics!</span>
</div>
<?php if(empty($jsonData)):?>
<div class="card"><p style="color:#6b7a6e">Upload content first.</p></div>
<?php else:?>
<script>
var tgDirty=false;
function markDirty(){tgDirty=true;var sv=document.getElementById('tgSaved');if(sv)sv.style.display='none';}
function toggleAll(v){document.querySelectorAll('.tg-chk').forEach(function(c){c.checked=v;});markDirty();}
function filterTgCls(cls,btn){
  document.querySelectorAll('.tcf').forEach(function(b){b.classList.remove('on');});btn.classList.add('on');
  document.querySelectorAll('#tgGrid .tg-subj').forEach(function(el){el.style.display=(cls==='all'||el.dataset.cls===cls)?'':'none';});
}
async function saveToggles(){
  var data={},btn=document.getElementById('tgSaveBtn');
  document.querySelectorAll('.tg-chk').forEach(function(c){data[c.dataset.key]=c.checked;});
  btn.disabled=true;btn.textContent='Saving...';btn.style.opacity='.6';
  try{
    var r=await fetch('?ajax=toggles',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    var d=await r.json();
    if(d.ok){tgDirty=false;btn.disabled=false;btn.style.opacity='';btn.textContent='Save Toggles';
      var sv=document.getElementById('tgSaved');if(sv){sv.style.display='inline';setTimeout(function(){sv.style.display='none';},2500);}
    }else{alert('Save failed.');btn.disabled=false;btn.style.opacity='';}
  }catch(e){alert('Network error.');btn.disabled=false;btn.style.opacity='';}
}
window.addEventListener('beforeunload',function(e){if(tgDirty){e.preventDefault();e.returnValue='Unsaved toggle changes!';}});
</script>
<div class="tg-cls-filter">
  <button class="tcf on" onclick="filterTgCls('all',this)">All</button>
  <?php foreach(array_keys($byC2) as $cls2):?>
  <button class="tcf" onclick="filterTgCls(<?=json_encode($cls2)?>,this)"><?=htmlspecialchars($cls2)?></button>
  <?php endforeach;?>
</div>
<div class="tg-grid" id="tgGrid">
<?php foreach($byC2 as $cls=>$subs):foreach($subs as $sid=>$info):$con=$info['c'];?>
<div class="tg-subj" data-cls="<?=htmlspecialchars($cls)?>">
  <div class="tg-subj-hd"><span class="tg-cls-lbl"><?=htmlspecialchars($cls)?></span><span class="tg-subj-name"><?=htmlspecialchars($info['s'])?></span></div>
  <div class="tg-items">
  <?php $hi=false;foreach(['inter'=>'Lesson','vid'=>'Video','pdf'=>'Paper'] as $tp=>$lb):foreach($con[$tp]??[] as $ix=>$it):$hi=true;$key="{$sid}__{$tp}__{$ix}";$on=!isset($toggleData[$key])||$toggleData[$key]===true;?>
  <div class="tg-item">
    <div class="tg-item-info"><span class="tg-type <?=$tp?>"><?=$lb?></span><span class="tg-item-title"><?=htmlspecialchars($it['title'])?></span><div class="tg-item-meta"><?=htmlspecialchars($it['file'])?></div></div>
    <label class="sw"><input type="checkbox" class="tg-chk" data-key="<?=$key?>" <?=$on?'checked':''?> onchange="markDirty()"><div class="sw-track"><div class="sw-knob"></div></div></label>
  </div>
  <?php endforeach;endforeach;if(!$hi):?><div class="tg-empty">No content yet</div><?php endif;?>
  </div>
</div>
<?php endforeach;endforeach;?>
</div>
<div class="tg-save-bar">
  <button class="btn btn-o" id="tgSaveBtn" onclick="saveToggles()">Save Toggles</button>
  <button class="btn btn-g" onclick="toggleAll(true)">Show All</button>
  <button class="btn btn-g" onclick="toggleAll(false)">Hide All</button>
  <span class="tg-saved" id="tgSaved">Saved!</span>
  <span style="font-size:.72rem;color:#9b9;margin-left:auto">Hidden = Coming Soon on student portal</span>
</div>
<?php endif;?>

<?php elseif($tab==='announce'):?>
<div class="ptitle">Noticeboard</div>
<div class="card">
  <div class="ctitle">Post New Announcement</div>
  <div class="an-form">
    <div class="form-row">
      <div class="fg" style="margin:0"><label>Type</label>
        <select id="anType"><option value="upcoming">Upcoming Lesson</option><option value="notice">General Notice</option><option value="congrats">Celebration</option></select></div>
      <div class="fg" style="margin:0"><label>Emoji</label><input type="text" id="anEmoji" value="📚" maxlength="4" style="max-width:100px"></div>
    </div>
    <div class="fg" style="margin-top:11px"><label>Headline</label><input type="text" id="anTitle" placeholder="e.g. Next Week: Form 4 Chemistry - Electrochemistry"></div>
    <div class="fg"><label>Message</label><textarea id="anBody" placeholder="Tell students what is coming up..."></textarea></div>
    <div class="form-row">
      <div class="fg" style="margin:0"><label>Subject Tag</label><input type="text" id="anSubject" placeholder="e.g. Form 4 Chemistry"></div>
      <div class="fg" style="margin:0"><label>Go-live Date</label><input type="date" id="anDate"></div>
    </div>
    <div style="display:flex;gap:18px;margin:11px 0 14px;flex-wrap:wrap">
      <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.82rem;text-transform:none;font-weight:600"><input type="checkbox" id="anPinned" style="width:auto"> Pin to top</label>
      <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.82rem;text-transform:none;font-weight:600"><input type="checkbox" id="anSuggest" style="width:auto"> Add suggestion prompt</label>
    </div>
    <button class="btn btn-o" onclick="postNotice()">Post Announcement</button>
    <span id="anMsg" style="font-size:.78rem;margin-left:12px;font-weight:700;display:none"></span>
  </div>
</div>
<div class="card">
  <div class="ctitle">Active Notices</div>
  <?php $notices=$announceData['notices']??[];if(empty($notices)):?>
  <p style="color:#6b7a6e;font-size:.82rem">No notices yet.</p>
  <?php else:foreach($notices as $n):$nid=htmlspecialchars($n['id']);?>
  <div class="an-card" id="anc-<?=$nid?>">
    <div class="an-emoji"><?=htmlspecialchars($n['emoji']??'📌')?></div>
    <div class="an-body">
      <div class="an-title"><?=htmlspecialchars($n['title']??'')?></div>
      <div class="an-text"><?=htmlspecialchars($n['body']??'')?></div>
      <div class="an-meta">
        <span class="an-tag <?=htmlspecialchars($n['type']??'notice')?>"><?=ucfirst($n['type']??'notice')?></span>
        <?php if(!empty($n['pinned'])):?><span class="an-tag pinned">Pinned</span><?php endif;?>
        <?php if(!empty($n['subject'])):?><span class="an-tag notice"><?=htmlspecialchars($n['subject'])?></span><?php endif;?>
        <span class="an-tag <?=(($n['active']??true)?'':'inactive')?>"><?=(($n['active']??true)?'Visible':'Hidden')?></span>
      </div>
      <div style="display:flex;gap:6px;margin-top:7px">
        <button class="btn btn-g btn-sm" onclick="toggleNotice(<?=json_encode($n['id'])?>,<?=(($n['active']??true)?'false':'true')?>)"><?=(($n['active']??true)?'Hide':'Show')?></button>
        <button class="btn btn-r btn-sm" onclick="deleteNotice(<?=json_encode($n['id'])?>,<?=json_encode($n['title']??'')?>)">Delete</button>
      </div>
    </div>
  </div>
  <?php endforeach;endif;?>
  <div style="margin-top:16px;padding-top:14px;border-top:1px solid #d4e6d9">
    <div class="ctitle" style="margin-bottom:10px">Suggestion Box</div>
    <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;cursor:pointer;text-transform:none">
      <input type="checkbox" id="suggestToggle" <?=!empty($announceData['suggestions_enabled'])?'checked':''?> onchange="saveSuggest()">
      Show suggestion box on student portal
    </label>
    <div class="fg" style="margin-top:10px;max-width:500px">
      <label>Prompt Text</label>
      <input type="text" id="sugPrompt" value="<?=htmlspecialchars($announceData['suggestion_prompt']??'What topic would you like us to cover next?')?>" onblur="saveSuggest()">
    </div>
    <span id="sugMsg" style="font-size:.75rem;color:#2e7d32;font-weight:700;display:none">Saved</span>
  </div>
</div>

<?php elseif($tab==='learners'):?>
<div class="ptitle">Registered Learners</div>
<?php if(empty($learners)):?>
<div class="card"><p style="color:#6b7a6e">No data available or API unreachable.</p></div>
<?php else:?>
<div class="card">
  <div style="margin-bottom:12px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <input type="text" id="ls" placeholder="Search name, phone, class..." style="max-width:320px;border-radius:50px;padding:8px 15px" oninput="filterL()">
    <span style="font-size:.76rem;color:#6b7a6e"><?=count($learners)?> learners</span>
  </div>
  <table class="ft" id="lt">
    <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Class</th><th>County</th><th>Registered</th></tr></thead>
    <tbody>
    <?php foreach($learners as $i=>$l):?>
    <tr><td><?=$i+1?></td><td><?=htmlspecialchars($l['name']??'')?></td><td><?=htmlspecialchars($l['phone']??'')?></td>
      <td><span class="lb"><?=htmlspecialchars($l['class']??'')?></span></td>
      <td><?=htmlspecialchars($l['county']??'')?></td>
      <td style="font-size:.68rem;color:#6b7a6e"><?=htmlspecialchars($l['created_at']??'')?></td></tr>
    <?php endforeach;?>
    </tbody>
  </table>
</div>
<?php endif;?>

<?php elseif($tab==='stats'):?>
<div class="ptitle">Stats</div>
<?php $tL=$tV=$tP=$tS=0;foreach($jsonData as $sid=>$c){$l=count($c['inter']??[]);$v=count($c['vid']??[]);$p=count($c['pdf']??[]);$tL+=$l;$tV+=$v;$tP+=$p;if($l||$v||$p)$tS++;}?>
<div class="sr">
  <div class="sc"><strong><?=$tL?></strong><span>Lessons</span></div>
  <div class="sc"><strong><?=$tV?></strong><span>Videos</span></div>
  <div class="sc"><strong><?=$tP?></strong><span>Papers</span></div>
  <div class="sc"><strong><?=$tS?></strong><span>Subjects</span></div>
</div>
<div class="card"><div class="ctitle">Content Per Subject</div>
  <table class="ft"><thead><tr><th>Class</th><th>Subject</th><th>Lessons</th><th>Videos</th><th>Papers</th></tr></thead>
  <tbody>
  <?php foreach($jsonData as $sid=>$c):if(!isset($SUBJECTS[$sid]))continue;[$cls,$subj]=$SUBJECTS[$sid];$l=count($c['inter']??[]);$v=count($c['vid']??[]);$p=count($c['pdf']??[]);?>
  <tr><td><?=$cls?></td><td><?=$subj?></td><td><span class="badge bi"><?=$l?></span></td><td><span class="badge bv"><?=$v?></span></td><td><span class="badge bp"><?=$p?></span></td></tr>
  <?php endforeach;?>
  </tbody></table>
</div>

<?php elseif($tab==='suggest'):
  $sf=BASE_DIR.'/suggestions.json';
  $suggs=file_exists($sf)?json_decode(file_get_contents($sf),true):[];
?>
<div class="ptitle">Student Suggestions</div>
<div class="card">
  <div class="ctitle">Topic Suggestions <span style="font-size:.72rem;font-weight:400;color:#6b7a6e"> — from the portal suggestion box</span></div>
  <?php if(empty($suggs)):?>
  <p style="color:#6b7a6e;font-size:.82rem">No suggestions yet.</p>
  <?php else:?>
  <table class="ft" style="margin-bottom:12px">
    <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Suggestion</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach($suggs as $i=>$s):?>
    <tr id="sg-<?=htmlspecialchars($s['id']??$i)?>">
      <td><?=$i+1?></td>
      <td><?=htmlspecialchars($s['name']??'Anonymous')?></td>
      <td><?=htmlspecialchars($s['phone']??'')?></td>
      <td><?=htmlspecialchars($s['text']??$s['suggestion']??'')?></td>
      <td style="font-size:.68rem;color:#6b7a6e"><?=htmlspecialchars($s['date']??'')?></td>
      <td><button class="btn btn-r btn-sm" onclick="delSugg('<?=htmlspecialchars($s['id']??$i)?>')">Delete</button></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  <button class="btn btn-r" onclick="clearSuggs()">Clear All</button>
  <?php endif;?>
</div>
<script>
function delSugg(id){
  if(!confirm('Delete this suggestion?'))return;
  var fd=new FormData();fd.append('id',id);
  fetch('?ajax=del-suggestion',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
    if(d.ok){var el=document.getElementById('sg-'+id);if(el)el.remove();}
    else alert('Failed.');
  });
}
function clearSuggs(){
  if(!confirm('Clear ALL suggestions?'))return;
  fetch('?ajax=clear-suggestions',{method:'POST'}).then(function(r){return r.json();}).then(function(d){
    if(d.ok)location.reload();
  });
}
</script>

<?php elseif($tab==='visitors'):
  $vf=BASE_DIR.'/visitors.json';
  $visitors=file_exists($vf)?json_decode(file_get_contents($vf),true):[];
  if(!is_array($visitors))$visitors=[];
?>
<div class="card">
  <div class="ctitle">&#128100; Portal Visitors — <?=count($visitors)?> registered</div>
  <?php if(empty($visitors)):?>
  <p style="color:#6b7a6e;font-size:.82rem">No visitors yet. They appear here when someone registers on the portal.</p>
  <?php else:?>
  <div style="margin-bottom:10px"><input type="search" placeholder="Search name, phone or location..." oninput="filterV(this.value)" style="padding:8px 12px;border:1.5px solid #d4e6d9;border-radius:8px;width:100%;font-size:.85rem;outline:none"></div>
  <table style="width:100%;border-collapse:collapse;font-size:.82rem" id="vtable">
    <thead><tr style="background:#f4f7f5">
      <th style="padding:8px 10px;text-align:left;color:#3D6318;font-weight:700">#</th>
      <th style="padding:8px 10px;text-align:left;color:#3D6318;font-weight:700">Name</th>
      <th style="padding:8px 10px;text-align:left;color:#3D6318;font-weight:700">Phone</th>
      <th style="padding:8px 10px;text-align:left;color:#3D6318;font-weight:700">Location</th>
      <th style="padding:8px 10px;text-align:left;color:#3D6318;font-weight:700">Registered</th>
      <th style="padding:8px 10px;text-align:left;color:#3D6318;font-weight:700">Visits</th>
    </tr></thead>
    <tbody>
    <?php foreach(array_values($visitors) as $i=>$v):?>
    <tr style="border-bottom:1px solid #f0f0f0" class="vrow">
      <td style="padding:8px 10px;color:#999"><?=$i+1?></td>
      <td style="padding:8px 10px;font-weight:700"><?=htmlspecialchars($v['name']??'-')?></td>
      <td style="padding:8px 10px"><a href="https://wa.me/<?=preg_replace('/\D/','',['phone']??'')?>" target="_blank" style="color:#3D6318;font-weight:600"><?=htmlspecialchars($v['phone']??'-')?></a></td>
      <td style="padding:8px 10px"><?=htmlspecialchars($v['location']??'-')?></td>
      <td style="padding:8px 10px;color:#666"><?=$v['registered']??'-'?></td>
      <td style="padding:8px 10px;text-align:center"><span style="background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:10px;font-weight:700"><?=intval($v['visits']??1)?></span></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  <script>
  function filterV(q){q=q.toLowerCase();document.querySelectorAll('.vrow').forEach(function(r){r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}
  </script>
  <?php endif;?>
</div>

<?php elseif($tab==='popup'):
  $sf=BASE_DIR.'/popup-stories.json';
  $pd=file_exists($sf)?json_decode(file_get_contents($sf),true):['stories'=>[],'settings'=>[]];
  $pstories=$pd['stories']??[];
  $psettings=$pd['settings']??[];
?>
<div class="card" style="margin-bottom:16px">
  <div class="ctitle">&#127937; Popup Story Manager</div>
  <button class="btn" onclick="openPM(null)" style="margin-bottom:14px">+ Add Story</button>
  <?php if(empty($pstories)):?>
  <p style="color:#6b7a6e;font-size:.82rem">No stories yet. Add graduate stories to show in the popup.</p>
  <?php else:foreach($pstories as $ps):?>
  <div id="ps-<?=$ps['id']?>" style="border:1.5px solid #e8f0e4;border-radius:10px;padding:12px 14px;margin-bottom:8px;display:flex;align-items:flex-start;gap:10px;<?=($ps['active']??true)?'':'opacity:.45;'?>">
    <div style="font-size:26px;width:40px;text-align:center;flex-shrink:0"><?=htmlspecialchars($ps['emoji']??'🎓')?></div>
    <div style="flex:1">
      <strong style="font-size:.9rem"><?=htmlspecialchars($ps['name']??'')?></strong>
      <span style="font-size:.75rem;color:#3D6318;font-weight:600;margin-left:8px"><?=htmlspecialchars($ps['course']??'')?> · <?=htmlspecialchars($ps['location']??'')?></span>
      <?php if(!empty($ps['salary_min'])):?><span style="font-size:.72rem;background:#e8f5e9;color:#2e7d32;padding:2px 7px;border-radius:8px;margin-left:4px">KES <?=number_format($ps['salary_min'])?>–<?=number_format($ps['salary_max']??0)?></span><?php endif;?>
      <?php if(!empty($ps['abroad'])):?><span style="font-size:.72rem;background:#fff3e0;color:#e65100;padding:2px 7px;border-radius:8px;margin-left:4px">&#127757; Abroad</span><?php endif;?>
      <p style="font-size:.78rem;color:#666;margin:5px 0 0;line-height:1.5"><?=htmlspecialchars(substr($ps['story']??'',0,100))?>...</p>
    </div>
    <div style="display:flex;flex-direction:column;gap:5px;flex-shrink:0">
      <button class="btn btn-sm" onclick='openPM(<?=json_encode($ps)?>)' style="background:#3D6318">&#9998; Edit</button>
      <button class="btn btn-sm btn-g" onclick="togglePS(<?=intval($ps['id'])?>)"><?=($ps['active']??true)?'Hide':'Show'?></button>
      <button class="btn btn-sm btn-r" onclick="deletePS(<?=intval($ps['id'])?>,'<?=addslashes($ps['name']??'')?>')">&#128465; Del</button>
    </div>
  </div>
  <?php endforeach;endif;?>
</div>
<div class="card">
  <div class="ctitle">&#9881;&#65039; Popup Settings</div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="fg"><label>Show after (seconds)</label><input type="number" id="ps-show" value="<?=intval($psettings['show_after_seconds']??25)?>" min="5"></div>
    <div class="fg"><label>Rotate every (seconds)</label><input type="number" id="ps-rotate" value="<?=intval($psettings['rotate_every_seconds']??5)?>" min="2"></div>
    <div class="fg"><label>Facebook URL</label><input type="url" id="ps-fb" value="<?=htmlspecialchars($psettings['fb_url']??'')?>"></div>
    <div class="fg"><label>TikTok URL</label><input type="url" id="ps-tt" value="<?=htmlspecialchars($psettings['tiktok_url']??'')?>"></div>
    <div class="fg"><label>WhatsApp Number</label><input type="text" id="ps-wa" value="<?=htmlspecialchars($psettings['whatsapp']??'')?>"></div>
    <div class="fg"><label>Website URL</label><input type="url" id="ps-web" value="<?=htmlspecialchars($psettings['website']??'')?>"></div>
  </div>
  <button class="btn" style="margin-top:12px;background:#FF9700" onclick="savePSettings()">&#128190; Save Settings</button>
</div>

<!-- Popup Story Modal -->
<div id="pm-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center;padding:16px">
<div style="background:#fff;border-radius:16px;padding:24px;max-width:520px;width:100%;max-height:90vh;overflow-y:auto">
  <h3 style="color:#3D6318;font-size:1rem;margin-bottom:16px" id="pm-title">Add Story</h3>
  <input type="hidden" id="pm-id">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div class="fg"><label>Name</label><input type="text" id="pm-name" placeholder="Mercy Chebet"></div>
    <div class="fg"><label>Emoji</label><input type="text" id="pm-emoji" placeholder="🏥" maxlength="4"></div>
    <div class="fg"><label>Course</label><input type="text" id="pm-course" placeholder="Nursing Assistant"></div>
    <div class="fg"><label>Location</label><input type="text" id="pm-loc" placeholder="Eldoret"></div>
    <div class="fg"><label>Year</label><input type="text" id="pm-year" placeholder="2025" maxlength="4"></div>
    <div class="fg"><label>Salary Min (KES)</label><input type="number" id="pm-smin" placeholder="25000"></div>
    <div class="fg"><label>Salary Max (KES)</label><input type="number" id="pm-smax" placeholder="40000"></div>
    <div class="fg"><label>Highlight Tag</label><input type="text" id="pm-hl" placeholder="🔥 Next intake open!"></div>
  </div>
  <div class="fg" style="margin-top:10px"><label>Story</label><textarea id="pm-story" rows="4" placeholder="Graduate success story..."></textarea></div>
  <div style="display:flex;gap:20px;margin-top:10px">
    <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;cursor:pointer"><input type="checkbox" id="pm-abroad"> &#127757; Works Abroad</label>
    <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;cursor:pointer"><input type="checkbox" id="pm-active" checked> &#128065; Active</label>
  </div>
  <div style="display:flex;gap:8px;margin-top:14px">
    <button class="btn" onclick="savePS()" style="background:#3D6318">&#128190; Save</button>
    <button class="btn btn-g" onclick="closePM()">Cancel</button>
  </div>
</div></div>
<script>
function openPM(s){
  document.getElementById('pm-title').textContent=s?'Edit Story':'Add Story';
  document.getElementById('pm-id').value=s?s.id:'';
  document.getElementById('pm-name').value=s?s.name:'';
  document.getElementById('pm-emoji').value=s?s.emoji:'🎓';
  document.getElementById('pm-course').value=s?s.course:'';
  document.getElementById('pm-loc').value=s?s.location:'';
  document.getElementById('pm-year').value=s?s.year:'<?=date('Y')?>';
  document.getElementById('pm-smin').value=s?s.salary_min:'';
  document.getElementById('pm-smax').value=s?s.salary_max:'';
  document.getElementById('pm-hl').value=s?s.highlight||'':'';
  document.getElementById('pm-story').value=s?s.story:'';
  document.getElementById('pm-abroad').checked=s?!!s.abroad:false;
  document.getElementById('pm-active').checked=s?s.active!==false:true;
  document.getElementById('pm-overlay').style.display='flex';
}
function closePM(){document.getElementById('pm-overlay').style.display='none';}
async function savePS(){
  var fd=new FormData();
  ['id','name','emoji','course','loc','year','smin','smax','hl','story'].forEach(function(k){fd.append(k,document.getElementById('pm-'+k).value);});
  fd.append('abroad',document.getElementById('pm-abroad').checked?'1':'0');
  fd.append('active',document.getElementById('pm-active').checked?'1':'0');
  var r=await fetch('?ajax=save-popup-story',{method:'POST',body:fd});
  if((await r.json()).ok){closePM();location.reload();}else alert('Save failed.');
}
async function deletePS(id,name){
  if(!confirm('Delete story for "'+name+'"?'))return;
  var fd=new FormData();fd.append('id',id);
  var r=await fetch('?ajax=del-popup-story',{method:'POST',body:fd});
  if((await r.json()).ok){document.getElementById('ps-'+id).remove();}else alert('Delete failed.');
}
async function togglePS(id){
  var fd=new FormData();fd.append('id',id);
  var r=await fetch('?ajax=toggle-popup-story',{method:'POST',body:fd});
  if((await r.json()).ok)location.reload();else alert('Failed.');
}
async function savePSettings(){
  var fd=new FormData();
  fd.append('show_after',document.getElementById('ps-show').value);
  fd.append('rotate_every',document.getElementById('ps-rotate').value);
  fd.append('fb_url',document.getElementById('ps-fb').value);
  fd.append('tiktok_url',document.getElementById('ps-tt').value);
  fd.append('whatsapp',document.getElementById('ps-wa').value);
  fd.append('website',document.getElementById('ps-web').value);
  var r=await fetch('?ajax=save-popup-settings',{method:'POST',body:fd});
  if((await r.json()).ok)alert('Settings saved!');else alert('Failed.');
}
document.getElementById('pm-overlay').addEventListener('click',function(e){if(e.target===this)closePM();});
</script>
<?php elseif($tab==='generate'):?>
<div class="ptitle">Upload Interactive</div>
<div class="card">
  <div class="ctitle">Upload HTML Interactive Lesson</div>
  <p style="font-size:.81rem;color:#4a5c50;margin-bottom:14px;line-height:1.7">Upload a .html interactive lesson file. It will be saved to <code>/interactives/</code> and registered in <code>lessons.json</code>.</p>
  <div class="fg">
    <label>Class & Subject</label>
    <select id="genSubj">
      <option value="">-- Select --</option>
      <?php $grps=[];foreach($SUBJECTS as $id=>[$c,$s])$grps[$c][$id]=$s;ksort($grps);foreach($grps as $cls=>$subs){echo"<optgroup label=\"$cls\">";foreach($subs as $id=>$subj)echo"<option value=\"$id\">$subj</option>";echo"</optgroup>";}?>
    </select>
  </div>
  <div class="fg">
    <label>Title (shown to students)</label>
    <input type="text" id="genTitle" placeholder="e.g. Linear Programming">
  </div>
  <div class="fg">
    <label>HTML File</label>
    <input type="file" id="genFile" accept=".html,.htm" style="background:white;padding:8px">
  </div>
  <button class="btn btn-o" onclick="uploadGen()">Upload & Register</button>
  <div id="genMsg" style="margin-top:10px;font-size:.82rem;font-weight:700;display:none;padding:9px 13px;border-radius:8px"></div>
</div>
<script>
function uploadGen(){
  var sid=document.getElementById('genSubj').value.trim();
  var title=document.getElementById('genTitle').value.trim();
  var fileEl=document.getElementById('genFile');
  var btn=event.target;
  if(!sid||!title){alert('Select subject and enter title.');return;}
  if(!fileEl.files.length){alert('Choose a file.');return;}
  btn.disabled=true;btn.textContent='Uploading...';
  var fd=new FormData();
  fd.append('subject_id',sid);fd.append('type','inter');
  fd.append('custom_title',title);fd.append('file',fileEl.files[0]);
  fetch('?ajax=upload',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
    var msg=document.getElementById('genMsg');
    if(d.ok){
      msg.style.cssText='display:block;background:#e8f5e9;color:#2e7d32;font-size:.82rem;font-weight:700;padding:9px 13px;border-radius:8px';
      msg.textContent='Saved: '+d.msg;
      document.getElementById('genTitle').value='';
      document.getElementById('genFile').value='';
    }else{
      msg.style.cssText='display:block;background:#ffeaea;color:#c62828;font-size:.82rem;font-weight:700;padding:9px 13px;border-radius:8px';
      msg.textContent='Error: '+d.msg;
    }
    btn.disabled=false;btn.textContent='Upload & Register';
  }).catch(function(){
    alert('Network error.');btn.disabled=false;btn.textContent='Upload & Register';
  });
}
</script>

<?php elseif($tab==='branding'):
  $wm1=$brandData['watermark_line1']??'Masomotele Technical Training Institute';
  $wm2=$brandData['watermark_line2']??'Join us after school · Sagaas Centre, 4th Floor, Eldoret';
  $hdr=$brandData['header_tagline']??'Join us after school · M.T.T.I Eldoret';
?>
<div class="ptitle">Branding Settings</div>
<div class="card">
  <div class="ctitle">Video Player Watermark</div>
  <p style="font-size:.81rem;color:#4a5c50;margin-bottom:14px;line-height:1.7">Shown at the bottom of every video while it plays.</p>
  <div class="fg"><label>Line 1 (main text)</label><input type="text" id="bWm1" value="<?=htmlspecialchars($wm1)?>"></div>
  <div class="fg"><label>Line 2 (sub text)</label><input type="text" id="bWm2" value="<?=htmlspecialchars($wm2)?>"></div>
</div>
<div class="card">
  <div class="ctitle">Lesson Interactive Header Tagline</div>
  <p style="font-size:.81rem;color:#4a5c50;margin-bottom:14px;line-height:1.7">Subtitle shown under "Masomotele" in the green header bar of every lesson.</p>
  <div class="fg"><label>Header tagline</label><input type="text" id="bHdr" value="<?=htmlspecialchars($hdr)?>"></div>
</div>
<div class="card">
  <div class="ctitle">Live Preview</div>
  <div style="background:#064a1f;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;margin-bottom:14px">
    <img src="data:image/jpeg;base64,<?=substr($brandData['_logo']??'',0,0)?>" style="width:34px;height:34px;border-radius:50%;background:#ccc;flex-shrink:0">
    <div>
      <div style="color:white;font-size:.88rem;font-weight:800">Masomotele</div>
      <div style="color:rgba(255,255,255,.6);font-size:.6rem" id="previewHdr"><?=htmlspecialchars($hdr)?></div>
    </div>
  </div>
  <div style="background:#000;border-radius:10px;padding:14px 14px 10px;display:flex;align-items:center;gap:10px">
    <div style="width:36px;height:36px;border-radius:50%;background:#888;flex-shrink:0"></div>
    <div>
      <div style="color:white;font-size:.75rem;font-weight:800" id="previewWm1"><?=htmlspecialchars($wm1)?></div>
      <div style="color:rgba(255,255,255,.7);font-size:.62rem" id="previewWm2"><?=htmlspecialchars($wm2)?></div>
    </div>
  </div>
</div>
<button class="btn btn-o" id="bSaveBtn" onclick="saveBranding()" style="width:100%;padding:13px;font-size:.9rem">Save Branding</button>
<div id="bMsg" style="margin-top:10px;font-size:.8rem;font-weight:700;display:none;padding:9px 13px;border-radius:8px"></div>
<script>
['bWm1','bWm2','bHdr'].forEach(function(id){
  document.getElementById(id).addEventListener('input',function(){
    document.getElementById('previewWm1').textContent=document.getElementById('bWm1').value;
    document.getElementById('previewWm2').textContent=document.getElementById('bWm2').value;
    document.getElementById('previewHdr').textContent=document.getElementById('bHdr').value;
  });
});
function saveBranding(){
  var btn=document.getElementById('bSaveBtn');
  btn.disabled=true;btn.textContent='Saving...';
  var data={
    watermark_line1:document.getElementById('bWm1').value.trim(),
    watermark_line2:document.getElementById('bWm2').value.trim(),
    header_tagline:document.getElementById('bHdr').value.trim()
  };
  fetch('?ajax=branding',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)})
    .then(function(r){return r.json();}).then(function(d){
      var msg=document.getElementById('bMsg');msg.style.display='block';
      if(d.ok){msg.style.cssText='display:block;background:#e8f5e9;color:#2e7d32;font-size:.8rem;font-weight:700;padding:9px 13px;border-radius:8px';msg.textContent='Saved! Portal will update immediately.';}
      else{msg.style.cssText='display:block;background:#ffeaea;color:#c62828;font-size:.8rem;font-weight:700;padding:9px 13px;border-radius:8px';msg.textContent='Error: '+d.msg;}
      btn.disabled=false;btn.textContent='Save Branding';
    });
}
</script>

<?php endif;?>

</main>
</div>

<div id="pm">
  <div class="pb2"><span class="pt" id="pt"></span><button class="pnb" onclick="pPrev()">Prev</button><span id="pi">Page 1/1</span><button class="pnb" onclick="pNext()">Next</button><button class="pcl" onclick="closePdf()">Close</button></div>
  <div class="pbdy"><canvas id="pc"></canvas></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
// UPLOAD
const ALLOWED={inter:['html','htm'],vid:['mp4','webm','mov','mkv'],pdf:['pdf']};
const HINTS={inter:'HTML files, max 500MB',vid:'MP4, WEBM, MOV, MKV, max 500MB',pdf:'PDF files, max 500MB'};
let Q=[];

function onTypeChange(){
  const t=document.getElementById('upType').value;
  const dh=document.getElementById('dh');if(dh)dh.textContent=t?HINTS[t]:'Select type first';
  const el=document.getElementById('el');const ev=document.getElementById('ev');const ef=document.getElementById('ef');
  if(!el||!ev||!ef)return;
  el.textContent=t==='pdf'?'Year (optional)':'Duration (optional)';
  ev.placeholder=t==='pdf'?'e.g. 2023':'e.g. 14 min';
  ef.style.display=(t==='vid'||t==='pdf')?'block':'none';
}

const _dz=document.getElementById('dz');
if(_dz){
  _dz.addEventListener('dragover',function(e){e.preventDefault();_dz.classList.add('drag');});
  _dz.addEventListener('dragleave',function(){_dz.classList.remove('drag');});
  _dz.addEventListener('drop',function(e){e.preventDefault();_dz.classList.remove('drag');addFiles(Array.from(e.dataTransfer.files));});
}
const _fi=document.getElementById('fi');
if(_fi)_fi.addEventListener('change',function(e){addFiles(Array.from(e.target.files));e.target.value='';});

function genTitle(fn){
  let n=fn.replace(/\.[^/.]+$/,'');
  n=n.replace(/^[a-z][0-9]+[-_][a-z]+-[0-9]+[-_]/i,'');
  n=n.replace(/^[a-z][0-9]+[-_][a-z]+-/i,'');
  n=n.replace(/^[a-z0-9]+-[0-9]+[-_]/i,'');
  n=n.replace(/[-_](full|lesson|interactive|exam|quiz|test|exercise|revision)$/i,'');
  n=n.replace(/[-_]/g,' ').replace(/\s+/g,' ').trim();
  return n.replace(/\b\w/g,function(c){return c.toUpperCase();})||'Lesson';
}

function addFiles(files){
  const t=document.getElementById('upType').value;
  if(!t){alert('Select a Content Type first.');return;}
  const sid=document.getElementById('upSubj').value;
  if(!sid){alert('Select a Subject first.');return;}
  files.forEach(function(file){
    const ext=file.name.split('.').pop().toLowerCase();
    if(!ALLOWED[t].includes(ext)){alert(file.name+' wrong type. Allowed: '+ALLOWED[t].join(', '));return;}
    if(file.size>524288000){alert(file.name+' too large (max 500MB).');return;}
    const id='q'+Date.now()+Math.random().toString(36).slice(2,6);
    const title=genTitle(file.name);
    const el=document.createElement('div');el.className='qi';el.id=id;
    el.innerHTML='<div class="qi-top"><span class="qi-name" title="'+file.name+'">'+file.name+'</span><span class="qi-sz">'+(file.size/1048576).toFixed(1)+'MB</span><button class="qrm" onclick="rmQ(\''+id+'\')">X</button></div><input class="qi-title" type="text" value="'+title+'" placeholder="Title shown to students"><div class="pb"><div class="pf" id="pf-'+id+'"></div></div><div class="qs wait" id="st-'+id+'">Waiting...</div>';
    document.getElementById('queue').appendChild(el);
    Q.push({id:id,file:file,el:el});
  });
  if(Q.length){document.getElementById('upAllBtn').style.display='inline-flex';document.getElementById('clrBtn').style.display='inline-flex';}
}

function rmQ(id){Q=Q.filter(function(q){return q.id!==id;});const e=document.getElementById(id);if(e)e.remove();if(!Q.length){document.getElementById('upAllBtn').style.display='none';document.getElementById('clrBtn').style.display='none';}}
function clearQ(){Q=[];document.getElementById('queue').innerHTML='';document.getElementById('upAllBtn').style.display='none';document.getElementById('clrBtn').style.display='none';document.getElementById('summary').textContent='';}

async function uploadAll(){
  if(!Q.length)return;
  const t=document.getElementById('upType').value;const sid=document.getElementById('upSubj').value;
  const extra=document.getElementById('ev').value.trim();
  if(!t||!sid){alert('Select type and subject first.');return;}
  const btn=document.getElementById('upAllBtn');btn.disabled=true;btn.textContent='Uploading...';
  let ok=0,fail=0;
  for(const item of [...Q]){
    const titleEl=item.el.querySelector('.qi-title');
    const pfEl=document.getElementById('pf-'+item.id);
    const stEl=document.getElementById('st-'+item.id);
    stEl.className='qs up';stEl.textContent='Uploading...';
    const fd=new FormData();
    fd.append('file',item.file);fd.append('subject_id',sid);fd.append('type',t);
    fd.append('custom_title',titleEl.value.trim());
    if(t==='pdf')fd.append('year',extra);
    if(t==='vid')fd.append('duration',extra);
    await new Promise(function(resolve){
      const xhr=new XMLHttpRequest();xhr.open('POST','?ajax=upload');
      xhr.upload.onprogress=function(e){if(e.lengthComputable)pfEl.style.width=Math.round(e.loaded/e.total*100)+'%';};
      xhr.onload=function(){
        try{const r=JSON.parse(xhr.responseText);
          if(r.ok){pfEl.style.width='100%';pfEl.style.background='#2e7d32';stEl.className='qs done';stEl.textContent='OK: '+r.msg;ok++;Q=Q.filter(function(q){return q.id!==item.id;});}
          else{stEl.className='qs fail';stEl.textContent='FAIL: '+r.msg;fail++;}
        }catch(e){stEl.className='qs fail';stEl.textContent='Server error';fail++;}
        resolve();
      };
      xhr.onerror=function(){stEl.className='qs fail';stEl.textContent='Network error';fail++;resolve();};
      xhr.send(fd);
    });
  }
  btn.disabled=false;btn.textContent='Upload All';
  document.getElementById('summary').innerHTML='<span style="color:#2e7d32;font-weight:700">'+ok+' uploaded</span>'+(fail?' <span style="color:#c62828;font-weight:700">'+fail+' failed</span>':'')+' <a href="?tab=manage" style="color:#0a5e2a;font-weight:700;text-decoration:underline">View files</a>';
}

// MANAGE
function delFile(sid,type,idx,title){
  if(!confirm('Delete "'+title+'"? This removes the file permanently.'))return;
  const fd=new FormData();fd.append('sid',sid);fd.append('type',type);fd.append('idx',idx);
  fetch('?ajax=delete',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
    if(r.ok){const row=document.getElementById('row-'+sid+'-'+type+'-'+idx);if(row)row.remove();}
    else alert('Error: '+(r.msg||'unknown'));
  }).catch(function(){alert('Network error.');});
}
function renameTitle(span,sid,type,idx){
  const cur=span.textContent.trim();const nw=prompt('Enter new title:',cur);
  if(!nw||nw===cur)return;
  const fd=new FormData();fd.append('sid',sid);fd.append('type',type);fd.append('idx',idx);fd.append('title',nw);
  fetch('?ajax=rename',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
    if(r.ok)span.textContent=nw;else alert('Rename failed.');
  }).catch(function(){alert('Network error.');});
}

// ANNOUNCEMENTS
async function loadAD(){
  try{const r=await fetch('../announcements.json?n='+Date.now());if(r.ok)return await r.json();}catch(e){}
  return{notices:[],suggestions_enabled:false,suggestion_prompt:''};
}
async function postNotice(){
  const t=document.getElementById('anTitle').value.trim();const b=document.getElementById('anBody').value.trim();
  if(!t||!b){alert('Fill in title and message.');return;}
  const n={id:'n'+Date.now(),type:document.getElementById('anType').value,title:t,body:b,
    subject:(document.getElementById('anSubject').value||'').trim(),
    start_date:(document.getElementById('anDate').value||''),
    emoji:(document.getElementById('anEmoji').value||'').trim()||'📌',
    pinned:document.getElementById('anPinned').checked,
    suggest:document.getElementById('anSuggest').checked,
    active:true,created:new Date().toISOString().slice(0,10)};
  const data=await loadAD();if(!data.notices)data.notices=[];
  if(n.pinned)data.notices.unshift(n);else data.notices.push(n);
  const sr=await fetch('?ajax=announce',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  const sd=await sr.json();const msg=document.getElementById('anMsg');
  if(sd.ok){msg.style.display='inline';msg.style.color='#2e7d32';msg.textContent='Posted!';setTimeout(function(){location.reload();},900);}
  else{msg.style.display='inline';msg.style.color='#c62828';msg.textContent='Failed.';}
}
async function toggleNotice(id,newState){
  const fd=new FormData();fd.append('id',id);fd.append('active',newState);
  const r=await fetch(location.pathname+'?ajax=hide-notice',{method:'POST',body:fd});
  const res=await r.json();
  if(res.ok){location.reload();}else{alert('Hide/Show failed. Please try again.');}
}
async function deleteNotice(id,title){
  if(!confirm('Delete "'+title+'"?'))return;
  const fd=new FormData();fd.append('id',id);
  const r=await fetch(location.pathname+'?ajax=del-notice',{method:'POST',body:fd});
  const res=await r.json();
  if(res.ok){const el=document.getElementById('anc-'+id);if(el)el.remove();}
  else{alert('Delete failed. Error: '+(res.msg||'Unknown. Check file permissions on announcements.json'));}
}
async function saveSuggest(){
  const data=await loadAD();
  const tog=document.getElementById('suggestToggle');const pr=document.getElementById('sugPrompt');
  if(tog)data.suggestions_enabled=tog.checked;if(pr)data.suggestion_prompt=pr.value.trim();
  const sr=await fetch('?ajax=announce',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  if((await sr.json()).ok){const m=document.getElementById('sugMsg');if(m){m.style.display='inline';setTimeout(function(){m.style.display='none';},2000);}}
}

// LEARNERS
function filterL(){
  const q=document.getElementById('ls').value.toLowerCase();
  document.querySelectorAll('#lt tbody tr').forEach(function(row){row.style.display=row.textContent.toLowerCase().includes(q)?'':'none';});
}

// PDF VIEWER
if(typeof pdfjsLib!=='undefined')pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
let pDoc=null,pPage=1,pTotal=1,pBusy=false;
function previewPdf(url,title){
  document.getElementById('pt').textContent=title;document.getElementById('pm').classList.add('open');
  document.body.style.overflow='hidden';document.getElementById('pi').textContent='Loading...';pPage=1;
  pdfjsLib.getDocument(url).promise.then(function(doc){pDoc=doc;pTotal=doc.numPages;renderP(1);}).catch(function(e){document.getElementById('pi').textContent='Error: '+e.message;});
}
function renderP(num){
  if(pBusy||!pDoc)return;pBusy=true;
  pDoc.getPage(num).then(function(page){
    const canvas=document.getElementById('pc');const ctx=canvas.getContext('2d');
    const vw=Math.min(window.innerWidth-32,900);const vp0=page.getViewport({scale:1});const scale=vw/vp0.width;const vp=page.getViewport({scale:scale});
    canvas.width=vp.width;canvas.height=vp.height;
    page.render({canvasContext:ctx,viewport:vp}).promise.then(function(){pBusy=false;pPage=num;document.getElementById('pi').textContent='Page '+num+' / '+pTotal;});
  });
}
function pPrev(){if(pPage>1)renderP(pPage-1);}
function pNext(){if(pPage<pTotal)renderP(pPage+1);}
function closePdf(){document.getElementById('pm').classList.remove('open');document.body.style.overflow='';pDoc=null;}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closePdf();});
</script>
<?php endif;?>
</body>
</html>
