<?php
/**
 * Publication Template Loader
 *
 * Loads plugin templates for Block themes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Block Template for Publication Post Type
 */
function wp_publication_block_theme_template() {
	// Check if Gutenberg/block editor is available
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}
	$template_content = '<!-- wp:template-part {"slug":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="margin-top:var(--wp--preset--spacing--60)"><!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:post-title {"level":1} /-->

<!-- wp:post-featured-image {"aspectRatio":"3/2"} /-->

<!-- wp:post-content {"align":"full","layout":{"type":"constrained"}} /-->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:post-terms {"term":"post_tag","separator":"  ","className":"is-style-post-terms-1"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->';
	// Register the block template
	register_block_template(
		'wp-publications//single-publication',
		array(
			'title'       => __( 'Single Publication', 'wp-publications' ),
			'description' => __( 'Template for single publication posts.', 'wp-publications' ),
			'content'     => $template_content,
			'post_types'  => array( 'post' ),
			'is_default'  => false,
		)
	);
}
add_action( 'init', 'wp_publication_block_theme_template', 20 ); // Run after CPT registration
