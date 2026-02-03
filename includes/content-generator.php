<?php
/**
 * Content generation functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate post content from Crossref data
 */
function wp_publications_generate_content($crossref_data, $journal_data = null) {
    $content = '';
    
    // DOI
    $doi = isset($crossref_data['message']['DOI']) ? $crossref_data['message']['DOI'] : '';
    if (!empty($doi)) {
        $content .= '<div class="publication-doi">';
        $content .= '<strong>' . esc_html__('DOI:', 'wp-publications') . '</strong> ';
        $content .= '<a href="https://doi.org/' . esc_attr($doi) . '" target="_blank" rel="noopener">';
        $content .= esc_html($doi);
        $content .= '</a>';
        $content .= '</div>';
        $content .= "\n\n";
    }
    
    // Journal information
    $journal_name = wp_publications_extract_journal_name($crossref_data);
    if (!empty($journal_name)) {
        $content .= '<div class="publication-journal">';
        
        // Journal image if available from journal data
        $journal_image = wp_publications_get_journal_image($journal_data);
        if (!empty($journal_image)) {
            $content .= '<img src="' . esc_url($journal_image) . '" alt="' . esc_attr($journal_name) . '" class="journal-image" />';
        }
        
        $content .= '<strong>' . esc_html__('Journal:', 'wp-publications') . '</strong> ';
        $content .= esc_html($journal_name);
        $content .= '</div>';
        $content .= "\n\n";
    }
    
    // Authors
    $authors = wp_publications_extract_authors($crossref_data);
    if (!empty($authors)) {
        $content .= '<div class="publication-authors">';
        $content .= '<strong>' . esc_html__('Authors:', 'wp-publications') . '</strong> ';
        
        $author_links = array();
        foreach ($authors as $author) {
            $name = trim($author['given'] . ' ' . $author['family']);
            
            if (!empty($author['orcid'])) {
                $orcid_url = $author['orcid'];
                // Ensure full URL
                if (strpos($orcid_url, 'http') !== 0) {
                    $orcid_url = 'https://orcid.org/' . $orcid_url;
                }
                $author_links[] = '<a href="' . esc_url($orcid_url) . '" target="_blank" rel="noopener">' . esc_html($name) . '</a>';
            } else {
                $author_links[] = esc_html($name);
            }
        }
        
        $content .= implode(', ', $author_links);
        $content .= '</div>';
        $content .= "\n\n";
    }
    
    // Publication date
    $pub_date = wp_publications_format_date($crossref_data);
    if (!empty($pub_date)) {
        $content .= '<div class="publication-date">';
        $content .= '<strong>' . esc_html__('Published:', 'wp-publications') . '</strong> ';
        $content .= esc_html($pub_date);
        $content .= '</div>';
        $content .= "\n\n";
    }
    
    // Abstract
    $abstract = wp_publications_extract_abstract($crossref_data);
    if (!empty($abstract)) {
        $content .= '<div class="publication-abstract">';
        $content .= '<h3>' . esc_html__('Abstract', 'wp-publications') . '</h3>';
        $content .= '<p>' . wp_kses_post($abstract) . '</p>';
        $content .= '</div>';
        $content .= "\n\n";
    }
    
    // Images
    $images = wp_publications_extract_images($crossref_data);
    if (!empty($images)) {
        $content .= '<div class="publication-images">';
        
        if (count($images) === 1) {
            // Single image
            $content .= '<figure class="publication-image">';
            $content .= '<img src="' . esc_url($images[0]) . '" alt="' . esc_attr__('Publication image', 'wp-publications') . '" />';
            $content .= '</figure>';
        } else {
            // Gallery for multiple images
            $content .= '<div class="publication-gallery">';
            $content .= '<!-- wp:gallery {"columns":' . min(count($images), 3) . ',"linkTo":"file"} -->';
            $content .= '<figure class="wp-block-gallery has-nested-images columns-' . min(count($images), 3) . '">';
            
            foreach ($images as $image_url) {
                $content .= '<figure class="wp-block-image">';
                $content .= '<a href="' . esc_url($image_url) . '" target="_blank">';
                $content .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr__('Publication image', 'wp-publications') . '" />';
                $content .= '</a>';
                $content .= '</figure>';
            }
            
            $content .= '</figure>';
            $content .= '<!-- /wp:gallery -->';
            $content .= '</div>';
        }
        
        $content .= '</div>';
        $content .= "\n\n";
    }
    
    // Additional metadata
    $content .= wp_publications_generate_metadata_section($crossref_data);
    
    return $content;
}

/**
 * Format publication date from Crossref data
 */
function wp_publications_format_date($crossref_data) {
    $date_parts = null;
    
    // Try different date fields
    $date_fields = array('published-print', 'published-online', 'issued', 'created');
    
    foreach ($date_fields as $field) {
        if (isset($crossref_data['message'][$field]['date-parts'][0])) {
            $date_parts = $crossref_data['message'][$field]['date-parts'][0];
            break;
        }
    }
    
    if (empty($date_parts)) {
        return '';
    }
    
    $year = isset($date_parts[0]) ? $date_parts[0] : '';
    $month = isset($date_parts[1]) ? str_pad($date_parts[1], 2, '0', STR_PAD_LEFT) : '';
    $day = isset($date_parts[2]) ? str_pad($date_parts[2], 2, '0', STR_PAD_LEFT) : '';
    
    if (!empty($year) && !empty($month) && !empty($day)) {
        return date_i18n(get_option('date_format'), strtotime("$year-$month-$day"));
    } elseif (!empty($year) && !empty($month)) {
        return date_i18n('F Y', strtotime("$year-$month-01"));
    } elseif (!empty($year)) {
        return $year;
    }
    
    return '';
}

/**
 * Get journal image from journal data
 */
function wp_publications_get_journal_image($journal_data) {
    if (empty($journal_data) || !isset($journal_data['message'])) {
        return '';
    }
    
    // Check for cover image or other image fields
    if (isset($journal_data['message']['cover-url'])) {
        return $journal_data['message']['cover-url'];
    }
    
    return '';
}

/**
 * Generate additional metadata section
 */
function wp_publications_generate_metadata_section($crossref_data) {
    $content = '';
    $metadata = array();
    
    // Type
    if (isset($crossref_data['message']['type'])) {
        $metadata[__('Type', 'wp-publications')] = ucfirst(str_replace('-', ' ', $crossref_data['message']['type']));
    }
    
    // Volume/Issue/Page
    $citation_parts = array();
    if (isset($crossref_data['message']['volume'])) {
        $citation_parts[] = __('Vol.', 'wp-publications') . ' ' . $crossref_data['message']['volume'];
    }
    if (isset($crossref_data['message']['issue'])) {
        $citation_parts[] = __('Issue', 'wp-publications') . ' ' . $crossref_data['message']['issue'];
    }
    if (isset($crossref_data['message']['page'])) {
        $citation_parts[] = __('pp.', 'wp-publications') . ' ' . $crossref_data['message']['page'];
    }
    if (!empty($citation_parts)) {
        $metadata[__('Citation', 'wp-publications')] = implode(', ', $citation_parts);
    }
    
    // Publisher
    if (isset($crossref_data['message']['publisher'])) {
        $metadata[__('Publisher', 'wp-publications')] = $crossref_data['message']['publisher'];
    }
    
    // ISSN
    if (isset($crossref_data['message']['ISSN']) && !empty($crossref_data['message']['ISSN'])) {
        $issns = is_array($crossref_data['message']['ISSN']) 
            ? implode(', ', $crossref_data['message']['ISSN']) 
            : $crossref_data['message']['ISSN'];
        $metadata[__('ISSN', 'wp-publications')] = $issns;
    }
    
    // License
    if (isset($crossref_data['message']['license']) && !empty($crossref_data['message']['license'])) {
        $license = reset($crossref_data['message']['license']);
        if (isset($license['URL'])) {
            $metadata[__('License', 'wp-publications')] = '<a href="' . esc_url($license['URL']) . '" target="_blank" rel="noopener">' . esc_html($license['URL']) . '</a>';
        }
    }
    
    if (!empty($metadata)) {
        $content .= '<div class="publication-metadata">';
        $content .= '<h3>' . esc_html__('Publication Details', 'wp-publications') . '</h3>';
        $content .= '<dl>';
        
        foreach ($metadata as $label => $value) {
            $content .= '<dt>' . esc_html($label) . '</dt>';
            $content .= '<dd>' . wp_kses_post($value) . '</dd>';
        }
        
        $content .= '</dl>';
        $content .= '</div>';
    }
    
    return $content;
}
