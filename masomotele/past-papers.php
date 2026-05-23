<?php
ob_start();
/**
 * M.T.T.I LMS - KCSE Past Papers (Student View)
 * Browse by subject or year with clean card-based UI
 * Place in: /var/www/html/mtti-lms/past-papers.php
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();

// ── Download handler — must be before any output ──
if (isset($_GET['download'])) {
    $pid = intval($_GET['download']);
    $paper = $db->fetchOne("SELECT * FROM past_papers WHERE id=? AND status='active'", [$pid]);
    if ($paper) {
        $db->query("UPDATE past_papers SET download_count = download_count + 1 WHERE id=?", [$pid]);
        if ($paper['file_path'] && file_exists(__DIR__ . '/' . $paper['file_path'])) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($paper['file_path']) . '"');
            header('Content-Length: ' . filesize(__DIR__ . '/' . $paper['file_path']));
            ob_end_clean();
            readfile(__DIR__ . '/' . $paper['file_path']);
            exit;
        } elseif (!empty($paper['external_url'])) {
            header('Location: ' . $paper['external_url']);
            exit;
        }
    }
    http_response_code(404);
    exit('Paper not found');
}
$userId = $auth->getUserId();
$pageTitle = 'KCSE Past Papers - ' . SITE_NAME;

// Filters
$filterSubject = $_GET['subject'] ?? '';
$filterYear = intval($_GET['year'] ?? 0);
$view = $_GET['view'] ?? 'subjects'; // 'subjects' or 'years'

// download handler moved to top

// Fetch data
$kcseSubjects = $db->fetchAll("SELECT ks.*, COUNT(pp.id) as paper_count FROM kcse_subjects ks LEFT JOIN past_papers pp ON pp.subject=ks.name AND pp.status='active' GROUP BY ks.id ORDER BY ks.sort_order");
$availableYears = $db->fetchAll("SELECT DISTINCT year FROM past_papers WHERE status='active' ORDER BY year DESC");
$totalPapers = $db->fetchOne("SELECT COUNT(*) as c FROM past_papers WHERE status='active'");

// Fetch papers based on filter
$where = "WHERE status='active'";
$params = [];
if ($filterSubject) { $where .= " AND subject=?"; $params[] = $filterSubject; }
if ($filterYear) { $where .= " AND year=?"; $params[] = $filterYear; }
$papers = $db->fetchAll("SELECT * FROM past_papers $where ORDER BY year DESC, paper_number, paper_type", $params);

// Group papers
$grouped = [];
foreach ($papers as $p) {
    $key = $view === 'years' ? $p['year'] . '|||' . $p['subject'] : $p['subject'] . '|||' . $p['year'];
    $grouped[$key][] = $p;
}

$catLabels = ['compulsory'=>'Compulsory','sciences'=>'Sciences','humanities'=>'Humanities','technical'=>'Technical','languages'=>'Languages'];

require_once __DIR__ . '/templates/header.php';
?>
<style>
:root{--pp-blue:#1e3a5f;--pp-accent:#f59e0b;--pp-green:#16a34a;--pp-red:#dc2626}
body{background:#f5f6f8;font-family:'DM Sans',sans-serif}
.pp-hero{background:linear-gradient(135deg,#1e3a5f,#2d6a9f);color:#fff;padding:2rem;border-radius:0 0 16px 16px;margin:-1rem -1rem 1.5rem}
.pp-hero h3{font-weight:800;margin:0}.pp-hero p{opacity:.8;margin:4px 0 0;font-size:.9rem}
.pp-stats{display:flex;gap:12px;margin-top:12px}
.pp-stat{background:rgba(255,255,255,.12);padding:6px 16px;border-radius:20px;font-size:.82rem}
.pp-stat strong{color:var(--pp-accent)}
.view-tabs{display:flex;gap:4px;background:#e2e8f0;border-radius:10px;padding:3px;width:fit-content}
.view-tab{padding:6px 16px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;color:#64748b;transition:all .15s}
.view-tab.active{background:#fff;color:var(--pp-blue);box-shadow:0 1px 3px rgba(0,0,0,.1)}
.view-tab:hover{color:var(--pp-blue)}
.filter-bar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:20px}
.subject-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:20px}
.subj-card{background:#fff;border-radius:10px;padding:14px;text-decoration:none;color:var(--pp-blue);transition:all .2s;border:2px solid transparent;text-align:center}
.subj-card:hover{border-color:#2563eb;transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.subj-card.active{border-color:#2563eb;background:#eff6ff}
.subj-card .name{font-weight:700;font-size:.88rem}.subj-card .count{font-size:.72rem;color:#94a3b8;margin-top:2px}
.subj-card .count strong{color:var(--pp-green)}
.year-pills{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
.year-pill{padding:6px 16px;border-radius:20px;font-size:.82rem;font-weight:600;text-decoration:none;background:#fff;color:#475569;border:1px solid #e2e8f0;transition:all .15s}
.year-pill:hover{border-color:#2563eb;color:#2563eb}.year-pill.active{background:#2563eb;color:#fff;border-color:#2563eb}
.paper-group{margin-bottom:20px}
.paper-group-header{font-weight:700;font-size:1rem;color:var(--pp-blue);margin-bottom:8px;display:flex;align-items:center;gap:8px}
.paper-group-header .yr{background:#dbeafe;color:#1d4ed8;padding:2px 10px;border-radius:8px;font-size:.78rem}
.paper-item{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#fff;border-radius:10px;margin-bottom:6px;transition:all .15s;border:1px solid #f1f5f9}
.paper-item:hover{border-color:#cbd5e1;box-shadow:0 2px 6px rgba(0,0,0,.04)}
.paper-info{display:flex;align-items:center;gap:12px}
.paper-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.paper-icon.qp{background:#dbeafe;color:#1d4ed8}.paper-icon.ms{background:#dcfce7;color:#16a34a}.paper-icon.both{background:#fef3c7;color:#92400e}
.paper-icon.link{background:#e8eaf6;color:#283593}
.paper-title{font-weight:600;font-size:.88rem;color:#1e293b}
.paper-meta{font-size:.72rem;color:#94a3b8;margin-top:2px}
.paper-meta span{margin-right:8px}
.paper-actions{display:flex;gap:6px}
.btn-view{padding:6px 14px;border-radius:8px;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .15s;display:inline-flex;align-items:center;gap:4px}
.btn-view.pdf{background:#fce4ec;color:#c62828}.btn-view.pdf:hover{background:#f8bbd0}
.btn-view.html{background:#fff3e0;color:#e65100}.btn-view.html:hover{background:#ffe0b2}
.btn-view.link{background:#e8eaf6;color:#283593}.btn-view.link:hover{background:#c5cae9}
.empty-pp{text-align:center;padding:40px;color:#94a3b8}.empty-pp i{font-size:3rem}
@media(max-width:768px){.subject-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}.paper-item{flex-direction:column;align-items:flex-start;gap:10px}.paper-actions{align-self:flex-end}}
</style>

<div class="container-fluid">
    <div class="pp-hero">
        <a href="<?= SITE_URL ?>/dashboard.php" class="text-white text-decoration-none" style="font-size:.8rem"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
        <h3 class="mt-2"><i class="bi bi-journal-check me-2"></i>KCSE Past Papers</h3>
        <p>Browse and download past examination papers for revision</p>
        <div class="pp-stats">
            <span class="pp-stat"><strong><?= $totalPapers['c'] ?? 0 ?></strong> papers</span>
            <span class="pp-stat"><strong><?= count($availableYears) ?></strong> years</span>
            <span class="pp-stat"><strong><?= count(array_filter($kcseSubjects, fn($s) => $s['paper_count'] > 0)) ?></strong> subjects</span>
        </div>
    </div>

    <!-- View toggle + filters -->
    <div class="filter-bar">
        <div class="view-tabs">
            <a href="?view=subjects<?= $filterSubject ? '&subject='.urlencode($filterSubject) : '' ?><?= $filterYear ? '&year='.$filterYear : '' ?>" class="view-tab <?= $view === 'subjects' ? 'active' : '' ?>"><i class="bi bi-bookmark me-1"></i>By Subject</a>
            <a href="?view=years<?= $filterSubject ? '&subject='.urlencode($filterSubject) : '' ?><?= $filterYear ? '&year='.$filterYear : '' ?>" class="view-tab <?= $view === 'years' ? 'active' : '' ?>"><i class="bi bi-calendar me-1"></i>By Year</a>
        </div>
        <?php if ($filterSubject || $filterYear): ?>
        <a href="?view=<?= $view ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear Filters</a>
        <?php endif; ?>
    </div>

    <?php if ($view === 'subjects'): ?>
    <!-- Subject cards -->
    <?php if (!$filterSubject): ?>
    <?php foreach ($catLabels as $cat => $label):
        $catSubs = array_filter($kcseSubjects, fn($s) => $s['category'] === $cat);
        if (empty($catSubs)) continue;
    ?>
    <h6 style="font-weight:700;color:#64748b;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;margin:16px 0 8px"><?= $label ?></h6>
    <div class="subject-grid">
        <?php foreach ($catSubs as $s): ?>
        <a href="?view=subjects&subject=<?= urlencode($s['name']) ?>" class="subj-card <?= $filterSubject === $s['name'] ? 'active' : '' ?>">
            <div class="name"><?= htmlspecialchars($s['name']) ?></div>
            <div class="count"><strong><?= $s['paper_count'] ?></strong> papers</div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($filterSubject): ?>
    <h5 style="font-weight:700;margin-bottom:4px"><i class="bi bi-bookmark-fill me-2 text-primary"></i><?= htmlspecialchars($filterSubject) ?></h5>
    <div class="year-pills">
        <a href="?view=subjects&subject=<?= urlencode($filterSubject) ?>" class="year-pill <?= !$filterYear ? 'active' : '' ?>">All Years</a>
        <?php foreach ($availableYears as $y):
            $yc = $db->fetchOne("SELECT COUNT(*) as c FROM past_papers WHERE subject=? AND year=? AND status='active'", [$filterSubject, $y['year']]);
            if ($yc['c'] == 0) continue;
        ?>
        <a href="?view=subjects&subject=<?= urlencode($filterSubject) ?>&year=<?= $y['year'] ?>" class="year-pill <?= $filterYear == $y['year'] ? 'active' : '' ?>"><?= $y['year'] ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($view === 'years'): ?>
    <!-- Year pills -->
    <div class="year-pills">
        <a href="?view=years" class="year-pill <?= !$filterYear ? 'active' : '' ?>">All</a>
        <?php foreach ($availableYears as $y): ?>
        <a href="?view=years&year=<?= $y['year'] ?><?= $filterSubject ? '&subject='.urlencode($filterSubject) : '' ?>" class="year-pill <?= $filterYear == $y['year'] ? 'active' : '' ?>"><?= $y['year'] ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Papers list -->
    <?php if (empty($papers) && ($filterSubject || $filterYear)): ?>
    <div class="empty-pp"><i class="bi bi-journal-x"></i><h5 class="mt-2">No papers found</h5><p>Try a different subject or year, or ask your teacher to upload papers.</p></div>
    <?php elseif (!empty($papers)): ?>
    <?php
    $lastGroup = '';
    foreach ($papers as $p):
        $groupKey = $view === 'years' ? $p['year'] : $p['subject'];
        $subKey = $view === 'years' ? $p['subject'] : $p['year'];
        $fullKey = $groupKey . '|' . $subKey;

        if ($groupKey !== $lastGroup) {
            $lastGroup = $groupKey;
            if ($view === 'years') {
                echo '<div class="paper-group"><div class="paper-group-header"><i class="bi bi-calendar-event"></i>' . $p['year'] . '</div>';
            } else {
                echo '<div class="paper-group"><div class="paper-group-header"><i class="bi bi-bookmark-fill text-primary"></i>' . htmlspecialchars($p['subject']) . '</div>';
            }
        }
        // Sub-header for year when browsing by subject (or subject when by year)
        static $lastSub = '';
        if ($fullKey !== $lastSub) {
            $lastSub = $fullKey;
            if ($view === 'subjects' && !$filterYear) {
                echo '<div style="font-size:.78rem;font-weight:600;color:#64748b;margin:8px 0 4px 4px">' . $p['year'] . '</div>';
            } elseif ($view === 'years') {
                echo '<div style="font-size:.78rem;font-weight:600;color:#64748b;margin:8px 0 4px 4px">' . htmlspecialchars($p['subject']) . '</div>';
            }
        }

        $iconClass = $p['paper_type'] === 'marking_scheme' ? 'ms' : ($p['paper_type'] === 'both' ? 'both' : 'qp');
        $iconEmoji = $p['paper_type'] === 'marking_scheme' ? '✓' : ($p['paper_type'] === 'both' ? '📋' : '📝');
        $typeLabel = $p['paper_type'] === 'marking_scheme' ? 'Marking Scheme' : ($p['paper_type'] === 'both' ? 'Q/P + M/S' : 'Paper ' . $p['paper_number']);
        $btnClass = $p['format'] === 'html' ? 'html' : ($p['format'] === 'link' ? 'link' : 'pdf');
        $btnIcon = $p['format'] === 'html' ? 'code-slash' : ($p['format'] === 'link' ? 'link-45deg' : 'file-pdf');
        $btnLabel = $p['format'] === 'html' ? 'Open' : ($p['format'] === 'link' ? 'Open Link' : 'View PDF');
        $paperUrl = SITE_URL . '/past-papers.php?download=' . $p['id'];
    ?>
    <div class="paper-item">
        <div class="paper-info">
            <div class="paper-icon <?= $iconClass ?>"><?= $iconEmoji ?></div>
            <div>
                <div class="paper-title"><?= htmlspecialchars($p['title']) ?></div>
                <div class="paper-meta">
                    <span><i class="bi bi-tag me-1"></i><?= $typeLabel ?></span>
                    <span><i class="bi bi-file-earmark me-1"></i><?= strtoupper($p['format']) ?></span>
                    <?php if ($p['file_size']): ?><span><?= round($p['file_size']/(1024*1024), 1) ?>MB</span><?php endif; ?>
                    <?php if ($p['download_count']): ?><span><i class="bi bi-download me-1"></i><?= $p['download_count'] ?></span><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="paper-actions">
            <a href="<?= $paperUrl ?>" target="_blank" class="btn-view <?= $btnClass ?>"><i class="bi bi-<?= $btnIcon ?> me-1"></i><?= $btnLabel ?></a>
        </div>
    </div>
    <?php endforeach; ?>
    </div><!-- close last group -->
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
