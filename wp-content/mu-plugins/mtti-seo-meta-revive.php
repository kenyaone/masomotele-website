<?php
/**
 * Revives per-page SEO title/description that already exist in the
 * database (_yoast_wpseo_title / _yoast_wpseo_metadesc postmeta) but were
 * never being rendered — Yoast itself is no longer an active plugin on
 * this site, so its own wp_head output stopped firing, even though the
 * meta values it wrote are still sitting in postmeta unused. Without a
 * title override, WordPress' default title-tag support concatenates the
 * post title with the site name AND tagline, producing ~140-170 character
 * <title> tags that get truncated in search results.
 */
if (!defined('ABSPATH')) exit;

add_filter('pre_get_document_title', function ($title) {
    if (!is_singular()) return $title;
    $post_id = get_queried_object_id();
    if (!$post_id) return $title;

    $custom_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
    if ($custom_title) return $custom_title;

    // No title override, but a description exists — this page was SEO-
    // reviewed at some point, so use its bare post title (no site-name
    // suffix) instead of the default double-suffixed one.
    if (get_post_meta($post_id, '_yoast_wpseo_metadesc', true)) {
        $post_title = get_the_title($post_id);
        if ($post_title) return $post_title;
    }

    return $title;
}, 20);

add_action('wp_head', function () {
    if (!is_singular()) return;
    $post_id = get_queried_object_id();
    if (!$post_id) return;

    $desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
    if ($desc) {
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
    }
}, 1);
