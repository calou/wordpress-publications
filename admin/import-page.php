<?php
/**
 * Import page for WP Publications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the import page
 */
function wp_publications_import_page() {
	$mailto     = wp_publications_get_mailto();
	$has_mailto = ! empty( $mailto ) && wp_publications_is_valid_email( $mailto );
	$tags       = wp_publications_get_available_tags();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import Publications', 'wp-publications' ); ?></h1>
		
		<?php if ( ! $has_mailto ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php
					printf(
						/* translators: %s: settings page URL */
						esc_html__( 'Please configure your email address in the %s before importing publications.', 'wp-publications' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=wp-publications-settings' ) ) . '">' . esc_html__( 'settings', 'wp-publications' ) . '</a>'
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		
		<form id="wp-publications-import-form" method="post">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="wp-publications-dois"><?php esc_html_e( 'DOI List', 'wp-publications' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<textarea 
								id="wp-publications-dois" 
								name="dois" 
								rows="10" 
								class="large-text code"
								placeholder="<?php esc_attr_e( 'Enter DOIs, one per line or separated by commas', 'wp-publications' ); ?>"
								required
								<?php disabled( ! $has_mailto ); ?>
							></textarea>
							<p class="description">
								<?php esc_html_e( 'Enter one DOI per line, or separate with commas. URL prefixes (https://doi.org/) will be automatically removed.', 'wp-publications' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wp-publications-tags"><?php esc_html_e( 'Tags', 'wp-publications' ); ?></label>
						</th>
						<td>
							<select 
								id="wp-publications-tags" 
								name="tags[]" 
								multiple="multiple" 
								class="wp-publications-select2"
								style="width: 100%;"
								<?php disabled( ! $has_mailto ); ?>
							>
								<?php foreach ( $tags as $tag ) : ?>
									<option value="<?php echo esc_attr( $tag['id'] ); ?>">
										<?php echo esc_html( $tag['text'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Select existing tags to associate with imported publications. New tags cannot be created during import.', 'wp-publications' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			
			<p class="submit">
				<button type="submit" id="wp-publications-import-btn" class="button button-primary" <?php disabled( ! $has_mailto ); ?>>
					<?php esc_html_e( 'Import Publications', 'wp-publications' ); ?>
				</button>
			</p>
		</form>
		
		<div id="wp-publications-progress" style="display: none;">
			<h2><?php esc_html_e( 'Import Progress', 'wp-publications' ); ?></h2>
			
			<div class="wp-publications-progress-bar-container">
				<div id="wp-publications-progress-bar" class="wp-publications-progress-bar"></div>
			</div>
			
			<p id="wp-publications-progress-text">
				<?php esc_html_e( 'Preparing import...', 'wp-publications' ); ?>
			</p>
			
			<div id="wp-publications-results">
				<table class="widefat" id="wp-publications-results-table" style="display: none;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'DOI', 'wp-publications' ); ?></th>
							<th><?php esc_html_e( 'Title', 'wp-publications' ); ?></th>
							<th><?php esc_html_e( 'Status', 'wp-publications' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'wp-publications' ); ?></th>
						</tr>
					</thead>
					<tbody id="wp-publications-results-body">
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
}
