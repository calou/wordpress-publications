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
function wp_publications_create_or_update_post( $doi, $work, $tag_ids = array() ) {
	$doi           = wp_publications_normalize_doi( $doi );
	$existing_post = wp_publications_find_by_doi( $doi );

	// Extract title
	$title = wp_publications_extract_title( $work );

	// Generate content
	$content = wp_publications_generate_content( $work );

	$full_date = wp_publications_block_extract_full_date( $work );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $full_date ) ) {
		$full_date = current_time( 'Y-m-d' );
	}

	$post_data = array(
		'post_title'   => $title,
		'post_content' => $content,
		'post_type'    => 'publication',
		'post_status'  => 'publish',
		'post_date'    => $full_date . ' 12:00:00',
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
	update_post_meta( $post_id, WP_PUBLICATIONS_META_DOI, $doi );
	update_post_meta( $post_id, WP_PUBLICATIONS_META_WORK, wp_json_encode( $work, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

	// Attach cover image (if provided by OpenAlex and no thumbnail exists yet).
	if ( ! empty( $work['image'] ) ) {
		wp_publications_set_featured_image( $post_id, $work['image'] );
	}

	return $post_id;
}

/**
 * Best-effort assignment of an OpenAlex cover image as the post thumbnail.
 *
 * Uses the media sideloader so the image is hosted locally. Runs a single
 * network request; failures are logged and never break the import.
 */
function wp_publications_set_featured_image( $post_id, $image_url ) {
	if ( empty( $image_url ) ) {
		return;
	}

	// Don't overwrite an existing featured image.
	if ( has_post_thumbnail( $post_id ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );

	if ( is_wp_error( $attachment_id ) ) {
		wp_publications_log( 'Failed to sideload cover image: ' . $attachment_id->get_error_message() );
		return;
	}

	if ( is_numeric( $attachment_id ) ) {
		set_post_thumbnail( $post_id, $attachment_id );
	}
}
