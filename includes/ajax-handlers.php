<?php
/**
 * AJAX handlers for WP Publications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register AJAX handlers
add_action( 'wp_ajax_wp_publications_import', 'wp_publications_ajax_import' );
add_action( 'wp_ajax_wp_publications_import_single', 'wp_publications_ajax_import_single' );

/**
 * AJAX handler for batch import initialization
 */
function wp_publications_ajax_import() {
	check_ajax_referer( 'wp_publications_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to import publications.', 'wp-publications' ),
			)
		);
	}

	$mailto = wp_publications_get_mailto();
	if ( empty( $mailto ) || ! wp_publications_is_valid_email( $mailto ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Please configure a valid email address in the settings first.', 'wp-publications' ),
			)
		);
	}

	$dois_input = isset( $_POST['dois'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dois'] ) ) : '';
	$dois       = wp_publications_parse_doi_list( $dois_input );

	if ( empty( $dois ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Please enter at least one valid DOI.', 'wp-publications' ),
			)
		);
	}

	$tag_ids = isset( $_POST['tags'] ) ? wp_publications_sanitize_tag_ids( $_POST['tags'] ) : array();
	$delay   = wp_publications_get_api_delay();

	wp_send_json_success(
		array(
			'dois'    => $dois,
			'total'   => count( $dois ),
			'tag_ids' => $tag_ids,
			'delay'   => $delay,
			'mailto'  => $mailto,
		)
	);
}

/**
 * AJAX handler for importing a single DOI
 */
function wp_publications_ajax_import_single() {
	check_ajax_referer( 'wp_publications_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to import publications.', 'wp-publications' ),
			)
		);
	}

	$doi     = isset( $_POST['doi'] ) ? sanitize_text_field( wp_unslash( $_POST['doi'] ) ) : '';
	$mailto  = isset( $_POST['mailto'] ) ? sanitize_email( wp_unslash( $_POST['mailto'] ) ) : '';
	$tag_ids = isset( $_POST['tag_ids'] ) ? wp_publications_sanitize_tag_ids( $_POST['tag_ids'] ) : array();

	if ( empty( $doi ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid DOI provided.', 'wp-publications' ),
				'doi'     => $doi,
			)
		);
	}

	if ( empty( $mailto ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'No email address configured.', 'wp-publications' ),
				'doi'     => $doi,
			)
		);
	}

	$existing = wp_publications_find_by_doi( $doi );

	// Fetch work data from Crossref
	$work_result = wp_publications_fetch_crossref_work( $doi, $mailto );

	if ( ! $work_result['success'] ) {
		wp_send_json_error(
			array(
				'message' => $work_result['error'],
				'doi'     => $doi,
			)
		);
	}

	$crossref_data = $work_result['data'];

	// Create or update post
	$post_id = wp_publications_create_or_update_post( $doi, $crossref_data, $tag_ids );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error(
			array(
				'message' => $post_id->get_error_message(),
				'doi'     => $doi,
			)
		);
	}

	$action = ( $existing && $existing->ID === $post_id ) ? 'updated' : 'created';
	$title  = wp_publications_extract_title( $crossref_data );

	wp_send_json_success(
		array(
			'doi'      => $doi,
			'post_id'  => $post_id,
			'title'    => $title,
			'action'   => $action,
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
			'view_url' => get_permalink( $post_id ),
		)
	);
}
