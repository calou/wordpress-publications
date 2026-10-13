<?php
/**
 * Publication custom post type registration.
 *
 * Publications are stored as a dedicated, non-public post type so they never
 * mix with regular blog posts and have no publicly-accessible single page.
 * They are rendered only via the Publications block.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'wp_publications_register_post_type' );

/**
 * Register the 'publication' custom post type.
 */
function wp_publications_register_post_type() {
	$args = array(
		'labels'              => array(
			'name'               => __( 'Publications', 'wp-publications' ),
			'singular_name'      => __( 'Publication', 'wp-publications' ),
			'add_new'            => __( 'Add New', 'wp-publications' ),
			'add_new_item'       => __( 'Add New Publication', 'wp-publications' ),
			'edit_item'          => __( 'Edit Publication', 'wp-publications' ),
			'new_item'           => __( 'New Publication', 'wp-publications' ),
			'view_item'          => __( 'View Publication', 'wp-publications' ),
			'search_items'       => __( 'Search Publications', 'wp-publications' ),
			'not_found'          => __( 'No publications found.', 'wp-publications' ),
			'not_found_in_trash' => __( 'No publications found in Trash.', 'wp-publications' ),
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => 'wp-publications',
		'show_in_rest'        => true,
		'taxonomies'          => array( 'post_tag' ),
		'supports'            => array( 'title', 'editor', 'thumbnail' ),
		'has_archive'         => false,
		'rewrite'             => false,
		'menu_icon'           => 'dashicons-book-alt',
	);

	register_post_type( 'publication', $args );
}
