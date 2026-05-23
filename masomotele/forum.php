<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Forum - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$topicId = (int)($_GET['topic'] ?? 0);
$action = $_GET['action'] ?? '';

// Create new topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'new_topic') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $classId = (int)($_POST['class_id'] ?? 0) ?: null;
    if ($title && $content) {
        $tid = $db->insert('forum_topics', ['title'=>$title,'user_id'=>$userId,'class_id'=>$classId,'created_at'=>date('Y-m-d H:i:s')]);
        $db->insert('forum_posts', ['topic_id'=>$tid,'user_id'=>$userId,'content'=>$content,'created_at'=>date('Y-m-d H:i:s')]);
        header('Location: ' . SITE_URL . '/forum.php?topic=' . $tid); exit;
    }
}

// Post reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reply' && $topicId) {
    $content = trim($_POST['content'] ?? '');
    if ($content) {
        $db->insert('forum_posts', ['topic_id'=>$topicId,'user_id'=>$userId,'content'=>$content,'created_at'=>date('Y-m-d H:i:s')]);
        // Badge: first forum post
        $postCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM forum_posts WHERE user_id=?", [$userId]);
        if ($postCount === 1) {
            $badge = $db->fetchOne("SELECT id FROM badges WHERE trigger_type='first_post'");
            if ($badge) {
                $has = $db->fetchOne("SELECT id FROM user_badges WHERE user_id=? AND badge_id=?", [$userId, $badge['id']]);
                if (!$has) $db->insert('user_badges', ['user_id'=>$userId,'badge_id'=>$badge['id'],'earned_at'=>date('Y-m-d H:i:s')]);
            }
        }
        header('Location: ' . SITE_URL . '/forum.php?topic=' . $topicId); exit;
    }
}

require_once __DIR__ . '/templates/header.php';
?>
<div class="container">
    <?php if ($topicId): ?>
        <?php
        $topic = $db->fetchOne("SELECT ft.*, u.name as author FROM forum_topics ft JOIN users u ON ft.user_id=u.id WHERE ft.id=?", [$topicId]);
        if (!$topic) { echo '<div class="alert alert-danger">Topic not found.</div>'; require_once __DIR__ . '/templates/footer.php'; exit; }
        $posts = $db->fetchAll("SELECT fp.*, u.name as author, u.photo as author_photo FROM forum_posts fp JOIN users u ON fp.user_id=u.id WHERE fp.topic_id=? ORDER BY fp.created_at", [$topicId]);
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i class="bi bi-chat-dots me-2 text-primary"></i><?= htmlspecialchars($topic['title']) ?></h4>
            <a href="<?= SITE_URL ?>/forum.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
        <small class="text-muted">Started by <?= htmlspecialchars($topic['author']) ?> on <?= date('M j, Y', strtotime($topic['created_at'])) ?></small>

        <!-- Posts -->
        <?php foreach ($posts as $p): ?>
        <div class="card mt-3">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:35px;height:35px"><i class="bi bi-person"></i></div>
                    <div><strong><?= htmlspecialchars($p['author']) ?></strong><br><small class="text-muted"><?= date('M j, Y g:ia', strtotime($p['created_at'])) ?></small></div>
                </div>
                <p class="mb-0"><?= nl2br(htmlspecialchars($p['content'])) ?></p>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Reply Form -->
        <div class="card mt-3">
            <div class="card-body">
                <h6>Reply</h6>
                <form method="POST" action="?topic=<?= $topicId ?>&action=reply">
                    <textarea name="content" class="form-control mb-2" rows="3" placeholder="Write your reply..." required></textarea>
                    <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Post Reply</button>
                </form>
            </div>
        </div>

    <?php elseif ($action === 'new'): ?>
        <h4 class="mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i>New Discussion</h4>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="?action=new_topic">
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Related Class (optional)</label>
                        <select name="class_id" class="form-select">
                            <option value="">General Discussion</option>
                            <?php $classes = $db->fetchAll("SELECT id, title FROM classes WHERE status='active'"); ?>
                            <?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Your Message</label><textarea name="content" class="form-control" rows="5" required></textarea></div>
                    <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Create Topic</button>
                    <a href="<?= SITE_URL ?>/forum.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>

    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bi bi-chat-dots me-2 text-primary"></i>Discussion Forum</h4>
            <a href="?action=new" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>New Topic</a>
        </div>
        <?php
        $topics = $db->fetchAll("SELECT ft.*, u.name as author, c.title as class_title,
            (SELECT COUNT(*) FROM forum_posts WHERE topic_id=ft.id) as replies,
            (SELECT MAX(created_at) FROM forum_posts WHERE topic_id=ft.id) as last_reply
            FROM forum_topics ft JOIN users u ON ft.user_id=u.id LEFT JOIN classes c ON ft.class_id=c.id ORDER BY last_reply DESC");
        ?>
        <?php if (empty($topics)): ?>
            <div class="card"><div class="card-body text-muted">No discussions yet. Be the first to start one!</div></div>
        <?php else: ?>
            <div class="card">
                <div class="list-group list-group-flush">
                    <?php foreach ($topics as $t): ?>
                    <a href="?topic=<?= $t['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($t['title']) ?></h6>
                                <small class="text-muted">By <?= htmlspecialchars($t['author']) ?> <?= $t['class_title'] ? '• in '.htmlspecialchars($t['class_title']) : '' ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary"><?= $t['replies'] ?> replies</span><br>
                                <small class="text-muted"><?= $t['last_reply'] ? date('M j, g:ia', strtotime($t['last_reply'])) : '' ?></small>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
