<?php

/**
 * Publications block render callback
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string  Rendered block HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$selected_tags = isset( $attributes['selectedTags'] ) ? $attributes['selectedTags'] : array();
$match_all     = $attributes['matchAllTags'] ?? false;

// Build query args
$args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'meta_query'     => array(
		array(
			'key'     => '_publication_doi',
			'compare' => 'EXISTS',
		),
		'orderby' => 'date',
		'order'   => 'DESC',
	),
);

if ( $match_all ) {
	// Posts must have ALL selected tags
	$args['tag__and'] = $selected_tags;
} else {
	// Posts must have AT LEAST ONE selected tag
	$args['tag__in'] = $selected_tags;
}

$query                = new WP_Query( $args );
$publications_by_year = array();

if ( $query->have_posts() ) {
	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id = get_the_ID();

		// Get Crossref data
		$crossref_json = get_post_meta( $post_id, WP_PUBLICATIONS_META_CROSSREF, true );
		$crossref_data = $crossref_json ? json_decode( $crossref_json, true ) : null;


		// Extract year
		$year = wp_publications_block_extract_year( $crossref_data );

		if ( ! isset( $publications_by_year[ $year ] ) ) {
			$publications_by_year[ $year ] = array();
		}

		$publications_by_year[ $year ][] = array(
			'post_id'       => $post_id,
			'crossref_data' => $crossref_data,
		);
	}
	wp_reset_postdata();
}

// Sort years descending
krsort( $publications_by_year );

// Build output
$wrapper_attributes = get_block_wrapper_attributes();

ob_start();
?>
<div <?php echo $wrapper_attributes; ?>>
	<?php if ( empty( $publications_by_year ) ) : ?>
		<p class="wp-publications-no-results">
			<?php esc_html_e( 'No publications found.', 'wp-publications' ); ?>
		</p>
	<?php else : ?>
		<?php foreach ( $publications_by_year as $year => $publications ) : ?>
			<div class="wp-publications-year-group">
				<h3 class="wp-publications-year-heading"><?php echo esc_html( $year ); ?></h3>
				<ol class="wp-publications-list">
					<?php foreach ( $publications as $pub ) : ?>
						<li class="wp-publications-item">
							<?php echo wp_publications_block_format_apa( $pub['crossref_data'], $pub['post_id'] ); ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
<?php
return ob_end_flush();
