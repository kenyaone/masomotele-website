<?php
/**
 * Plugin Name: SEO Recommender Pro
 * Plugin URI: https://masomotele.ke
 * Description: Complete SEO analysis with detailed recommendations for posts, pages, and custom post types
 * Version: 2.3.0
 * Author: Masomotele Institute
 * Author URI: https://masomotele.ke
 * License: GPL v2 or later
 * Text Domain: seo-recommender
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SEO_RECOMMENDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEO_RECOMMENDER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SEO_RECOMMENDER_VERSION', '2.3.0');

class SEORecommenderPro {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_analyze_post', array($this, 'analyze_post'));
        add_action('wp_ajax_get_recommendations', array($this, 'get_recommendations'));
        add_action('wp_ajax_get_posts_by_type', array($this, 'get_posts_by_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_data'));
        add_filter('manage_posts_columns', array($this, 'add_posts_columns'));
        add_filter('manage_pages_columns', array($this, 'add_posts_columns'));
        add_action('manage_posts_custom_column', array($this, 'display_posts_column'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'display_posts_column'), 10, 2);
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('seo-recommender', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'SEO Recommender Pro',
            'SEO Recommender',
            'manage_options',
            'seo-recommender',
            array($this, 'display_dashboard'),
            'dashicons-chart-bar',
            25
        );
        
        add_submenu_page(
            'seo-recommender',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'seo-recommender',
            array($this, 'display_dashboard')
        );
        
        add_submenu_page(
            'seo-recommender',
            'Analyze Content',
            'Analyze Content',
            'manage_options',
            'seo-recommender-analyze',
            array($this, 'display_analyze_page')
        );
        
        add_submenu_page(
            'seo-recommender',
            'SEO Elements Guide',
            'SEO Guide',
            'manage_options',
            'seo-recommender-guide',
            array($this, 'display_seo_guide')
        );
        
        add_submenu_page(
            'seo-recommender',
            'Settings',
            'Settings',
            'manage_options',
            'seo-recommender-settings',
            array($this, 'display_settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'seo-recommender') === false) {
            return;
        }
        
        wp_enqueue_style(
            'seo-recommender-style',
            SEO_RECOMMENDER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SEO_RECOMMENDER_VERSION
        );
        
        wp_enqueue_script(
            'seo-recommender-script',
            SEO_RECOMMENDER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SEO_RECOMMENDER_VERSION,
            true
        );
        
        wp_localize_script(
            'seo-recommender-script',
            'seoRecommender',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('seo-recommender-nonce')
            )
        );
    }
    
    public function display_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'seo-recommender'));
        }
        
        $posts = get_posts(array(
            'post_type'      => array('post', 'page'),
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
        ));
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="seo-dashboard">
                <h2><?php _e('SEO Health Overview', 'seo-recommender'); ?></h2>
                
                <div class="seo-stats">
                    <div class="stat-box">
                        <h3><?php echo count($posts); ?></h3>
                        <p>Total Posts & Pages</p>
                    </div>
                    
                    <div class="stat-box">
                        <h3><?php echo $this->count_optimized(); ?></h3>
                        <p>Well Optimized (70%+)</p>
                    </div>
                    
                    <div class="stat-box">
                        <h3><?php echo $this->get_average_seo_score(); ?>%</h3>
                        <p>Average SEO Score</p>
                    </div>
                </div>
                
                <h2><?php _e('Recent Content Analysis', 'seo-recommender'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'seo-recommender'); ?></th>
                            <th><?php _e('Type', 'seo-recommender'); ?></th>
                            <th><?php _e('SEO Score', 'seo-recommender'); ?></th>
                            <th><?php _e('Issues', 'seo-recommender'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><a href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo esc_html($post->post_title ?: '(No title)'); ?></a></td>
                                <td><?php echo esc_html(ucfirst($post->post_type)); ?></td>
                                <td><?php echo $this->get_seo_score($post->ID); ?>%</td>
                                <td><?php echo $this->count_issues($post->ID); ?> issues</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function display_analyze_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'seo-recommender'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="seo-analyze">
                <div class="analyze-selector">
                    <label for="post-select"><?php _e('Select Post or Page to Analyze:', 'seo-recommender'); ?></label>
                    <select id="post-select" style="margin-right: 10px; margin-bottom: 10px; min-width: 320px;">
                        <option value=""><?php _e('-- Choose content --', 'seo-recommender'); ?></option>
                        <?php
                        $statuses = array('publish', 'draft', 'pending', 'future', 'private');

                        $all_posts = get_posts(array(
                            'posts_per_page' => -1,
                            'post_type'      => 'post',
                            'post_status'    => $statuses,
                            'orderby'        => 'title',
                            'order'          => 'ASC',
                        ));

                        $all_pages = get_posts(array(
                            'posts_per_page' => -1,
                            'post_type'      => 'page',
                            'post_status'    => $statuses,
                            'orderby'        => 'title',
                            'order'          => 'ASC',
                        ));

                        $render_options = function($items) {
                            foreach ($items as $item) {
                                $label = $item->post_title ? $item->post_title : '(No title)';
                                if ($item->post_status !== 'publish') {
                                    $label .= ' [' . $item->post_status . ']';
                                }
                                echo '<option value="' . esc_attr($item->ID) . '">' . esc_html($label) . '</option>';
                            }
                        };

                        if (!empty($all_posts)) {
                            echo '<optgroup label="' . esc_attr__('Posts', 'seo-recommender') . ' (' . count($all_posts) . ')">';
                            $render_options($all_posts);
                            echo '</optgroup>';
                        }

                        if (!empty($all_pages)) {
                            echo '<optgroup label="' . esc_attr__('Pages', 'seo-recommender') . ' (' . count($all_pages) . ')">';
                            $render_options($all_pages);
                            echo '</optgroup>';
                        }

                        if (empty($all_posts) && empty($all_pages)) {
                            echo '<option value="">' . esc_html__('No posts or pages found - create one first', 'seo-recommender') . '</option>';
                        }
                        ?>
                    </select>

                    <button class="button button-primary" id="analyze-btn"><?php _e('Analyze', 'seo-recommender'); ?></button>

                    <p class="description" style="margin-top: 10px;">
                        <?php printf(
                            esc_html__('Found %1$d posts and %2$d pages (including drafts).', 'seo-recommender'),
                            count($all_posts),
                            count($all_pages)
                        ); ?>
                    </p>
                </div>
                
                <div id="recommendations-container" class="hidden">
                    <h2><?php _e('Detailed SEO Recommendations', 'seo-recommender'); ?></h2>
                    <div id="recommendations-list"></div>
                </div>
                
                <div id="loading" class="hidden">
                    <p><?php _e('Analyzing content...', 'seo-recommender'); ?></p>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#analyze-btn').click(function() {
                    var postId = $('#post-select').val();
                    if (!postId) {
                        alert('<?php _e('Please select content to analyze', 'seo-recommender'); ?>');
                        return;
                    }
                    
                    $('#loading').removeClass('hidden');
                    $('#recommendations-container').addClass('hidden');
                    
                    $.ajax({
                        url: seoRecommender.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'get_recommendations',
                            post_id: postId,
                            nonce: seoRecommender.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                displayRecommendations(response.data);
                            } else {
                                alert('<?php _e('Error analyzing content', 'seo-recommender'); ?>');
                            }
                            $('#loading').addClass('hidden');
                        }
                    });
                });
                
                function escapeHtml(s) {
                    return String(s == null ? '' : s)
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                }

                function displayRecommendations(recommendations) {
                    var html = '<div class="recommendations">';

                    if (recommendations.length === 0) {
                        html += '<p class="success">✓ Excellent! Your content is well optimized.</p>';
                    } else {
                        html += '<div class="issues-list">';
                        recommendations.forEach(function(rec) {
                            var icon = rec.severity === 'critical' ? '❌' :
                                       rec.severity === 'warning' ? '⚠️' : 'ℹ️';
                            html += '<div class="recommendation ' + escapeHtml(rec.severity) + '">';
                            html += '<p class="rec-title"><strong>' + icon + ' ' + escapeHtml(rec.title) + '</strong></p>';
                            html += '<p class="rec-issue"><strong><?php _e('Issue:', 'seo-recommender'); ?></strong> ' + escapeHtml(rec.message) + '</p>';
                            html += '<p class="rec-summary"><strong><?php _e('How to Fix:', 'seo-recommender'); ?></strong> ' + escapeHtml(rec.suggestion) + '</p>';
                            if (rec.steps && rec.steps.length) {
                                html += '<div class="rec-steps"><strong><?php _e('Step-by-step:', 'seo-recommender'); ?></strong><ol>';
                                rec.steps.forEach(function(step) {
                                    html += '<li>' + escapeHtml(step) + '</li>';
                                });
                                html += '</ol></div>';
                            }
                            if (rec.example) {
                                html += '<p class="rec-example"><strong><?php _e('Example:', 'seo-recommender'); ?></strong> <code>' + escapeHtml(rec.example) + '</code></p>';
                            }
                            html += '</div>';
                        });
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    $('#recommendations-list').html(html);
                    $('#recommendations-container').removeClass('hidden');
                }
            });
        </script>
        <?php
    }
    
    public function display_seo_guide() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'seo-recommender'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Complete SEO Elements Guide', 'seo-recommender'); ?></h1>
            
            <div class="seo-guide">
                <h2><?php _e('Essential SEO Elements & How to Add Them', 'seo-recommender'); ?></h2>
                
                <div class="seo-element">
                    <h3>1️⃣ Title Tag (SEO Title)</h3>
                    <p><strong><?php _e('What it is:', 'seo-recommender'); ?></strong> The main headline of your page that appears in browser tab and Google search results.</p>
                    <p><strong><?php _e('Ideal length:', 'seo-recommender'); ?></strong> 50-60 characters</p>
                    <p><strong><?php _e('How to add in WordPress:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Edit your post/page</li>
                        <li>The title field at the top is your title tag</li>
                        <li>Make it descriptive with keywords</li>
                        <li>Example: "Complete WordPress SEO Guide for 2024"</li>
                    </ol>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> People read this before clicking. It's one of the most important SEO elements.</p>
                </div>
                
                <div class="seo-element">
                    <h3>2️⃣ Meta Description</h3>
                    <p><strong><?php _e('What it is:', 'seo-recommender'); ?></strong> A 160-character summary that appears below your URL in Google results.</p>
                    <p><strong><?php _e('Ideal length:', 'seo-recommender'); ?></strong> 120-160 characters</p>
                    <p><strong><?php _e('How to add in WordPress:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Edit your post/page</li>
                        <li>Scroll down to find "SEO" or "Meta Description" field</li>
                        <li>Write a compelling summary of your content</li>
                        <li>Example: "Learn 10 proven WordPress SEO strategies to boost rankings. Complete guide covering optimization and link building."</li>
                    </ol>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> Directly affects click-through rate from search results.</p>
                </div>
                
                <div class="seo-element">
                    <h3>3️⃣ H1 Tag (Main Heading)</h3>
                    <p><strong><?php _e('What it is:', 'seo-recommender'); ?></strong> The biggest, most important heading on your page.</p>
                    <p><strong><?php _e('How many:', 'seo-recommender'); ?></strong> One per page only</p>
                    <p><strong><?php _e('How to add in WordPress:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Edit your post/page</li>
                        <li>Click in the first heading</li>
                        <li>Click the format dropdown (shows "Paragraph" by default)</li>
                        <li>Select "Heading 1"</li>
                        <li>This heading should match your title or be very similar</li>
                    </ol>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> Tells Google what your page is about. Essential for SEO.</p>
                </div>
                
                <div class="seo-element">
                    <h3>4️⃣ Focus Keyword</h3>
                    <p><strong><?php _e('What it is:', 'seo-recommender'); ?></strong> The main term you want to rank for (appears 1-3 times naturally in your content).</p>
                    <p><strong><?php _e('Where to place:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>In the title</li>
                        <li>In the H1 heading</li>
                        <li>In the first paragraph</li>
                        <li>In subheadings (H2, H3) naturally</li>
                        <li>Throughout content naturally</li>
                    </ol>
                    <p><strong><?php _e('How to add:', 'seo-recommender'); ?></strong> Simply write naturally and use your keyword when it makes sense. Don't force it.</p>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> Google needs to know what your page is about.</p>
                </div>
                
                <div class="seo-element">
                    <h3>5️⃣ Content & Word Count</h3>
                    <p><strong><?php _e('Recommended:', 'seo-recommender'); ?></strong> 800-2000+ words for best rankings</p>
                    <p><strong><?php _e('Minimum:', 'seo-recommender'); ?></strong> 300 words</p>
                    <p><strong><?php _e('Structure your content:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>H1 Title at top</li>
                        <li>Introduction paragraph (50-100 words)</li>
                        <li>H2 subheadings for main points</li>
                        <li>H3 subheadings for sub-points</li>
                        <li>Regular paragraphs (2-3 sentences each)</li>
                        <li>Examples, screenshots, images</li>
                        <li>Conclusion paragraph</li>
                    </ol>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> Longer, comprehensive content ranks better than short content.</p>
                </div>
                
                <div class="seo-element">
                    <h3>6️⃣ Headings (H2, H3)</h3>
                    <p><strong><?php _e('What they are:', 'seo-recommender'); ?></strong> Subheadings that organize your content structure.</p>
                    <p><strong><?php _e('How to add:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Click in the heading text</li>
                        <li>Click format dropdown</li>
                        <li>Select "Heading 2" or "Heading 3"</li>
                        <li>Use for section titles</li>
                    </ol>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> Helps Google understand content structure and helps readers scan the page.</p>
                </div>
                
                <div class="seo-element">
                    <h3>7️⃣ Internal Links</h3>
                    <p><strong><?php _e('What they are:', 'seo-recommender'); ?></strong> Links to other pages on YOUR website.</p>
                    <p><strong><?php _e('How many:', 'seo-recommender'); ?></strong> 2-3 per post</p>
                    <p><strong><?php _e('How to add:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Highlight text in your content</li>
                        <li>Click the link button (chain icon)</li>
                        <li>Search for and select another post on your site</li>
                        <li>Make link text descriptive (not "click here")</li>
                    </ol>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> Spreads ranking power across your site and keeps readers engaged.</p>
                </div>
                
                <div class="seo-element">
                    <h3>8️⃣ Images & Alt Text</h3>
                    <p><strong><?php _e('How many images:', 'seo-recommender'); ?></strong> At least 1 per 300 words</p>
                    <p><strong><?php _e('How to add images:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Click "Add Image" button</li>
                        <li>Upload or select image</li>
                        <li>Fill in Alt Text field with descriptive text</li>
                        <li>Example: "WordPress SEO optimization chart showing on-page ranking factors"</li>
                    </ol>
                    <p><strong><?php _e('Alt Text Guidelines:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Describe what the image shows</li>
                        <li>Keep it 5-15 words</li>
                        <li>Include keyword naturally if it fits</li>
                        <li>Don't keyword stuff</li>
                    </ol>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> Google can't see images, so alt text tells it what they're about. Also helps accessibility.</p>
                </div>
                
                <div class="seo-element">
                    <h3>9️⃣ URL Slug (Permalink)</h3>
                    <p><strong><?php _e('What it is:', 'seo-recommender'); ?></strong> The part of your URL after the domain name.</p>
                    <p><strong><?php _e('How to set:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Edit your post/page</li>
                        <li>Look for "Permalink" section (usually below title)</li>
                        <li>Click "Edit" to change it</li>
                        <li>Use 3-5 short words separated by hyphens</li>
                        <li>Example: "wordpress-seo-guide"</li>
                    </ol>
                    <p><strong><?php _e('Best practices:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Use hyphens, not underscores</li>
                        <li>Use lowercase letters only</li>
                        <li>Keep it short (3-5 words)</li>
                        <li>Include main keyword</li>
                        <li>Make it descriptive and readable</li>
                    </ol>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> Appears in search results and helps Google understand the page topic.</p>
                </div>
                
                <div class="seo-element">
                    <h3>🔟 Readability & Structure</h3>
                    <p><strong><?php _e('Best practices:', 'seo-recommender'); ?></strong></p>
                    <ol>
                        <li>Short paragraphs (2-4 sentences)</li>
                        <li>Short sentences (under 20 words)</li>
                        <li>Use subheadings to break up text</li>
                        <li>Add images every 300 words</li>
                        <li>Use lists where appropriate</li>
                        <li>Bold important keywords naturally</li>
                        <li>Use bullet points for key takeaways</li>
                    </ol>
                    <p><strong><?php _e('Why it matters:', 'seo-recommender'); ?></strong> Improves user experience and tells Google your content is high quality.</p>
                </div>
            </div>
        </div>
        <style>
            .seo-guide { background: #fff; border: 1px solid #ccc; border-radius: 5px; padding: 20px; }
            .seo-element { 
                background: #f9f9f9; 
                border-left: 4px solid #0073aa; 
                padding: 15px; 
                margin-bottom: 20px; 
                border-radius: 3px;
            }
            .seo-element h3 { color: #0073aa; margin-top: 0; }
            .seo-element ol { padding-left: 20px; }
            .seo-element li { margin-bottom: 8px; }
        </style>
        <?php
    }
    
    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'seo-recommender'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="seo-settings">
                <h2><?php _e('SEO Recommender Settings', 'seo-recommender'); ?></h2>
                
                <form method="post" action="options.php">
                    <?php settings_fields('seo-recommender-settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="min-title-length"><?php _e('Minimum Title Length', 'seo-recommender'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="min-title-length" name="seo_min_title_length" value="<?php echo get_option('seo_min_title_length', 30); ?>" />
                                <p class="description"><?php _e('Recommended: 50-60 characters', 'seo-recommender'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="min-description-length"><?php _e('Minimum Meta Description Length', 'seo-recommender'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="min-description-length" name="seo_min_description_length" value="<?php echo get_option('seo_min_description_length', 120); ?>" />
                                <p class="description"><?php _e('Recommended: 120-160 characters', 'seo-recommender'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="min-content-length"><?php _e('Minimum Content Length', 'seo-recommender'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="min-content-length" name="seo_min_content_length" value="<?php echo get_option('seo_min_content_length', 300); ?>" />
                                <p class="description"><?php _e('Recommended: 800+ words for best rankings', 'seo-recommender'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    public function get_recommendations() {
        check_ajax_referer('seo-recommender-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error('Content not found');
        }

        $recommendations = $this->analyze_content($post);
        wp_send_json_success($recommendations);
    }

    public function get_posts_by_type() {
        check_ajax_referer('seo-recommender-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : 'post';
        if (!in_array($post_type, array('post', 'page'), true)) {
            $post_type = 'post';
        }

        $items = get_posts(array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));

        $data = array();
        foreach ($items as $item) {
            $label = $item->post_title ? $item->post_title : '(No title)';
            if ($item->post_status !== 'publish') {
                $label .= ' [' . $item->post_status . ']';
            }
            $data[] = array(
                'ID'         => $item->ID,
                'post_title' => $label,
            );
        }

        wp_send_json_success($data);
    }
    
    public function analyze_content($post) {
        $recommendations = array();

        $title            = $post->post_title;
        $content          = $post->post_content;
        $excerpt          = $post->post_excerpt;
        $slug             = $post->post_name;
        $meta_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true)
                         ?: get_post_meta($post->ID, '_seo_description', true)
                         ?: $excerpt;

        // 1. Title too short
        $min_title = get_option('seo_min_title_length', 50);
        if (strlen($title) < $min_title) {
            $recommendations[] = array(
                'title'      => 'Title Too Short',
                'message'    => sprintf('Your title is only %d characters. Search engines prefer 50-60 characters.', strlen($title)),
                'suggestion' => 'Expand your title to 50-60 characters using [Main Keyword] + [Benefit or Number] + [Year or Modifier].',
                'steps'      => array(
                    'In the WordPress editor, click the large "Add title" field at the very top of the page.',
                    'Delete the current title.',
                    'Rewrite it using the formula: [Main Keyword] + [Benefit / Number / Year] + [Brand or Modifier].',
                    'Aim for 50-60 characters - long enough to be descriptive, short enough that Google won\'t cut it off.',
                    'Click the blue "Update" (or "Publish") button in the top-right corner to save.',
                ),
                'example'    => 'SEO Tips  →  10 Proven WordPress SEO Tips to Rank Higher in 2024',
                'severity'   => 'warning',
            );
        }

        // 2. Title too long
        if (strlen($title) > 70) {
            $recommendations[] = array(
                'title'      => 'Title Too Long',
                'message'    => sprintf('Your title is %d characters. This is too long for Google to display fully.', strlen($title)),
                'suggestion' => 'Trim your title to 50-60 characters. Keep the main keyword at the start.',
                'steps'      => array(
                    'Click the title field at the top of the WordPress editor.',
                    'Remove filler words first: "the", "a", "very", "really", "how to" (if not needed), "guide to".',
                    'Move your main keyword to the START of the title - Google weights the first words more.',
                    'Cut down to 50-60 characters total.',
                    'Click Update / Publish to save.',
                ),
                'example'    => 'The Complete Ultimate Guide on How to Optimize Your WordPress Site for SEO (74)  →  WordPress SEO: Complete Optimization Guide (43)',
                'severity'   => 'warning',
            );
        }

        // 3. Missing / short meta description
        $min_desc = get_option('seo_min_description_length', 120);
        if (strlen($meta_description) < $min_desc) {
            $recommendations[] = array(
                'title'      => 'Missing or Short Meta Description',
                'message'    => 'Your meta description is missing or too short.',
                'suggestion' => 'Add a 120-160 character summary in the SEO plugin\'s Meta Description field (or the Excerpt field if you have no SEO plugin).',
                'steps'      => array(
                    'In the WordPress editor sidebar, look for a Yoast SEO / Rank Math / All in One SEO panel - open it.',
                    'If you have no SEO plugin, open the "Excerpt" panel in the sidebar. If it\'s hidden: click the three-dot menu (top-right) → Preferences → Panels → enable "Excerpt".',
                    'Click into the Meta Description (or Excerpt) field.',
                    'Write 120-160 characters using: [What the reader gets] + [Why it matters] + [Call to action].',
                    'Click Update / Publish to save.',
                ),
                'example'    => 'Learn 10 proven WordPress SEO tactics used by top-ranking sites. Step-by-step guide covering keywords, links, and speed. Start ranking today.',
                'severity'   => 'critical',
            );
        }

        // 4. Meta description too long
        if (strlen($meta_description) > 160) {
            $recommendations[] = array(
                'title'      => 'Meta Description Too Long',
                'message'    => sprintf('Your meta description is %d characters. Google will cut it off.', strlen($meta_description)),
                'suggestion' => 'Trim your description to 120-160 characters.',
                'steps'      => array(
                    'Open the SEO plugin panel (Yoast / Rank Math / All in One SEO) or the Excerpt panel in the editor sidebar.',
                    'Click into the Meta Description / Excerpt field.',
                    'Remove filler and repetition. Keep only the value proposition + one action.',
                    'Aim for 120-160 characters - use the character counter shown by your SEO plugin.',
                    'Click Update / Publish.',
                ),
                'example'    => 'Learn 10 proven WordPress SEO tactics used by top-ranking sites. Step-by-step guide covering keywords, links, and speed. Start ranking today. (156)',
                'severity'   => 'warning',
            );
        }

        // 5. Missing H1
        if (strpos($content, '<h1') === false) {
            $recommendations[] = array(
                'title'      => 'Missing H1 Tag',
                'message'    => 'Your content does not have an H1 heading.',
                'suggestion' => 'Add exactly one H1 at the top of your content that mirrors (or closely matches) your title.',
                'steps'      => array(
                    'Open the post/page in the WordPress editor.',
                    'Click at the very top of the content area, just below the title.',
                    'Click the "+" button → search for "Heading" → insert a Heading block.',
                    'In the block toolbar that appears above the heading, click the "H2" button and change it to "H1".',
                    'Type your main heading - use words very similar to your title.',
                    'Click Update / Publish.',
                ),
                'example'    => 'H1: WordPress SEO: Complete Optimization Guide',
                'severity'   => 'critical',
            );
        }

        // 6. Content too short
        $word_count  = str_word_count(strip_tags($content));
        $min_content = get_option('seo_min_content_length', 300);
        if ($word_count < $min_content) {
            $recommendations[] = array(
                'title'      => 'Content Too Short',
                'message'    => sprintf('Your content has only %d words. Google prefers at least 300, ideally 800+.', $word_count),
                'suggestion' => 'Expand to 800+ words with an intro, 3-5 H2 sections, and a conclusion.',
                'steps'      => array(
                    'Below the title, add an intro paragraph (50-100 words) answering "what is this post about and why should I read it?".',
                    'Add 3-5 H2 sections. Click "+" → Heading block → change level to H2 in the block toolbar → write a section title.',
                    'Under each H2, write 150-200 words of explanation plus one concrete example, screenshot, or list.',
                    'Where you have multiple sub-points under one H2, add H3 headings.',
                    'End with a "Conclusion" H2 section (80-120 words) summarizing takeaways and next steps.',
                    'Check word count via the "i" info icon in the top-left of the editor (or three-dot menu → "Content structure"). Target: 800+.',
                ),
                'example'    => 'H1 Title  →  Intro (80w)  →  H2 Why it matters (180w)  →  H2 Step 1 (200w)  →  H2 Step 2 (200w)  →  H2 Common mistakes (170w)  →  H2 Conclusion (100w)',
                'severity'   => 'warning',
            );
        }

        // 7. No internal links
        $internal_links = substr_count($content, home_url());
        if ($internal_links === 0) {
            $recommendations[] = array(
                'title'      => 'No Internal Links',
                'message'    => 'Your content has no links to other pages on your site.',
                'suggestion' => 'Add 2-3 internal links to related posts or pages on your own site.',
                'steps'      => array(
                    'Pick a sentence in your content where mentioning another article makes sense.',
                    'Highlight 3-5 words of descriptive text (NOT the phrase "click here").',
                    'Click the link icon in the block toolbar (looks like a chain).',
                    'Start typing the title of another post/page on your site - WordPress will search and suggest matches.',
                    'Click the correct result to insert the link, then press Enter.',
                    'Repeat 1-2 more times in different sections. Click Update / Publish.',
                ),
                'example'    => 'Instead of "click here to learn more", use "read our complete WordPress SEO guide".',
                'severity'   => 'warning',
            );
        }

        // 8. No images
        $images = substr_count($content, '<img');
        if ($images === 0) {
            $recommendations[] = array(
                'title'      => 'No Images',
                'message'    => 'Your content has no images.',
                'suggestion' => 'Add at least one image per 300 words. Always fill in the Alt Text field.',
                'steps'      => array(
                    'Click where you want the image, then click the "+" button and choose "Image".',
                    'Upload from your computer, drag-and-drop, or select from the Media Library.',
                    'After the image appears, look at the block sidebar on the right for the "Alt text" field.',
                    'Write a 5-15 word description of what the image shows (include your keyword if it fits naturally).',
                    'Click Update / Publish.',
                ),
                'example'    => 'Alt text: "WordPress SEO dashboard showing the on-page ranking score for a blog post"',
                'severity'   => 'info',
            );
        }

        // 9. Missing alt text
        if ($images > 0) {
            preg_match_all('/<img[^>]+>/i', $content, $img_tags);
            $missing_alt = 0;
            foreach ($img_tags[0] as $tag) {
                if (strpos($tag, 'alt=') === false || preg_match('/alt=(""|\'\')/', $tag)) {
                    $missing_alt++;
                }
            }
            if ($missing_alt > 0) {
                $recommendations[] = array(
                    'title'      => 'Missing Image Alt Text',
                    'message'    => sprintf('%d of your images are missing alt text.', $missing_alt),
                    'suggestion' => 'Add alt text to every image. Alt text helps Google understand the image and helps screen readers for accessibility.',
                    'steps'      => array(
                        'Scroll through your post and find each image that has no alt text.',
                        'Click on the image once to select it.',
                        'On the right sidebar, find the "Alt text (alternative text)" field.',
                        'Type a 5-15 word description of what the image actually shows.',
                        'Move to the next image and repeat.',
                        'Click Update / Publish when done.',
                    ),
                    'example'    => 'Bad: alt=""   Good: alt="Bar chart showing traffic growth of 40% after SEO changes"',
                    'severity'   => 'warning',
                );
            }
        }

        // 10. Bad URL slug
        if (strlen($slug) > 50 || strpos($slug, '_') !== false) {
            $recommendations[] = array(
                'title'      => 'URL Slug Issues',
                'message'    => 'Your URL slug is too long or uses underscores instead of hyphens.',
                'suggestion' => 'Rewrite the slug as 3-5 short lowercase words separated by hyphens, including the main keyword.',
                'steps'      => array(
                    'In the WordPress editor sidebar, open the "Summary" or "Post" panel.',
                    'Find the "URL" or "Permalink" section - it shows the slug part of your URL.',
                    'Click on the slug text to edit it.',
                    'Replace it with 3-5 lowercase words separated by hyphens (never underscores or spaces).',
                    'Include your main keyword.',
                    'Click Update / Publish. WARNING: if the post is already published, changing the slug breaks any existing links to it.',
                ),
                'example'    => 'Bad: my_new_post_about_wordpress_seo_2024   Good: wordpress-seo-guide',
                'severity'   => 'info',
            );
        }

        // 11. No H2 structure on long content
        $h2_count = substr_count($content, '<h2');
        if ($h2_count === 0 && $word_count > 500) {
            $recommendations[] = array(
                'title'      => 'No Heading Structure',
                'message'    => 'Your long content has no H2 subheadings.',
                'suggestion' => 'Break your content into 3-5 sections with H2 headings, plus H3 sub-sections where needed.',
                'steps'      => array(
                    'Read through your content and identify 3-5 natural sections.',
                    'Click where you want a section title to appear.',
                    'Click the "+" button → Heading block.',
                    'In the block toolbar, change the level from H2 (it defaults to H2 - keep it).',
                    'Type the section title. Use action words: "Why...", "How to...", "Step 1:...".',
                    'For sub-points inside a section, insert another Heading block and set it to H3.',
                    'Click Update / Publish.',
                ),
                'example'    => 'H2 Why WordPress SEO Matters  →  H2 Step 1: Keyword Research  →  H3 Free Tools  →  H3 Paid Tools  →  H2 Conclusion',
                'severity'   => 'warning',
            );
        }

        // 12. Low keyword density
        $first_words = array_slice(explode(' ', strtolower($title)), 0, 2);
        $keyword = isset($first_words[0]) ? $first_words[0] : '';
        if (strlen($keyword) > 3) {
            $content_lower   = strtolower(strip_tags($content));
            $keyword_count   = substr_count($content_lower, $keyword);
            $keyword_density = ($keyword_count / max(1, $word_count)) * 100;

            if ($keyword_density < 0.5) {
                $recommendations[] = array(
                    'title'      => 'Low Keyword Density',
                    'message'    => sprintf('Your main keyword "%s" appears only %d times.', $keyword, $keyword_count),
                    'suggestion' => sprintf('Aim for "%s" to appear 3-8 times (about 0.5-1.5%% of word count). Place it in these exact spots.', $keyword),
                    'steps'      => array(
                        'Spot 1 - Title field: make sure "' . $keyword . '" is in the title, ideally near the start.',
                        'Spot 2 - Intro paragraph: click the first Paragraph block; rewrite so "' . $keyword . '" appears in the first 100 characters.',
                        'Spot 3 - One H2 heading: click an H2 Heading block and edit it to include "' . $keyword . '" naturally (e.g. "Why ' . $keyword . ' Matters").',
                        'Spot 4 - Featured image alt text: sidebar → Featured Image → Replace/Edit → fill the Alt Text field with a phrase containing "' . $keyword . '".',
                        'Spot 5 - Conclusion paragraph: end by restating "' . $keyword . '" in a natural summary sentence.',
                        'Verify: press Ctrl+F (Cmd+F on Mac) in the editor and search for "' . $keyword . '" to count occurrences. Target 3-8. Do NOT copy-paste - Google penalizes keyword stuffing.',
                    ),
                    'example'    => 'Title: "' . $keyword . ' Guide 2024"  →  Intro: "Learning ' . $keyword . ' takes..."  →  H2: "How ' . $keyword . ' works"  →  Alt: "' . $keyword . ' dashboard screenshot"  →  Conclusion: "Master ' . $keyword . ' by..."',
                    'severity'   => 'info',
                );
            }
        }

        return $recommendations;
    }
    
    public function analyze_post() {
        check_ajax_referer('seo-recommender-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if ($post) {
            $recommendations = $this->analyze_content($post);
            update_post_meta($post_id, '_seo_recommendations', $recommendations);
        }
        
        wp_send_json_success();
    }
    
    public function add_meta_box() {
        add_meta_box(
            'seo-recommender-metabox',
            'SEO Analysis & Recommendations',
            array($this, 'display_meta_box'),
            array('post', 'page'),
            'side'
        );
    }
    
    public function display_meta_box($post) {
        $score = $this->get_seo_score($post->ID);
        
        echo '<div class="seo-metabox">';
        echo '<p><strong>SEO Score:</strong> ' . $score . '%</p>';
        
        if ($score < 60) {
            echo '<p style="color: #ff9800; background: #fff3cd; padding: 10px; border-radius: 3px; border: 1px solid #ffc107;">⚠️ Your content needs SEO optimization</p>';
        } elseif ($score < 80) {
            echo '<p style="color: #666; background: #d1ecf1; padding: 10px; border-radius: 3px; border: 1px solid #bee5eb;">ℹ️ Consider making some improvements</p>';
        } else {
            echo '<p style="color: #28a745; background: #d4edda; padding: 10px; border-radius: 3px; border: 1px solid #c3e6cb;">✓ Your content is well optimized!</p>';
        }
        
        echo '<p><a href="' . admin_url('admin.php?page=seo-recommender-analyze') . '" class="button button-small">' . __('Full Analysis', 'seo-recommender') . '</a></p>';
        echo '</div>';
    }
    
    public function save_meta_data($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (get_post_type($post_id) === 'post' || get_post_type($post_id) === 'page') {
            $post = get_post($post_id);
            $recommendations = $this->analyze_content($post);
            update_post_meta($post_id, '_seo_recommendations', $recommendations);
        }
    }
    
    public function get_seo_score($post_id) {
        $post = get_post($post_id);
        $recommendations = $this->analyze_content($post);
        
        $score = 100;
        foreach ($recommendations as $rec) {
            if ($rec['severity'] === 'critical') {
                $score -= 20;
            } elseif ($rec['severity'] === 'warning') {
                $score -= 10;
            } else {
                $score -= 5;
            }
        }
        
        return max(0, min(100, $score));
    }
    
    public function count_issues($post_id) {
        $recommendations = get_post_meta($post_id, '_seo_recommendations', true);
        return is_array($recommendations) ? count($recommendations) : 0;
    }
    
    public function count_optimized() {
        $posts = get_posts(array(
            'posts_per_page' => -1,
            'post_type'      => array('post', 'page'),
            'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
        ));
        $optimized = 0;
        
        foreach ($posts as $post) {
            if ($this->get_seo_score($post->ID) >= 70) {
                $optimized++;
            }
        }
        
        return $optimized;
    }
    
    public function get_average_seo_score() {
        $posts = get_posts(array(
            'posts_per_page' => -1,
            'post_type'      => array('post', 'page'),
            'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
        ));
        
        if (empty($posts)) {
            return 0;
        }
        
        $total = 0;
        foreach ($posts as $post) {
            $total += $this->get_seo_score($post->ID);
        }
        
        return round($total / count($posts));
    }
    
    public function add_posts_columns($columns) {
        $columns['seo_score'] = 'SEO Score';
        return $columns;
    }
    
    public function display_posts_column($column, $post_id) {
        if ($column === 'seo_score') {
            $score = $this->get_seo_score($post_id);
            $class = $score >= 80 ? 'seo-good' : ($score >= 60 ? 'seo-warning' : 'seo-bad');
            echo '<span style="display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; color: #fff; ';
            if ($score >= 80) echo 'background: #28a745;';
            elseif ($score >= 60) echo 'background: #ff9800;';
            else echo 'background: #dc3545;';
            echo '">' . $score . '%</span>';
        }
    }
}

add_action('plugins_loaded', function() {
    SEORecommenderPro::get_instance();
});

add_action('admin_init', function() {
    register_setting('seo-recommender-settings', 'seo_min_title_length');
    register_setting('seo-recommender-settings', 'seo_min_description_length');
    register_setting('seo-recommender-settings', 'seo_min_content_length');
});

register_activation_hook(__FILE__, function() {
    update_option('seo_min_title_length', 50);
    update_option('seo_min_description_length', 120);
    update_option('seo_min_content_length', 300);
});
?>
