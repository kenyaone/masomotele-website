<?php
class MTTI_MIS_Shortcodes {
    
    public function __construct() {
        // Register shortcodes immediately on construct as backup
        add_shortcode('mtti_verify_certificate', array($this, 'verify_certificate_shortcode'));
    }
    
    public function register_shortcodes() {
        // Register shortcodes on init hook
        add_shortcode('mtti_verify_certificate', array($this, 'verify_certificate_shortcode'));
        add_shortcode('mtti_courses', array($this, 'courses_shortcode'));
        // mtti_student_portal / mtti_learner_portal registered by Learner Portal class (init)
        // mtti_lecturer_portal registered by Lecturer Portal class (init)

        // Online admission form
        if (class_exists('MTTI_MIS_Public_Admission_Form')) {
            $form = new MTTI_MIS_Public_Admission_Form();
            add_shortcode('mtti_admission_form', array($form, 'render_shortcode'));
        }

        // Online-only self-checkout (pick a course, pay, get auto-enrolled)
        if (class_exists('MTTI_MIS_Public_Course_Checkout')) {
            $checkout = new MTTI_MIS_Public_Course_Checkout();
            add_shortcode('mtti_course_checkout', array($checkout, 'render_shortcode'));
        }
    }
    
    /**
     * Public course catalog.
     * Usage: [mtti_courses]
     * Grid view by default; add ?course_id=N to the page URL for a single-course detail view.
     */
    public function courses_shortcode($atts) {
        $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

        ob_start();
        echo $this->catalog_styles();

        if ($course_id) {
            $this->render_course_detail($course_id);
        } else {
            $this->render_catalog_grid();
        }

        return ob_get_clean();
    }

    /**
     * MTTI brand palette, sampled from the institute logo (Resources/Logo.jpeg).
     */
    const BRAND_BLUE       = '#1A5889';
    const BRAND_BLUE_LIGHT = '#259BD7';
    const BRAND_GREEN      = '#387633';
    const BRAND_GREEN_BG   = '#E8F5E6';
    const BRAND_GOLD       = '#E8942E';
    const BRAND_GOLD_BG    = '#FCF0DD';

    /**
     * Rotates the three brand hues across cards so a same-category catalog
     * doesn't render as a wall of identical colour, without going off-brand.
     */
    private function course_accent($course) {
        $palette = array(
            array(self::BRAND_BLUE,  self::BRAND_BLUE_LIGHT),
            array(self::BRAND_GREEN, '#5DA355'),
            array(self::BRAND_GOLD,  '#F2B45C'),
        );
        return $palette[intval($course->course_id) % count($palette)];
    }

    /**
     * MTTI institute logo — used as the card/hero icon for every course that
     * has no specific thumbnail_url set, same asset used on certificates.
     */
    private function logo_url() {
        return MTTI_MIS_PLUGIN_URL . 'assets/images/logo.jpeg';
    }

    /**
     * Minimal single-colour line icons (stroke = currentColor) used instead
     * of emoji, which render inconsistently across OS/browser combinations.
     */
    private function svg_icon($name) {
        $icons = array(
            'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
            'book'   => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15.5A2.5 2.5 0 0 0 17.5 21H6.5A2.5 2.5 0 0 1 4 18.5v-13Z"/><path d="M8 3v15"/>',
            'tag'    => '<path d="m20.59 13.41-7.17 7.17a2 2 0 0 1-2.83 0L3 13V3h10l7.59 7.59a2 2 0 0 1 0 2.82Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'check'  => '<path d="m20 6-11 11-5-5"/>',
        );
        $path = $icons[$name] ?? '';
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mtti-icon mtti-icon-' . esc_attr($name) . '">' . $path . '</svg>';
    }

    private function portal_home_url() {
        $page = get_page_by_path('student-portal');
        return $page ? get_permalink($page) : home_url('/student-portal/');
    }

    private function portal_lessons_url($course_id) {
        $page = get_page_by_path('student-portal');
        $base = $page ? get_permalink($page) : home_url('/student-portal/');
        return add_query_arg(array('portal_tab' => 'lessons', 'filter_course' => intval($course_id)), $base);
    }

    private function admission_url($course_id) {
        $page = get_page_by_path('online-admission');
        $base = $page ? get_permalink($page) : home_url('/online-admission/');
        return add_query_arg('course_id', intval($course_id), $base);
    }

    /**
     * True for site admins/MTTI staff — same capability pair used
     * elsewhere in this plugin (e.g. class-mtti-mis-lecturer-portal.php)
     * to mean "admin/staff account".
     */
    private function is_admin_preview() {
        return current_user_can('manage_options') || current_user_can('manage_mtti');
    }

    /**
     * Catalog CTA: enrolled students continue straight to their lessons;
     * admins get a preview link straight into the portal (see
     * is_admin_preview() bypass in the learner-portal/interactive gates —
     * no payment or real enrollment involved); everyone else has to pay
     * to enroll first.
     */
    private function enroll_cta($course, $is_enrolled) {
        if ($is_enrolled) {
            return '<a href="' . esc_url($this->portal_lessons_url($course->course_id)) . '" class="mtti-enroll-btn mtti-enroll-btn-enrolled">▶ Continue Learning</a>';
        }
        if ($this->is_admin_preview()) {
            return '<a href="' . esc_url($this->portal_lessons_url($course->course_id)) . '" class="mtti-enroll-btn mtti-enroll-btn-admin">👁 Preview (Admin)</a>';
        }
        return '<a href="' . esc_url($this->admission_url($course->course_id)) . '" class="mtti-enroll-btn">Enroll & Pay Now →</a>';
    }

    /**
     * Course IDs the currently logged-in visitor already has an active
     * enrollment for — used to swap "Enroll & Pay" for "Continue Learning"
     * so already-paying students aren't sent through admission/payment again.
     * Empty for logged-out visitors or users with no linked student record.
     */
    private function current_student_enrolled_ids() {
        if (!is_user_logged_in()) return array();
        global $wpdb;
        $student_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT student_id FROM {$wpdb->prefix}mtti_students WHERE user_id = %d LIMIT 1",
            get_current_user_id()
        ));
        if (!$student_id) return array();
        // 'Completed' is included so a student who finished a course still
        // sees "Continue Learning" (to review it) instead of being sent
        // back through "Enroll & Pay Now" for a course they already paid
        // for and finished — same fix as get_enrolled_course_ids() in the
        // learner portal class, which this must stay in sync with.
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT course_id FROM {$wpdb->prefix}mtti_enrollments
             WHERE student_id = %d AND status IN ('Active','Enrolled','In Progress','Completed')",
            $student_id
        ));
        return array_map('intval', $ids);
    }

    private function render_catalog_grid() {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'mtti_courses';
        $units_table   = $wpdb->prefix . 'mtti_course_units';

        $courses = $wpdb->get_results(
            "SELECT c.*, COUNT(u.unit_id) AS unit_count
             FROM {$courses_table} c
             LEFT JOIN {$units_table} u ON u.course_id = c.course_id AND u.is_active = 1
             WHERE c.status = 'Active' AND c.is_active = 1 AND c.deleted_at IS NULL
             GROUP BY c.course_id
             ORDER BY c.course_name ASC"
        );

        $current_url   = home_url(add_query_arg(null, null));
        $enrolled_ids  = $this->current_student_enrolled_ids();
        ?>
        <div class="mtti-catalog-container">
            <div class="mtti-catalog-header">
                <h2 class="mtti-catalog-title">Explore Our Courses</h2>
                <p class="mtti-catalog-subtitle">Technical training courses at Masomotele Technical Training Institute, Eldoret</p>
                <div class="mtti-search-wrap">
                    <?php echo $this->svg_icon('search'); ?>
                    <input type="text" id="mttiCourseSearch" class="mtti-catalog-search" placeholder="Search courses by name or category...">
                </div>
            </div>

            <?php if (empty($courses)): ?>
                <p style="text-align:center;color:#666;">No courses are available right now — please check back soon.</p>
            <?php else: ?>
            <div class="mtti-course-grid" id="mttiCourseGrid">
                <?php foreach ($courses as $course):
                    $detail_url = add_query_arg('course_id', $course->course_id, $current_url);
                    $search_key = strtolower($course->course_name . ' ' . $course->category);
                ?>
                <?php list($accent_a, $accent_b) = $this->course_accent($course); ?>
                <?php $is_enrolled = in_array(intval($course->course_id), $enrolled_ids, true); ?>
                <a href="<?php echo esc_url($detail_url); ?>" class="mtti-course-card" data-search="<?php echo esc_attr($search_key); ?>">
                    <div class="mtti-course-thumb" style="background:linear-gradient(135deg, <?php echo esc_attr($accent_a); ?> 0%, <?php echo esc_attr($accent_b); ?> 100%);">
                        <?php if ($is_enrolled): ?>
                            <span class="mtti-enrolled-ribbon"><?php echo $this->svg_icon('check'); ?> Enrolled</span>
                        <?php endif; ?>
                        <?php if (!empty($course->thumbnail_url)): ?>
                            <img src="<?php echo esc_url($course->thumbnail_url); ?>" alt="<?php echo esc_attr($course->course_name); ?>">
                        <?php else: ?>
                            <img class="mtti-thumb-logo" src="<?php echo esc_url($this->logo_url()); ?>" alt="MTTI">
                        <?php endif; ?>
                    </div>
                    <div class="mtti-course-body">
                        <span class="mtti-course-badge"><?php echo esc_html($course->category ?: 'General'); ?></span>
                        <h3 class="mtti-course-name"><?php echo esc_html($course->course_name); ?></h3>
                        <div class="mtti-course-meta">
                            <span><?php echo $this->svg_icon('clock'); ?> <?php echo esc_html($course->duration_weeks); ?> weeks</span>
                            <?php if ($course->unit_count > 0): ?>
                                <span><?php echo $this->svg_icon('book'); ?> <?php echo intval($course->unit_count); ?> modules</span>
                            <?php endif; ?>
                        </div>
                        <div class="mtti-course-fee">KES <?php echo number_format((float) $course->fee); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <p id="mttiCourseNoResults" style="display:none;text-align:center;color:#666;margin-top:20px;">No courses match your search.</p>
            <?php endif; ?>
        </div>
        <script>
        (function() {
            var input = document.getElementById('mttiCourseSearch');
            if (!input) return;
            input.addEventListener('input', function() {
                var q = input.value.trim().toLowerCase();
                var cards = document.querySelectorAll('#mttiCourseGrid .mtti-course-card');
                var visible = 0;
                cards.forEach(function(card) {
                    var match = card.getAttribute('data-search').indexOf(q) !== -1;
                    card.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                document.getElementById('mttiCourseNoResults').style.display = visible === 0 ? '' : 'none';
            });
        })();
        </script>
        <?php
    }

    private function render_course_detail($course_id) {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'mtti_courses';
        $units_table   = $wpdb->prefix . 'mtti_course_units';

        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$courses_table} WHERE course_id = %d AND status = 'Active' AND is_active = 1 AND deleted_at IS NULL",
            $course_id
        ));

        $catalog_url = remove_query_arg('course_id');

        if (!$course) {
            echo '<div class="mtti-catalog-container"><p style="text-align:center;color:#666;">Course not found. <a href="' . esc_url($catalog_url) . '">← Back to all courses</a></p></div>';
            return;
        }

        $units = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$units_table} WHERE course_id = %d AND is_active = 1 ORDER BY order_number ASC, unit_id ASC",
            $course_id
        ));

        $description = $course->course_description ?: $course->description;
        $outcomes = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $course->learning_outcomes)));
        $is_enrolled = in_array(intval($course->course_id), $this->current_student_enrolled_ids(), true);
        ?>
        <?php list($accent_a, $accent_b) = $this->course_accent($course); ?>
        <div class="mtti-catalog-container">
            <a href="<?php echo esc_url($catalog_url); ?>" class="mtti-back-link">← Back to all courses</a>

            <div class="mtti-detail-hero">
                <div class="mtti-detail-thumb" style="background:linear-gradient(135deg, <?php echo esc_attr($accent_a); ?> 0%, <?php echo esc_attr($accent_b); ?> 100%);">
                    <?php if (!empty($course->thumbnail_url)): ?>
                        <img src="<?php echo esc_url($course->thumbnail_url); ?>" alt="<?php echo esc_attr($course->course_name); ?>">
                    <?php else: ?>
                        <img class="mtti-thumb-logo mtti-thumb-logo-lg" src="<?php echo esc_url($this->logo_url()); ?>" alt="MTTI">
                    <?php endif; ?>
                </div>
                <div class="mtti-detail-hero-body">
                    <span class="mtti-course-badge"><?php echo esc_html($course->category ?: 'General'); ?></span>
                    <h1 class="mtti-detail-title"><?php echo esc_html($course->course_name); ?></h1>
                    <div class="mtti-course-meta">
                        <span><?php echo $this->svg_icon('clock'); ?> <?php echo esc_html($course->duration_weeks); ?> weeks</span>
                        <?php if (!empty($units)): ?><span><?php echo $this->svg_icon('book'); ?> <?php echo count($units); ?> modules</span><?php endif; ?>
                        <span><?php echo $this->svg_icon('tag'); ?> <?php echo esc_html($course->course_code); ?></span>
                    </div>
                    <div class="mtti-detail-fee">KES <?php echo number_format((float) $course->fee); ?></div>
                    <?php echo $this->enroll_cta($course, $is_enrolled); ?>
                </div>
            </div>

            <?php if ($description): ?>
            <div class="mtti-detail-section">
                <h2>About this course</h2>
                <p><?php echo nl2br(esc_html($description)); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($outcomes)): ?>
            <div class="mtti-detail-section">
                <h2>What you'll learn</h2>
                <ul class="mtti-outcomes-list">
                    <?php foreach ($outcomes as $o): ?>
                        <li><?php echo $this->svg_icon('check'); ?> <?php echo esc_html(ltrim($o, "-•* \t")); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($units)): ?>
            <div class="mtti-detail-section">
                <h2>Curriculum</h2>
                <div class="mtti-unit-list">
                    <?php foreach ($units as $i => $unit): ?>
                    <div class="mtti-unit-row">
                        <span class="mtti-unit-index"><?php echo $i + 1; ?></span>
                        <div class="mtti-unit-info">
                            <strong><?php echo esc_html($unit->unit_name); ?></strong>
                            <?php if (!empty($unit->description)): ?>
                                <p><?php echo esc_html($unit->description); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="mtti-detail-section">
                <h2>Curriculum</h2>
                <p style="color:#666;">Full curriculum details coming soon — contact MTTI for the current syllabus.</p>
            </div>
            <?php endif; ?>

            <div class="mtti-detail-cta">
                <?php echo $this->enroll_cta($course, $is_enrolled); ?>
            </div>
        </div>
        <?php
    }

    private function catalog_styles() {
        ob_start();
        ?>
        <style>
            .mtti-catalog-container {
                --mtti-blue: <?php echo self::BRAND_BLUE; ?>;
                --mtti-blue-light: <?php echo self::BRAND_BLUE_LIGHT; ?>;
                --mtti-green: <?php echo self::BRAND_GREEN; ?>;
                --mtti-green-bg: <?php echo self::BRAND_GREEN_BG; ?>;
                --mtti-gold: <?php echo self::BRAND_GOLD; ?>;
                --mtti-gold-bg: <?php echo self::BRAND_GOLD_BG; ?>;
                max-width: 1100px; margin: 0 auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #333;
            }
            .mtti-icon { width: 15px; height: 15px; vertical-align: -2px; flex-shrink: 0; }

            .mtti-catalog-header { text-align: center; margin-bottom: 30px; }
            .mtti-catalog-title { font-size: 30px; color: var(--mtti-blue); font-weight: 700; margin-bottom: 8px; }
            .mtti-catalog-subtitle { color: #666; margin-bottom: 20px; }
            .mtti-search-wrap { position: relative; max-width: 420px; margin: 0 auto; }
            .mtti-search-wrap .mtti-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #999; width: 17px; height: 17px; }
            .mtti-catalog-search { width: 100%; padding: 12px 18px 12px 42px; border: 2px solid #e0e0e0; border-radius: 30px; font-size: 15px; box-sizing: border-box; }
            .mtti-catalog-search:focus { outline: none; border-color: var(--mtti-blue-light); }

            .mtti-course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
            .mtti-course-card { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); text-decoration: none; color: inherit; display: flex; flex-direction: column; transition: transform .15s, box-shadow .15s; }
            .mtti-course-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }

            .mtti-course-thumb, .mtti-detail-thumb { display: flex; align-items: center; justify-content: center; position: relative; }
            .mtti-course-thumb { height: 130px; }
            .mtti-enrolled-ribbon { position: absolute; top: 10px; right: 10px; background: var(--mtti-green); color: #fff; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px; }
            .mtti-enrolled-ribbon .mtti-icon { width: 11px; height: 11px; }
            .mtti-course-thumb img, .mtti-detail-thumb img { width: 100%; height: 100%; object-fit: cover; }
            .mtti-thumb-logo { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; background: #fff; padding: 3px; box-shadow: 0 2px 8px rgba(0,0,0,.18); }
            .mtti-thumb-logo-lg { width: 60px; height: 60px; padding: 4px; }

            .mtti-course-body { padding: 16px 18px 20px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
            .mtti-course-badge { align-self: flex-start; background: var(--mtti-green-bg); color: var(--mtti-green); font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; }
            .mtti-course-name { font-size: 17px; margin: 0; color: var(--mtti-blue); line-height: 1.3; }
            .mtti-course-meta { display: flex; gap: 14px; font-size: 13px; color: #666; flex-wrap: wrap; }
            .mtti-course-meta span { display: inline-flex; align-items: center; gap: 4px; }
            .mtti-course-fee { margin-top: auto; font-size: 16px; font-weight: 700; color: var(--mtti-gold); }

            .mtti-back-link { display: inline-block; margin-bottom: 20px; color: var(--mtti-blue); text-decoration: none; font-weight: 600; }
            .mtti-back-link:hover { text-decoration: underline; }

            .mtti-detail-hero { display: flex; gap: 24px; background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 24px; flex-wrap: wrap; }
            .mtti-detail-thumb { width: 260px; min-height: 200px; flex-shrink: 0; }
            .mtti-detail-hero-body { padding: 24px 28px 24px 0; display: flex; flex-direction: column; gap: 10px; }
            .mtti-detail-title { font-size: 26px; color: var(--mtti-blue); margin: 0; }
            .mtti-detail-fee { font-size: 22px; font-weight: 700; color: var(--mtti-gold); }

            .mtti-enroll-btn { display: inline-block; align-self: flex-start; background: var(--mtti-blue); color: #fff !important; padding: 14px 26px; border-radius: 12px; text-decoration: none; font-weight: 700; transition: transform .15s, box-shadow .15s, background .15s; }
            .mtti-enroll-btn:hover { background: var(--mtti-blue-light); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(26,88,137,.3); }
            .mtti-enroll-btn-enrolled { background: var(--mtti-green); }
            .mtti-enroll-btn-enrolled:hover { background: #5DA355; box-shadow: 0 8px 20px rgba(56,118,51,.3); }
            .mtti-enroll-btn-admin { background: var(--mtti-gold); }
            .mtti-enroll-btn-admin:hover { background: #F2B45C; box-shadow: 0 8px 20px rgba(232,148,46,.3); }

            .mtti-detail-section { background: #fff; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 24px 28px; margin-bottom: 20px; }
            .mtti-detail-section h2 { font-size: 19px; color: var(--mtti-blue); margin-top: 0; }
            .mtti-detail-section p { line-height: 1.7; color: #444; }

            .mtti-outcomes-list { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
            .mtti-outcomes-list li { line-height: 1.5; display: flex; align-items: flex-start; gap: 8px; }
            .mtti-outcomes-list .mtti-icon { color: var(--mtti-green); margin-top: 4px; }

            .mtti-unit-list { display: flex; flex-direction: column; gap: 4px; }
            .mtti-unit-row { display: flex; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f0f0f0; }
            .mtti-unit-row:last-child { border-bottom: none; }
            .mtti-unit-index { flex-shrink: 0; width: 30px; height: 30px; border-radius: 50%; background: var(--mtti-green-bg); color: var(--mtti-green); font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 13px; }
            .mtti-unit-info p { margin: 4px 0 0; color: #666; font-size: 14px; line-height: 1.5; }

            .mtti-detail-cta { text-align: center; margin: 30px 0 10px; }

            @media (max-width: 640px) {
                .mtti-detail-hero { flex-direction: column; }
                .mtti-detail-thumb { width: 100%; height: 160px; }
                .mtti-detail-hero-body { padding: 20px; }
            }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Certificate Verification Shortcode
     * Usage: [mtti_verify_certificate]
     */
    public function verify_certificate_shortcode($atts) {
        global $wpdb;
        
        $search_term = '';
        $certificate = null;
        $searched = false;
        $debug_info = '';
        
        // Check for code parameter (from form or QR code)
        if (isset($_GET['code']) && !empty($_GET['code'])) {
            $search_term = sanitize_text_field($_GET['code']);
            $searched = true;
        }
        // Also check POST as fallback
        elseif (isset($_POST['search_term']) && !empty($_POST['search_term'])) {
            $search_term = sanitize_text_field($_POST['search_term']);
            $searched = true;
        }
        
        // Search for certificate
        if ($searched && !empty($search_term)) {
            // Get the correct table name using WordPress prefix
            $table = $wpdb->prefix . 'mtti_certificates';
            
            // Debug: Show table name being searched
            $debug_info = "<!-- Debug: Searching table: {$table} for: {$search_term} -->";
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            $debug_info .= "<!-- Table exists: " . ($table_exists ? 'YES' : 'NO') . " -->";
            
            if ($table_exists) {
                // Search for the certificate
                $certificate = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$table} 
                     WHERE certificate_number = %s 
                     OR verification_code = %s 
                     LIMIT 1",
                    $search_term, $search_term
                ));
                
                $debug_info .= "<!-- Certificate found: " . ($certificate ? 'YES' : 'NO') . " -->";
                $debug_info .= "<!-- Last SQL error: " . $wpdb->last_error . " -->";
                
                // If certificate found but no status column or empty, assume Valid
                if ($certificate) {
                    if (!property_exists($certificate, 'status') || empty($certificate->status)) {
                        $certificate->status = 'Valid';
                    }
                }
            }
        }
        
        ob_start();
        echo $debug_info; // Output debug info as HTML comments
        ?>
        <style>
            .mtti-verify-container {
                max-width: 700px;
                margin: 0 auto;
                padding: 20px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .mtti-verify-card {
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                padding: 40px;
                margin-bottom: 30px;
            }
            .mtti-verify-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .mtti-verify-icon {
                font-size: 60px;
                margin-bottom: 15px;
            }
            .mtti-verify-title {
                font-size: 28px;
                color: #1e3c72;
                margin-bottom: 10px;
                font-weight: 700;
            }
            .mtti-verify-subtitle {
                font-size: 16px;
                color: #666;
            }
            .mtti-verify-form {
                margin: 30px 0;
            }
            .mtti-verify-label {
                display: block;
                font-size: 16px;
                font-weight: 600;
                color: #333;
                margin-bottom: 12px;
            }
            .mtti-verify-input {
                width: 100%;
                padding: 16px 20px;
                font-size: 18px;
                border: 2px solid #e0e0e0;
                border-radius: 12px;
                font-family: 'Courier New', monospace;
                letter-spacing: 1px;
                text-transform: uppercase;
                transition: all 0.3s;
                margin-bottom: 20px;
                box-sizing: border-box;
            }
            .mtti-verify-input:focus {
                outline: none;
                border-color: #2a5298;
                box-shadow: 0 0 0 4px rgba(42, 82, 152, 0.1);
            }
            .mtti-verify-btn {
                width: 100%;
                padding: 18px;
                background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
                color: white !important;
                border: none;
                border-radius: 12px;
                font-size: 18px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .mtti-verify-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(42, 82, 152, 0.3);
                color: white !important;
            }
            
            /* Result Styles */
            .mtti-result {
                margin-top: 30px;
                padding: 30px;
                border-radius: 12px;
            }
            .mtti-result.valid {
                background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
                border: 2px solid #4CAF50;
            }
            .mtti-result.invalid {
                background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
                border: 2px solid #f44336;
            }
            .mtti-result.revoked {
                background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
                border: 2px solid #FF9800;
            }
            .mtti-result-header {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 20px;
            }
            .mtti-result-icon {
                font-size: 48px;
            }
            .mtti-result-title {
                font-size: 24px;
                font-weight: 700;
                margin: 0;
            }
            .mtti-result.valid .mtti-result-title { color: #2E7D32; }
            .mtti-result.invalid .mtti-result-title { color: #c62828; }
            .mtti-result.revoked .mtti-result-title { color: #E65100; }
            
            .mtti-result-desc {
                font-size: 16px;
                color: #555;
                margin-bottom: 25px;
                line-height: 1.6;
            }
            .mtti-cert-details {
                background: rgba(255,255,255,0.7);
                border-radius: 10px;
                padding: 20px;
            }
            .mtti-detail-row {
                display: flex;
                padding: 12px 0;
                border-bottom: 1px solid rgba(0,0,0,0.08);
            }
            .mtti-detail-row:last-child {
                border-bottom: none;
            }
            .mtti-detail-label {
                width: 180px;
                font-weight: 600;
                color: #555;
                flex-shrink: 0;
            }
            .mtti-detail-value {
                flex: 1;
                color: #333;
            }
            .mtti-status-badge {
                display: inline-block;
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 700;
                letter-spacing: 1px;
            }
            .mtti-status-badge.valid {
                background: #4CAF50;
                color: white;
            }
            .mtti-status-badge.revoked {
                background: #FF9800;
                color: white;
            }
            
            .mtti-help-box {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 25px;
                border-left: 4px solid #2a5298;
                margin-top: 20px;
            }
            .mtti-help-box strong {
                color: #1e3c72;
                font-size: 16px;
            }
            .mtti-help-box ul {
                margin: 15px 0 0 20px;
                line-height: 1.8;
            }
            
            @media (max-width: 600px) {
                .mtti-verify-card { padding: 25px; }
                .mtti-detail-row { flex-direction: column; }
                .mtti-detail-label { width: 100%; margin-bottom: 5px; }
            }
        </style>
        
        <div class="mtti-verify-container">
            <div class="mtti-verify-card">
                <div class="mtti-verify-header">
                    <div class="mtti-verify-icon">🎓</div>
                    <h2 class="mtti-verify-title">Certificate Verification</h2>
                    <p class="mtti-verify-subtitle">Verify the authenticity of MTTI certificates</p>
                </div>
                
                <form method="GET" class="mtti-verify-form" action="">
                    <label for="mtti_search_term" class="mtti-verify-label">
                        Enter Certificate Number or Verification Code
                    </label>
                    <input 
                        type="text" 
                        name="code" 
                        id="mtti_search_term"
                        class="mtti-verify-input"
                        placeholder="e.g., MTTI/CERT/2025/123456"
                        value="<?php echo esc_attr($search_term); ?>"
                        required
                    >
                    <button type="submit" class="mtti-verify-btn">
                        🔍 Verify Certificate
                    </button>
                </form>
                
                    <?php if ($searched): ?>
                    <?php if ($certificate && strtolower($certificate->status) !== 'revoked'): ?>
                        <div class="mtti-result valid">
                            <div class="mtti-result-header">
                                <div class="mtti-result-icon">✅</div>
                                <h3 class="mtti-result-title">Certificate Valid</h3>
                            </div>
                            <p class="mtti-result-desc">
                                This is an authentic certificate issued by Masomotele Technical Training Institute.
                            </p>
                            <div class="mtti-cert-details">
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Certificate No:</div>
                                    <div class="mtti-detail-value"><strong><?php echo esc_html($certificate->certificate_number); ?></strong></div>
                                </div>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Student Name:</div>
                                    <div class="mtti-detail-value"><?php echo esc_html($certificate->student_name); ?></div>
                                </div>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Admission No:</div>
                                    <div class="mtti-detail-value"><?php echo esc_html($certificate->admission_number); ?></div>
                                </div>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Course:</div>
                                    <div class="mtti-detail-value"><?php echo esc_html($certificate->course_name); ?> (<?php echo esc_html($certificate->course_code); ?>)</div>
                                </div>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Grade:</div>
                                    <div class="mtti-detail-value"><strong><?php echo esc_html($certificate->grade); ?></strong></div>
                                </div>
                                <?php if (!empty($certificate->completion_date)): ?>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Completion Date:</div>
                                    <div class="mtti-detail-value"><?php echo date('F j, Y', strtotime($certificate->completion_date)); ?></div>
                                </div>
                                <?php endif; ?>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Issue Date:</div>
                                    <div class="mtti-detail-value"><?php echo date('F j, Y', strtotime($certificate->issue_date)); ?></div>
                                </div>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Verification Code:</div>
                                    <div class="mtti-detail-value" style="font-family: monospace; letter-spacing: 2px;"><?php echo esc_html($certificate->verification_code); ?></div>
                                </div>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Status:</div>
                                    <div class="mtti-detail-value"><span class="mtti-status-badge valid">✓ VALID</span></div>
                                </div>
                            </div>
                        </div>
                        
                    <?php elseif ($certificate && strtolower($certificate->status) === 'revoked'): ?>
                        <div class="mtti-result revoked">
                            <div class="mtti-result-header">
                                <div class="mtti-result-icon">⚠️</div>
                                <h3 class="mtti-result-title">Certificate Revoked</h3>
                            </div>
                            <p class="mtti-result-desc">
                                This certificate has been revoked and is no longer valid.
                            </p>
                            <div class="mtti-cert-details">
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Certificate No:</div>
                                    <div class="mtti-detail-value"><?php echo esc_html($certificate->certificate_number); ?></div>
                                </div>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Student Name:</div>
                                    <div class="mtti-detail-value"><?php echo esc_html($certificate->student_name); ?></div>
                                </div>
                                <?php if ($certificate->notes): ?>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Reason:</div>
                                    <div class="mtti-detail-value"><?php echo esc_html($certificate->notes); ?></div>
                                </div>
                                <?php endif; ?>
                                <div class="mtti-detail-row">
                                    <div class="mtti-detail-label">Status:</div>
                                    <div class="mtti-detail-value"><span class="mtti-status-badge revoked">⚠ REVOKED</span></div>
                                </div>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <div class="mtti-result invalid">
                            <div class="mtti-result-header">
                                <div class="mtti-result-icon">❌</div>
                                <h3 class="mtti-result-title">Certificate Not Found</h3>
                            </div>
                            <p class="mtti-result-desc">
                                The certificate number or verification code "<strong><?php echo esc_html($search_term); ?></strong>" could not be found in our records.
                            </p>
                            <div class="mtti-cert-details">
                                <p><strong>This could mean:</strong></p>
                                <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                                    <li>The certificate number was entered incorrectly</li>
                                    <li>The certificate was not issued by MTTI</li>
                                    <li>The certificate may be fraudulent</li>
                                </ul>
                                <p style="margin-top: 15px;">Please double-check and try again, or contact MTTI for assistance.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="mtti-help-box">
                    <strong>📌 How to Verify</strong>
                    <p style="margin: 10px 0;">You can verify any MTTI certificate using:</p>
                    <ul>
                        <li><strong>Certificate Number</strong> - Found at bottom of certificate (e.g., MTTI/CERT/2025/123456)</li>
                        <li><strong>Verification Code</strong> - Found on certificate (e.g., ABCD-EFGH-JKLM)</li>
                        <li><strong>QR Code</strong> - Scan with your phone for instant verification</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
