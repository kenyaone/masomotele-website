<?php
if (!defined('ABSPATH')) exit;

/**
 * Public "try before you enroll" demo pages — a curated, explicit set of
 * interactive lessons (the same ones served in the learner portal) exposed
 * at stable, indexable URLs for marketing/SEO purposes. Only ever serves a
 * lesson if it's BOTH in the hardcoded $demos map below AND flagged
 * is_free_preview=1 in the database — two independent gates, so nothing
 * becomes public just by an admin ticking a checkbox, and nothing here can
 * expose paid course content by accident.
 *
 * URL: /demo-lesson/{slug}/ — rendered via a rewrite rule, not a real page,
 * so new demos are added by editing $demos, no wp-admin page creation.
 */
class MTTI_MIS_Demo_Lessons {

    /**
     * slug => [lesson_id, course landing page slug for the "Enroll" CTA]
     * The landing-page slugs match MTTI_MIS_Shortcodes::$landing_pages.
     */
    private static $demos = array(
        'your-first-html-page' => array(464, 'web-design-course-eldoret'),
        'your-first-python-program' => array(1432, 'coding-programming-course-eldoret'),
        'choosing-fonts-graphic-design' => array(2439, 'graphic-design-course-eldoret'),
        'cybersecurity-cia-triad' => array(926, 'cybersecurity-course-eldoret'),
        'healthcare-terminology-basics' => array(1911, 'certified-nursing-assistant-course-eldoret'),
        'digital-marketing-basics' => array(138, 'digital-marketing-course-eldoret'),
        'what-is-artificial-intelligence' => array(2298, 'artificial-intelligence-course-eldoret'),
        'lan-wan-networking-basics' => array(1749, 'computer-networking-course-eldoret'),
        'cctv-camera-types-explained' => array(2469, 'cctv-installation-course-eldoret'),
        'video-editing-capcut-canva' => array(2622, 'video-editing-course-eldoret'),
        'ms-word-basics-free-lesson' => array(637, 'computer-applications-course-eldoret'),
        'computer-parts-explained' => array(789, 'computer-essentials-course-eldoret'),
        'smartphone-hardware-basics' => array(2544, 'mobile-phone-repair-course-eldoret'),
        'computer-repair-esd-safety' => array(1587, 'computer-repair-course-eldoret'),
        // German Language A1-B2 has no demo — none of its 4 course levels
        // (GEA-01, GL-02, GL-B1, GLB-02) have any lesson content in the
        // system yet, so there's nothing real to demo.
    );

    /**
     * course landing page slug => demo slug — used only to inject the
     * "try it free" CTA into the matching course page via a content
     * filter, never written into the stored post content.
     */
    private static $course_page_ctas = array(
        'web-design-course-eldoret'                  => 'your-first-html-page',
        'coding-programming-course-eldoret'          => 'your-first-python-program',
        'graphic-design-course-eldoret'              => 'choosing-fonts-graphic-design',
        'cybersecurity-course-eldoret'               => 'cybersecurity-cia-triad',
        'certified-nursing-assistant-course-eldoret' => 'healthcare-terminology-basics',
        'digital-marketing-course-eldoret'           => 'digital-marketing-basics',
        'artificial-intelligence-course-eldoret'     => 'what-is-artificial-intelligence',
        'computer-networking-course-eldoret'         => 'lan-wan-networking-basics',
        'cctv-installation-course-eldoret'           => 'cctv-camera-types-explained',
        'video-editing-course-eldoret'               => 'video-editing-capcut-canva',
        'computer-applications-course-eldoret'       => 'ms-word-basics-free-lesson',
        'computer-essentials-course-eldoret'         => 'computer-parts-explained',
        'mobile-phone-repair-course-eldoret'         => 'smartphone-hardware-basics',
        'computer-repair-course-eldoret'             => 'computer-repair-esd-safety',
    );

    public function __construct() {
        add_action('init', array($this, 'add_rewrite_rule'));
        add_filter('query_vars', array($this, 'add_query_var'));
        add_action('template_redirect', array($this, 'maybe_render'));
        add_action('init', array($this, 'maybe_flush_rewrite_rules'), 20);
        add_filter('the_content', array($this, 'maybe_append_demo_cta'));
    }

    /**
     * Appends a "try a free lesson" banner to the matching course landing
     * page, without altering its stored content — only fires for the
     * specific pages in $course_page_ctas, on the main singular query.
     */
    public function maybe_append_demo_cta($content) {
        if (!is_singular('page') || !in_the_loop() || !is_main_query()) return $content;

        $slug = get_post_field('post_name', get_the_ID());
        if (!isset(self::$course_page_ctas[$slug])) return $content;

        $demo_url = esc_url(self::get_demo_url(self::$course_page_ctas[$slug]));
        $banner = '<div style="max-width:900px;margin:24px auto;padding:20px;background:#e8f5e9;border:2px solid #2E7D32;border-radius:10px;text-align:center;">'
            . '<p style="margin:0 0 10px;font-size:16px;color:#1B5E20;font-weight:700;">🎯 Try a real lesson from this course — free, no signup</p>'
            . '<a href="' . $demo_url . '" style="display:inline-block;padding:10px 24px;background:#2E7D32;color:#fff;text-decoration:none;font-weight:700;border-radius:6px;">Try the Free Interactive Lesson →</a>'
            . '</div>';

        return $content . $banner;
    }

    public function add_rewrite_rule() {
        add_rewrite_rule('^demo-lesson/([^/]+)/?$', 'index.php?mtti_demo_lesson=$matches[1]', 'top');
    }

    public function add_query_var($vars) {
        $vars[] = 'mtti_demo_lesson';
        return $vars;
    }

    /**
     * Rewrite rules only need flushing once, after this file starts
     * registering the new rule — not on every request.
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('mtti_demo_lessons_rewrite_flushed') !== MTTI_MIS_VERSION) {
            flush_rewrite_rules();
            update_option('mtti_demo_lessons_rewrite_flushed', MTTI_MIS_VERSION);
        }
    }

    public static function get_demo_url($slug) {
        return home_url('/demo-lesson/' . $slug . '/');
    }

    /**
     * Hand-written title/description per demo, targeting the actual
     * search intent a stranger (not already looking for "MTTI") would
     * type — "what is X" / "learn X free" / "X basics" style queries —
     * rather than the generic "Free Interactive Lesson: {title}" default,
     * which ran well past 60 characters for several of these and led with
     * the internal lesson title instead of the keyword. Falls back to the
     * generic pattern for any demo added here without an entry below.
     */
    private static $seo_copy = array(
        'your-first-html-page' => array(
            'Free HTML Lesson for Beginners — Try It Online | MTTI',
            'Write and preview real HTML in your browser, free, no signup. A hands-on beginner lesson from MTTI\'s Web Design course in Eldoret.',
        ),
        'your-first-python-program' => array(
            'Free Python Lesson — Write & Run Code Online | MTTI',
            'Write your first Python program and run it live in your browser, free, no signup. From MTTI\'s Coding & Programming course, Eldoret.',
        ),
        'choosing-fonts-graphic-design' => array(
            'Free Graphic Design Lesson: Font Pairing | MTTI',
            'Learn how professional designers pair fonts, hands-on, free interactive lesson from MTTI\'s Graphic Design course in Eldoret.',
        ),
        'cybersecurity-cia-triad' => array(
            'What is the CIA Triad? Free Cybersecurity Lesson | MTTI',
            'Understand Confidentiality, Integrity and Availability, the foundation of cybersecurity, in this free interactive lesson from MTTI Eldoret.',
        ),
        'healthcare-terminology-basics' => array(
            'Free Medical Terminology Lesson for CNA Students | MTTI',
            'Practice breaking medical terms into prefixes, roots and suffixes, free interactive lesson from MTTI\'s Certified Nursing Assistant course.',
        ),
        'digital-marketing-basics' => array(
            'What is Digital Marketing? Free Interactive Lesson | MTTI',
            'A hands-on introduction to digital marketing fundamentals, free, no signup, from MTTI\'s Digital Marketing course in Eldoret.',
        ),
        'what-is-artificial-intelligence' => array(
            'What is Artificial Intelligence? Free Lesson | MTTI',
            'A simple, hands-on explanation of what AI actually is, free interactive lesson from MTTI\'s Artificial Intelligence course, Eldoret.',
        ),
        'lan-wan-networking-basics' => array(
            'LAN vs WAN Explained — Free Networking Lesson | MTTI',
            'Understand LANs, WANs and the client-server model, free interactive lesson from MTTI\'s Computer Networking course in Eldoret.',
        ),
        'cctv-camera-types-explained' => array(
            'Types of CCTV Cameras Explained — Free Lesson | MTTI',
            'Learn the different types of CCTV cameras and where each is used, free interactive lesson from MTTI\'s CCTV Installation course.',
        ),
        'video-editing-capcut-canva' => array(
            'Free CapCut & Canva Video Editing Lesson | MTTI',
            'Practice importing and organizing media like a pro editor, free interactive lesson from MTTI\'s Video Editing course in Eldoret.',
        ),
        'ms-word-basics-free-lesson' => array(
            'Free MS Word Basics Lesson for Beginners | MTTI',
            'Practice the Word interface and document basics, free interactive lesson from MTTI\'s Computer Applications course in Eldoret.',
        ),
        'computer-parts-explained' => array(
            'Computer Parts Explained — Free Beginner Lesson | MTTI',
            'Identify basic computer components hands-on, free interactive lesson from MTTI\'s Computer Essentials course in Eldoret.',
        ),
        'smartphone-hardware-basics' => array(
            'Smartphone Hardware Basics — Free Repair Lesson | MTTI',
            'Get an inside look at smartphone hardware, free interactive lesson from MTTI\'s Mobile Phone Repair course in Eldoret.',
        ),
        'computer-repair-esd-safety' => array(
            'ESD Safety Basics — Free Computer Repair Lesson | MTTI',
            'Learn how to protect components from static damage, free interactive lesson from MTTI\'s Computer Repair course in Eldoret.',
        ),
    );

    /**
     * Forces a real 404 — the main query has already resolved this URL to
     * something else (there's no actual post behind /demo-lesson/{slug}/),
     * so without explicitly marking it 404 here, an unmapped or no-longer-
     * public slug falls through to whatever the main query guessed (this
     * site's setup resolves it to the front page), returning 200 instead.
     */
    private function render_404() {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        include(get_query_template('404'));
        exit;
    }

    public function maybe_render() {
        $slug = get_query_var('mtti_demo_lesson');
        if (!$slug) return; // not a /demo-lesson/ request at all — leave WP's normal routing alone

        if (!isset(self::$demos[$slug])) {
            $this->render_404();
        }

        list($lesson_id, $course_slug) = self::$demos[$slug];

        global $wpdb;
        $lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT l.lesson_id, l.title, l.course_id, l.is_free_preview, l.status, c.course_name, c.course_code
             FROM {$wpdb->prefix}mtti_lessons l
             INNER JOIN {$wpdb->prefix}mtti_courses c ON c.course_id = l.course_id
             WHERE l.lesson_id = %d AND l.deleted_at IS NULL",
            $lesson_id
        ));

        // Both gates must hold: mapped here AND still marked free-preview
        // in the DB — an admin un-checking "Free Preview" on this lesson
        // takes it out of public circulation immediately, no code change.
        if (!$lesson || !$lesson->is_free_preview || $lesson->status !== 'Published') {
            $this->render_404();
        }

        $nonce = wp_create_nonce('serve_interactive_' . $lesson_id);
        $iframe_src = admin_url('admin-ajax.php?action=mtti_serve_interactive&lesson_id=' . $lesson_id . '&nonce=' . $nonce);
        $enroll_url = home_url('/' . $course_slug . '/');
        $demo_url = self::get_demo_url($slug);
        if (isset(self::$seo_copy[$slug])) {
            list($page_title, $meta_desc) = self::$seo_copy[$slug];
            $headline = preg_replace('/\s*\|\s*MTTI$/', '', $page_title);
        } else {
            $page_title = 'Free Interactive Lesson: ' . $lesson->title . ' | ' . $lesson->course_name . ' | MTTI Eldoret';
            $meta_desc = 'Try a real interactive lesson from our ' . $lesson->course_name . ' course, free — no signup required. See exactly how hands-on learning works at MTTI Eldoret before you enroll.';
            $headline = 'Free Interactive Lesson: ' . $lesson->title;
        }
        $institute_name = get_option('mtti_mis_institute_name', 'Masomotele Technical Training Institute');

        status_header(200);
        nocache_headers();
        ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html($page_title); ?></title>
<meta name="description" content="<?php echo esc_attr($meta_desc); ?>">
<link rel="canonical" href="<?php echo esc_url($demo_url); ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo esc_url($demo_url); ?>">
<meta property="og:title" content="<?php echo esc_attr($page_title); ?>">
<meta property="og:description" content="<?php echo esc_attr($meta_desc); ?>">
<meta property="og:site_name" content="<?php echo esc_attr($institute_name); ?>">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; background:#f0f2f5; }
.demo-header { background:linear-gradient(135deg,#1B5E20,#2E7D32); color:#fff; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.demo-header a.back { color:#fff; text-decoration:none; font-size:14px; opacity:.9; }
.demo-header a.back:hover { opacity:1; text-decoration:underline; }
.demo-header .brand { font-weight:700; font-size:15px; }
.demo-banner { max-width:900px; margin:20px auto 0; padding:0 16px; text-align:center; }
.demo-banner h1 { font-size:22px; color:#1B5E20; margin-bottom:6px; }
.demo-banner p { color:#555; font-size:14px; }
.demo-frame-wrap { max-width:900px; margin:16px auto; padding:0 16px; }
.demo-frame-wrap iframe { width:100%; height:70vh; min-height:520px; border:2px solid #2E7D32; border-radius:10px; background:#fff; }
.demo-cta { max-width:900px; margin:0 auto 40px; padding:20px; text-align:center; background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.08); }
.demo-cta a { display:inline-block; margin-top:10px; padding:12px 28px; background:#FF9800; color:#fff; text-decoration:none; font-weight:700; border-radius:6px; }
</style>
</head>
<body>
<div class="demo-header">
    <span class="brand">🎓 <?php echo esc_html($institute_name); ?></span>
    <a class="back" href="<?php echo esc_url($enroll_url); ?>">← Back to <?php echo esc_html($lesson->course_name); ?></a>
</div>
<div class="demo-banner">
    <h1><?php echo esc_html($headline); ?></h1>
    <p>This is a real lesson from our <?php echo esc_html($lesson->course_name); ?> (<?php echo esc_html($lesson->course_code); ?>) course — try it right here, no signup needed.</p>
</div>
<div class="demo-frame-wrap">
    <iframe src="<?php echo esc_url($iframe_src); ?>" title="<?php echo esc_attr($lesson->title); ?>" loading="lazy"></iframe>
</div>
<div class="demo-cta">
    <p>Like what you see? This is exactly how every lesson in <?php echo esc_html($lesson->course_name); ?> works.</p>
    <a href="<?php echo esc_url($enroll_url); ?>">See the full course →</a>
</div>
</body>
</html><?php
        exit;
    }
}
