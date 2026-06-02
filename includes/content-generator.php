<?php
/**
 * Content generation functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate post content from Crossref data
 */
function wp_publications_generate_content( $data ) {
	$blocks = array();

	// Publication date
	$pub_date = wp_publications_format_date( $data['publication_date'] );
	if ( ! empty( $pub_date ) ) {
		$blocks[] = "<!-- wp:paragraph -->\n"
			. '<p>' . $pub_date . '</p>'
			. "\n<!-- /wp:paragraph -->";
	}

	// Volume/Issue/Page
	$citation_parts = array();
	if ( isset( $data['primary_location'] ) && isset( $data['primary_location']['raw_source_name'] ) ) {
		$citation_parts[] = $data['primary_location']['raw_source_name'];
	}
	if ( isset( $data['biblio']['volume'] ) ) {
		$citation_parts[] = __( 'Vol.', 'wp-publications' ) . ' ' . $data['biblio']['volume'];
	}
	if ( isset( $data['biblio']['issue'] ) ) {
		$citation_parts[] = __( 'Issue', 'wp-publications' ) . ' ' . $data['biblio']['issue'];
	}

	$doi = isset( $data['doi'] ) ? $data['doi'] : '';
	if ( ! empty( $doi ) ) {
			$citation_parts[] = '<a href="' . esc_url( $doi ) . '" target="_blank" rel="noopener">' . esc_html( $doi ) . '</a></p>';
	}

	if ( ! empty( $citation_parts ) ) {
		$blocks[] = "<!-- wp:paragraph -->\n<p>" . implode( ', ', $citation_parts ) . "\n<!-- /wp:paragraph -->";
	}

	// Authors
	$authors = wp_publications_extract_authors( $data );
	if ( ! empty( $authors ) ) {
		$author_parts = array();
		foreach ( $authors as $author ) {
			if ( ! empty( $author['orcid'] ) ) {
				$author_parts[] = '<a href="' . esc_url( $author['orcid'] ) . '" target="_blank" rel="noopener">' . esc_html( $author['display_name'] ) . '</a>';
			} else {
				$author_parts[] = esc_html( $author['display_name'] );
			}
		}

		$blocks[] = "<!-- wp:paragraph -->\n"
			. '<p>' . implode( ', ', $author_parts ) . '</p>'
			. "\n<!-- /wp:paragraph -->";
	}

	// Abstract
	$abstract = wp_publications_extract_abstract( $data );
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
 * Format publication date from Crossref data
 */
function wp_publications_format_date( $date ) {

	$date_format = get_option( 'date_format', 'F j, Y' );

	// Create DateTime object
	$datetime = new DateTime( $date );

	// Use WordPress localization
	return date_i18n( $date_format, $datetime->getTimestamp() );
}

/**
 * Get journal image from journal data
 */
function wp_publications_get_journal_image( $journal_data ) {
	if ( empty( $journal_data ) || ! isset( $journal_data['message'] ) ) {
		return '';
	}

	// Check for cover image or other image fields
	if ( isset( $journal_data['message']['cover-url'] ) ) {
		return $journal_data['message']['cover-url'];
	}

	return '';
}
