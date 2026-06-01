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
function wp_publications_generate_content( $crossref_data ) {
	$blocks = array();

	// Publication date
	$pub_date = wp_publications_format_date( $crossref_data );
	if ( ! empty( $pub_date ) ) {
		$blocks[] = "<!-- wp:paragraph -->\n"
			. '<p>' . esc_html( $pub_date ) . '</p>'
			. "\n<!-- /wp:paragraph -->";
	}

	// Volume/Issue/Page
	$citation_parts = array();
	if ( isset( $crossref_data['message']['publisher'] ) ) {
		$citation_parts[] = $crossref_data['message']['publisher'];
	}
	if ( isset( $crossref_data['message']['volume'] ) ) {
		$citation_parts[] = __( 'Vol.', 'wp-publications' ) . ' ' . $crossref_data['message']['volume'];
	}
	if ( isset( $crossref_data['message']['issue'] ) ) {
		$citation_parts[] = __( 'Issue', 'wp-publications' ) . ' ' . $crossref_data['message']['issue'];
	}
	if ( isset( $crossref_data['message']['page'] ) ) {
		$citation_parts[] = __( 'pp.', 'wp-publications' ) . ' ' . $crossref_data['message']['page'];
	}

	$doi = isset( $crossref_data['message']['DOI'] ) ? $crossref_data['message']['DOI'] : '';
	if ( ! empty( $doi ) ) {
			$citation_parts[] = '<a href="' . esc_url( 'https://doi.org/' . $doi ) . '" target="_blank" rel="noopener">' . esc_html( $doi ) . '</a></p>';
	}

	if ( ! empty( $citation_parts ) ) {
		$blocks[] = "<!-- wp:paragraph -->\n<p>" . implode( ', ', $citation_parts ) . "\n<!-- /wp:paragraph -->";
	}

	// Authors
	$authors = wp_publications_extract_authors( $crossref_data );
	if ( ! empty( $authors ) ) {
		$author_parts = array();
		foreach ( $authors as $author ) {
			$name = trim( $author['given'] . ' ' . $author['family'] );
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
	$abstract = wp_publications_extract_abstract( $crossref_data );
	if ( ! empty( $abstract ) ) {
		$blocks[] = "<!-- wp:heading {\"level\":3} -->\n"
			. '<h3 class="wp-block-heading">' . esc_html__( 'Abstract', 'wp-publications' ) . '</h3>'
			. "\n<!-- /wp:heading -->";
		$blocks[] = "<!-- wp:paragraph -->\n"
			. '<p>' . wp_kses_post( $abstract ) . '</p>'
			. "\n<!-- /wp:paragraph -->";
	}

	return implode( "\n\n", $blocks );
}

/**
 * Format publication date from Crossref data
 */
function wp_publications_format_date( $crossref_data ) {
	$date_parts = null;

	// Try different date fields
	$date_fields = array( 'published-print', 'published-online', 'issued', 'created' );

	foreach ( $date_fields as $field ) {
		if ( isset( $crossref_data['message'][ $field ]['date-parts'][0] ) ) {
			$date_parts = $crossref_data['message'][ $field ]['date-parts'][0];
			break;
		}
	}

	if ( empty( $date_parts ) ) {
		return '';
	}

	$year  = isset( $date_parts[0] ) ? $date_parts[0] : '';
	$month = isset( $date_parts[1] ) ? str_pad( $date_parts[1], 2, '0', STR_PAD_LEFT ) : '';
	$day   = isset( $date_parts[2] ) ? str_pad( $date_parts[2], 2, '0', STR_PAD_LEFT ) : '';

	if ( ! empty( $year ) && ! empty( $month ) && ! empty( $day ) ) {
		return date_i18n( get_option( 'date_format' ), strtotime( "$year-$month-$day" ) );
	} elseif ( ! empty( $year ) && ! empty( $month ) ) {
		return date_i18n( 'F Y', strtotime( "$year-$month-01" ) );
	} elseif ( ! empty( $year ) ) {
		return $year;
	}

	return '';
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
