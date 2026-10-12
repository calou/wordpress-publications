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
$show_timeline = $attributes['showYearTimeline'] ?? true;
$show_headings = $attributes['showYearHeadings'] ?? true;
$max_publications = isset( $attributes['maxPublications'] ) ? max( 0, (int) $attributes['maxPublications'] ) : 0;

// Build query args
$args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => $max_publications > 0 ? $max_publications : -1,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'meta_query'     => array(
		array(
			'key'     => WP_PUBLICATIONS_META_CROSSREF,
			'compare' => 'EXISTS',
		),
	),
);

if ( $match_all ) {
	// Posts must have ALL selected tags
	$args['tag__and'] = $selected_tags;
} else {
	// Posts must have AT LEAST ONE selected tag
	$args['tag__in'] = $selected_tags;
}

$query                 = new WP_Query( $args );
$publications_by_year  = array();
$unknown_publications  = array();
$unknown_label         = __( 'Unknown', 'wp-publications' );

while ( $query->have_posts() ) {
	$query->the_post();
	$post_id = get_the_ID();

	// Get Crossref data
	$crossref_json = get_post_meta( $post_id, WP_PUBLICATIONS_META_CROSSREF, true );
	$crossref_data = $crossref_json ? json_decode( $crossref_json, true ) : null;

	// Extract year
	$year = wp_publications_block_extract_year( $crossref_data );

	$publication = array(
		'post_id'       => $post_id,
		'crossref_data' => $crossref_data,
	);

	if ( $year === $unknown_label ) {
		// Publications without a known year are grouped at the end of the list.
		$unknown_publications[] = $publication;
	} else {
		if ( ! isset( $publications_by_year[ $year ] ) ) {
			$publications_by_year[ $year ] = array();
		}
		$publications_by_year[ $year ][] = $publication;
	}
}
wp_reset_postdata();

// Sort known years descending.
krsort( $publications_by_year );

// Build output
$wrapper_attributes = get_block_wrapper_attributes();

ob_start();
?>
<div <?php echo $wrapper_attributes; ?>>
	<?php if ( empty( $publications_by_year ) && empty( $unknown_publications ) ) : ?>
		<p class="wp-publications-no-results">
			<?php esc_html_e( 'No publications found.', 'wp-publications' ); ?>
		</p>
	<?php else : ?>
		<div class="wp-publications-layout">
			<div class="wp-publications-content">
				<?php foreach ( $publications_by_year as $year => $publications ) : ?>
					<div id="wp-publications-year-<?php echo esc_attr( sanitize_title( $year ) ); ?>" class="wp-publications-year-group">
						<?php if ( $show_headings ) : ?>
							<h3 class="wp-publications-year-heading"><?php echo esc_html( $year ); ?></h3>
						<?php endif; ?>
						<ol class="wp-publications-list">
							<?php foreach ( $publications as $pub ) : ?>
								<li class="wp-publications-item">
									<?php echo wp_publications_block_format_apa( $pub['crossref_data'], $pub['post_id'] ); ?>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endforeach; ?>

				<?php if ( ! empty( $unknown_publications ) ) : ?>
					<div id="wp-publications-unknown-year" class="wp-publications-year-group wp-publications-year-group-unknown">
						<?php if ( $show_headings ) : ?>
							<h3 class="wp-publications-year-heading"><?php echo esc_html( $unknown_label ); ?></h3>
						<?php endif; ?>
						<ol class="wp-publications-list">
							<?php foreach ( $unknown_publications as $pub ) : ?>
								<li class="wp-publications-item">
									<?php echo wp_publications_block_format_apa( $pub['crossref_data'], $pub['post_id'] ); ?>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $show_timeline && count( $publications_by_year ) > 1 ) : ?>
				<nav class="wp-publications-timeline" aria-label="<?php esc_attr_e( 'Navigate by publication year', 'wp-publications' ); ?>">
					<p class="wp-publications-timeline-title"><?php esc_html_e( 'Years', 'wp-publications' ); ?></p>
					<ul class="wp-publications-timeline-list">
						<?php foreach ( $publications_by_year as $year => $publications ) : ?>
							<li>
								<a href="#wp-publications-year-<?php echo esc_attr( sanitize_title( $year ) ); ?>" class="wp-publications-timeline-link">
									<?php echo esc_html( $year ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
<?php
return ob_end_flush();
