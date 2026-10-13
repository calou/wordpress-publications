<?php
/**
 * Featured image field in the Quick Edit row of the publications list.
 *
 * WordPress doesn't ship a featured-image control in Quick Edit, so we add one
 * via the `quick_edit_custom_box` action and save the chosen attachment through
 * `save_post_{post_type}`. The media library picker (wp.media) powers the UI.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the featured image control inside the inline edit row.
 *
 * Fires once per non-core custom column; we only render on the first one so the
 * field appears a single time in the Quick Edit form.
 */
function wp_publications_quick_edit_field( $column_name, $post_type ) {
	if ( 'publication' !== $post_type || 'doi' !== $column_name ) {
		return;
	}
	?>
	<div class="inline-edit-col wp-publications-quick-thumb">
		<span class="title"><strong><?php esc_html_e( 'Featured image', 'wp-publications' ); ?></strong></span>
		<div class="wp-publications-quick-thumb-preview">
			<img class="wp-publications-quick-thumb-img" src="" alt="" style="display:none; max-width:80px; height:auto; margin-bottom:6px; border-radius:2px;" />
			<span class="wp-publications-quick-thumb-none"><?php esc_html_e( 'No image set.', 'wp-publications' ); ?></span>
		</div>
		<input type="hidden" name="wp_publications_thumbnail_id" value="" />
		<p class="wp-publications-quick-thumb-actions">
			<a href="#" class="button wp-publications-quick-thumb-select"><?php esc_html_e( 'Select image', 'wp-publications' ); ?></a>
			<a href="#" class="button button-link-delete wp-publications-quick-thumb-remove" style="display:none;"><?php esc_html_e( 'Remove', 'wp-publications' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'quick_edit_custom_box', 'wp_publications_quick_edit_field', 10, 2 );

/**
 * Output the current featured image details on each row so the Quick Edit
 * control can be pre-populated when it opens.
 */
function wp_publications_quick_edit_row_data( $column, $post_id ) {
	if ( 'authors' !== $column ) {
		return;
	}
	$thumb_id  = get_post_thumbnail_id( $post_id );
	$thumb_url = $thumb_id ? get_the_post_thumbnail_url( $post_id, 'thumbnail' ) : '';
	printf(
		'<span class="wp-publications-thumb-data" data-id="%s" data-url="%s" style="display:none;"></span>',
		esc_attr( $thumb_id ? (string) $thumb_id : '' ),
		esc_url( $thumb_url )
	);
}
add_action( 'manage_publication_posts_custom_column', 'wp_publications_quick_edit_row_data', 20, 2 );

/**
 * Save the thumbnail chosen in Quick Edit.
 *
 * The `wp_publications_thumbnail_id` field only exists in the Quick Edit form,
 * so this never interferes with regular saves or imports.
 */
function wp_publications_save_quick_edit_thumbnail( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['wp_publications_thumbnail_id'] ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$thumb_id = absint( $_POST['wp_publications_thumbnail_id'] );
	if ( $thumb_id ) {
		set_post_thumbnail( $post_id, $thumb_id );
	} else {
		delete_post_thumbnail( $post_id );
	}
}
add_action( 'save_post_publication', 'wp_publications_save_quick_edit_thumbnail', 20 );

/**
 * Enqueue the media picker and our script on the publications list screen.
 */
function wp_publications_enqueue_quick_edit( $hook ) {
	if ( 'edit.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'publication' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script(
		'wp-publications-quick-edit',
		WP_PUBLICATIONS_PLUGIN_URL . 'assets/js/publications-quick-edit.js',
		array( 'jquery', 'media-editor' ),
		WP_PUBLICATIONS_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'wp_publications_enqueue_quick_edit' );
