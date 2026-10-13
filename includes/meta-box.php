<?php
/**
 * Publication data meta box.
 *
 * Lets admins enter/edit the OpenAlex-like normalized work data directly on
 * the 'publication' post type edit screen. On save it is written to the
 * WP_PUBLICATIONS_META_WORK meta (the same structure the block/APA formatter
 * reads), so hand-created publications render in APA format just like
 * imported ones.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the meta box on the publication post type.
 */
function wp_publications_add_work_meta_box() {
	add_meta_box(
		'wp-publications-work-meta',
		__( 'Publication Data (APA)', 'wp-publications' ),
		'wp_publications_work_meta_box',
		'publication',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'wp_publications_add_work_meta_box' );

/**
 * Render the meta box.
 */
function wp_publications_work_meta_box( $post ) {
	wp_nonce_field( 'wp_publications_work_save', 'wp_publications_work_nonce' );

	$raw  = get_post_meta( $post->ID, WP_PUBLICATIONS_META_WORK, true );
	$work = $raw ? json_decode( $raw, true ) : array();
	if ( ! is_array( $work ) ) {
		$work = array();
	}

	$title = isset( $work['title'] ) && '' !== $work['title'] ? $work['title'] : get_the_title( $post );

	$doi       = isset( $work['doi'] ) ? $work['doi'] : '';
	$year      = isset( $work['year'] ) ? $work['year'] : '';
	$journal   = isset( $work['journal'] ) ? $work['journal'] : '';
	$publisher = isset( $work['publisher'] ) ? $work['publisher'] : '';
	$volume    = isset( $work['volume'] ) ? $work['volume'] : '';
	$issue     = isset( $work['issue'] ) ? $work['issue'] : '';
	$pages     = isset( $work['pages'] ) ? $work['pages'] : '';
	$abstract  = isset( $work['abstract'] ) ? $work['abstract'] : '';
	$image     = isset( $work['image'] ) ? $work['image'] : '';
	$authors   = isset( $work['authors'] ) && is_array( $work['authors'] ) ? $work['authors'] : array();

	if ( empty( $authors ) ) {
		$authors = array( array( 'given' => '', 'family' => '', 'name' => '', 'orcid' => '' ) );
	}
	?>
	<style>
		.wp-publications-meta .wp-publications-field { margin-bottom: 12px; }
		.wp-publications-meta label { display: block; font-weight: 600; margin-bottom: 4px; }
		.wp-publications-meta input[type="text"], .wp-publications-meta input[type="url"], .wp-publications-meta textarea { width: 100%; }
		.wp-publications-author-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
		.wp-publications-author-row .wp-publications-author-given { width: 22%; }
		.wp-publications-author-row .wp-publications-author-family { width: 22%; }
		.wp-publications-author-row .wp-publications-author-orcid { width: 30%; }
		.wp-publications-author-row .wp-publications-remove-author { flex: none; }
		.wp-publications-row-actions { margin-top: 4px; }
		.wp-publications-hint { color: #666; font-style: italic; margin-bottom: 12px; }
	</style>

	<div class="wp-publications-meta">
		<p class="wp-publications-hint">
			<?php esc_html_e( 'The document title is taken from the post title above. Everything else is stored as the OpenAlex-style work JSON used to render the APA citation.', 'wp-publications' ); ?>
		</p>

		<div class="wp-publications-field">
			<label for="wp-publications-doi"><?php esc_html_e( 'DOI', 'wp-publications' ); ?></label>
			<input type="text" id="wp-publications-doi" name="wp_publications_doi" value="<?php echo esc_attr( $doi ); ?>" placeholder="10.xxxx/xxxxx">
		</div>

		<div class="wp-publications-field">
			<label for="wp-publications-year"><?php esc_html_e( 'Publication year', 'wp-publications' ); ?></label>
			<input type="text" id="wp-publications-year" name="wp_publications_year" value="<?php echo esc_attr( $year ); ?>" placeholder="2020">
		</div>

		<div class="wp-publications-field">
			<label for="wp-publications-journal"><?php esc_html_e( 'Journal name', 'wp-publications' ); ?></label>
			<input type="text" id="wp-publications-journal" name="wp_publications_journal" value="<?php echo esc_attr( $journal ); ?>">
		</div>

		<div class="wp-publications-field">
			<label for="wp-publications-publisher"><?php esc_html_e( 'Publisher (optional)', 'wp-publications' ); ?></label>
			<input type="text" id="wp-publications-publisher" name="wp_publications_publisher" value="<?php echo esc_attr( $publisher ); ?>">
		</div>

		<div class="wp-publications-field" style="display:flex; gap:12px;">
			<div style="flex:1;">
				<label for="wp-publications-volume"><?php esc_html_e( 'Volume', 'wp-publications' ); ?></label>
				<input type="text" id="wp-publications-volume" name="wp_publications_volume" value="<?php echo esc_attr( $volume ); ?>">
			</div>
			<div style="flex:1;">
				<label for="wp-publications-issue"><?php esc_html_e( 'Issue', 'wp-publications' ); ?></label>
				<input type="text" id="wp-publications-issue" name="wp_publications_issue" value="<?php echo esc_attr( $issue ); ?>">
			</div>
			<div style="flex:1;">
				<label for="wp-publications-pages"><?php esc_html_e( 'Pages', 'wp-publications' ); ?></label>
				<input type="text" id="wp-publications-pages" name="wp_publications_pages" value="<?php echo esc_attr( $pages ); ?>" placeholder="357–362">
			</div>
		</div>

		<div class="wp-publications-field">
			<label for="wp-publications-abstract"><?php esc_html_e( 'Abstract (optional)', 'wp-publications' ); ?></label>
			<textarea id="wp-publications-abstract" name="wp_publications_abstract" rows="4"><?php echo esc_textarea( $abstract ); ?></textarea>
		</div>

		<div class="wp-publications-field">
			<label for="wp-publications-image"><?php esc_html_e( 'Cover image URL (optional)', 'wp-publications' ); ?></label>
			<input type="url" id="wp-publications-image" name="wp_publications_image" value="<?php echo esc_attr( $image ); ?>">
		</div>

		<div class="wp-publications-field">
			<label><?php esc_html_e( 'Authors', 'wp-publications' ); ?></label>
			<div class="wp-publications-authors">
				<?php foreach ( $authors as $author ) : ?>
					<?php $author = wp_parse_args( (array) $author, array( 'given' => '', 'family' => '', 'orcid' => '' ) ); ?>
					<div class="wp-publications-author-row">
						<input type="text" class="wp-publications-author-given" name="wp_publications_author_given[]" value="<?php echo esc_attr( $author['given'] ); ?>" placeholder="<?php esc_attr_e( 'Given', 'wp-publications' ); ?>">
						<input type="text" class="wp-publications-author-family" name="wp_publications_author_family[]" value="<?php echo esc_attr( $author['family'] ); ?>" placeholder="<?php esc_attr_e( 'Family', 'wp-publications' ); ?>">
						<input type="text" class="wp-publications-author-orcid" name="wp_publications_author_orcid[]" value="<?php echo esc_attr( $author['orcid'] ); ?>" placeholder="<?php esc_attr_e( 'ORCID', 'wp-publications' ); ?>">
						<button type="button" class="button wp-publications-remove-author"><?php esc_html_e( 'Remove', 'wp-publications' ); ?></button>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="wp-publications-row-actions">
				<button type="button" class="button wp-publications-add-author"><?php esc_html_e( 'Add author', 'wp-publications' ); ?></button>
			</div>
		</div>
	</div>

	<script>
	( function ( $ ) {
		$( function () {
			$( '.wp-publications-add-author' ).on( 'click', function () {
				var row = $( '.wp-publications-author-row' ).first().clone();
				row.find( 'input' ).val( '' );
				$( '.wp-publications-authors' ).append( row );
			} );
			$( document ).on( 'click', '.wp-publications-remove-author', function () {
				var rows = $( '.wp-publications-author-row' );
				if ( rows.length > 1 ) {
					$( this ).closest( '.wp-publications-author-row' ).remove();
				} else {
					$( this ).closest( '.wp-publications-author-row' ).find( 'input' ).val( '' );
				}
			} );
		} );
	} )( jQuery );
	</script>
	<?php
}

/**
 * Save the meta box values into the normalized work JSON.
 */
function wp_publications_save_work_meta( $post_id, $post ) {
	if ( ! isset( $_POST['wp_publications_work_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( $_POST['wp_publications_work_nonce'] ), 'wp_publications_work_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( $post->post_type !== 'publication' ) {
		return;
	}

	// The post title is the source of truth for the citation title.
	$title = get_the_title( $post_id );

	$doi       = isset( $_POST['wp_publications_doi'] ) ? wp_publications_normalize_doi( sanitize_text_field( wp_unslash( $_POST['wp_publications_doi'] ) ) ) : '';
	$year      = isset( $_POST['wp_publications_year'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_publications_year'] ) ) : '';
	$journal   = isset( $_POST['wp_publications_journal'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_publications_journal'] ) ) : '';
	$publisher = isset( $_POST['wp_publications_publisher'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_publications_publisher'] ) ) : '';
	$volume    = isset( $_POST['wp_publications_volume'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_publications_volume'] ) ) : '';
	$issue     = isset( $_POST['wp_publications_issue'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_publications_issue'] ) ) : '';
	$pages     = isset( $_POST['wp_publications_pages'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_publications_pages'] ) ) : '';
	$abstract  = isset( $_POST['wp_publications_abstract'] ) ? wp_kses_post( wp_unslash( $_POST['wp_publications_abstract'] ) ) : '';
	$image     = isset( $_POST['wp_publications_image'] ) ? esc_url_raw( wp_unslash( $_POST['wp_publications_image'] ) ) : '';

	// Authors (repeatable rows).
	$given  = isset( $_POST['wp_publications_author_given'] ) ? (array) wp_unslash( $_POST['wp_publications_author_given'] ) : array();
	$family = isset( $_POST['wp_publications_author_family'] ) ? (array) wp_unslash( $_POST['wp_publications_author_family'] ) : array();
	$orcid  = isset( $_POST['wp_publications_author_orcid'] ) ? (array) wp_unslash( $_POST['wp_publications_author_orcid'] ) : array();

	$count   = max( count( $given ), count( $family ), count( $orcid ) );
	$authors = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$g = isset( $given[ $i ] ) ? sanitize_text_field( $given[ $i ] ) : '';
		$f = isset( $family[ $i ] ) ? sanitize_text_field( $family[ $i ] ) : '';
		$o = isset( $orcid[ $i ] ) ? sanitize_text_field( $orcid[ $i ] ) : '';

		$name = trim( trim( $g ) . ' ' . trim( $f ) );
		if ( '' === $name ) {
			continue;
		}
		$authors[] = array(
			'given'  => $g,
			'family' => $f,
			'name'   => $name,
			'orcid'  => $o,
		);
	}

	$date = $year;
	if ( preg_match( '/^\d{4}$/', $year ) ) {
		$date = $year;
	}

	// Preserve type/links from any previously stored work so that editing an
	// imported publication doesn't discard those fields.
	$existing = get_post_meta( $post_id, WP_PUBLICATIONS_META_WORK, true );
	$existing = $existing ? json_decode( $existing, true ) : array();
	$existing = is_array( $existing ) ? $existing : array();

	$work = array(
		'doi'       => $doi,
		'url'       => $doi ? 'https://doi.org/' . $doi : '',
		'title'     => $title,
		'type'      => isset( $existing['type'] ) ? $existing['type'] : 'article',
		'journal'   => $journal,
		'publisher' => $publisher,
		'volume'    => $volume,
		'issue'     => $issue,
		'pages'     => $pages,
		'year'      => $year,
		'date'      => $date,
		'abstract'  => $abstract,
		'authors'   => $authors,
		'image'     => $image,
		'links'     => isset( $existing['links'] ) && is_array( $existing['links'] ) ? $existing['links'] : array(),
	);

	update_post_meta(
		$post_id,
		WP_PUBLICATIONS_META_WORK,
		wp_json_encode( $work, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
	);

	if ( ! empty( $doi ) ) {
		update_post_meta( $post_id, WP_PUBLICATIONS_META_DOI, $doi );
	}
}
add_action( 'save_post_publication', 'wp_publications_save_work_meta', 10, 2 );
