<?php
/**
 * Content generation functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate post content from normalized work data
 */
function wp_publications_generate_content( $work ) {
	$blocks = array();

	// Publication date
	$pub_date = wp_publications_format_date( $work );
	if ( ! empty( $pub_date ) ) {
		$blocks[] = "<!-- wp:paragraph -->\n"
			. '<p>' . esc_html( $pub_date ) . '</p>'
			. "\n<!-- /wp:paragraph -->";
	}

	// Volume/Issue/Pages
	$citation_parts = array();
	if ( ! empty( $work['publisher'] ) ) {
		$citation_parts[] = $work['publisher'];
	}
	if ( ! empty( $work['volume'] ) ) {
		$citation_parts[] = __( 'Vol.', 'wp-publications' ) . ' ' . $work['volume'];
	}
	if ( ! empty( $work['issue'] ) ) {
		$citation_parts[] = __( 'Issue', 'wp-publications' ) . ' ' . $work['issue'];
	}
	if ( ! empty( $work['pages'] ) ) {
		$citation_parts[] = __( 'pp.', 'wp-publications' ) . ' ' . $work['pages'];
	}

	$doi = isset( $work['doi'] ) ? $work['doi'] : '';
	if ( ! empty( $doi ) ) {
		$citation_parts[] = '<a href="' . esc_url( 'https://doi.org/' . $doi ) . '" target="_blank" rel="noopener">' . esc_html( $doi ) . '</a></p>';
	}

	if ( ! empty( $citation_parts ) ) {
		$blocks[] = "<!-- wp:paragraph -->\n<p>" . implode( ', ', $citation_parts ) . "\n<!-- /wp:paragraph -->";
	}

	// Authors
	$authors = wp_publications_extract_authors( $work );
	if ( ! empty( $authors ) ) {
		$author_parts = array();
		foreach ( $authors as $author ) {
			$name = trim( $author['name'] );
			if ( empty( $name ) ) {
				$name = trim( $author['given'] . ' ' . $author['family'] );
			}

			if ( ! empty( $author['orcid'] ) ) {
				$orcid_url = $author['orcid'];
				if ( strpos( $orcid_url, 'http' ) !== 0 ) {
					$orcid_url = 'https://orcid.org/' . $orcid_url;
				}
				$author_parts[] = '<a href="' . esc_url( $orcid_url ) . '" target="_blank" rel="noopener">' . esc_html( $name ) . '</a>';
			} else {
				$author_parts[] = esc_html( $name );
			}
		}

		$blocks[] = "<!-- wp:paragraph -->\n"
			. '<p>' . implode( ', ', $author_parts ) . '</p>'
			. "\n<!-- /wp:paragraph -->";
	}

	// Abstract
	$abstract = wp_publications_extract_abstract( $work );
	if ( ! empty( $abstract ) ) {
		$blocks[] = "<!-- wp:heading {\"level\":3} -->\n"
			. '<h3 class="wp-block-heading">' . esc_html__( 'Abstract', 'wp-publications' ) . '</h3>'
			. "\n<!-- /wp:heading -->";
		$blocks[] = "<!-- wp:paragraph -->\n"
			. '<p>' . wp_kses_post( $abstract ) . '</p>'
			. "\n<!-- /wp:paragraph -->";
	}

	return implode( '', $blocks );
}

/**
 * Format publication date from normalized work data
 */
function wp_publications_format_date( $work ) {
	$date = isset( $work['date'] ) ? $work['date'] : '';

	if ( empty( $date ) ) {
		return '';
	}

	// Year only.
	if ( preg_match( '/^\d{4}$/', $date ) ) {
		return $date;
	}

	$timestamp = strtotime( $date );
	if ( false === $timestamp ) {
		return $date;
	}

	// Year + month.
	if ( preg_match( '/^\d{4}-\d{2}$/', $date ) ) {
		return date_i18n( 'F Y', $timestamp );
	}

	// Full date.
	return date_i18n( get_option( 'date_format' ), $timestamp );
}

/**
 * Get journal image from journal data
 */
function wp_publications_get_journal_image( $journal_data ) {
	if ( empty( $journal_data ) || ! is_array( $journal_data ) ) {
		return '';
	}

	if ( ! empty( $journal_data['image'] ) ) {
		return $journal_data['image'];
	}

	return '';
}
