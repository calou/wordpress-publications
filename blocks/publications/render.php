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

$selected_tags       = isset( $attributes['selectedTags'] ) ? $attributes['selectedTags'] : array();
$match_all           = $attributes['matchAllTags'] ?? false;
$show_timeline       = $attributes['showYearTimeline'] ?? true;
$show_headings       = $attributes['showYearHeadings'] ?? true;
$max_publications    = isset( $attributes['maxPublications'] ) ? max( 0, (int) $attributes['maxPublications'] ) : 0;
$show_featured_image = $attributes['showFeaturedImage'] ?? false;
$show_client_filter  = $attributes['showClientFilter'] ?? false;

// Build query args
$args = array(
	'post_type'      => 'publication',
	'post_status'    => 'publish',
	'posts_per_page' => $max_publications > 0 ? $max_publications : -1,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'meta_query'     => array(
		array(
			'key'     => WP_PUBLICATIONS_META_WORK,
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

$query                = new WP_Query( $args );
$publications_by_year = array();
$unknown_publications = array();
$unknown_label        = __( 'Unknown', 'wp-publications' );

while ( $query->have_posts() ) {
	$query->the_post();
	$post_id = get_the_ID();

	// Get OpenAlex work data
	$work_json = get_post_meta( $post_id, WP_PUBLICATIONS_META_WORK, true );
	$work      = $work_json ? json_decode( $work_json, true ) : null;

	// Extract year
	$year = wp_publications_block_extract_year( $work );

	$publication = array(
		'post_id'       => $post_id,
		'work'          => $work,
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
$filter_id          = wp_unique_id( 'wp-publications-filter-' );
$show_timeline_nav  = $show_timeline && count( $publications_by_year ) > 1;
$show_sidebar       = $show_timeline_nav || $show_client_filter;
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
								<li class="wp-publications-item<?php echo ( $show_featured_image && has_post_thumbnail( $pub['post_id'] ) ) ? ' has-featured-image' : ''; ?>">
									<?php if ( $show_featured_image && has_post_thumbnail( $pub['post_id'] ) ) : ?>
										<div class="wp-publications-featured-image">
											<?php echo get_the_post_thumbnail( $pub['post_id'], 'thumbnail', array( 'class' => 'wp-publications-thumb' ) ); ?>
										</div>
									<?php endif; ?>
									<div class="wp-publications-text">
										<?php echo wp_publications_block_format_apa( $pub['work'], $pub['post_id'] ); ?>
									</div>
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
								<li class="wp-publications-item<?php echo ( $show_featured_image && has_post_thumbnail( $pub['post_id'] ) ) ? ' has-featured-image' : ''; ?>">
									<?php if ( $show_featured_image && has_post_thumbnail( $pub['post_id'] ) ) : ?>
										<div class="wp-publications-featured-image">
											<?php echo get_the_post_thumbnail( $pub['post_id'], 'thumbnail', array( 'class' => 'wp-publications-thumb' ) ); ?>
										</div>
									<?php endif; ?>
									<div class="wp-publications-text">
										<?php echo wp_publications_block_format_apa( $pub['work'], $pub['post_id'] ); ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>
				<?php if ( $show_client_filter ) : ?>
					<p class="wp-publications-filter-empty is-hidden"><?php esc_html_e( 'No publications match your search.', 'wp-publications' ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $show_sidebar ) : ?>
				<div class="wp-publications-timeline-col">
					<?php if ( $show_client_filter ) : ?>
						<div class="wp-publications-filter">
							<input id="<?php echo esc_attr( $filter_id ); ?>" type="search" class="wp-publications-filter-input" placeholder="<?php esc_attr_e( 'Search publications...', 'wp-publications' ); ?>" aria-label="<?php esc_attr_e( 'Search', 'wp-publications' ); ?>">
						</div>
					<?php endif; ?>

					<?php if ( $show_timeline_nav ) : ?>
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
	<?php endif; ?>
</div>
<?php
return ob_end_flush();
