<?php
/**
 * M.T.T.I LMS - Interactive Library (Student View)
 * Browse & launch HTML interactives by subject
 * Place in: /var/www/html/mtti-lms/interactives.php
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$pageTitle = 'Interactive Library - ' . SITE_NAME;

$filterSubject = $_GET['subject'] ?? '';
$filterType = $_GET['type'] ?? '';

// Track play
if (isset($_GET['play'])) {
    $iid = intval($_GET['play']);
    $db->query("UPDATE interactives SET play_count = play_count + 1 WHERE id=?", [$iid]);
    $item = $db->fetchOne("SELECT file_path FROM interactives WHERE id=? AND status='active'", [$iid]);
    if ($item) { header('Location: ' . SITE_URL . '/' . $item['file_path']); exit; }
}

$where = "WHERE status='active'"; $params = [];
if ($filterSubject) { $where .= " AND subject=?"; $params[] = $filterSubject; }
if ($filterType) { $where .= " AND type=?"; $params[] = $filterType; }
$items = $db->fetchAll("SELECT * FROM interactives $where ORDER BY subject, title", $params);
$subjectCounts = $db->fetchAll("SELECT subject, COUNT(*) as cnt FROM interactives WHERE status='active' GROUP BY subject ORDER BY subject");
$typeCounts = $db->fetchAll("SELECT type, COUNT(*) as cnt FROM interactives WHERE status='active' GROUP BY type ORDER BY type");
$total = $db->fetchOne("SELECT COUNT(*) as c FROM interactives WHERE status='active'");

require_once __DIR__ . '/templates/header.php';
?>
<style>
:root{--bg:#f5f6f8;--pur:#4a1d8e;--pur2:#7c3aed;--txt:#1e293b;--mut:#94a3b8;--brd:#e2e8f0}
body{background:var(--bg);font-family:'DM Sans',sans-serif}
.lib-hero{background:linear-gradient(135deg,var(--pur),var(--pur2));color:#fff;padding:2rem;border-radius:0 0 16px 16px;margin:-1rem -1rem 1.5rem}
.lib-hero h3{font-weight:800;margin:0}.lib-hero p{opacity:.8;font-size:.9rem;margin:4px 0 0}
.lib-stats{display:flex;gap:12px;margin-top:12px}
.lib-stat{background:rgba(255,255,255,.12);padding:6px 16px;border-radius:20px;font-size:.82rem}
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;align-items:center}
.filter-pill{padding:6px 16px;border-radius:20px;font-size:.82rem;font-weight:600;text-decoration:none;background:#fff;color:#475569;border:1px solid var(--brd);transition:all .15s}
.filter-pill:hover{border-color:var(--pur);color:var(--pur)}
.filter-pill.active{background:var(--pur);color:#fff;border-color:var(--pur)}
.type-pills{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.tp{padding:4px 12px;border-radius:14px;font-size:.75rem;font-weight:600;text-decoration:none;border:1px solid var(--brd);transition:all .15s}
.tp:hover{border-color:var(--pur)}.tp.active{background:var(--pur);color:#fff;border-color:var(--pur)}
.tp-lesson{background:#eff6ff;color:#1d4ed8}.tp-quiz{background:#f0fdf4;color:#16a34a}
.tp-simulation{background:#fffbeb;color:#92400e}.tp-game{background:#fef2f2;color:#dc2626}.tp-exercise{background:#f5f3ff;color:#6d28d9}

.int-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px}
.int-card{background:#fff;border-radius:14px;border:1px solid var(--brd);overflow:hidden;transition:all .2s;cursor:pointer;text-decoration:none;color:var(--txt)}
.int-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.08);border-color:var(--pur)}
.int-top{height:100px;display:flex;align-items:center;justify-content:center;position:relative}
.int-top.lesson{background:linear-gradient(135deg,#dbeafe,#eff6ff)}.int-top.quiz{background:linear-gradient(135deg,#dcfce7,#f0fdf4)}
.int-top.simulation{background:linear-gradient(135deg,#fef3c7,#fffbeb)}.int-top.game{background:linear-gradient(135deg,#fce4ec,#fef2f2)}
.int-top.exercise{background:linear-gradient(135deg,#ede9fe,#f5f3ff)}
.int-top .play-icon{width:50px;height:50px;border-radius:50%;background:rgba(255,255,255,.8);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--pur);transition:all .2s}
.int-card:hover .play-icon{transform:scale(1.15);background:#fff;box-shadow:0 4px 12px rgba(0,0,0,.1)}
.int-top .type-tag{position:absolute;top:8px;right:8px;font-size:.65rem;padding:2px 8px;border-radius:8px;font-weight:700;background:rgba(255,255,255,.85)}
.int-body{padding:12px 14px}
.int-title{font-weight:700;font-size:.9rem;margin-bottom:4px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.int-meta{font-size:.72rem;color:var(--mut);display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.int-meta .subj{background:#f1f5f9;padding:1px 8px;border-radius:8px;font-weight:600;color:#475569}
.diff-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.diff-beginner{background:#22c55e}.diff-intermediate{background:#f59e0b}.diff-advanced{background:#ef4444}

.empty-lib{text-align:center;padding:50px;color:var(--mut)}.empty-lib i{font-size:3rem}
.subj-header{font-weight:700;color:var(--pur);font-size:1rem;margin:20px 0 10px;display:flex;align-items:center;gap:8px}
@media(max-width:768px){.int-grid{grid-template-columns:repeat(auto-fill,minmax(200px,1fr))}}
</style>

<div class="container-fluid">
    <div class="lib-hero">
        <a href="<?= SITE_URL ?>/dashboard.php" class="text-white text-decoration-none" style="font-size:.8rem"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
        <h3 class="mt-2"><i class="bi bi-play-circle-fill me-2"></i>Interactive Library</h3>
        <p>Learn through interactive HTML lessons, quizzes, simulations & games</p>
        <div class="lib-stats">
            <span class="lib-stat"><strong><?= $total['c'] ?? 0 ?></strong> interactives</span>
            <span class="lib-stat"><strong><?= count($subjectCounts) ?></strong> subjects</span>
        </div>
    </div>

    <!-- Subject filter -->
    <div class="filter-bar">
        <a href="?<?= $filterType ? 'type='.urlencode($filterType) : '' ?>" class="filter-pill <?= !$filterSubject ? 'active' : '' ?>">All Subjects</a>
        <?php foreach ($subjectCounts as $sc): ?>
        <a href="?subject=<?= urlencode($sc['subject']) ?><?= $filterType ? '&type='.urlencode($filterType) : '' ?>" class="filter-pill <?= $filterSubject===$sc['subject']?'active':'' ?>"><?= htmlspecialchars($sc['subject']) ?> <span style="opacity:.6"><?= $sc['cnt'] ?></span></a>
        <?php endforeach; ?>
    </div>

    <!-- Type filter -->
    <?php if (!empty($typeCounts)): ?>
    <div class="type-pills">
        <a href="?<?= $filterSubject ? 'subject='.urlencode($filterSubject) : '' ?>" class="tp <?= !$filterType ? 'active' : '' ?>">All Types</a>
        <?php foreach ($typeCounts as $tc): ?>
        <a href="?type=<?= $tc['type'] ?><?= $filterSubject ? '&subject='.urlencode($filterSubject) : '' ?>" class="tp tp-<?= $tc['type'] ?> <?= $filterType===$tc['type']?'active':'' ?>"><?= ucfirst($tc['type']) ?> (<?= $tc['cnt'] ?>)</a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
    <div class="empty-lib"><i class="bi bi-collection-play"></i><h5 class="mt-2">No interactives found</h5><p>Try a different subject or check back later.</p></div>
    <?php else: ?>
    <?php
    $lastSubj = '';
    foreach ($items as $it):
        if ($it['subject'] !== $lastSubj) {
            if ($lastSubj) echo '</div>'; // close previous grid
            $lastSubj = $it['subject'];
            echo '<div class="subj-header"><i class="bi bi-bookmark-fill"></i>' . htmlspecialchars($it['subject']) . '</div>';
            echo '<div class="int-grid">';
        }
    ?>
    <a href="?play=<?= $it['id'] ?>" target="_blank" class="int-card">
        <div class="int-top <?= $it['type'] ?>">
            <div class="play-icon"><i class="bi bi-play-fill"></i></div>
            <span class="type-tag"><?= ucfirst($it['type']) ?></span>
        </div>
        <div class="int-body">
            <div class="int-title"><?= htmlspecialchars($it['title']) ?></div>
            <div class="int-meta">
                <span class="subj"><?= htmlspecialchars($it['subject']) ?></span>
                <?php if ($it['grade_level']): ?><span><?= htmlspecialchars($it['grade_level']) ?></span><?php endif; ?>
                <?php if ($it['difficulty']): ?><span><span class="diff-dot diff-<?= $it['difficulty'] ?>"></span> <?= ucfirst($it['difficulty']) ?></span><?php endif; ?>
                <span><i class="bi bi-play-circle"></i> <?= $it['play_count'] ?></span>
            </div>
            <?php if ($it['description']): ?><div style="font-size:.78rem;color:var(--mut);margin-top:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars($it['description']) ?></div><?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
    </div><!-- close last grid -->
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
