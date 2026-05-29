<?php
/**
 * Admin menu registration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'wp_publications_admin_menu' );

/**
 * Register admin menu
 */
function wp_publications_admin_menu() {
	// Main menu
	add_menu_page(
		__( 'Publications', 'wp-publications' ),
		__( 'Publications', 'wp-publications' ),
		'edit_posts',
		'wp-publications',
		'wp_publications_import_page',
		'dashicons-book-alt',
		25
	);

	// Import submenu (same as main)
	add_submenu_page(
		'wp-publications',
		__( 'Import Publications', 'wp-publications' ),
		__( 'Import', 'wp-publications' ),
		'edit_posts',
		'wp-publications',
		'wp_publications_import_page'
	);

	// Settings submenu
	add_submenu_page(
		'wp-publications',
		__( 'Settings', 'wp-publications' ),
		__( 'Settings', 'wp-publications' ),
		'manage_options',
		'wp-publications-settings',
		'wp_publications_settings_page'
	);
}
