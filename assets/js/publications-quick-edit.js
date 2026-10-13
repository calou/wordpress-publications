/* global wp */
/**
 * Adds a featured image picker to the Quick Edit row of the Publications list.
 *
 * WordPress core clones a single `#inline-edit` template row per post when
 * Quick Edit opens, assigning it the id `edit-<ID>` and the class
 * `inline-editor`. We scope all our read/writes to `.inline-editor` so we never
 * touch the hidden template or a stale row.
 */
( function ( $ ) {
	'use strict';

	var frame = null;

	/**
	 * Return the featured-image control for the currently open Quick Edit row.
	 */
	function getBox() {
		return $( '.inline-editor .wp-publications-quick-thumb' ).first();
	}

	/**
	 * Populate the field from an attachment ID and an image URL.
	 *
	 * @param {number|string} id  Attachment ID (empty string to clear).
	 * @param {string}        url Image URL (empty string to hide the preview).
	 */
	function setThumb( id, url ) {
		var box = getBox();
		if ( ! box.length ) {
			return;
		}

		box.find( 'input[name="wp_publications_thumbnail_id"]' ).val( id || '' );

		var img    = box.find( '.wp-publications-quick-thumb-img' );
		var none   = box.find( '.wp-publications-quick-thumb-none' );
		var remove = box.find( '.wp-publications-quick-thumb-remove' );

		if ( url ) {
			img.attr( 'src', url ).show();
			none.hide();
			remove.show();
		} else {
			img.attr( 'src', '' ).hide();
			none.show();
			remove.hide();
		}
	}

	// Pre-populate the control when Quick Edit is opened for a row. The timeout
	// lets core's inlineEditPost.edit() finish populating the form first.
	$( document ).on( 'click', '.editinline', function () {
		var data = $( this ).closest( 'tr' ).find( '.wp-publications-thumb-data' );
		var id   = String( data.attr( 'data-id' ) || '' );
		var url  = String( data.attr( 'data-url' ) || '' );

		setTimeout( function () {
			setThumb( id, url );
		}, 0 );
	} );

	// Open the media library picker.
	$( document ).on( 'click', '.wp-publications-quick-thumb-select', function ( e ) {
		e.preventDefault();

		if ( frame ) {
			frame.open();
			return;
		}

		frame = wp.media( {
			title: 'Select featured image',
			button: { text: 'Use this image' },
			multiple: false,
			library: { type: 'image' },
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var thumb      = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
			setThumb( attachment.id, thumb );
		} );

		frame.open();
	} );

	// Clear the current featured image.
	$( document ).on( 'click', '.wp-publications-quick-thumb-remove', function ( e ) {
		e.preventDefault();
		setThumb( '', '' );
	} );

} )( jQuery );
