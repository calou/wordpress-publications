<?php
/**
 * Settings page for WP Publications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register settings
add_action( 'admin_init', 'wp_publications_register_settings' );

function wp_publications_register_settings() {
	register_setting(
		'wp_publications_settings',
		'wp_publications_mailto',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => '',
		)
	);

	register_setting(
		'wp_publications_settings',
		'wp_publications_api_delay',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 1000,
		)
	);

	add_settings_section(
		'wp_publications_api_section',
		__( 'Crossref API Settings', 'wp-publications' ),
		'wp_publications_api_section_callback',
		'wp_publications_settings'
	);

	add_settings_field(
		'wp_publications_mailto',
		__( 'Email Address', 'wp-publications' ),
		'wp_publications_mailto_field_callback',
		'wp_publications_settings',
		'wp_publications_api_section'
	);

	add_settings_field(
		'wp_publications_api_delay',
		__( 'API Request Delay', 'wp-publications' ),
		'wp_publications_api_delay_field_callback',
		'wp_publications_settings',
		'wp_publications_api_section'
	);
}

function wp_publications_api_section_callback() {
	echo '<p>' . esc_html__( 'Configure the Crossref API settings. The email address is required and will be used for the "polite pool" to get better rate limits.', 'wp-publications' ) . '</p>';
}

function wp_publications_mailto_field_callback() {
	$mailto = get_option( 'wp_publications_mailto', '' );
	if ( empty( $mailto ) ) {
		$current_user = wp_get_current_user();
		$mailto       = $current_user->user_email ?? '';
	}
	?>
	<input 
		type="email" 
		id="wp_publications_mailto" 
		name="wp_publications_mailto" 
		value="<?php echo esc_attr( $mailto ); ?>" 
		class="regular-text"
		required
	/>
	<p class="description">
		<?php esc_html_e( 'This email will be sent with each API request to Crossref (polite pool). A valid email is required.', 'wp-publications' ); ?>
		<br />
		<a href="https://github.com/CrossRef/rest-api-doc#good-manners--more-reliable-service" target="_blank" rel="noopener">
			<?php esc_html_e( 'Learn more about the Crossref polite pool', 'wp-publications' ); ?>
		</a>
	</p>
	<?php
}

function wp_publications_api_delay_field_callback() {
	$delay = get_option( 'wp_publications_api_delay', 1000 );
	?>
	<input 
		type="number" 
		id="wp_publications_api_delay" 
		name="wp_publications_api_delay" 
		value="<?php echo esc_attr( $delay ); ?>" 
		class="small-text"
		min="100"
		max="10000"
		step="100"
	/>
	<span><?php esc_html_e( 'milliseconds', 'wp-publications' ); ?></span>
	<p class="description">
		<?php esc_html_e( 'Delay between API requests to avoid overloading the Crossref servers. Recommended: 1000ms (1 second).', 'wp-publications' ); ?>
	</p>
	<?php
}

/**
 * Render the settings page
 */
function wp_publications_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Show success message
	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error(
			'wp_publications_messages',
			'wp_publications_message',
			__( 'Settings saved.', 'wp-publications' ),
			'updated'
		);
	}

	// Validate email on page load
	$mailto = get_option( 'wp_publications_mailto', '' );
	if ( ! empty( $mailto ) && ! is_email( $mailto ) ) {
		add_settings_error(
			'wp_publications_messages',
			'wp_publications_invalid_email',
			__( 'The configured email address is invalid. Please enter a valid email.', 'wp-publications' ),
			'error'
		);
	}

	settings_errors( 'wp_publications_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<form action="options.php" method="post">
			<?php
			settings_fields( 'wp_publications_settings' );
			do_settings_sections( 'wp_publications_settings' );
			submit_button( __( 'Save Settings', 'wp-publications' ) );
			?>
		</form>
	</div>
	<?php
}
