<?php
/**
 * Block registration and helper functions
 */

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
 * Extract publication year from normalized work data
 */
function wp_publications_block_extract_year( $work ) {
	if ( ! $work || empty( $work['year'] ) ) {
		return __( 'Unknown', 'wp-publications' );
	}

	return (string) $work['year'];
}

/**
 * Extract full publication date from normalized work data
 *
 * Returns a YYYY-MM-DD string suitable for a WordPress post_date, or
 * 'Unknown'.
 */
function wp_publications_block_extract_full_date( $work ) {
	if ( ! $work || empty( $work['date'] ) ) {
		return __( 'Unknown', 'wp-publications' );
	}

	$date = (string) $work['date'];

	if ( preg_match( '/^\d{4}$/', $date ) ) {
		return $date . '-01-01';
	}

	if ( preg_match( '/^\d{4}-\d{2}$/', $date ) ) {
		return $date . '-01';
	}

	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return $date;
	}

	return __( 'Unknown', 'wp-publications' );
}


/**
 * Format publication in APA style
 * APA format: Author, A. A., Author, B. B., & Author, C. C. (Year). Title of article. Title of Periodical, volume(issue), page–page. https://doi.org/xxxxx
 */
function wp_publications_block_format_apa( $work, $post_id ) {
	if ( ! $work ) {
		return esc_html( get_the_title( $post_id ) );
	}

	$parts = array();

	// Title is intentionally not linked: publications have no front-end single page.

	// Authors
	$authors = wp_publications_block_format_apa_authors( $work );
	if ( ! empty( $authors ) ) {
		$parts[] = $authors;
	}

	// Year
	$year = wp_publications_block_extract_year( $work );
	if ( $year !== __( 'Unknown', 'wp-publications' ) ) {
		$parts[] = '(' . $year . ').';
	}

	// Title
	$title = '';
	if ( isset( $work['title'] ) && ! empty( $work['title'] ) ) {
		$title = $work['title'];
	}
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
		$parts[]    = '<b>' . wp_kses( $title, $title_tags ) . '.</b>';
	}

	// Journal name (italicized)
	$journal = wp_publications_extract_journal_name( $work );
	if ( ! empty( $journal ) ) {
		$journal_part = esc_html( $journal );

		// Volume and issue
		$vol_issue = '';
		if ( ! empty( $work['volume'] ) ) {
			$vol_issue = ', ' . esc_html( $work['volume'] );
			if ( ! empty( $work['issue'] ) ) {
				$vol_issue .= '(' . esc_html( $work['issue'] ) . ')';
			}
		}

		// Pages
		$pages = '';
		if ( ! empty( $work['pages'] ) ) {
			$pages = ', ' . esc_html( $work['pages'] );
		}

		$parts[] = '<em>' . $journal_part . $vol_issue . $pages . '.' . '</em>';
	}

	// DOI
	if ( isset( $work['doi'] ) && ! empty( $work['doi'] ) ) {
		$doi     = $work['doi'];
		$doi_url = 'https://doi.org/' . $doi;
		$parts[] = '<a href="' . esc_url( $doi_url ) . '" target="_blank" rel="noopener">' . esc_url( $doi_url ) . '</a>';
	}

	return implode( ' ', $parts );
}

/**
 * Format authors in APA style
 * APA: Last, F. M., Last, F. M., & Last, F. M.
 */
function wp_publications_block_format_apa_authors( $work ) {
	if ( ! isset( $work['authors'] ) || empty( $work['authors'] ) ) {
		return '';
	}

	$authors   = $work['authors'];
	$formatted = array();

	foreach ( $authors as $author ) {
		$family = isset( $author['family'] ) ? $author['family'] : '';
		$given  = isset( $author['given'] ) ? $author['given'] : '';
		$name   = isset( $author['name'] ) ? $author['name'] : '';

		// OpenAlex only exposes a display name; split it when needed.
		if ( empty( $family ) && empty( $given ) && ! empty( $name ) ) {
			$split  = wp_publications_split_author_name( $name );
			$family = $split['family'];
			$given  = $split['given'];
		}

		if ( empty( $family ) && empty( $given ) ) {
			continue;
		}

		// Format given name as initials
		$initials = '';
		if ( ! empty( $given ) ) {
			$given_parts = preg_split( '/[\s\-]+/', $given );
			foreach ( $given_parts as $part ) {
				if ( ! empty( $part ) ) {
					$initials .= mb_strtoupper( mb_substr( $part, 0, 1 ) ) . '. ';
				}
			}
			$initials = trim( $initials );
		}

		if ( ! empty( $family ) && ! empty( $initials ) ) {
			$formatted[] = esc_html( $family ) . ' ' . esc_html( $initials );
		} elseif ( ! empty( $family ) ) {
			$formatted[] = esc_html( $family );
		} elseif ( ! empty( $initials ) ) {
			$formatted[] = esc_html( $initials );
		}
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
		return implode( ', ', $formatted ) . ', & ' . $last;
	}
}
