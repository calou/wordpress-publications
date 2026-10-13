<?php
/**
 * OpenAlex API functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch work data from OpenAlex API by DOI.
 *
 * Returns a normalized work array (see wp_publications_normalize_openalex_work()).
 */
function wp_publications_fetch_openalex_work( $doi, $mailto ) {
	$doi = wp_publications_normalize_doi( $doi );

	$url = 'https://api.openalex.org/works/doi:' . rawurlencode( $doi );

	if ( ! empty( $mailto ) ) {
		$url = add_query_arg( 'mailto', $mailto, $url );
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout'  => 30,
			'headers'  => array(
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

	$work = wp_publications_normalize_openalex_work( $data );

	return array(
		'success' => true,
		'data'    => $work,
	);
}

/**
 * Convert a raw OpenAlex work response into the normalized structure used
 * throughout the plugin.
 *
 * Normalized work format:
 * array(
 *   'doi'       => '10.xxxx',
 *   'url'       => 'https://doi.org/10.xxxx',
 *   'title'     => 'Article title',
 *   'type'      => 'article',
 *   'journal'   => 'Journal name',
 *   'publisher' => 'Publisher name (best-effort)',
 *   'volume'    => '585',
 *   'issue'     => '7825',
 *   'pages'     => '357–362',
 *   'year'      => 2020,
 *   'date'      => '2020-09-16',
 *   'abstract'  => 'Reconstructed abstract text',
 *   'authors'   => array( array( 'given','family','name','orcid' ) ),
 *   'image'     => 'https://... cover image (may be empty)',
 *   'links'     => array( array( 'url','content_type','intended_application' ) ),
 * )
 */
function wp_publications_normalize_openalex_work( $raw ) {
	$doi = isset( $raw['doi'] ) ? wp_publications_normalize_doi( $raw['doi'] ) : '';

	$title = isset( $raw['display_name'] ) ? $raw['display_name'] : '';
	if ( empty( $title ) && isset( $raw['title'] ) ) {
		$title = $raw['title'];
	}

	$journal = '';
	if ( isset( $raw['primary_location']['source']['display_name'] ) ) {
		$journal = $raw['primary_location']['source']['display_name'];
	}

	$publisher = '';
	if ( isset( $raw['primary_location']['source']['publisher'] ) ) {
		$publisher = $raw['primary_location']['source']['publisher'];
	}

	$biblio     = isset( $raw['biblio'] ) ? $raw['biblio'] : array();
	$volume     = isset( $biblio['volume'] ) ? $biblio['volume'] : '';
	$issue      = isset( $biblio['issue'] ) ? $biblio['issue'] : '';
	$first_page = isset( $biblio['first_page'] ) ? $biblio['first_page'] : '';
	$last_page  = isset( $biblio['last_page'] ) ? $biblio['last_page'] : '';
	$pages      = $first_page;
	if ( ! empty( $last_page ) && $last_page !== $first_page ) {
		$pages .= '–' . $last_page;
	}

	$year = isset( $raw['publication_year'] ) ? $raw['publication_year'] : '';

	$date = isset( $raw['publication_date'] ) ? $raw['publication_date'] : '';
	if ( empty( $date ) && ! empty( $year ) ) {
		$date = $year;
	}

	$abstract = wp_publications_reconstruct_abstract(
		isset( $raw['abstract_inverted_index'] ) ? $raw['abstract_inverted_index'] : array()
	);

	// OpenAlex abstracts are commonly prefixed with the literal word "Abstract".
	// Since the plugin renders its own "Abstract" heading, drop that prefix.
	$abstract = preg_replace( '/^\s*Abstract\b[\s:]*/i', '', $abstract );

	$authors = array();
	if ( isset( $raw['authorships'] ) && is_array( $raw['authorships'] ) ) {
		$authors = wp_publications_normalize_openalex_authors( $raw['authorships'] );
	}

	$image = '';
	if ( ! empty( $raw['primary_image']['url'] ) ) {
		$image = $raw['primary_image']['url'];
	} elseif ( ! empty( $raw['primary_image']['thumbnail'] ) ) {
		$image = $raw['primary_image']['thumbnail'];
	}

	$links = array();
	$loc   = isset( $raw['primary_location'] ) ? $raw['primary_location'] : array();
	if ( ! empty( $loc['landing_page_url'] ) ) {
		$links[] = array(
			'url'                  => $loc['landing_page_url'],
			'content_type'         => 'text/html',
			'intended_application' => 'text-mining',
		);
	}
	if ( ! empty( $loc['pdf_url'] ) ) {
		$links[] = array(
			'url'                  => $loc['pdf_url'],
			'content_type'         => 'application/pdf',
			'intended_application' => 'text-mining',
		);
	}

	return array(
		'doi'       => $doi,
		'url'       => 'https://doi.org/' . $doi,
		'title'     => $title,
		'type'      => isset( $raw['type'] ) ? $raw['type'] : '',
		'journal'   => $journal,
		'publisher' => $publisher,
		'volume'    => $volume,
		'issue'     => $issue,
		'pages'     => $pages,
		'year'      => $year,
		'date'      => $date,
		'abstract'  => $abstract,
		'authors'   => $authors,
		'image'     => $image,
		'links'     => $links,
	);
}

/**
 * Convert OpenAlex authorships into the plugin's author array.
 *
 * Each author is: array( 'given', 'family', 'name', 'orcid' ).
 */
function wp_publications_normalize_openalex_authors( $authorships ) {
	$authors = array();

	foreach ( $authorships as $authorship ) {
		if ( ! is_array( $authorship ) ) {
			continue;
		}

		$export = '';
		if ( isset( $authorship['author']['display_name'] ) ) {
			$export = $authorship['author']['display_name'];
		} elseif ( isset( $authorship['raw_author_name'] ) ) {
			$export = $authorship['raw_author_name'];
		}

		if ( empty( $export ) ) {
			continue;
		}

		$orcid = '';
		if ( ! empty( $authorship['author']['orcid'] ) ) {
			$orcid = $authorship['author']['orcid'];
		} elseif ( ! empty( $authorship['orcid'] ) ) {
			$orcid = $authorship['orcid'];
		}

		$parts = wp_publications_split_author_name( $export );

		$authors[] = array(
			'given'  => $parts['given'],
			'family' => $parts['family'],
			'name'   => $export,
			'orcid'  => $orcid,
		);
	}

	return $authors;
}

/**
 * Best-effort split of a full author name into given/family parts.
 *
 * OpenAlex only exposes a single display name, so the last whitespace-
 * separated token is treated as the family name and the rest as the given
 * name. Compound surnames (e.g. "van der Berg") are not handled perfectly.
 */
function wp_publications_split_author_name( $name ) {
	$name = trim( (string) $name );

	if ( '' === $name ) {
		return array( 'given' => '', 'family' => '' );
	}

	$parts = preg_split( '/\s+/', $name );

	if ( count( $parts ) === 1 ) {
		return array( 'given' => '', 'family' => $parts[0] );
	}

	$family = array_pop( $parts );

	return array(
		'given'  => implode( ' ', $parts ),
		'family' => $family,
	);
}

/**
 * Reconstruct plain-text abstract from OpenAlex's abstract_inverted_index.
 */
function wp_publications_reconstruct_abstract( $inverted_index ) {
	if ( empty( $inverted_index ) || ! is_array( $inverted_index ) ) {
		return '';
	}

	$words = array();

	foreach ( $inverted_index as $word => $positions ) {
		if ( ! is_array( $positions ) ) {
			continue;
		}
		foreach ( $positions as $position ) {
			$words[ (int) $position ] = $word;
		}
	}

	if ( empty( $words ) ) {
		return '';
	}

	ksort( $words );

	return trim( implode( ' ', $words ) );
}

/**
 * Extract title from normalized work data
 */
function wp_publications_extract_title( $work ) {
	if ( isset( $work['title'] ) && ! empty( $work['title'] ) ) {
		return $work['title'];
	}

	return __( 'Untitled Publication', 'wp-publications' );
}

/**
 * Extract abstract from normalized work data
 */
function wp_publications_extract_abstract( $work ) {
	return isset( $work['abstract'] ) ? $work['abstract'] : '';
}

/**
 * Extract authors from normalized work data
 */
function wp_publications_extract_authors( $work ) {
	return isset( $work['authors'] ) && is_array( $work['authors'] ) ? $work['authors'] : array();
}

/**
 * Extract journal name from normalized work data
 */
function wp_publications_extract_journal_name( $work ) {
	return isset( $work['journal'] ) ? $work['journal'] : '';
}

/**
 * Extract links from normalized work data
 */
function wp_publications_extract_links( $work ) {
	return isset( $work['links'] ) && is_array( $work['links'] ) ? $work['links'] : array();
}

/**
 * Extract images from normalized work data
 */
function wp_publications_extract_images( $work ) {
	$images = array();

	if ( ! empty( $work['image'] ) ) {
		$images[] = $work['image'];
	}

	foreach ( wp_publications_extract_links( $work ) as $link ) {
		if ( isset( $link['content_type'] ) && strpos( $link['content_type'], 'image' ) !== false ) {
			$images[] = $link['url'];
		}
	}

	return array_values( array_unique( $images ) );
}
