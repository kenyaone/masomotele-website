<?php
/**
 * MTTI Portal — Custom page template (bypasses theme completely)
 */
if (!defined('ABSPATH')) exit;

// Completely disable admin bar on portal pages
add_action('template_redirect', function() {
    if (is_page('student-portal') || is_page('learner-portal') || is_page('lecturer-portal')) {
        show_admin_bar(false);
    }
});

// Register the custom template
add_filter('theme_page_templates', function($templates) {
    $templates['mtti-portal-template.php'] = 'MTTI Portal';
    return $templates;
});

// Point WordPress to our template file (in plugin folder, not theme)
add_filter('template_include', function($template) {
    if (is_page('student-portal') || is_page('learner-portal') || is_page('lecturer-portal')) {
        $custom = MTTI_MIS_PLUGIN_DIR . 'templates/portal-page.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}, 999);

// Hide page title if theme still renders it
add_filter('the_title', function($title, $id = null) {
    if (!is_admin() && is_page() && in_the_loop()) {
        $slug = get_post_field('post_name', $id);
        if (in_array($slug, array('student-portal', 'learner-portal', 'lecturer-portal'))) return '';
    }
    return $title;
}, 10, 2);
