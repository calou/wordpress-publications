<?php
/**
 * Post creation and update functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create or update a publication post
 */
function wp_publications_create_or_update_post( $doi, $crossref_data, $tag_ids = array() ) {
	$doi           = wp_publications_normalize_doi( $doi );
	$existing_post = wp_publications_find_by_doi( $doi );

	// Extract title
	$title = wp_publications_extract_title( $crossref_data );

	// Generate content
	$content = wp_publications_generate_content( $crossref_data );

	$post_data = array(
		'post_title'   => $title,
		'post_content' => $content,
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_date'    => wp_publications_block_extract_full_date( $crossref_data ) . ' 12:00:00',
	);

	if ( $existing_post ) {
		// Update existing post
		$post_data['ID'] = $existing_post->ID;
		$post_id         = wp_update_post( $post_data );

		// Get existing tags and merge with new ones
		$existing_tags = wp_get_post_tags( $existing_post->ID, array( 'fields' => 'ids' ) );
		$merged_tags   = array_unique( array_merge( $existing_tags, $tag_ids ) );

		// Set tags
		if ( ! empty( $merged_tags ) ) {
			wp_set_post_tags( $post_id, $merged_tags, false );
		}
	} else {
		// Create new post
		$post_id = wp_insert_post( $post_data );

		// Set tags for new post
		if ( ! empty( $tag_ids ) && ! is_wp_error( $post_id ) ) {
			wp_set_post_tags( $post_id, $tag_ids, false );
		}
	}

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// Save metadata
	update_post_meta( $post_id, '_wp_page_template', 'single-publication' );
	update_post_meta( $post_id, WP_PUBLICATIONS_META_DOI, $doi );
	update_post_meta( $post_id, WP_PUBLICATIONS_META_CROSSREF, wp_json_encode( $crossref_data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

	return $post_id;
}
