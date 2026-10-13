<?php
/**
 * Admin list-table customisation for the 'publication' post type.
 *
 * Adds DOI / Journal / Authors columns and makes the admin search match the
 * publication's title, DOI, journal, authors and year.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read and decode the stored work JSON for a publication.
 */
function wp_publications_get_work_meta( $post_id ) {
	$raw  = get_post_meta( $post_id, WP_PUBLICATIONS_META_WORK, true );
	$work = $raw ? json_decode( $raw, true ) : array();
	return is_array( $work ) ? $work : array();
}

/**
 * Define the columns for the publications list.
 *
 * Order: checkbox, DOI, title, journal, authors, then any remaining defaults
 * (e.g. date).
 */
function wp_publications_columns( $columns ) {
	$cb = isset( $columns['cb'] ) ? $columns['cb'] : '';
	unset( $columns['cb'] );

	$new = array(
		'cb'  => $cb,
		'doi' => __( 'DOI', 'wp-publications' ),
	);

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['journal'] = __( 'Journal', 'wp-publications' );
			$new['authors'] = __( 'Authors', 'wp-publications' );
		}
	}

	return $new;
}
add_filter( 'manage_publication_posts_columns', 'wp_publications_columns' );

/**
 * Render the custom column cells.
 */
function wp_publications_custom_column( $column, $post_id ) {
	switch ( $column ) {
		case 'doi':
			$doi = get_post_meta( $post_id, WP_PUBLICATIONS_META_DOI, true );
			if ( empty( $doi ) ) {
				$work = wp_publications_get_work_meta( $post_id );
				$doi  = isset( $work['doi'] ) ? $work['doi'] : '';
			}
			echo $doi ? esc_html( $doi ) : '&mdash;';
			break;

		case 'journal':
			$work    = wp_publications_get_work_meta( $post_id );
			$journal = isset( $work['journal'] ) ? $work['journal'] : '';
			echo $journal ? esc_html( $journal ) : '&mdash;';
			break;

		case 'authors':
			$work    = wp_publications_get_work_meta( $post_id );
			$authors = isset( $work['authors'] ) && is_array( $work['authors'] ) ? $work['authors'] : array();
			if ( empty( $authors ) ) {
				echo '&mdash;';
				break;
			}
			$names = array();
			foreach ( $authors as $author ) {
				$author = wp_parse_args( (array) $author, array( 'given' => '', 'family' => '', 'name' => '' ) );
				$name   = $author['name'] ? $author['name'] : trim( $author['given'] . ' ' . $author['family'] );
				if ( '' !== $name ) {
					$names[] = $name;
				}
			}
			echo esc_html( implode( ', ', $names ) );
			break;
	}
}
add_action( 'manage_publication_posts_custom_column', 'wp_publications_custom_column', 10, 2 );

/**
 * Extend the admin search for the publications list so it also matches the
 * stored work JSON (title, DOI, journal, authors, year).
 */
function wp_publications_admin_search( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'publication' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( empty( $query->get( 's' ) ) ) {
		return;
	}

	add_filter( 'posts_join', 'wp_publications_search_join', 10, 2 );
	add_filter( 'posts_search', 'wp_publications_search_clause', 10, 2 );
}

/**
 * Add a LEFT JOIN to the work-data postmeta so it can be searched.
 */
function wp_publications_search_join( $join, $query ) {
	if ( 'publication' !== $query->get( 'post_type' ) || empty( $query->get( 's' ) ) ) {
		return $join;
	}
	if ( false === strpos( $join, 'wp_pub_meta' ) ) {
		global $wpdb;
		$join .= " LEFT JOIN {$wpdb->postmeta} wp_pub_meta ON ( {$wpdb->posts}.ID = wp_pub_meta.post_id AND wp_pub_meta.meta_key = '" . WP_PUBLICATIONS_META_WORK . "' )";
	}
	return $join;
}

/**
 * Replace the default search clause with one that ORs the title/content/
 * excerpt search with a LIKE against the work JSON.
 */
function wp_publications_search_clause( $search, $query ) {
	if ( 'publication' !== $query->get( 'post_type' ) || empty( $query->get( 's' ) ) ) {
		return $search;
	}

	global $wpdb;
	$t    = $wpdb->posts;
	$like = '%' . $wpdb->esc_like( $query->get( 's' ) ) . '%';

	return $wpdb->prepare(
		"AND ( ( ( {$t}.post_title LIKE %s ) OR ( {$t}.post_content LIKE %s ) OR ( {$t}.post_excerpt LIKE %s ) OR ( wp_pub_meta.meta_value LIKE %s ) ) )",
		$like,
		$like,
		$like,
		$like
	);
}
add_action( 'pre_get_posts', 'wp_publications_admin_search' );
