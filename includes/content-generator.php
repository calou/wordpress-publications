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
    $blocks = array();

    // DOI
    $doi = isset($crossref_data['message']['DOI']) ? $crossref_data['message']['DOI'] : '';
    if (!empty($doi)) {
        $blocks[] = "<!-- wp:paragraph -->\n"
            . '<p><strong>' . esc_html__('DOI:', 'wp-publications') . '</strong> '
            . '<a href="' . esc_url('https://doi.org/' . $doi) . '" target="_blank" rel="noopener">' . esc_html($doi) . '</a></p>'
            . "\n<!-- /wp:paragraph -->";
    }

    // Journal information
    $journal_name = wp_publications_extract_journal_name($crossref_data);
    if (!empty($journal_name)) {
        $journal_image = wp_publications_get_journal_image($journal_data);

        if (!empty($journal_image)) {
            $blocks[] = "<!-- wp:group {\"layout\":{\"type\":\"flex\",\"flexWrap\":\"nowrap\"}} -->\n"
                . "<div class=\"wp-block-group\">\n"
                . "<!-- wp:image {\"sizeSlug\":\"thumbnail\"} -->\n"
                . '<figure class="wp-block-image size-thumbnail"><img src="' . esc_url($journal_image) . '" alt="' . esc_attr($journal_name) . '"/></figure>'
                . "\n<!-- /wp:image -->\n"
                . "<!-- wp:paragraph -->\n"
                . '<p><strong>' . esc_html__('Journal:', 'wp-publications') . '</strong> ' . esc_html($journal_name) . '</p>'
                . "\n<!-- /wp:paragraph -->\n"
                . "</div>\n<!-- /wp:group -->";
        } else {
            $blocks[] = "<!-- wp:paragraph -->\n"
                . '<p><strong>' . esc_html__('Journal:', 'wp-publications') . '</strong> ' . esc_html($journal_name) . '</p>'
                . "\n<!-- /wp:paragraph -->";
        }
    }

    // Authors
    $authors = wp_publications_extract_authors($crossref_data);
    if (!empty($authors)) {
        $author_parts = array();
        foreach ($authors as $author) {
            $name = trim($author['given'] . ' ' . $author['family']);
            if (!empty($author['orcid'])) {
                $orcid_url = $author['orcid'];
                if (strpos($orcid_url, 'http') !== 0) {
                    $orcid_url = 'https://orcid.org/' . $orcid_url;
                }
                $author_parts[] = '<a href="' . esc_url($orcid_url) . '" target="_blank" rel="noopener">' . esc_html($name) . '</a>';
            } else {
                $author_parts[] = esc_html($name);
            }
        }

        $blocks[] = "<!-- wp:paragraph -->\n"
            . '<p><strong>' . esc_html__('Authors:', 'wp-publications') . '</strong> ' . implode(', ', $author_parts) . '</p>'
            . "\n<!-- /wp:paragraph -->";
    }

    // Publication date
    $pub_date = wp_publications_format_date($crossref_data);
    if (!empty($pub_date)) {
        $blocks[] = "<!-- wp:paragraph -->\n"
            . '<p><strong>' . esc_html__('Published:', 'wp-publications') . '</strong> ' . esc_html($pub_date) . '</p>'
            . "\n<!-- /wp:paragraph -->";
    }

    // Abstract
    $abstract = wp_publications_extract_abstract($crossref_data);
    if (!empty($abstract)) {
        $blocks[] = "<!-- wp:heading {\"level\":3} -->\n"
            . '<h3 class="wp-block-heading">' . esc_html__('Abstract', 'wp-publications') . '</h3>'
            . "\n<!-- /wp:heading -->";
        $blocks[] = "<!-- wp:paragraph -->\n"
            . '<p>' . wp_kses_post($abstract) . '</p>'
            . "\n<!-- /wp:paragraph -->";
    }

    // Images
    $images = wp_publications_extract_images($crossref_data);
    if (!empty($images)) {
        if (count($images) === 1) {
            $blocks[] = "<!-- wp:image -->\n"
                . '<figure class="wp-block-image"><img src="' . esc_url($images[0]) . '" alt="' . esc_attr__('Publication image', 'wp-publications') . '"/></figure>'
                . "\n<!-- /wp:image -->";
        } else {
            $cols   = min(count($images), 3);
            $inner  = '';
            foreach ($images as $image_url) {
                $inner .= "<!-- wp:image -->\n"
                    . '<figure class="wp-block-image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr__('Publication image', 'wp-publications') . '"/></figure>'
                    . "\n<!-- /wp:image -->\n";
            }
            $blocks[] = '<!-- wp:gallery {"columns":' . $cols . ',"linkTo":"none"} -->' . "\n"
                . '<figure class="wp-block-gallery has-nested-images columns-default is-cropped">' . "\n"
                . $inner
                . "</figure>\n<!-- /wp:gallery -->";
        }
    }

    // Additional metadata
    $metadata_block = wp_publications_generate_metadata_section($crossref_data);
    if (!empty($metadata_block)) {
        $blocks[] = $metadata_block;
    }

    return implode("\n\n", $blocks);
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
    
    if (empty($metadata)) {
        return '';
    }

    $rows = '';
    foreach ($metadata as $label => $value) {
        $rows .= '<tr><td><strong>' . esc_html($label) . '</strong></td><td>' . wp_kses_post($value) . '</td></tr>';
    }

    return "<!-- wp:heading {\"level\":3} -->\n"
        . '<h3 class="wp-block-heading">' . esc_html__('Publication Details', 'wp-publications') . '</h3>'
        . "\n<!-- /wp:heading -->\n\n"
        . "<!-- wp:table -->\n"
        . '<figure class="wp-block-table"><table><tbody>' . $rows . '</tbody></table></figure>'
        . "\n<!-- /wp:table -->";
}
