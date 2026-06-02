<?php
/**
 * Plugin Name: WP Publications
 * Plugin URI: https://github.com/your-username/wp-publications
 * Description: Fetch and display academic publications from Crossref API based on DOIs.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-publications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_PUBLICATIONS_VERSION', '1.0.0' );
define( 'WP_PUBLICATIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_PUBLICATIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_PUBLICATIONS_META_DOI', '_publication_doi' );
define( 'WP_PUBLICATIONS_META_DATA', '_publication_data' );

// Include required files
require_once WP_PUBLICATIONS_PLUGIN_DIR . 'includes/functions.php';
require_once WP_PUBLICATIONS_PLUGIN_DIR . 'includes/openalex-api.php';
require_once WP_PUBLICATIONS_PLUGIN_DIR . 'includes/post-functions.php';
require_once WP_PUBLICATIONS_PLUGIN_DIR . 'includes/content-generator.php';
require_once WP_PUBLICATIONS_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once WP_PUBLICATIONS_PLUGIN_DIR . 'includes/block-functions.php';
require_once WP_PUBLICATIONS_PLUGIN_DIR . 'includes/template-loader.php';

if ( is_admin() ) {
	include_once WP_PUBLICATIONS_PLUGIN_DIR . 'admin/admin-menu.php';
	include_once WP_PUBLICATIONS_PLUGIN_DIR . 'admin/import-page.php';
	include_once WP_PUBLICATIONS_PLUGIN_DIR . 'admin/settings-page.php';
}


// Enqueue admin scripts and styles
add_action( 'admin_enqueue_scripts', 'wp_publications_admin_enqueue' );

function wp_publications_admin_enqueue( $hook ) {
	if ( strpos( $hook, 'wp-publications' ) === false && strpos( $hook, 'publications' ) === false ) {
		return;
	}

	// Select2
	wp_enqueue_style(
		'select2',
		'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
		array(),
		'4.1.0-rc.0'
	);
	wp_enqueue_script(
		'select2',
		'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
		array( 'jquery' ),
		'4.1.0-rc.0',
		true
	);

	// Plugin admin styles
	wp_enqueue_style(
		'wp-publications-admin',
		WP_PUBLICATIONS_PLUGIN_URL . 'assets/css/admin.css',
		array( 'select2' ),
		WP_PUBLICATIONS_VERSION
	);

	// Plugin admin scripts
	wp_enqueue_script(
		'wp-publications-admin',
		WP_PUBLICATIONS_PLUGIN_URL . 'assets/js/admin.js',
		array( 'jquery', 'select2' ),
		WP_PUBLICATIONS_VERSION,
		true
	);

	wp_localize_script(
		'wp-publications-admin',
		'wpPublications',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wp_publications_nonce' ),
			'strings' => array(
				'importing'  => __( 'Importing publications...', 'wp-publications' ),
				'success'    => __( 'Import completed successfully!', 'wp-publications' ),
				'error'      => __( 'An error occurred during import.', 'wp-publications' ),
				'selectTags' => __( 'Select tags...', 'wp-publications' ),
			),
		)
	);
}
