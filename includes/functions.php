<?php
/**
 * Helper functions for WP Publications
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the mailto email for Crossref polite pool
 */
function wp_publications_get_mailto() {
    return get_option('wp_publications_mailto', '');
}

/**
 * Get the API delay in milliseconds
 */
function wp_publications_get_api_delay() {
    return (int) get_option('wp_publications_api_delay', 1000);
}

/**
 * Validate email address
 */
function wp_publications_is_valid_email($email) {
    return is_email($email);
}

/**
 * Normalize DOI (remove URL prefix if present)
 */
function wp_publications_normalize_doi($doi) {
    $doi = trim($doi);
    
    // Remove common DOI URL prefixes
    $prefixes = array(
        'https://doi.org/',
        'http://doi.org/',
        'https://dx.doi.org/',
        'http://dx.doi.org/',
        'doi:',
    );
    
    foreach ($prefixes as $prefix) {
        if (stripos($doi, $prefix) === 0) {
            $doi = substr($doi, strlen($prefix));
            break;
        }
    }
    
    return trim($doi);
}

/**
 * Parse DOI list from textarea input
 */
function wp_publications_parse_doi_list($input) {
    $dois = array();
    
    // Split by newlines, commas, or semicolons
    $lines = preg_split('/[\r\n,;]+/', $input);
    
    foreach ($lines as $line) {
        $doi = wp_publications_normalize_doi($line);
        if (!empty($doi)) {
            $dois[] = $doi;
        }
    }
    
    return array_unique($dois);
}

/**
 * Find existing publication by DOI
 */
function wp_publications_find_by_doi($doi) {
    $doi = wp_publications_normalize_doi($doi);
    
    $args = array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'pending', 'private'),
        'meta_query' => array(
            array(
                'key' => WP_PUBLICATIONS_META_DOI,
                'value' => $doi,
                'compare' => '=',
            ),
        ),
        'posts_per_page' => 1,
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        return $query->posts[0];
    }
    
    return null;
}

/**
 * Get all available post tags
 */
function wp_publications_get_available_tags() {
    $tags = get_tags(array(
        'hide_empty' => false,
    ));
    
    $result = array();
    foreach ($tags as $tag) {
        $result[] = array(
            'id' => $tag->term_id,
            'text' => $tag->name,
        );
    }
    
    return $result;
}

/**
 * Sanitize tag IDs array
 */
function wp_publications_sanitize_tag_ids($tag_ids) {
    if (!is_array($tag_ids)) {
        return array();
    }
    
    return array_map('absint', array_filter($tag_ids));
}

/**
 * Log debug message
 */
function wp_publications_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        if ($data !== null) {
            error_log('[WP Publications] ' . $message . ': ' . print_r($data, true));
        } else {
            error_log('[WP Publications] ' . $message);
        }
    }
}
