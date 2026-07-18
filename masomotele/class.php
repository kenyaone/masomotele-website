<?php
/**
 * M.T.T.I LMS - Student Class View (UX v6)
 * Two-tab sidebar: Worksheets | Interactives
 * Organized: Parent Subject → Strand → Lessons
 * Full-view overlay for content
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$role = $auth->getRole();
$classId = (int)($_GET['id'] ?? 0);
$lessonId = (int)($_GET['lesson'] ?? 0);
$class = $db->fetchOne("SELECT * FROM lms_classes WHERE id=? AND status='active'", [$classId]);
if (!$class) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }
$pageTitle = htmlspecialchars($class['title']) . ' - ' . SITE_NAME;

if ($role === 'student') {
    $enrolled = $db->fetchOne("SELECT id FROM lms_enrolments WHERE user_id=? AND class_id=?", [$userId, $classId]);
    if (!$enrolled) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }
}

// Get ALL published lessons with subject hierarchy + file type
$allLessons = $db->fetchAll(
    "SELECT l.*, 
            s.id as sid, s.title as subject_name, s.parent_id as sparent, s.sort_order as ssort,
            p.id as pid, p.title as parent_name, p.parent_id as gparent, p.sort_order as psort,
            gp.id as gpid, gp.title as grandparent_name, gp.sort_order as gpsort,
            lf.filetype as primary_filetype
     FROM lms_lessons l 
     LEFT JOIN lms_subjects s ON l.subject_id = s.id
     LEFT JOIN lms_subjects p ON s.parent_id = p.id
     LEFT JOIN lms_subjects gp ON p.parent_id = gp.id
     LEFT JOIN lms_lesson_files lf ON lf.lesson_id = l.id AND lf.id = (SELECT MIN(id) FROM lms_lesson_files WHERE lesson_id = l.id)
     WHERE l.class_id = ? AND l.status = 'published'
     ORDER BY COALESCE(gp.sort_order, p.sort_order, s.sort_order, 0),
              COALESCE(p.sort_order, s.sort_order, 0),
              s.sort_order, l.sort_order, l.id",
    [$classId]
);

$completions = $db->fetchAll("SELECT lesson_id FROM lms_completions WHERE user_id=? AND class_id=?", [$userId, $classId]);
$completedIds = array_column($completions, 'lesson_id');

// All lessons in one unified list

// Build tree from a flat list
function buildTree($lessons) {
    $tree = [];
    foreach ($lessons as $l) {
        if ($l['grandparent_name']) {
            $topKey = $l['grandparent_name']; $midKey = $l['parent_name'];
        } elseif ($l['parent_name']) {
            $topKey = $l['parent_name']; $midKey = $l['subject_name'];
        } else {
            $topKey = $l['subject_name'] ?: 'General'; $midKey = null;
        }
        if (!isset($tree[$topKey])) $tree[$topKey] = ['strands' => [], 'sort' => $l['gpsort'] ?? $l['psort'] ?? $l['ssort'] ?? 999];
        if ($midKey) {
            if (!isset($tree[$topKey]['strands'][$midKey])) $tree[$topKey]['strands'][$midKey] = ['lms_lessons' => [], 'sort' => $l['psort'] ?? $l['ssort'] ?? 0];
            $tree[$topKey]['strands'][$midKey]['lms_lessons'][] = $l;
        } else {
            if (!isset($tree[$topKey]['strands']['_direct'])) $tree[$topKey]['strands']['_direct'] = ['lms_lessons' => [], 'sort' => -1];
            $tree[$topKey]['strands']['_direct']['lms_lessons'][] = $l;
        }
    }
    uasort($tree, fn($a, $b) => ($a['sort'] ?? 999) <=> ($b['sort'] ?? 999));
    foreach ($tree as &$s) { uasort($s['strands'], fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0)); }
    return $tree;
}

$lessonTree = buildTree($allLessons);

// Flat list for prev/next
$flatCurrent = $allLessons;
$totalLessons = count($flatCurrent);
$completedCount = 0;
foreach ($flatCurrent as $fl) { if (in_array($fl['id'], $completedIds)) $completedCount++; }
$pct = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;

// Overall stats
$totalAll = count($allLessons);
$doneAll = 0; foreach ($allLessons as $al) { if (in_array($al['id'], $completedIds)) $doneAll++; }
$pctAll = $totalAll > 0 ? round(($doneAll / $totalAll) * 100) : 0;

$currentLesson = null;
if ($lessonId) $currentLesson = $db->fetchOne("SELECT * FROM lms_lessons WHERE id=? AND class_id=?", [$lessonId, $classId]);
if (!$currentLesson && !empty($allLessons)) { $currentLesson = $allLessons[0]; $lessonId = $currentLesson['id']; }
$files = $lessonId ? $db->fetchAll("SELECT * FROM lms_lesson_files WHERE lesson_id=? ORDER BY id", [$lessonId]) : [];

$currentIdx = false;
foreach ($flatCurrent as $i => $fl) { if ($fl['id'] == $lessonId) { $currentIdx = $i; break; } }
$prevId = ($currentIdx !== false && $currentIdx > 0) ? $flatCurrent[$currentIdx - 1]['id'] : null;
$nextId = ($currentIdx !== false && $currentIdx < $totalLessons - 1) ? $flatCurrent[$currentIdx + 1]['id'] : null;

$pf = !empty($files) ? $files[0] : null;
$ext = $pf ? strtolower($pf['filetype'] ?? pathinfo($pf['filepath'], PATHINFO_EXTENSION)) : '';
$url = $pf ? SITE_URL . '/' . $pf['filepath'] : '';
$isHtml = in_array($ext, ['html', 'htm']);
$isPdf = ($ext === 'pdf');
$isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
$isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Upcoming live sessions for this class
$liveSessions = $db->fetchAll(
    "SELECT * FROM lms_live_sessions WHERE class_id=? AND status IN ('scheduled','live') ORDER BY scheduled_at ASC LIMIT 3",
    [$classId]
);

require_once __DIR__ . '/templates/header.php';
?>
<style>
:root{--bg:#f4f5f7;--card:#fff;--pri:#1a6b3c;--pri-l:#e8f5ee;--ok:#16a34a;--ok-l:#dcfce7;--txt:#1e293b;--mut:#8896a7;--brd:#e5e7eb;--sb-w:280px;--r:8px;--blue:#2563eb;--blue-l:#dbeafe;--orange:#ea580c;--orange-l:#fff7ed}
*{box-sizing:border-box}
.lw{display:flex;min-height:calc(100vh - 56px);background:var(--bg)}

/* SIDEBAR */
.sb{width:var(--sb-w);background:var(--card);border-right:1px solid var(--brd);display:flex;flex-direction:column;position:sticky;top:56px;height:calc(100vh - 56px);z-index:20;transition:transform .25s}
.sb::-webkit-scrollbar{width:3px}.sb::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:3px}
.sb-h{padding:12px 14px 0;border-bottom:1px solid var(--brd)}
.sb-h a.back{font-size:.7rem;color:var(--mut);text-decoration:none;display:flex;align-items:center;gap:3px;margin-bottom:4px}.sb-h a.back:hover{color:var(--pri)}
.sb-h h3{font-size:.85rem;font-weight:700;color:var(--txt);margin:0 0 6px;line-height:1.3}
.pb{height:5px;background:var(--brd);border-radius:3px;overflow:hidden}.pb-f{height:100%;border-radius:3px;transition:width .4s}
.pb-t{font-size:.66rem;color:var(--mut);margin-top:2px;margin-bottom:8px}

/* Tabs */
.sb-tabs{display:flex;border-bottom:1px solid var(--brd)}
.sb-tab{flex:1;padding:9px 8px;font-size:.74rem;font-weight:700;text-align:center;cursor:pointer;border-bottom:2px solid transparent;color:var(--mut);transition:all .15s;user-select:none;display:flex;align-items:center;justify-content:center;gap:4px}
.sb-tab:hover{color:var(--txt);background:#f9fafb}
.sb-tab.active-ws{color:var(--pri);border-bottom-color:var(--pri);background:var(--pri-l)}
.sb-tab.active-int{color:var(--orange);border-bottom-color:var(--orange);background:var(--orange-l)}
.sb-tab.active-vid{color:#7c3aed;border-bottom-color:#7c3aed;background:#ede9fe}
.sb-tab .cnt{font-size:.62rem;padding:1px 5px;border-radius:8px;background:#f1f3f5}
.sb-tab.active-ws .cnt{background:var(--ok-l);color:var(--ok)}
.sb-tab.active-int .cnt{background:#fed7aa;color:var(--orange)}
.sb-tab.active-vid .cnt{background:#ede9fe;color:#7c3aed}

.sb-panel{flex:1;overflow-y:auto;display:none}.sb-panel.active{display:block}

/* Subject/strand/lesson items */
.sj-h{display:flex;align-items:center;gap:5px;padding:8px 12px;font-size:.75rem;font-weight:700;color:var(--txt);cursor:pointer;user-select:none;text-transform:uppercase;letter-spacing:.01em}
.sj-h:hover{background:#f0f1f3}
.sj-h .ar{font-size:.55rem;color:var(--mut);transition:transform .2s;flex-shrink:0}
.sj-h.fold .ar{transform:rotate(-90deg)}
.sj-h .ct{margin-left:auto;font-size:.6rem;padding:1px 5px;border-radius:8px;background:#f1f3f5;color:var(--mut);font-weight:600}
.sj-h .ct.dn{background:var(--ok-l);color:var(--ok)}
.st-h{display:flex;align-items:center;gap:5px;padding:4px 12px 4px 20px;font-size:.7rem;font-weight:600;color:var(--mut);cursor:pointer}.st-h:hover{color:var(--txt)}
.st-h .ar{font-size:.5rem;transition:transform .15s}.st-h.fold .ar{transform:rotate(-90deg)}
.coll{overflow:hidden;transition:max-height .25s ease}.coll.fold{max-height:0!important}
.li{display:flex;align-items:center;gap:6px;padding:5px 12px 5px 30px;text-decoration:none;color:var(--txt);font-size:.74rem;transition:all .1s;border-left:2px solid transparent;line-height:1.25;cursor:pointer}
.li:hover{background:var(--pri-l)}
.li.ac{background:var(--pri-l);border-left-color:var(--pri);font-weight:600}
.li.int-ac{background:var(--orange-l);border-left-color:var(--orange);font-weight:600}
.li .ck{font-size:.65rem;color:#d1d5db;flex-shrink:0}.li .ck.ok{color:var(--ok)}
.li .tt{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.li .ft-badge{font-size:.55rem;padding:1px 4px;border-radius:3px;font-weight:600;flex-shrink:0}
.li .ft-pdf{background:#fef2f2;color:#dc2626}.li .ft-html{background:var(--orange-l);color:var(--orange)}.li .ft-vid{background:#ede9fe;color:#7c3aed}

/* MAIN */
.mn{flex:1;min-width:0;display:flex;flex-direction:column}
.lh{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 16px;background:var(--card);border-bottom:1px solid var(--brd);flex-wrap:wrap}
.lh h4{font-size:.92rem;font-weight:700;color:var(--txt);margin:0}.lh .bc{font-size:.66rem;color:var(--mut);margin-bottom:1px}
.lh-btns{display:flex;gap:6px;align-items:center;flex-shrink:0}
.btn-s{display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:var(--r);font-size:.76rem;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:all .15s}
.btn-g{background:var(--ok);color:#fff}.btn-g:hover{opacity:.9}
.btn-done{background:var(--ok-l);color:var(--ok);cursor:default}
.btn-p{background:var(--pri);color:#fff}.btn-p:hover{opacity:.9}
.btn-o{background:#f1f3f5;color:var(--txt)}.btn-o:hover{background:#e5e7eb}
.btn-w{background:var(--card);border:1px solid var(--brd);color:var(--txt)}.btn-w:hover{background:#f9fafb}
.lc{flex:1;position:relative;background:#1a1a1a}
.lc iframe,.lc video{width:100%;border:none;display:block}
.lc iframe{height:calc(100vh - 150px);min-height:400px}
.lc video{max-height:calc(100vh - 150px)}
.lc img.li-img{width:100%;max-height:calc(100vh - 150px);object-fit:contain;background:var(--bg);display:block}
.lc-dl{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:50px 20px;background:var(--bg);color:var(--mut)}
.lc-dl i{font-size:2.5rem;margin-bottom:8px;opacity:.4}
.att{display:flex;gap:6px;flex-wrap:wrap;padding:8px 16px;background:var(--bg);border-top:1px solid var(--brd)}
.att-c{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:var(--card);border:1px solid var(--brd);border-radius:16px;font-size:.7rem;color:var(--pri);text-decoration:none;cursor:pointer;transition:all .12s}.att-c:hover{background:var(--pri-l);border-color:var(--pri)}
.bn{display:flex;align-items:center;justify-content:space-between;padding:8px 16px;background:var(--card);border-top:1px solid var(--brd)}
.bn .cnt{font-size:.73rem;color:var(--mut)}

/* Full-view */
.fv{position:fixed;inset:0;z-index:9999;background:#fff;display:none;flex-direction:column}.fv.open{display:flex}
.fv-bar{display:flex;align-items:center;gap:8px;padding:6px 12px;background:#1e293b;color:#fff;flex-shrink:0}
.fv-bar h5{font-size:.8rem;font-weight:600;margin:0;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fv-b{flex:1;overflow:auto}.fv-b iframe{width:100%;height:100%;border:none}
.fv-btn{display:inline-flex;align-items:center;gap:3px;padding:4px 10px;border-radius:5px;font-size:.72rem;font-weight:600;border:none;cursor:pointer;text-decoration:none;transition:all .12s}
.fv-x{background:rgba(255,255,255,.15);color:#fff}.fv-x:hover{background:rgba(255,255,255,.25)}
.fv-n{background:rgba(255,255,255,.08);color:#94a3b8}.fv-n:hover{background:rgba(255,255,255,.15);color:#fff}.fv-n.dis{opacity:.3;pointer-events:none}

/* Mobile */
.mob-btn{display:none;position:fixed;bottom:16px;right:16px;z-index:30;width:44px;height:44px;border-radius:50%;background:var(--pri);color:#fff;border:none;cursor:pointer;font-size:1.1rem;box-shadow:0 3px 10px rgba(0,0,0,.25);align-items:center;justify-content:center}
.sb-bg{position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:19;display:none}.sb-bg.show{display:block}
@media(max-width:768px){
    .sb{position:fixed;top:0;left:0;height:100%;transform:translateX(-100%);z-index:25;box-shadow:3px 0 15px rgba(0,0,0,.1)}.sb.open{transform:translateX(0)}
    .mob-btn{display:flex}
}
.empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;color:var(--mut);height:50vh}.empty i{font-size:2.5rem;margin-bottom:8px;opacity:.4}
</style>

<div class="lw">
    <div class="sb" id="sidebar">
        <div class="sb-h">
            <a href="<?= SITE_URL ?>/dashboard.php" class="back"><i class="bi bi-arrow-left"></i> Dashboard</a>
            <h3><?= htmlspecialchars($class['title']) ?></h3>
            <div class="pb"><div class="pb-f" style="width:<?= $pctAll ?>%;background:var(--ok)"></div></div>
            <div class="pb-t"><?= $doneAll ?>/<?= $totalAll ?> overall (<?= $pctAll ?>%)</div>
        </div>

        <!-- LIVE SESSIONS PANEL -->
        <?php if (!empty($liveSessions)): ?>
        <div style="padding:8px 14px;border-bottom:1px solid var(--brd)">
            <?php foreach ($liveSessions as $ls):
                $isLive = ($ls['status'] === 'live');
                $lsTs   = strtotime($ls['scheduled_at']);
            ?>
            <a href="<?= SITE_URL ?>/live-class.php?id=<?= $ls['id'] ?>"
               style="display:flex;align-items:center;gap:8px;padding:7px 0;text-decoration:none;color:var(--txt)">
                <div style="width:32px;height:32px;border-radius:50%;background:<?= $isLive ? '#dcfce7' : '#dbeafe' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-camera-video-fill" style="font-size:.8rem;color:<?= $isLive ? '#16a34a' : '#2563eb' ?>"></i>
                </div>
                <div style="min-width:0">
                    <div style="font-size:.75rem;font-weight:700;color:<?= $isLive ? '#16a34a' : '#2563eb' ?>">
                        <?= $isLive ? '&#11044; LIVE NOW' : date('d M, g:i A', $lsTs) ?>
                    </div>
                    <div style="font-size:.72rem;color:var(--mut);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= htmlspecialchars($ls['title']) ?>
                    </div>
                </div>
                <i class="bi bi-arrow-right" style="font-size:.65rem;color:var(--mut);margin-left:auto;flex-shrink:0"></i>
            </a>
            <?php endforeach; ?>
            <a href="<?= SITE_URL ?>/live-sessions.php" style="font-size:.7rem;color:var(--mut);text-decoration:none;display:block;padding-top:4px">
                View all live sessions →
            </a>
        </div>
        <?php endif; ?>

        <!-- LESSONS HEADER -->
        <div style="padding:8px 14px;border-bottom:1px solid var(--brd);display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:700;color:var(--pri);">
            <i class="bi bi-journals"></i> Lessons <span style="font-size:.65rem;padding:1px 6px;border-radius:8px;background:var(--pri-l);color:var(--ok);font-weight:600;"><?= count($allLessons) ?></span>
        </div>

        <!-- LESSONS PANEL -->
        <div class="sb-panel active" id="panelLessons">
        <?php if (empty($allLessons)): ?>
            <div style="padding:30px 16px;text-align:center;color:var(--mut);font-size:.8rem">
                <i class="bi bi-journal-x" style="font-size:1.5rem;display:block;margin-bottom:6px;opacity:.4"></i>
                No lessons yet
            </div>
        <?php else: ?>
            <?php echo renderTree($lessonTree, $classId, $lessonId, $completedIds, 'all'); ?>
        <?php endif; ?>
        </div>
    </div>

    <div class="sb-bg" id="sbBg" onclick="closeSb()"></div>

    <div class="mn">
        <?php if ($currentLesson): ?>
        <?php
        $bc = '';
        if ($currentLesson['subject_id']) {
            $sr = $db->fetchOne("SELECT s.title as t1,p.title as t2,gp.title as t3 FROM lms_subjects s LEFT JOIN lms_subjects p ON s.parent_id=p.id LEFT JOIN lms_subjects gp ON p.parent_id=gp.id WHERE s.id=?",[$currentLesson['subject_id']]);
            if ($sr) { $parts = array_filter([$sr['t3'],$sr['t2'],$sr['t1']]); $bc = implode(' → ', $parts); }
        }
        ?>
        <div class="lh">
            <div style="min-width:0">
                <?php if ($bc): ?><div class="bc"><?= htmlspecialchars($bc) ?></div><?php endif; ?>
                <h4><?= htmlspecialchars($currentLesson['title']) ?></h4>
            </div>
            <div class="lh-btns">
                <?php if ($isHtml || $isPdf): ?>
                <button class="btn-s btn-w" onclick="openFv()"><i class="bi bi-arrows-fullscreen"></i> Full View</button>
                <?php endif; ?>
                <?php if (!in_array($lessonId,$completedIds)): ?>
                <form method="POST" action="<?= SITE_URL ?>/api/complete-lesson.php" style="margin:0">
                    <input type="hidden" name="class_id" value="<?= $classId ?>">
                    <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                    <button class="btn-s btn-g"><i class="bi bi-check-lg"></i> Complete</button>
                </form>
                <?php else: ?>
                <span class="btn-s btn-done"><i class="bi bi-check-circle-fill"></i> Done</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="lc">
            <?php if ($pf): ?>
                <?php if ($isVideo): ?>
<div style="position:relative;width:100%;background:#000;">
    <video id="mainVideo" controls preload="metadata" controlsList="nodownload" oncontextmenu="return false;" 
           style="width:100%;max-height:calc(100vh - 120px);display:block;">
        <source src="<?= $url ?>" type="video/<?= $ext ?>">
    </video>
    <button onclick="document.getElementById('mainVideo').requestFullscreen()" 
            style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:.8rem;display:flex;align-items:center;gap:4px;">
        <i class="bi bi-fullscreen"></i> Fullscreen
    </button>
</div>
                <?php elseif ($isPdf): ?><iframe src="<?= $url ?>#toolbar=1&view=FitH" id="cf"></iframe>
                <?php elseif ($isHtml): ?><iframe src="<?= $url ?>" id="cf" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
                <?php elseif ($isImage): ?><img src="<?= $url ?>" class="li-img">
                <?php else: ?>
                    <div class="lc-dl"><i class="bi bi-file-earmark"></i><h5 style="margin:8px 0 4px;color:var(--txt)"><?= htmlspecialchars($pf['original_name']) ?></h5><a href="<?= $url ?>" target="_blank" class="btn-s btn-p" style="margin-top:6px"><i class="bi bi-download"></i> Download</a></div>
                <?php endif; ?>
            <?php elseif ($currentLesson['content_html']): ?>
                <div style="padding:20px;background:var(--card)"><?= $currentLesson['content_html'] ?></div>
            <?php else: ?><div class="empty"><i class="bi bi-journal-text"></i><h5>No content yet</h5></div><?php endif; ?>
        </div>
        <?php if (count($files) > 1): ?>
        <div class="att">
            <?php for ($i=1;$i<count($files);$i++): $f=$files[$i]; $fu=SITE_URL.'/'.$f['filepath']; $fe=strtolower($f['filetype']??pathinfo($f['filepath'],PATHINFO_EXTENSION)); ?>
            <?php if (in_array($fe,['html','htm','pdf'])): ?>
            <a class="att-c" onclick="openFile('<?= $fu ?>','<?= htmlspecialchars(addslashes($f['original_name'])) ?>')"><i class="bi bi-<?= $fe==='pdf'?'file-pdf':'filetype-html' ?>"></i> <?= htmlspecialchars($f['original_name']) ?></a>
            <?php else: ?>
            <a href="<?= $fu ?>" target="_blank" class="att-c"><i class="bi bi-download"></i> <?= htmlspecialchars($f['original_name']) ?></a>
            <?php endif; endfor; ?>
        </div>
        <?php endif; ?>
        <div class="bn">
            <?php if ($prevId): ?><a href="?id=<?= $classId ?>&lesson=<?= $prevId ?>" class="btn-s btn-o"><i class="bi bi-chevron-left"></i> Prev</a><?php else: ?><span></span><?php endif; ?>
            <span class="cnt"><?= ($currentIdx!==false?$currentIdx+1:0) ?> / <?= $totalLessons ?></span>
            <?php if ($nextId): ?><a href="?id=<?= $classId ?>&lesson=<?= $nextId ?>" class="btn-s btn-p">Next <i class="bi bi-chevron-right"></i></a>
            <?php else: ?><span class="btn-s btn-done"><i class="bi bi-trophy"></i> Complete!</span><?php endif; ?>
        </div>
        <?php else: ?><div class="empty"><i class="bi bi-journal-x"></i><h5>No lessons yet</h5><p style="font-size:.85rem">Content is being prepared.</p></div><?php endif; ?>
    </div>
</div>

<!-- FULL VIEW -->
<div class="fv" id="fvO">
    <div class="fv-bar">
        <?php if ($prevId): ?><a href="?id=<?= $classId ?>&lesson=<?= $prevId ?>" class="fv-btn fv-n"><i class="bi bi-chevron-left"></i></a><?php else: ?><span class="fv-btn fv-n dis"><i class="bi bi-chevron-left"></i></span><?php endif; ?>
        <h5 id="fvT"><?= $currentLesson?htmlspecialchars($currentLesson['title']):'' ?></h5>
        <?php if ($nextId): ?><a href="?id=<?= $classId ?>&lesson=<?= $nextId ?>" class="fv-btn fv-n"><i class="bi bi-chevron-right"></i></a><?php else: ?><span class="fv-btn fv-n dis"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
        <button class="fv-btn fv-x" onclick="closeFv()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="fv-b" id="fvB"></div>
</div>
<button class="mob-btn" onclick="openSb()"><i class="bi bi-list"></i></button>

<script>
function tog(el){el.classList.toggle('fold');const c=el.nextElementSibling;if(c&&c.classList.contains('coll')){if(c.classList.contains('fold')){c.style.maxHeight=c.scrollHeight+'px';c.classList.remove('fold')}else{c.style.maxHeight='0';c.classList.add('fold')}}}
function openSb(){document.getElementById('sidebar').classList.add('open');document.getElementById('sbBg').classList.add('show')}
function closeSb(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sbBg').classList.remove('show')}
function openFv(){const f=document.getElementById('cf');if(!f)return;document.getElementById('fvB').innerHTML='<iframe src="'+f.src+'"></iframe>';document.getElementById('fvO').classList.add('open');document.body.style.overflow='hidden';var m=document.querySelector('.mob-btn');if(m)m.style.display='none'}
function openFile(u,t){document.getElementById('fvT').textContent=t;document.getElementById('fvB').innerHTML='<iframe src="'+u+'" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>';document.getElementById('fvO').classList.add('open');document.body.style.overflow='hidden'}
function closeFv(){document.getElementById('fvO').classList.remove('open');document.body.style.overflow='';document.getElementById('fvT').textContent=<?=json_encode($currentLesson?$currentLesson['title']:'')?>;var m=document.querySelector('.mob-btn');if(m)m.style.display=''}
document.addEventListener('DOMContentLoaded',function(){const a=document.querySelector('.li.ac,.li.int-ac');if(a)a.scrollIntoView({block:'center',behavior:'smooth'})});
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&document.getElementById('fvO').classList.contains('open'))closeFv()});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>

<?php
// Helper: render a subject tree as sidebar HTML
function renderTree($tree, $classId, $lessonId, $completedIds, $mode) {
    $acClass = 'ac';
    $html = '';
    foreach ($tree as $subjectName => $subjectData) {
        $sjLessons = []; $sjDone = 0;
        foreach ($subjectData['strands'] as $sd) { foreach ($sd['lms_lessons'] as $sl) { $sjLessons[] = $sl; if (in_array($sl['id'], $completedIds)) $sjDone++; } }
        $sjTotal = count($sjLessons); $sjAllDone = ($sjDone===$sjTotal && $sjTotal>0);
        $sjHasActive = false; foreach ($sjLessons as $sl) { if ($sl['id']==$lessonId) $sjHasActive = true; }
        $fold = !$sjHasActive;
        
        $html .= '<div class="sj-h '.($fold?'fold':'').'" onclick="tog(this)"><i class="bi bi-chevron-down ar"></i>'.htmlspecialchars($subjectName).'<span class="ct '.($sjAllDone?'dn':'').'">'.$sjDone.'/'.$sjTotal.'</span></div>';
        $html .= '<div class="coll '.($fold?'fold':'').'" '.($fold?'style="max-height:0"':'').'>';
        
        foreach ($subjectData['strands'] as $strandName => $strandData) {
            if ($strandName === '_direct') {
                foreach ($strandData['lms_lessons'] as $sl) {
                    $d = in_array($sl['id'], $completedIds);
                    $html .= '<a href="?id='.$classId.'&lesson='.$sl['id'].'" class="li '.($sl['id']==$lessonId?$acClass:'').'"><i class="bi bi-'.($d?'check-circle-fill ok':'circle').' ck"></i><span class="tt">'.htmlspecialchars($sl['title']).'</span></a>';
                }
            } else {
                $stHasActive = false;
                foreach ($strandData['lms_lessons'] as $sl) { if ($sl['id']==$lessonId) $stHasActive = true; }
                $stFold = !$stHasActive;
                $html .= '<div class="st-h '.($stFold?'fold':'').'" onclick="tog(this)"><i class="bi bi-chevron-down ar"></i>'.htmlspecialchars($strandName).'</div>';
                $html .= '<div class="coll '.($stFold?'fold':'').'" '.($stFold?'style="max-height:0"':'').'>';
                foreach ($strandData['lms_lessons'] as $sl) {
                    $d = in_array($sl['id'], $completedIds);
                    $html .= '<a href="?id='.$classId.'&lesson='.$sl['id'].'" class="li '.($sl['id']==$lessonId?$acClass:'').'"><i class="bi bi-'.($d?'check-circle-fill ok':'circle').' ck"></i><span class="tt">'.htmlspecialchars($sl['title']).'</span></a>';
                }
                $html .= '</div>';
            }
        }
        $html .= '</div>';
    }
    return $html;
}
?>
