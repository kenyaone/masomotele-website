<?php
/**
 * M.T.T.I LMS - Class Leaderboard
 * URL: /leaderboard.php?class_id=1
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/GamificationEngine.php';
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$classId = (int)($_GET['class_id'] ?? 0);

// Get all lms_classes if no class specified
$classes = $db->fetchAll("SELECT c.* FROM lms_classes c JOIN enrolments e ON c.id=e.class_id WHERE e.user_id=? AND c.status='active'", [$userId]);
if (!$classId && !empty($classes)) $classId = $classes[0]['id'];
$class = $classId ? $db->fetchOne("SELECT * FROM lms_classes WHERE id=?", [$classId]) : null;

// Leaderboard data
$leaders = $classId ? $db->fetchAll(
    "SELECT u.id, u.name, u.photo,
        COALESCE(ux.xp,0) as xp,
        COALESCE(ux.level,1) as level,
        COALESCE(ux.streak_days,0) as streak,
        COALESCE(ux.total_lessons_watched,0) as lessons_done,
        (SELECT COUNT(*) FROM lms_user_badges WHERE user_id=u.id) as badge_count,
        (SELECT COUNT(*) FROM lms_completions WHERE user_id=u.id AND class_id=?) as completed
     FROM lms_users u
     JOIN enrolments e ON u.id=e.user_id
     LEFT JOIN user_xp ux ON u.id=ux.user_id AND ux.class_id=?
     WHERE e.class_id=? AND u.role='student' AND u.status='active'
     ORDER BY xp DESC, completed DESC
     LIMIT 50",
    [$classId, $classId, $classId]
) : [];

// My rank
$myRank = 0;
foreach ($leaders as $i => $l) { if ($l['id'] == $userId) { $myRank = $i + 1; break; } }

// My badges
$myBadges = $db->fetchAll(
    "SELECT b.*, ub.earned_at FROM lms_badges b JOIN user_badges ub ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.earned_at DESC",
    [$userId]);

// All badges (for display)
$allBadges = $db->fetchAll("SELECT * FROM lms_badges ORDER BY sort_order");
$myBadgeSlugs = array_column($myBadges, 'slug');

// Hardcode emoji icons (DB charset workaround)
$emojiMap = [
    1=>'&#x1F680;', 2=>'&#x1F3C5;', 3=>'&#x1F4AC;', 4=>'&#x2B50;', 5=>'&#x1F4AA;',
    6=>'&#x1F3AF;', 7=>'&#x1F3AC;', 8=>'&#x1F525;', 9=>'&#x26A1;', 10=>'&#x1F3C6;',
    11=>'&#x1F4DA;', 12=>'&#x1F4CA;', 13=>'&#x1F393;', 14=>'&#x1F319;',
    15=>'&#x1F305;', 16=>'&#x1F4A8;', 17=>'&#x1F4C5;'
];
foreach ($allBadges as &$b) { if (isset($emojiMap[$b['id']])) $b['icon'] = $emojiMap[$b['id']]; }
unset($b);


// My XP
$myXp = $db->fetchOne("SELECT * FROM user_xp WHERE user_id=? AND class_id=?", [$userId, $classId]);
$myXpVal = (int)($myXp['xp'] ?? 0);
$myLevel = (int)($myXp['level'] ?? 1);
$myStreak = (int)($myXp['streak_days'] ?? 0);
$nextLevelXp = GamificationEngine::getNextLevelXp($myLevel);
$prevLevelXp = GamificationEngine::getNextLevelXp($myLevel - 1);
$xpProgress = $nextLevelXp > $prevLevelXp ? round((($myXpVal - $prevLevelXp) / ($nextLevelXp - $prevLevelXp)) * 100) : 100;

$pageTitle = 'Leaderboard - ' . SITE_NAME;
require_once __DIR__ . '/templates/header.php';
?>
<style>
:root{--grn:#1a5632;--gold:#e8a423;--dark:#0f1923;--dark2:#1c2b3a}
body{background:#f0f4f8}
.lb-wrap{max-width:1100px;margin:0 auto;padding:24px 16px}

/* Hero XP card */
.xp-hero{background:linear-gradient(135deg,#1a5632,#2d7a4c);border-radius:16px;padding:24px;color:#fff;margin-bottom:24px;display:flex;gap:24px;flex-wrap:wrap;align-items:center}
.xp-avatar{width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#fff;flex-shrink:0;border:3px solid var(--gold)}
.xp-info{flex:1}
.xp-info h5{margin:0 0 4px;font-size:1.1rem;font-weight:700}
.xp-info .sub{opacity:.7;font-size:.8rem}
.xp-bar-wrap{margin-top:10px}
.xp-bar{height:8px;background:rgba(255,255,255,.2);border-radius:4px;overflow:hidden}
.xp-fill{height:100%;background:var(--gold);border-radius:4px;transition:width .8s}
.xp-bar-txt{display:flex;justify-content:space-between;font-size:.72rem;opacity:.7;margin-top:3px}
.xp-stats{display:flex;gap:20px;flex-wrap:wrap}
.xp-stat{text-align:center;background:rgba(255,255,255,.1);padding:12px 20px;border-radius:10px}
.xp-stat .val{font-size:1.5rem;font-weight:800;color:var(--gold)}
.xp-stat .lbl{font-size:.7rem;opacity:.7;text-transform:uppercase;letter-spacing:.05em}

/* Badges */
.badge-grid{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:24px}
.bdg{display:flex;flex-direction:column;align-items:center;gap:4px;padding:12px;border-radius:12px;background:#fff;border:2px solid #e2e8f0;width:90px;text-align:center;transition:all .2s}
.bdg.earned{border-color:var(--gold);background:linear-gradient(135deg,#fffbeb,#fef9c3)}
.bdg.locked{opacity:.35;filter:grayscale(1)}
.bdg .bi{font-size:1.8rem}
.bdg .btitle{font-size:.65rem;font-weight:700;color:#475569;line-height:1.2}
.bdg.earned .btitle{color:#92400e}
.bdg .bxp{font-size:.6rem;color:#94a3b8;margin-top:1px}.bdg .bdesc{font-size:.6rem;color:#64748b;line-height:1.3;margin-top:2px}.bdg{width:130px}

/* Leaderboard table */
.lb-card{background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;margin-bottom:24px}
.lb-card .hd{padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between}
.lb-card .hd h5{margin:0;font-weight:700;font-size:1rem}
.lb-row{display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f1f5f9;transition:background .15s}
.lb-row:hover{background:#f8fafc}
.lb-row.me{background:linear-gradient(90deg,#fffbeb,#fff);border-left:3px solid var(--gold)}
.lb-row.top1{background:linear-gradient(90deg,#fef9c3,#fff)}
.lb-row.top2{background:linear-gradient(90deg,#f1f5f9,#fff)}
.lb-row.top3{background:linear-gradient(90deg,#fef3c7,#fff)}
.lb-rank{width:32px;text-align:center;font-weight:800;font-size:.9rem;flex-shrink:0}
.rank-1{color:#f59e0b;font-size:1.2rem}
.rank-2{color:#94a3b8;font-size:1.1rem}
.rank-3{color:#b45309}
.lb-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#1a5632,#2d7a4c);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0}
.lb-name{flex:1;font-weight:600;font-size:.88rem;color:#1e293b}
.lb-name .sub{font-size:.72rem;color:#94a3b8;font-weight:400}
.lb-xp{font-weight:800;font-size:.9rem;color:var(--grn);min-width:60px;text-align:right}
.lb-streak{display:flex;align-items:center;gap:3px;font-size:.78rem;color:#ef4444;font-weight:700;min-width:50px}
.lb-badges{display:flex;gap:3px;min-width:40px}
.lb-mini-badge{font-size:.85rem}
.lb-level{background:#e8f5ee;color:#1a5632;font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:8px}

/* Class tabs */
.class-tabs{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.class-tab{padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600;text-decoration:none;background:#fff;color:#475569;border:1px solid #e2e8f0;transition:all .15s}
.class-tab.active,.class-tab:hover{background:var(--grn);color:#fff;border-color:var(--grn)}

@media(max-width:600px){.xp-hero{flex-direction:column}.xp-stats{justify-content:center}.lb-streak,.lb-badges{display:none}}
</style>

<div class="lb-wrap">

  <!-- Class tabs -->
  <?php if (count($classes) > 1): ?>
  <div class="class-tabs">
    <?php foreach ($classes as $c): ?>
    <a href="?class_id=<?= $c['id'] ?>" class="class-tab <?= $c['id']==$classId?'active':'' ?>"><?= htmlspecialchars($c['title']) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- My XP Hero -->
<?php if ($auth->getRole() === 'student'): ?>
  <div class="xp-hero">
    <div class="xp-avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
    <div class="xp-info">
      <h5><?= htmlspecialchars($_SESSION['user_name']) ?> <?php if($myStreak>=3): ?><span style="background:rgba(239,68,68,.2);padding:2px 8px;border-radius:8px;font-size:.75rem">🔥 <?= $myStreak ?>-day streak</span><?php endif; ?></h5>
      <div class="sub">Level <?= $myLevel ?> · <?php echo $myRank ? "#$myRank on leaderboard" : "Not ranked yet"; ?> · <?= count($myBadges) ?> badges earned</div>
      <div class="xp-bar-wrap">
        <div class="xp-bar"><div class="xp-fill" style="width:<?= $xpProgress ?>%"></div></div>
        <div class="xp-bar-txt"><span><?= $myXpVal ?> XP</span><span>Level <?= $myLevel+1 ?> at <?= $nextLevelXp ?> XP</span></div>
      </div>
    </div>
    <div class="xp-stats">
      <div class="xp-stat"><div class="val"><?= $myXpVal ?></div><div class="lbl">Total XP</div></div>
      <div class="xp-stat"><div class="val"><?= $myStreak ?></div><div class="lbl">Day Streak</div></div>
      <div class="xp-stat"><div class="val"><?= $myXp['total_lessons_watched'] ?? 0 ?></div><div class="lbl">Lessons</div></div>
      <div class="xp-stat"><div class="val"><?= $myRank ?: '-' ?></div><div class="lbl">Rank</div></div>
    </div>
  </div>

<?php endif; ?>
  <!-- Badges -->
<?php if ($auth->getRole() === 'student'): ?>
  <div class="lb-card">
    <div class="hd"><h5><i class="bi bi-award-fill me-2" style="color:var(--gold)"></i>Badges</h5><span style="font-size:.78rem;color:#94a3b8"><?= count($myBadges) ?> / <?= count($allBadges) ?> earned</span></div>
    <div style="padding:16px"><div class="badge-grid">
      <?php foreach ($allBadges as $b):
        $earned = in_array($b['slug'], $myBadgeSlugs); ?>
      <div class="bdg <?= $earned?'earned':'locked' ?>"
           onclick="showBadge('<?= addslashes($b['title']) ?>','<?= addslashes($b['description']) ?>','<?= $b['icon'] ?>','<?= $b['color'] ?>',<?= $b['xp_reward'] ?>,'<?= $earned?'earned':'locked' ?>','')"
           title="<?= htmlspecialchars($b['description']) ?>">
        <span style="font-size:2rem;line-height:1.2"><?= $b["icon"] ?></span>
        <span class="btitle"><?= htmlspecialchars($b['title']) ?></span>
        <span class="bdesc"><?= htmlspecialchars($b['description']) ?></span>
        <span class="bxp"><?= $earned ? '<span style=color:#16a34a>&#10003; Earned</span>' : '+'.($b['xp_reward']).' XP' ?></span>
      </div>
      <?php endforeach; ?>
    </div></div>
  </div>

<?php endif; ?>
  <!-- Leaderboard -->
  <div class="lb-card">
    <div class="hd">
      <h5><i class="bi bi-trophy-fill me-2" style="color:var(--gold)"></i>Class Leaderboard <?php if($class): ?>— <?= htmlspecialchars($class['title']) ?><?php endif; ?></h5>
      <span style="font-size:.78rem;color:#94a3b8"><?= count($leaders) ?> students</span>
    </div>
    <?php if (empty($leaders)): ?>
      <div style="text-align:center;padding:40px;color:#94a3b8"><i class="bi bi-people" style="font-size:2rem"></i><p style="margin-top:8px">No students ranked yet. Start learning to appear here!</p></div>
    <?php else: ?>
      <?php foreach ($leaders as $i => $l):
        $rank = $i + 1;
        $isMe = $l['id'] == $userId;
        $rankIcon = $rank===1?'🥇':($rank===2?'🥈':($rank===3?'🥉':$rank));
        $rowClass = $isMe?'me':($rank===1?'top1':($rank===2?'top2':($rank===3?'top3':'')));
        $initial = strtoupper(substr($l['name'],0,1));
        // Get top 3 badges for this user
        $ubadges = $db->fetchAll("SELECT b.icon, b.color FROM lms_user_badges ub JOIN badges b ON ub.badge_id=b.id WHERE ub.user_id=? ORDER BY ub.earned_at DESC LIMIT 3", [$l['id']]);
      ?>
      <div class="lb-row <?= $rowClass ?>">
        <div class="lb-rank <?= $rank<=3?'rank-'.$rank:'' ?>"><?= $rankIcon ?></div>
        <div class="lb-avatar"><?= $initial ?></div>
        <div class="lb-name">
          <?= htmlspecialchars($l['name']) ?> <?= $isMe?'<span style="background:#fef9c3;color:#92400e;font-size:.65rem;padding:1px 6px;border-radius:6px">You</span>':'' ?>
          <div class="sub">Level <?= $l['level'] ?> · <?= $l['lessons_done'] ?> lessons</div>
        </div>
        <div class="lb-badges">
          <?php foreach($ubadges as $ub): ?>
          <span class="lb-mini-badge" title="badge"><i class="bi bi-<?= $ub['icon'] ?>" style="color:<?= $ub['color'] ?>"></i></span>
          <?php endforeach; ?>
        </div>
        <?php if($l['streak']>=3): ?>
        <div class="lb-streak">🔥<?= $l['streak'] ?>d</div>
        <?php else: ?><div class="lb-streak"></div><?php endif; ?>
        <div><span class="lb-level">Lv <?= $l['level'] ?></span></div>
        <div class="lb-xp"><?= number_format($l['xp']) ?> XP</div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
