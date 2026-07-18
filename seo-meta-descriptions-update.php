<?php
/**
 * Script to update SEO meta descriptions for key pages
 * Run once via admin, then delete this file for security
 */

// Load WordPress
require_once('wp-load.php');

// Check if user is logged in and is admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Error: Admin access required');
}

// Meta descriptions mapping - page slug or ID to meta description
$meta_descriptions = array(
    'home' => 'Learn TVETA-accredited computer, nursing & language courses in Eldoret. Flexible schedules, affordable fees. Start your career now with MTTI.',
    'about' => 'Discover why 1000+ students chose MTTI for hands-on TVETA training in Eldoret. Industry experts, modern labs, career support. Your success starts here.',
    'courses' => 'Explore our TVETA-accredited courses: ICT, Healthcare, Hospitality, Electronics, Languages & Media. Flexible duration, hands-on training in Eldoret, Kenya.',
    'german-language' => 'Learn German in Eldoret with TVETA-certified training. Earn 30-40% more with language skills. Affordable fees from KES 10,000/month. Enroll today.',
    'cna' => 'Become a certified nursing assistant with TVETA accreditation. Hands-on healthcare training in Eldoret. Career-ready skills for Kenya\'s healthcare sector.',
    'fees' => 'MTTI transparent course fees structure. Flexible payment plans, installments available. View pricing for all TVETA-accredited programs in Eldoret.',
    'gallery' => 'See inside MTTI campus in Eldoret. Modern computer labs, healthcare facilities, student life. Discover our training environment and resources.',
    'blog' => 'Latest career tips, tech insights, and student success stories from MTTI. Stay updated on training trends and job opportunities in Kenya.',
    'contact' => 'Contact MTTI Eldoret. Phone: +254 712 464 936. Email: info@masomoteletraining.co.ke. Visit us at Sagaas Centre. Enroll in your course today.',
    'online-admission' => 'Apply online for TVETA-accredited courses at MTTI. Quick enrollment form, flexible payment options. Start your training journey in Eldoret, Kenya today.',
);

// Also by page ID for homepage and specific pages
$id_meta_descriptions = array(
    1 => 'Learn TVETA-accredited computer, nursing & language courses in Eldoret. Flexible schedules, affordable fees. Start your career now with MTTI.',
);

$updated_count = 0;
$results = array();

// Update by post name/slug
foreach ($meta_descriptions as $slug => $description) {
    $post = get_page_by_path($slug, OBJECT, array('page', 'post'));
    if ($post) {
        update_post_meta($post->ID, '_meta_description', sanitize_text_field($description));
        update_post_meta($post->ID, 'seo_meta_description', sanitize_text_field($description)); // Alternative meta key
        $results[] = "✓ Updated '$slug' (ID: {$post->ID})";
        $updated_count++;
    } else {
        $results[] = "✗ Not found: '$slug'";
    }
}

// Update by ID
foreach ($id_meta_descriptions as $post_id => $description) {
    if (get_post($post_id)) {
        update_post_meta($post_id, '_meta_description', sanitize_text_field($description));
        update_post_meta($post_id, 'seo_meta_description', sanitize_text_field($description));
        $results[] = "✓ Updated post ID $post_id by ID";
        $updated_count++;
    }
}

// Output results
echo "<h2>SEO Meta Description Update Results</h2>";
echo "<p><strong>Updated: $updated_count pages</strong></p>";
echo "<ul>";
foreach ($results as $result) {
    echo "<li>$result</li>";
}
echo "</ul>";
echo "<p><strong>Important:</strong> Delete this file (seo-meta-descriptions-update.php) after running for security.</p>";
echo '<p><a href="' . esc_url(admin_url()) . '">Back to Admin</a></p>';
?>
