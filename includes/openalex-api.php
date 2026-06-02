<?php
/**
 * Crossref API functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch work data from OpenAlex API
 */
function wp_publications_fetch_openalex_work( $doi, $mailto ) {
	$doi = wp_publications_normalize_doi( $doi );

	$url = sprintf(
		'https://api.openalex.org/works/https://doi.org/%s',
		urlencode( $doi )
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'WP-Publications/1.0.0 (WordPress Plugin; mailto:' . $mailto . ')',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'success' => false,
			'error'   => $response->get_error_message(),
		);
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );

	if ( $code !== 200 ) {
		return array(
			'success' => false,
			'error'   => sprintf( __( 'API returned status code %d', 'wp-publications' ), $code ),
		);
	}

	$data = json_decode( $body, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return array(
			'success' => false,
			'error'   => __( 'Invalid JSON response from API', 'wp-publications' ),
		);
	}

	return array(
		'success' => true,
		'data'    => $data,
	);
}


function wp_publications_extract_abstract( array $data ): string {
	$abstract_inverted_index = $data['abstract_inverted_index'];
	if ( empty( $abstract_inverted_index ) ) {
		return '';
	}

	// Find the maximum position to determine array size
	$max_position = 0;
	foreach ( $abstract_inverted_index as $positions ) {
		if ( is_array( $positions ) ) {
			$local_max = max( $positions );
			if ( $local_max > $max_position ) {
				$max_position = $local_max;
			}
		}
	}

	// If no positions found, return empty string
	if ( $max_position === 0 ) {
		return '';
	}

	// Create array with enough slots
	$text_array = array_fill( 0, $max_position + 1, '' );

	// Place each word at its position(s)
	foreach ( $abstract_inverted_index as $word => $positions ) {
		if ( ! is_array( $positions ) ) {
			$positions = array( (int) $positions );
		}

		foreach ( $positions as $position ) {
			$position                = (int) $position;
			$text_array[ $position ] = $word;
		}
	}

	// Join words with spaces, collapse multiple spaces
	$reconstructed = implode( ' ', $text_array );
	$reconstructed = preg_replace( '/\s+/', ' ', $reconstructed );
	$reconstructed = trim( $reconstructed );

	// Capitalize first letter
	if ( ! empty( $reconstructed ) ) {
		$reconstructed = mb_strtoupper( mb_substr( $reconstructed, 0, 1, 'UTF-8' ), 'UTF-8' )
						. mb_substr( $reconstructed, 1, null, 'UTF-8' );
	}

	return $reconstructed;
}

/**
 * Extract authors from work data
 */
function wp_publications_extract_authors( $data ) {
	$authors = array();

	if ( isset( $data['authorships'] ) ) {
		foreach ( $data ['authorships'] as $author ) {
			$author_data = array(
				'display_name' => $author['author']['display_name'],
				'orcid'        => $author['author']['orcid'],
			);

			$authors[] = $author_data;
		}
	}

	return $authors;
}
