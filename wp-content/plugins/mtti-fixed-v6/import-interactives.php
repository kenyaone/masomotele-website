<?php
/**
 * Interactive Lessons Importer
 * Imports all HTML interactive lessons into wp_mtti_lessons table
 * Usage: Load this file in browser or via WP admin
 */

// Check if run from WordPress context
if (!defined('ABSPATH')) {
    die('This script must be run from WordPress context');
}

global $wpdb;
$table = $wpdb->prefix . 'mtti_lessons';
$interactives_dir = WP_CONTENT_DIR . '/../../public_html/interactives';
$course_id = 9; // Digital Marketing course

// Array of interactive lessons with metadata
$lessons = array(
    array('file' => 'lesson-1.1-what-is-digital-marketing.html', 'title' => 'What is Digital Marketing?', 'module' => '1.1'),
    array('file' => 'lesson-1.2-planning-a-digital-marketing-strategy.html', 'title' => 'Planning a Digital Marketing Strategy', 'module' => '1.2'),
    array('file' => 'lesson-2.1-web-presence-options.html', 'title' => 'Web Presence Options', 'module' => '2.1'),
    array('file' => 'lesson-2.2-website-considerations.html', 'title' => 'Website Considerations', 'module' => '2.2'),
    array('file' => 'lesson-2.3-seo-theory.html', 'title' => 'SEO Theory', 'module' => '2.3'),
    array('file' => 'lesson-3.1-social-media-platforms.html', 'title' => 'Social Media Platforms', 'module' => '3.1'),
    array('file' => 'lesson-3.2-social-media-accounts.html', 'title' => 'Social Media Accounts Setup', 'module' => '3.2'),
    array('file' => 'lesson-4.1-social-media-management-services.html', 'title' => 'Social Media Management Services', 'module' => '4.1'),
    array('file' => 'lesson-4.2-marketing-promotion.html', 'title' => 'Marketing Promotion', 'module' => '4.2'),
    array('file' => 'lesson-4.3-lead-to-sale-funnel.html', 'title' => 'Lead to Sale Funnel', 'module' => '4.3'),
    array('file' => 'lesson-5.1-online-advertising-overview.html', 'title' => 'Online Advertising Overview', 'module' => '5.1'),
    array('file' => 'lesson-5.2-email-marketing.html', 'title' => 'Email Marketing', 'module' => '5.2'),
    array('file' => 'lesson-5.3-mobile-marketing.html', 'title' => 'Mobile Marketing', 'module' => '5.3'),
    array('file' => 'lesson-5.4-meta-ads-manager.html', 'title' => 'Meta Ads Manager', 'module' => '5.4'),
    array('file' => 'lesson-5.5-linkedin-campaign-manager.html', 'title' => 'LinkedIn Campaign Manager', 'module' => '5.5'),
    array('file' => 'lesson-5.6-tiktok-ads-manager.html', 'title' => 'TikTok Ads Manager', 'module' => '5.6'),
    array('file' => 'lesson-5.7-cross-platform-quick-reference.html', 'title' => 'Cross-Platform Quick Reference', 'module' => '5.7'),
    array('file' => 'lesson-6.1-analytics-getting-started.html', 'title' => 'Analytics Getting Started', 'module' => '6.1'),
    array('file' => 'lesson-6.2-web-analytics-ga4.html', 'title' => 'Web Analytics - GA4', 'module' => '6.2'),
    array('file' => 'lesson-6.3-social-media-insights.html', 'title' => 'Social Media Insights', 'module' => '6.3'),
    array('file' => 'lesson-6.4-email-advertising-analytics.html', 'title' => 'Email & Advertising Analytics', 'module' => '6.4'),
    array('file' => 'lesson-7.1-why-visual-content-matters.html', 'title' => 'Why Visual Content Matters', 'module' => '7.1'),
    array('file' => 'lesson-7.2-image-optimization.html', 'title' => 'Image Optimization', 'module' => '7.2'),
    array('file' => 'lesson-7.3-color-psychology.html', 'title' => 'Color Psychology', 'module' => '7.3'),
    array('file' => 'lesson-7.4-canvas-comparator.html', 'title' => 'Canvas Comparator', 'module' => '7.4'),
    array('file' => 'lesson-7.5-content-calendar.html', 'title' => 'Content Calendar', 'module' => '7.5'),
    array('file' => 'lesson-7.6-accessibility.html', 'title' => 'Accessibility in Design', 'module' => '7.6'),
    array('file' => 'lesson-7.7-video-creation-basics.html', 'title' => 'Video Creation Basics', 'module' => '7.7'),
    array('file' => 'lesson-7.8-user-generated-content.html', 'title' => 'User Generated Content', 'module' => '7.8'),
    array('file' => 'lesson-8.1-wordpress-fast-start.html', 'title' => 'WordPress Fast Start', 'module' => '8.1'),
    array('file' => 'lesson-8.2-introducing-wordpress-studio.html', 'title' => 'Introducing WordPress Studio', 'module' => '8.2'),
    array('file' => 'lesson-8.3-install-wordpress-studio.html', 'title' => 'Install WordPress Studio', 'module' => '8.3'),
    array('file' => 'lesson-8.4-wordpress-dashboard-tour.html', 'title' => 'WordPress Dashboard Tour', 'module' => '8.4'),
    array('file' => 'lesson-8.5-create-the-5-essential-pages.html', 'title' => 'Create the 5 Essential Pages', 'module' => '8.5'),
    array('file' => 'lesson-8.6-install-and-customise-a-theme.html', 'title' => 'Install and Customize a Theme', 'module' => '8.6'),
    array('file' => 'lesson-8.7-install-essential-plugins.html', 'title' => 'Install Essential Plugins', 'module' => '8.7'),
    array('file' => 'lesson-9.2-keyword-research.html', 'title' => 'Keyword Research', 'module' => '9.2'),
    array('file' => 'lesson-9.3-on-page-seo-with-yoast.html', 'title' => 'On-Page SEO with Yoast', 'module' => '9.3'),
    array('file' => 'lesson-9.4-technical-seo-core-web-vitals.html', 'title' => 'Technical SEO & Core Web Vitals', 'module' => '9.4'),
    array('file' => 'lesson-9.5-off-page-seo-backlinks.html', 'title' => 'Off-Page SEO & Backlinks', 'module' => '9.5'),
    array('file' => 'lesson-9.6-setting-up-google-search-console.html', 'title' => 'Setting up Google Search Console', 'module' => '9.6'),
    array('file' => 'lesson-9.7-setting-up-google-analytics-4.html', 'title' => 'Setting up Google Analytics 4', 'module' => '9.7'),
    array('file' => 'lesson-10.1-google-ads-simulator-lab.html', 'title' => 'Google Ads Simulator Lab', 'module' => '10.1'),
    array('file' => 'lesson-11.1-meta-business-manager.html', 'title' => 'Meta Business Manager', 'module' => '11.1'),
    array('file' => 'lesson-11.2-events-manager-pixel-capi.html', 'title' => 'Events Manager - Pixel & CAPI', 'module' => '11.2'),
    array('file' => 'lesson-11.3-audiences-dashboard.html', 'title' => 'Audiences Dashboard', 'module' => '11.3'),
    array('file' => 'lesson-11.4-creative-formats-ad-preview.html', 'title' => 'Creative Formats & Ad Preview', 'module' => '11.4'),
    array('file' => 'lesson-11.5-budgets-advantage-learning-phase.html', 'title' => 'Budgets & Advantage+ Learning Phase', 'module' => '11.5'),
    array('file' => 'lesson-12.1-tiktok-ads-manager-deep-tour.html', 'title' => 'TikTok Ads Manager Deep Tour', 'module' => '12.1'),
    array('file' => 'lesson-12.2-tiktok-pixel-events-api.html', 'title' => 'TikTok Pixel & Events API', 'module' => '12.2'),
    array('file' => 'lesson-12.3-spark-ads-creator-marketplace.html', 'title' => 'Spark Ads & Creator Marketplace', 'module' => '12.3'),
    array('file' => 'lesson-12.4-trend-jacking-creative-center.html', 'title' => 'Trend-jacking & Creative Center', 'module' => '12.4'),
    array('file' => 'lesson-12.5-ads-manager-analytics-optimisation.html', 'title' => 'Ads Manager Analytics & Optimization', 'module' => '12.5'),
    array('file' => 'lesson-13.1-youtube-search-the-algorithm.html', 'title' => 'YouTube Search & the Algorithm', 'module' => '13.1'),
    array('file' => 'lesson-13.2-channel-setup-branding.html', 'title' => 'Channel Setup & Branding', 'module' => '13.2'),
    array('file' => 'lesson-13.3-youtube-seo-in-studio-upload.html', 'title' => 'YouTube SEO in Studio Upload', 'module' => '13.3'),
    array('file' => 'lesson-13.4-long-form-vs-shorts-strategy.html', 'title' => 'Long-form vs Shorts Strategy', 'module' => '13.4'),
    array('file' => 'lesson-13.5-youtube-ads-via-google-ads.html', 'title' => 'YouTube Ads via Google Ads', 'module' => '13.5'),
    array('file' => 'lesson-14.1-local-pack-why-gbp-wins.html', 'title' => 'Local Pack - Why GBP Wins', 'module' => '14.1'),
    array('file' => 'lesson-14.2-setup-verification.html', 'title' => 'Setup & Verification', 'module' => '14.2'),
    array('file' => 'lesson-14.3-optimising-the-listing.html', 'title' => 'Optimising the Listing', 'module' => '14.3'),
    array('file' => 'lesson-14.4-reviews-q-a-google-posts.html', 'title' => 'Reviews, Q&A, Google Posts', 'module' => '14.4'),
    array('file' => 'lesson-14.5-gbp-insights-local-pack-ranking.html', 'title' => 'GBP Insights & Local Pack Ranking', 'module' => '14.5'),
    array('file' => 'lesson-15.1-ai-for-marketers-copy-content.html', 'title' => 'AI for Copy & Content', 'module' => '15.1'),
    array('file' => 'lesson-15.2-ai-for-visuals-automation.html', 'title' => 'AI for Visuals & Automation', 'module' => '15.2'),
);

$imported = 0;
$skipped = 0;
$errors = array();

foreach ($lessons as $index => $lesson) {
    $file_path = '/home/uvyzhdzt/public_html/interactives/' . $lesson['file'];

    if (!file_exists($file_path)) {
        $skipped++;
        continue;
    }

    // Check if lesson already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT lesson_id FROM {$table} WHERE course_id = %d AND title = %s",
        $course_id,
        $lesson['title']
    ));

    if ($existing) {
        $skipped++;
        continue;
    }

    // Get file size
    $file_size = filesize($file_path);
    $file_url = 'https://example.com/interactives/' . $lesson['file'];

    // Insert into database
    $result = $wpdb->insert(
        $table,
        array(
            'course_id' => $course_id,
            'unit_id' => null,
            'title' => $lesson['title'],
            'description' => 'Interactive lesson with embedded content and quiz',
            'content' => '<iframe src="' . esc_url($file_url) . '" style="width:100%; height:800px; border:none;"></iframe>',
            'video_url' => null,
            'duration_minutes' => 30,
            'order_number' => $index + 1,
            'status' => 'Active',
            'created_by' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s')
    );

    if ($result) {
        $imported++;
    } else {
        $errors[] = "Failed to import: " . $lesson['title'];
    }
}

// Return results
echo "<div style='padding: 20px; background: #f5f5f5;'>";
echo "<h2>Interactive Lessons Import Report</h2>";
echo "<p><strong>Imported:</strong> " . $imported . " lessons</p>";
echo "<p><strong>Skipped:</strong> " . $skipped . " (already exist or missing files)</p>";
echo "<p><strong>Course ID:</strong> " . $course_id . " (Digital Marketing)</p>";

if (!empty($errors)) {
    echo "<h3>Errors:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . $error . "</li>";
    }
    echo "</ul>";
}

echo "<p><strong>Status:</strong> Import complete! Lessons are now available in the student dashboard.</p>";
echo "</div>";

?>
