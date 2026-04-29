<?php
/**
 * Block registration and helper functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the Publications block
 */
add_action('init', 'wp_publications_register_block');

function wp_publications_register_block() {
	  wp_register_block_types_from_metadata_collection( WP_PUBLICATIONS_PLUGIN_DIR . '/build', WP_PUBLICATIONS_PLUGIN_DIR . '/build/blocks-manifest.php' );
}

/**
 * Extract publication year from Crossref data
 */
function wp_publications_block_extract_year($crossref_data) {
    if (!$crossref_data || !isset($crossref_data['message'])) {
        return __('Unknown', 'wp-publications');
    }
    
    $date_fields = array('published-print', 'published-online', 'issued', 'created');
    
    foreach ($date_fields as $field) {
        if (isset($crossref_data['message'][$field]['date-parts'][0][0])) {
            return (string) $crossref_data['message'][$field]['date-parts'][0][0];
        }
    }
    
    return __('Unknown', 'wp-publications');
}

/**
 * Format publication in APA style
 * APA format: Author, A. A., Author, B. B., & Author, C. C. (Year). Title of article. Title of Periodical, volume(issue), page–page. https://doi.org/xxxxx
 */
function wp_publications_block_format_apa($crossref_data, $post_id) {
    if (!$crossref_data || !isset($crossref_data['message'])) {
        return esc_html(get_the_title($post_id));
    }
    
    $message = $crossref_data['message'];
    $parts = array();
    
    // Authors
    $authors = wp_publications_block_format_apa_authors($message);
    if (!empty($authors)) {
        $parts[] = $authors;
    }
    
    // Year
    $year = wp_publications_block_extract_year($crossref_data);
    if ($year !== __('Unknown', 'wp-publications')) {
        $parts[] = '(' . $year . ').';
    }
    
    // Title
    $title = '';
    if (isset($message['title']) && !empty($message['title'])) {
        $title = is_array($message['title']) ? reset($message['title']) : $message['title'];
    }
    if (!empty($title)) {
        // Decode any literal \uXXXX sequences that survived JSON parsing (e.g. double-escaped values).
        $title = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/i', function ($m) {
            return html_entity_decode('&#x' . $m[1] . ';', ENT_HTML5, 'UTF-8');
        }, $title);
        $title_tags = array(
            'sub'    => array(),
            'sup'    => array(),
            'i'      => array(),
            'em'     => array(),
            'b'      => array(),
            'strong' => array(),
        );
        $parts[] = wp_kses($title, $title_tags) . '.';
    }
    
    // Journal name (italicized)
    $journal = '';
    if (isset($message['container-title']) && !empty($message['container-title'])) {
        $journal = is_array($message['container-title']) ? reset($message['container-title']) : $message['container-title'];
    }
    if (!empty($journal)) {
        $journal_part = '<em>' . esc_html($journal) . '</em>';
        
        // Volume and issue
        $vol_issue = '';
        if (isset($message['volume'])) {
            $vol_issue = ', <em>' . esc_html($message['volume']) . '</em>';
            if (isset($message['issue'])) {
                $vol_issue .= '(' . esc_html($message['issue']) . ')';
            }
        }
        
        // Pages
        $pages = '';
        if (isset($message['page'])) {
            $pages = ', ' . esc_html($message['page']);
        }
        
        $parts[] = $journal_part . $vol_issue . $pages . '.';
    }
    
    // DOI
    if (isset($message['DOI'])) {
        $doi = $message['DOI'];
        $doi_url = 'https://doi.org/' . $doi;
        $parts[] = '<a href="' . esc_url($doi_url) . '" target="_blank" rel="noopener">' . esc_url($doi_url) . '</a>';
    }
    
    return implode(' ', $parts);
}

/**
 * Format authors in APA style
 * APA: Last, F. M., Last, F. M., & Last, F. M.
 */
function wp_publications_block_format_apa_authors($message) {
    if (!isset($message['author']) || empty($message['author'])) {
        return '';
    }
    
    $authors = $message['author'];
    $formatted = array();
    
    foreach ($authors as $author) {
        $family = isset($author['family']) ? $author['family'] : '';
        $given = isset($author['given']) ? $author['given'] : '';
        
        if (empty($family) && empty($given)) {
            continue;
        }
        
        // Format given name as initials
        $initials = '';
        if (!empty($given)) {
            $given_parts = preg_split('/[\s\-]+/', $given);
            foreach ($given_parts as $part) {
                if (!empty($part)) {
                    $initials .= mb_strtoupper(mb_substr($part, 0, 1)) . '. ';
                }
            }
            $initials = trim($initials);
        }
        
        if (!empty($family) && !empty($initials)) {
            $formatted[] = esc_html($family) . ' ' . esc_html($initials);
        } elseif (!empty($family)) {
            $formatted[] = esc_html($family);
        } elseif (!empty($initials)) {
            $formatted[] = esc_html($initials);
        }
    }
    
    if (empty($formatted)) {
        return '';
    }
    
    // APA style: use & before last author
    $count = count($formatted);
    if ($count === 1) {
        return $formatted[0];
    } elseif ($count === 2) {
        return $formatted[0] . ' & ' . $formatted[1];
    } else {
        // For 3+ authors, list all with commas and & before the last
        $last = array_pop($formatted);
        return implode(', ', $formatted) . ', & ' . $last;
    }
}
