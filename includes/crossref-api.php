<?php
/**
 * Crossref API functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch work data from Crossref API
 */
function wp_publications_fetch_crossref_work( $doi, $mailto ) {
	$doi = wp_publications_normalize_doi( $doi );

	$url = sprintf(
		'https://api.crossref.org/works/%s?mailto=%s',
		urlencode( $doi ),
		urlencode( $mailto )
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

	if ( ! isset( $data['message'] ) ) {
		return array(
			'success' => false,
			'error'   => __( 'Unexpected API response format', 'wp-publications' ),
		);
	}

	return array(
		'success' => true,
		'data'    => $data,
	);
}

/**
 * Extract title from work data
 */
function wp_publications_extract_title( $work_data ) {
	if ( isset( $work_data['message']['title'] ) && ! empty( $work_data['message']['title'] ) ) {
		return is_array( $work_data['message']['title'] )
			? reset( $work_data['message']['title'] )
			: $work_data['message']['title'];
	}

	return __( 'Untitled Publication', 'wp-publications' );
}

/**
 * Extract abstract from work data
 */
function wp_publications_extract_abstract( $work_data ) {
	if ( isset( $work_data['message']['abstract'] ) ) {
		// Strip JATS XML tags if present
		$abstract = $work_data['message']['abstract'];
		$abstract = preg_replace( '/<jats:[^>]+>/', '', $abstract );
		$abstract = preg_replace( '/<\/jats:[^>]+>/', '', $abstract );
		return trim( $abstract );
	}

	return '';
}

/**
 * Extract authors from work data
 */
function wp_publications_extract_authors( $work_data ) {
	$authors = array();

	if ( isset( $work_data['message']['author'] ) ) {
		foreach ( $work_data['message']['author'] as $author ) {
			$author_data = array(
				'given'  => isset( $author['given'] ) ? $author['given'] : '',
				'family' => isset( $author['family'] ) ? $author['family'] : '',
				'orcid'  => null,
			);

			if ( isset( $author['ORCID'] ) ) {
				$author_data['orcid'] = $author['ORCID'];
			}

			$authors[] = $author_data;
		}
	}

	return $authors;
}

/**
 * Extract journal name from work data
 */
function wp_publications_extract_journal_name( $work_data ) {
	if ( isset( $work_data['message']['container-title'] ) && ! empty( $work_data['message']['container-title'] ) ) {
		return is_array( $work_data['message']['container-title'] )
			? reset( $work_data['message']['container-title'] )
			: $work_data['message']['container-title'];
	}

	return '';
}

/**
 * Extract links/images from work data
 */
function wp_publications_extract_links( $work_data ) {
	$links = array();

	if ( isset( $work_data['message']['link'] ) ) {
		foreach ( $work_data['message']['link'] as $link ) {
			$links[] = array(
				'url'                  => isset( $link['URL'] ) ? $link['URL'] : '',
				'content_type'         => isset( $link['content-type'] ) ? $link['content-type'] : '',
				'intended_application' => isset( $link['intended-application'] ) ? $link['intended-application'] : '',
			);
		}
	}

	return $links;
}

/**
 * Extract images from work data (from various fields)
 */
function wp_publications_extract_images( $work_data ) {
	$images = array();

	// Check for subject images or covers
	if ( isset( $work_data['message']['link'] ) ) {
		foreach ( $work_data['message']['link'] as $link ) {
			if ( isset( $link['content-type'] ) && strpos( $link['content-type'], 'image' ) !== false ) {
				$images[] = $link['URL'];
			}
		}
	}

	return array_unique( $images );
}
