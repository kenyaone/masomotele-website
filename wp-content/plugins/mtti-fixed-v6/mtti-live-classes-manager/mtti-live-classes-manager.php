<?php
/**
 * Plugin Name: MTTI Live Classes Manager
 * Plugin URI: https://masomoteletraining.co.ke
 * Description: Admin panel to schedule and manage live online classes using the existing wp_mtti_live_classes table.
 * Version: 2.0.0
 * Author: M.T.T.I
 * Author URI: https://masomoteletraining.co.ke
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTTI_Live_Classes_Manager {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mtti_live_classes';
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_styles'));
    }
    
    /**
     * Admin Styles
     */
    public function admin_styles($hook) {
        if (strpos($hook, 'mtti-live') === false) return;
        
        echo '<style>
            .mtti-admin-wrap { max-width: 1200px; }
            .mtti-stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0; }
            .mtti-stat-box { background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #2E7D32; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .mtti-stat-box.live { border-color: #dc3232; background: #fef7f7; }
            .mtti-stat-box.scheduled { border-color: #0073aa; }
            .mtti-stat-box.completed { border-color: #46b450; }
            .mtti-stat-value { font-size: 32px; font-weight: 700; line-height: 1; color: #1d2327; }
            .mtti-stat-label { color: #646970; font-size: 13px; margin-top: 5px; }
            .mtti-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            @media (max-width: 782px) { .mtti-form-grid { grid-template-columns: 1fr; } }
            .status-live, .status-Live { background: #dc3232; color: #fff; }
            .status-scheduled, .status-Scheduled { background: #0073aa; color: #fff; }
            .status-completed, .status-Completed, .status-Ended { background: #46b450; color: #fff; }
            .status-cancelled, .status-Cancelled { background: #646970; color: #fff; }
            .mtti-status-badge { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-block; }
            .mtti-link-box { background: #f0f6fc; border: 1px solid #c3c4c7; padding: 10px; border-radius: 4px; word-break: break-all; }
        </style>';
    }
    
    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Live Classes',
            'Live Classes',
            'edit_posts',
            'mtti-live-classes',
            array($this, 'page_dashboard'),
            'dashicons-video-alt2',
            26
        );
        
        add_submenu_page(
            'mtti-live-classes',
            'All Classes',
            'All Classes',
            'edit_posts',
            'mtti-live-classes',
            array($this, 'page_dashboard')
        );
        
        add_submenu_page(
            'mtti-live-classes',
            'Schedule Class',
            'Schedule Class',
            'edit_posts',
            'mtti-live-schedule',
            array($this, 'page_schedule')
        );
    }
    
    /**
     * Generate Jitsi Room Name
     */
    private function generate_room_name($title) {
        return 'mtti-' . sanitize_title($title) . '-' . substr(md5(time() . rand()), 0, 6);
    }
    
    /**
     * Dashboard Page
     */
    public function page_dashboard() {
        global $wpdb;
        $table = $this->table_name;
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            $id = intval($_GET['id']);
            $action = sanitize_key($_GET['action']);
            
            if (wp_verify_nonce($_GET['_wpnonce'], 'class_action_' . $id)) {
                switch ($action) {
                    case 'start':
                        $wpdb->update($table, array('status' => 'Live'), array('class_id' => $id));
                        echo '<div class="notice notice-success"><p>✅ Class started! Students can now join.</p></div>';
                        break;
                    case 'end':
                        $wpdb->update($table, array('status' => 'Ended'), array('class_id' => $id));
                        echo '<div class="notice notice-success"><p>✅ Class ended.</p></div>';
                        break;
                    case 'cancel':
                        $wpdb->update($table, array('status' => 'Cancelled'), array('class_id' => $id));
                        echo '<div class="notice notice-warning"><p>Class cancelled.</p></div>';
                        break;
                    case 'delete':
                        $wpdb->delete($table, array('class_id' => $id));
                        echo '<div class="notice notice-warning"><p>Class deleted.</p></div>';
                        break;
                }
            }
        }
        
        // Get stats
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}") ?: 0;
        $live = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'Live'") ?: 0;
        $scheduled = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'Scheduled'") ?: 0;
        $completed = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status IN ('Ended', 'Completed')") ?: 0;
        
        // Get classes with staff info
        $staff_table = $wpdb->prefix . 'mtti_staff';
        $courses_table = $wpdb->prefix . 'mtti_courses';
        
        $classes = $wpdb->get_results(
            "SELECT c.*, 
                    s.full_name as instructor_name,
                    co.course_name
             FROM {$table} c 
             LEFT JOIN {$staff_table} s ON c.staff_id = s.staff_id
             LEFT JOIN {$courses_table} co ON c.course_id = co.course_id
             ORDER BY c.scheduled_date DESC
             LIMIT 50"
        );
        
        ?>
        <div class="wrap mtti-admin-wrap">
            <h1 class="wp-heading-inline">🎥 Live Classes</h1>
            <a href="<?php echo admin_url('admin.php?page=mtti-live-schedule'); ?>" class="page-title-action">Schedule New Class</a>
            <hr class="wp-header-end">
            
            <!-- Stats -->
            <div class="mtti-stats-row">
                <div class="mtti-stat-box">
                    <div class="mtti-stat-value"><?php echo $total; ?></div>
                    <div class="mtti-stat-label">Total Classes</div>
                </div>
                <div class="mtti-stat-box live">
                    <div class="mtti-stat-value"><?php echo $live; ?></div>
                    <div class="mtti-stat-label">🔴 Live Now</div>
                </div>
                <div class="mtti-stat-box scheduled">
                    <div class="mtti-stat-value"><?php echo $scheduled; ?></div>
                    <div class="mtti-stat-label">Scheduled</div>
                </div>
                <div class="mtti-stat-box completed">
                    <div class="mtti-stat-value"><?php echo $completed; ?></div>
                    <div class="mtti-stat-label">Completed</div>
                </div>
            </div>
            
            <!-- Classes Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 22%;">Class</th>
                        <th>Course</th>
                        <th>Instructor</th>
                        <th>Date/Time</th>
                        <th>Platform</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="7">
                            No classes found. 
                            <a href="<?php echo admin_url('admin.php?page=mtti-live-schedule'); ?>">Schedule your first class!</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($classes as $class): 
                        $nonce = wp_create_nonce('class_action_' . $class->class_id);
                        $status_lower = strtolower($class->status);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($class->title); ?></strong>
                            <?php if ($class->batch_number): ?>
                            <br><small style="color: #666;">Batch: <?php echo esc_html($class->batch_number); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($class->course_name ?: 'All'); ?></td>
                        <td><?php echo esc_html($class->instructor_name ?: 'N/A'); ?></td>
                        <td>
                            <?php echo date('M j, Y', strtotime($class->scheduled_date)); ?><br>
                            <small><?php echo date('g:i A', strtotime($class->scheduled_date)); ?></small>
                        </td>
                        <td><?php echo esc_html($class->meeting_platform ?: 'Jitsi'); ?></td>
                        <td>
                            <span class="mtti-status-badge status-<?php echo $status_lower; ?>">
                                <?php echo $status_lower === 'live' ? '🔴 LIVE' : esc_html($class->status); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($status_lower === 'scheduled'): ?>
                                <a href="?page=mtti-live-classes&action=start&id=<?php echo $class->class_id; ?>&_wpnonce=<?php echo $nonce; ?>" 
                                   class="button button-primary button-small"
                                   onclick="return confirm('Start this class now?');">▶ Start</a>
                            <?php elseif ($status_lower === 'live'): ?>
                                <?php if ($class->meeting_link): ?>
                                <a href="<?php echo esc_url($class->meeting_link); ?>" 
                                   class="button button-primary button-small"
                                   target="_blank"
                                   style="background: #4CAF50; border-color: #4CAF50;">🎥 Join</a>
                                <?php endif; ?>
                                <a href="?page=mtti-live-classes&action=end&id=<?php echo $class->class_id; ?>&_wpnonce=<?php echo $nonce; ?>" 
                                   class="button button-small"
                                   style="background: #dc3232; color: #fff; border-color: #dc3232;"
                                   onclick="return confirm('End this class?');">⏹ End</a>
                            <?php endif; ?>
                            <a href="?page=mtti-live-classes&action=delete&id=<?php echo $class->class_id; ?>&_wpnonce=<?php echo $nonce; ?>" 
                               class="button button-small"
                               style="color: #a00;"
                               onclick="return confirm('Delete this class permanently?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Help Box -->
            <div style="margin-top: 30px; padding: 20px; background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px;">
                <h3 style="margin-top: 0;">💡 How It Works</h3>
                <ol style="margin-bottom: 0;">
                    <li><strong>Schedule</strong> a class with date, time, and meeting link</li>
                    <li><strong>Start</strong> the class when ready (status changes to LIVE)</li>
                    <li>Students see the class in <strong>Learner Portal → 🎥 Live Classes</strong></li>
                    <li>Click <strong>🎥 Join</strong> to open the meeting</li>
                    <li><strong>End</strong> the class when finished</li>
                </ol>
                <p style="margin-top: 15px; margin-bottom: 0;"><strong>Tip:</strong> For unlimited free video calls, use Jitsi direct links like: <code>https://meet.jit.si/mtti-your-class-name</code></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Schedule Class Page
     */
    public function page_schedule() {
        global $wpdb;
        
        // Handle form submission
        if (isset($_POST['mtti_schedule_class']) && wp_verify_nonce($_POST['_wpnonce'], 'mtti_schedule_class')) {
            $title = sanitize_text_field($_POST['title']);
            $platform = sanitize_text_field($_POST['meeting_platform']);
            
            // Generate Jitsi link if platform is Jitsi
            $meeting_link = sanitize_url($_POST['meeting_link']);
            if ($platform === 'Jitsi' && empty($meeting_link)) {
                $room_name = $this->generate_room_name($title);
                $meeting_link = 'https://meet.jit.si/' . $room_name;
            }
            
            $data = array(
                'course_id' => intval($_POST['course_id']) ?: 0,
                'staff_id' => intval($_POST['staff_id']) ?: 0,
                'title' => $title,
                'description' => sanitize_textarea_field($_POST['description']),
                'meeting_link' => $meeting_link,
                'meeting_platform' => $platform,
                'meeting_id' => sanitize_text_field($_POST['meeting_id']),
                'meeting_password' => sanitize_text_field($_POST['meeting_password']),
                'scheduled_date' => sanitize_text_field($_POST['scheduled_date']),
                'duration_minutes' => intval($_POST['duration_minutes']) ?: 60,
                'batch_number' => sanitize_text_field($_POST['batch_number']),
                'status' => 'Scheduled'
            );
            
            $wpdb->insert($this->table_name, $data);
            
            echo '<div class="notice notice-success"><p>✅ Class "<strong>' . esc_html($title) . '</strong>" scheduled successfully!</p>';
            if ($platform === 'Jitsi') {
                echo '<p><strong>Meeting Link:</strong> <a href="' . esc_url($meeting_link) . '" target="_blank">' . esc_html($meeting_link) . '</a></p>';
            }
            echo '</div>';
        }
        
        // Get courses
        $courses = $wpdb->get_results("SELECT course_id, course_name, course_code FROM {$wpdb->prefix}mtti_courses WHERE status = 'Active' ORDER BY course_name");
        
        // Get staff/instructors
        $staff = $wpdb->get_results("SELECT staff_id, full_name, designation FROM {$wpdb->prefix}mtti_staff WHERE status = 'Active' ORDER BY full_name");
        
        ?>
        <div class="wrap mtti-admin-wrap">
            <h1>📅 Schedule New Class</h1>
            
            <form method="post" style="max-width: 800px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
                <?php wp_nonce_field('mtti_schedule_class'); ?>
                
                <div class="mtti-form-grid">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Class Title <span style="color: #dc3232;">*</span></label>
                        <input type="text" name="title" required class="regular-text" style="width: 100%;" placeholder="e.g., Introduction to Web Development">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Course</label>
                        <select name="course_id" class="regular-text" style="width: 100%;">
                            <option value="0">-- All Students --</option>
                            <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course->course_id; ?>">
                                <?php echo esc_html($course->course_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mtti-form-grid" style="margin-top: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Instructor</label>
                        <select name="staff_id" class="regular-text" style="width: 100%;">
                            <option value="0">-- Select Instructor --</option>
                            <?php foreach ($staff as $s): ?>
                            <option value="<?php echo $s->staff_id; ?>">
                                <?php echo esc_html($s->full_name); ?> 
                                <?php if ($s->designation): ?>(<?php echo esc_html($s->designation); ?>)<?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Batch / Group</label>
                        <input type="text" name="batch_number" class="regular-text" style="width: 100%;" placeholder="e.g., 2025-A">
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Description</label>
                    <textarea name="description" rows="3" class="large-text" placeholder="What will be covered in this class?"></textarea>
                </div>
                
                <hr style="margin: 25px 0; border: none; border-top: 1px solid #ddd;">
                <h3 style="margin: 0 0 15px;">📹 Meeting Details</h3>
                
                <div class="mtti-form-grid">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Platform</label>
                        <select name="meeting_platform" id="meeting_platform" class="regular-text" style="width: 100%;">
                            <option value="Jitsi">Jitsi Meet (Free, Unlimited)</option>
                            <option value="Zoom">Zoom</option>
                            <option value="Google Meet">Google Meet</option>
                            <option value="Microsoft Teams">Microsoft Teams</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Meeting Link</label>
                        <input type="url" name="meeting_link" id="meeting_link" class="regular-text" style="width: 100%;" placeholder="Auto-generated for Jitsi, or paste your link">
                        <p class="description">Leave empty for Jitsi - a link will be auto-generated.</p>
                    </div>
                </div>
                
                <div class="mtti-form-grid" style="margin-top: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Meeting ID (Optional)</label>
                        <input type="text" name="meeting_id" class="regular-text" style="width: 100%;" placeholder="For Zoom/Teams">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Meeting Password (Optional)</label>
                        <input type="text" name="meeting_password" class="regular-text" style="width: 100%;" placeholder="For Zoom/Teams">
                    </div>
                </div>
                
                <hr style="margin: 25px 0; border: none; border-top: 1px solid #ddd;">
                <h3 style="margin: 0 0 15px;">🕐 Schedule</h3>
                
                <div class="mtti-form-grid">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Date & Time <span style="color: #dc3232;">*</span></label>
                        <input type="datetime-local" name="scheduled_date" required class="regular-text" style="width: 100%;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Duration (minutes)</label>
                        <input type="number" name="duration_minutes" value="60" min="15" max="480" class="regular-text" style="width: 100%;">
                    </div>
                </div>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <button type="submit" name="mtti_schedule_class" class="button button-primary button-large">
                        📅 Schedule Class
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=mtti-live-classes'); ?>" class="button button-large">Cancel</a>
                </div>
            </form>
            
            <!-- Jitsi Info -->
            <div style="max-width: 800px; margin-top: 25px; padding: 20px; background: #e7f6e7; border: 1px solid #46b450; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #2e7d32;">✅ Why Jitsi Meet?</h3>
                <ul style="margin-bottom: 0;">
                    <li>✓ <strong>Free</strong> - No subscription needed</li>
                    <li>✓ <strong>Unlimited duration</strong> - No 40-minute limits</li>
                    <li>✓ <strong>No account required</strong> - Students just click the link</li>
                    <li>✓ <strong>HD Video & Audio</strong> - Screen sharing, chat included</li>
                </ul>
            </div>
        </div>
        <?php
    }
}

// Initialize
add_action('plugins_loaded', function() {
    MTTI_Live_Classes_Manager::get_instance();
});
