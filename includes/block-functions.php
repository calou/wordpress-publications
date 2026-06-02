<?php
/**
 * Block registration and helper functions
 */

use BcMath\Number;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Publications block
 */
add_action( 'init', 'wp_publications_register_block' );

function wp_publications_register_block() {
		wp_register_block_types_from_metadata_collection( WP_PUBLICATIONS_PLUGIN_DIR . '/build', WP_PUBLICATIONS_PLUGIN_DIR . '/build/blocks-manifest.php' );
}


/**
 * Format publication in APA style
 * APA format: Author, A. A., Author, B. B., & Author, C. C. (Year). Title of article. Title of Periodical, volume(issue), page–page. https://doi.org/xxxxx
 */
function wp_publications_block_format_apa( array $data, $post_id ) {
	$parts = array();

	$parts[] = '<a href="' . get_permalink( $post_id ) . '" target="_blank" style="text-decoration:none;">';

	// Authors
	$authors = wp_publications_block_format_apa_authors( $data );
	if ( ! empty( $authors ) ) {
		$parts[] = $authors;
	}

	// Year
	$year    = $data['publication_year'];
	$parts[] = '(' . $year . ').';

	// Title
	$title = $data['title'];
	if ( ! empty( $title ) ) {
		// Decode any literal \uXXXX sequences that survived JSON parsing (e.g. double-escaped values).
		$title      = preg_replace_callback(
			'/\\\\u([0-9a-fA-F]{4})/i',
			function ( $m ) {
				return html_entity_decode( '&#x' . $m[1] . ';', ENT_HTML5, 'UTF-8' );
			},
			$title
		);
		$title_tags = array(
			'sub'    => array(),
			'sup'    => array(),
			'i'      => array(),
			'em'     => array(),
			'b'      => array(),
			'strong' => array(),
		);
		$parts[]    = '<b>' . wp_kses( $title, $title_tags ) . '.</a></b>';
	}

	$journal = $data['primary_location']['raw_source_name'];
	if ( ! empty( $journal ) ) {
		$journal_part = esc_html( $journal );

		// Volume and issue
		$vol_issue = '';
		if ( isset( $data['biblio']['volume'] ) ) {
			$vol_issue = ', ' . esc_html( $data['biblio']['volume'] );
			if ( isset( $data['biblio']['issue'] ) ) {
				$vol_issue .= '(' . esc_html( $data['biblio']['issue'] ) . ')';
			}
		}

		$parts[] = '<em>' . $journal_part . $vol_issue . '.' . '</em>';
	}

	// DOI
	if ( isset( $data['doi'] ) ) {
		$doi     = $data['doi'];
		$parts[] = '<a href="' . esc_url( $doi ) . '" target="_blank" rel="noopener">' . esc_url( $doi ) . '</a>';
	}

	return implode( ' ', $parts );
}

/**
 * Format authors in APA style
 * APA: Last, F. M., Last, F. M., & Last, F. M.
 */
function wp_publications_block_format_apa_authors( array $data ) {
	$authors   = $data['authorships'];
	$formatted = array();

	foreach ( $authors as $author ) {
		$formatted[] = $author['author']['display_name'];
	}

	if ( empty( $formatted ) ) {
		return '';
	}

	// APA style: use & before last author
	$count = count( $formatted );
	if ( $count === 1 ) {
		return $formatted[0];
	} elseif ( $count === 2 ) {
		return $formatted[0] . ' & ' . $formatted[1];
	} else {
		// For 3+ authors, list all with commas and & before the last
		$last = array_pop( $formatted );
		return implode( ', ', $formatted ) . ' & ' . $last;
	}
}
