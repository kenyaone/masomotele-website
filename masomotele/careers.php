<?php
/**
 * M.T.T.I LMS — Career Connect
 * Students browse internships, jobs, scholarships posted by corporate sponsors
 * Place in: /var/www/html/mtti-lms/careers.php
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();

$filter = $_GET['type'] ?? '';
$county = $_GET['county'] ?? '';
$search = trim($_GET['q'] ?? '');

// Handle application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_posting'])) {
    $postingId = intval($_POST['posting_id']);
    $letter = trim($_POST['cover_letter'] ?? '');
    $exists = $db->fetchOne("SELECT id FROM career_applications WHERE posting_id=? AND student_id=?", [$postingId, $userId]);
    if (!$exists) {
        $db->insert('career_applications', [
            'posting_id' => $postingId, 'student_id' => $userId,
            'cover_letter' => $letter, 'applied_at' => date('Y-m-d H:i:s')
        ]);
        $db->query("UPDATE career_postings SET applications=applications+1 WHERE id=?", [$postingId]);
    }
    header('Location: ' . SITE_URL . '/careers.php?applied=1');
    exit;
}

// Fetch postings
$where = "WHERE cp.status='active' AND (cp.deadline IS NULL OR cp.deadline >= CURDATE())";
$params = [];
if ($filter) { $where .= " AND cp.type=?"; $params[] = $filter; }
if ($county) { $where .= " AND cp.county=?"; $params[] = $county; }
if ($search) { $where .= " AND (cp.title LIKE ? OR cp.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$postings = $db->fetchAll(
    "SELECT cp.*, s.name as sponsor_name, s.logo_path, s.package,
     (SELECT id FROM career_applications WHERE posting_id=cp.id AND student_id=?) as applied
     FROM career_postings cp
     LEFT JOIN sponsors s ON s.id = cp.sponsor_id
     $where ORDER BY cp.created_at DESC",
    array_merge([$userId], $params)
) ?? [];

// Track views
foreach ($postings as $p) {
    $db->query("UPDATE career_postings SET views=views+1 WHERE id=?", [$p['id']]);
}

require_once __DIR__ . '/templates/header.php';
?>
<style>
.cc-hero{background:linear-gradient(135deg,#0f2644,#1e3a5f);color:#fff;padding:28px 20px;border-radius:0 0 16px 16px;margin:-16px -16px 20px}
.cc-hero h2{font-size:1.4rem;font-weight:800;margin-bottom:6px}
.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;align-items:center}
.filter-btn{padding:6px 14px;border-radius:20px;border:1.5px solid #ddd;background:#fff;font-size:.82rem;font-weight:600;cursor:pointer;text-decoration:none;color:#333;transition:all .15s}
.filter-btn.active,.filter-btn:hover{background:#1e3a5f;color:#fff;border-color:#1e3a5f}
.posting-card{background:#fff;border-radius:14px;padding:18px;margin-bottom:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #f1f5f9;transition:transform .2s,box-shadow .2s}
.posting-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.1)}
.posting-header{display:flex;align-items:flex-start;gap:14px}
.sponsor-logo{width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;color:#fff;flex-shrink:0}
.posting-title{font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:4px}
.posting-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.meta-tag{padding:3px 10px;border-radius:8px;font-size:.72rem;font-weight:600}
.tag-type-internship{background:#dbeafe;color:#1d4ed8}
.tag-type-job{background:#dcfce7;color:#166534}
.tag-type-scholarship{background:#fef3c7;color:#92400e}
.tag-type-training{background:#fce7f3;color:#9d174d}
.tag-type-attachment{background:#e8eaf6;color:#283593}
.posting-desc{font-size:.85rem;color:#64748b;margin-top:10px;line-height:1.6}
.posting-footer{display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9}
.deadline{font-size:.75rem;color:#94a3b8}
.deadline.urgent{color:#dc2626;font-weight:700}
.btn-apply{background:#1e3a5f;color:#fff;border:none;padding:8px 20px;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}
.btn-apply:hover{background:#163256}
.btn-applied{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:8px 20px;border-radius:8px;font-size:.85rem;font-weight:700}
.empty-state{text-align:center;padding:40px;color:#94a3b8}
.search-bar{display:flex;gap:8px;margin-bottom:16px}
.search-bar input{flex:1;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:.9rem}
.search-bar button{background:#1e3a5f;color:#fff;border:none;padding:10px 18px;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal{background:#fff;border-radius:16px;padding:24px;max-width:500px;width:calc(100% - 32px);max-height:80vh;overflow-y:auto}
</style>

<div class="cc-hero">
    <div style="font-size:.8rem;opacity:.7;margin-bottom:6px"><a href="<?= SITE_URL ?>/dashboard.php" style="color:#fff;text-decoration:none">← Dashboard</a></div>
    <h2>💼 Career Connect</h2>
    <p style="opacity:.8;font-size:.9rem">Internships, jobs, scholarships and training opportunities from M.T.T.I partners</p>
</div>

<?php if (isset($_GET['applied'])): ?>
<div style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-weight:600">✅ Application submitted successfully! The employer will review and contact you.</div>
<?php endif; ?>

<!-- Search -->
<form method="GET">
<div class="search-bar">
    <input type="text" name="q" placeholder="Search opportunities..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</div>
</form>

<!-- Filters -->
<div class="filters">
    <a href="?<?= $county ? 'county='.urlencode($county) : '' ?>" class="filter-btn <?= !$filter ? 'active' : '' ?>">All</a>
    <a href="?type=internship<?= $county ? '&county='.urlencode($county) : '' ?>" class="filter-btn <?= $filter==='internship' ? 'active' : '' ?>">Internships</a>
    <a href="?type=attachment<?= $county ? '&county='.urlencode($county) : '' ?>" class="filter-btn <?= $filter==='attachment' ? 'active' : '' ?>">Attachments</a>
    <a href="?type=job<?= $county ? '&county='.urlencode($county) : '' ?>" class="filter-btn <?= $filter==='job' ? 'active' : '' ?>">Jobs</a>
    <a href="?type=scholarship<?= $county ? '&county='.urlencode($county) : '' ?>" class="filter-btn <?= $filter==='scholarship' ? 'active' : '' ?>">Scholarships</a>
    <a href="?type=training<?= $county ? '&county='.urlencode($county) : '' ?>" class="filter-btn <?= $filter==='training' ? 'active' : '' ?>">Training</a>
</div>

<?php if (empty($postings)): ?>
<div class="empty-state">
    <div style="font-size:3rem">💼</div>
    <h3 style="margin:12px 0 6px">No opportunities yet</h3>
    <p>Check back soon — M.T.T.I is partnering with leading organisations to bring opportunities to students.</p>
</div>
<?php else: ?>

<?php foreach ($postings as $p):
    $pkgColors = ['platinum'=>'#1a237e','gold'=>'#e65100','silver'=>'#37474f','bronze'=>'#4e342e'];
    $bgColor = $pkgColors[$p['package'] ?? 'bronze'] ?? '#1e3a5f';
    $initials = strtoupper(substr($p['sponsor_name'] ?? 'MT', 0, 2));
    $daysLeft = $p['deadline'] ? ceil((strtotime($p['deadline']) - time()) / 86400) : null;
    $typeClass = 'tag-type-' . ($p['type'] ?? 'internship');
?>
<div class="posting-card">
    <div class="posting-header">
        <div class="sponsor-logo" style="background:<?= $bgColor ?>"><?= $initials ?></div>
        <div style="flex:1">
            <div class="posting-title"><?= htmlspecialchars($p['title']) ?></div>
            <div style="font-size:.8rem;color:#64748b"><?= htmlspecialchars($p['sponsor_name'] ?? 'M.T.T.I Partner') ?> · <?= htmlspecialchars($p['location'] ?? 'Kenya') ?></div>
            <div class="posting-meta">
                <span class="meta-tag <?= $typeClass ?>"><?= ucfirst($p['type']) ?></span>
                <?php if ($p['slots'] > 0): ?><span class="meta-tag" style="background:#f1f5f9;color:#475569"><?= $p['slots'] ?> slots</span><?php endif; ?>
                <?php if ($p['county']): ?><span class="meta-tag" style="background:#f1f5f9;color:#475569">📍 <?= htmlspecialchars($p['county']) ?></span><?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($p['description']): ?>
    <div class="posting-desc"><?= htmlspecialchars(substr($p['description'], 0, 200)) ?><?= strlen($p['description']) > 200 ? '...' : '' ?></div>
    <?php endif; ?>
    <?php if ($p['requirements']): ?>
    <div style="font-size:.8rem;color:#475569;margin-top:8px"><strong>Requirements:</strong> <?= htmlspecialchars(substr($p['requirements'],0,150)) ?></div>
    <?php endif; ?>
    <div class="posting-footer">
        <div class="deadline <?= ($daysLeft !== null && $daysLeft <= 7) ? 'urgent' : '' ?>">
            <?php if ($daysLeft !== null): ?>
                <?= $daysLeft <= 0 ? '⚠️ Deadline passed' : ($daysLeft <= 7 ? "⏰ {$daysLeft} days left" : '📅 Deadline: ' . date('d M Y', strtotime($p['deadline']))) ?>
            <?php else: ?>
                <span>Open application</span>
            <?php endif; ?>
        </div>
        <?php if ($p['applied']): ?>
        <span class="btn-applied">✓ Applied</span>
        <?php elseif ($p['apply_url']): ?>
        <a href="<?= htmlspecialchars($p['apply_url']) ?>" target="_blank" class="btn-apply">Apply Now →</a>
        <?php else: ?>
        <button class="btn-apply" onclick="openModal(<?= $p['id'] ?>)">Apply Now →</button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Apply Modal -->
<div class="modal-overlay" id="applyModal">
    <div class="modal">
        <h3 style="margin-bottom:16px;color:#1e3a5f">📝 Submit Application</h3>
        <form method="POST">
            <input type="hidden" name="apply_posting" value="1">
            <input type="hidden" name="posting_id" id="modalPostingId" value="">
            <div style="margin-bottom:14px">
                <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:6px">Cover Letter / Why should you be selected?</label>
                <textarea name="cover_letter" rows="5" style="width:100%;border:1.5px solid #ddd;border-radius:8px;padding:10px;font-family:inherit;font-size:.88rem" placeholder="Tell the employer about yourself, your skills and why you're interested in this opportunity..."></textarea>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn-apply" style="flex:1;text-align:center">Submit Application</button>
                <button type="button" onclick="closeModal()" style="padding:8px 16px;border:1.5px solid #ddd;border-radius:8px;background:#fff;cursor:pointer">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById('modalPostingId').value = id;
    document.getElementById('applyModal').classList.add('show');
}
function closeModal() {
    document.getElementById('applyModal').classList.remove('show');
}
document.getElementById('applyModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
