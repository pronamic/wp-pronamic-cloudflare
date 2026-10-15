<?php
/**
 * Settings controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

use Pronamic\WordPress\Html\Element;

/**
 * Settings controller class
 */
final class SettingsController {
	/**
	 * Setup.
	 */
	public function setup() {
		\add_action( 'init', $this->init( ... ) );

		\add_action( 'admin_init', $this->admin_init( ... ) );

		\add_action( 'admin_menu', $this->admin_menu( ... ) );

		\add_filter( 'plugin_action_links_' . \plugin_basename( \dirname( __DIR__ ) . '/pronamic-cloudflare.php' ), $this->plugin_action_links( ... ) );

		\add_filter(
			'pre_option_pronamic_cloudflare_api_email',
			fn( $value ) => $this->maybe_retrieve_option_from_constant( 'CLOUDFLARE_EMAIL', $value )
		);

		\add_filter(
			'pre_option_pronamic_cloudflare_api_key',
			fn( $value ) => $this->maybe_retrieve_option_from_constant( 'CLOUDFLARE_API_KEY', $value )
		);

		\add_filter(
			'pre_option_pronamic_cloudflare_api_token',
			fn( $value ) => $this->maybe_retrieve_option_from_constant( 'CLOUDFLARE_API_TOKEN', $value )
		);

		\add_filter(
			'pre_option_pronamic_cloudflare_zone_id',
			fn( $value ) => $this->maybe_retrieve_option_from_constant( 'PRONAMIC_CLOUDFLARE_ZONE_ID', $value )
		);
	}

	/**
	 * Maybe retrieve option from constant.
	 *
	 * @param string $name The constant name.
	 * @param mixed  $value The value.
	 * @return mixed
	 */
	private function maybe_retrieve_option_from_constant( $name, $value ) {
		if ( \defined( $name ) ) {
			return \constant( $name );
		}

		return $value;
	}

	/**
	 * Check whether an API token is configured.
	 *
	 * @return bool
	 */
	private function has_api_token(): bool {
		return '' !== (string) \get_option( 'pronamic_cloudflare_api_token' );
	}

	/**
	 * Initialize.
	 */
	public function init() {
		if ( ! $this->has_api_token() ) {
			\register_setting(
				'pronamic_cloudflare',
				'pronamic_cloudflare_api_email',
				[
					'type' => 'string',
				]
			);

			\register_setting(
				'pronamic_cloudflare',
				'pronamic_cloudflare_api_key',
				[
					'type' => 'string',
				]
			);
		}

		\register_setting(
			'pronamic_cloudflare',
			'pronamic_cloudflare_api_token',
			[
				'type' => 'string',
			]
		);

		\register_setting(
			'pronamic_cloudflare',
			'pronamic_cloudflare_zone_id',
			[
				'type' => 'string',
			]
		);

		\register_setting(
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_browser_ttl',
			[
				'type'              => 'integer',
				'default'           => 300,
				'sanitize_callback' => self::sanitize_cache_ttl( ... ),
			]
		);

		\register_setting(
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_edge_ttl',
			[
				'type'              => 'integer',
				'default'           => 604800,
				'sanitize_callback' => self::sanitize_cache_ttl( ... ),
			]
		);

		\register_setting(
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_stale_while_revalidate',
			[
				'type'              => 'integer',
				'default'           => 86400,
				'sanitize_callback' => self::sanitize_cache_ttl( ... ),
			]
		);

		\register_setting(
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_stale_if_error',
			[
				'type'              => 'integer',
				'default'           => 86400,
				'sanitize_callback' => self::sanitize_cache_ttl( ... ),
			]
		);

		\register_setting(
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_headers_enabled',
			[
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => \rest_sanitize_boolean( ... ),
			]
		);
	}

	/**
	 * Admin initialize.
	 */
	public function admin_init() {
		\add_settings_section(
			'pronamic_cloudflare_general',
			\__( 'General', 'pronamic-cloudflare' ),
			function (): void { },
			'pronamic_cloudflare'
		);

		\add_settings_field(
			'pronamic_cloudflare_zone_id',
			\__( 'Zone ID', 'pronamic-cloudflare' ),
			function ( $args ): void {
				$this->input_text( $args );
			},
			'pronamic_cloudflare',
			'pronamic_cloudflare_general',
			[
				'label_for'     => 'pronamic_cloudflare_zone_id',
				'constant_name' => 'PRONAMIC_CLOUDFLARE_ZONE_ID',
			]
		);

		\add_settings_section(
			'pronamic_cloudflare_api_token',
			$this->has_api_token() ? \__( 'API Token', 'pronamic-cloudflare' ) : \__( 'API Token (Recommended)', 'pronamic-cloudflare' ),
			function (): void {
				echo '<p>';
				\esc_html_e( 'Cloudflare recommends using API Tokens for improved security and better access control. API Tokens use Bearer authentication.', 'pronamic-cloudflare' );
				echo '</p>';
			},
			'pronamic_cloudflare'
		);

		\add_settings_field(
			'pronamic_cloudflare_api_token',
			\__( 'API Token', 'pronamic-cloudflare' ),
			function ( $args ): void {
				$this->input_text( $args );
			},
			'pronamic_cloudflare',
			'pronamic_cloudflare_api_token',
			[
				'label_for'     => 'pronamic_cloudflare_api_token',
				'constant_name' => 'CLOUDFLARE_API_TOKEN',
			]
		);

		if ( ! $this->has_api_token() ) {
			\add_settings_section(
				'pronamic_cloudflare_global_api_key',
				\__( 'Global API Key (Legacy)', 'pronamic-cloudflare' ),
				function (): void {
					echo '<p>';
					\esc_html_e( 'The Global API Key authentication method is still supported but not recommended. If an API Token is configured, it will be used instead.', 'pronamic-cloudflare' );
					echo '</p>';
				},
				'pronamic_cloudflare'
			);

			\add_settings_field(
				'pronamic_cloudflare_api_email',
				\__( 'API email', 'pronamic-cloudflare' ),
				function ( $args ): void {
					$this->input_text( $args );
				},
				'pronamic_cloudflare',
				'pronamic_cloudflare_global_api_key',
				[
					'label_for'     => 'pronamic_cloudflare_api_email',
					'constant_name' => 'CLOUDFLARE_EMAIL',
				]
			);

			\add_settings_field(
				'pronamic_cloudflare_api_key',
				\__( 'API key', 'pronamic-cloudflare' ),
				function ( $args ): void {
					$this->input_text( $args );
				},
				'pronamic_cloudflare',
				'pronamic_cloudflare_global_api_key',
				[
					'label_for'     => 'pronamic_cloudflare_api_key',
					'constant_name' => 'CLOUDFLARE_API_KEY',
				]
			);
		}

		\add_settings_section(
			'pronamic_cloudflare_cache_headers',
			\__( 'Cache headers', 'pronamic-cloudflare' ),
			function (): void {
				echo '<p>';
				\esc_html_e( 'Configure browser and Cloudflare edge cache lifetimes for public responses.', 'pronamic-cloudflare' );
				echo '</p>';
			},
			'pronamic_cloudflare'
		);

		\add_settings_field(
			'pronamic_cloudflare_cache_headers_enabled',
			\__( 'Enable cache headers', 'pronamic-cloudflare' ),
			$this->cache_headers_enabled_field( ... ),
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_headers'
		);

		$cache_headers_field_class = 'pronamic-cloudflare-cache-headers-field';

		if ( ! self::get_cache_headers_enabled() ) {
			$cache_headers_field_class .= ' hidden';
		}

		\add_settings_field(
			'pronamic_cloudflare_cache_browser_ttl',
			\__( 'Browser TTL', 'pronamic-cloudflare' ),
			function ( $args ): void {
				$this->input_seconds( $args );
			},
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_headers',
			[
				'label_for'   => 'pronamic_cloudflare_cache_browser_ttl',
				'class'       => $cache_headers_field_class,
				'description' => \__( 'Browser caches cannot be purged, so visitors may see an outdated page for this long after a change.', 'pronamic-cloudflare' ),
			]
		);

		\add_settings_field(
			'pronamic_cloudflare_cache_edge_ttl',
			\__( 'Cloudflare edge TTL', 'pronamic-cloudflare' ),
			function ( $args ): void {
				$this->input_seconds( $args );
			},
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_headers',
			[
				'label_for'   => 'pronamic_cloudflare_cache_edge_ttl',
				'class'       => $cache_headers_field_class,
				'description' => \__( 'The edge cache is purged when content changes. Keep this below 12 hours if public pages contain nonces, for example in forms.', 'pronamic-cloudflare' ),
			]
		);

		\add_settings_field(
			'pronamic_cloudflare_cache_stale_while_revalidate',
			\__( 'Stale while revalidate', 'pronamic-cloudflare' ),
			function ( $args ): void {
				$this->input_seconds( $args );
			},
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_headers',
			[
				'label_for'   => 'pronamic_cloudflare_cache_stale_while_revalidate',
				'class'       => $cache_headers_field_class,
				'description' => \__( 'How long Cloudflare may keep serving an expired page while it fetches a fresh copy in the background.', 'pronamic-cloudflare' ),
			]
		);

		\add_settings_field(
			'pronamic_cloudflare_cache_stale_if_error',
			\__( 'Stale if error', 'pronamic-cloudflare' ),
			function ( $args ): void {
				$this->input_seconds( $args );
			},
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_headers',
			[
				'label_for'   => 'pronamic_cloudflare_cache_stale_if_error',
				'class'       => $cache_headers_field_class,
				'description' => \__( 'How long Cloudflare may keep serving an expired page when the origin server returns an error or is unreachable.', 'pronamic-cloudflare' ),
			]
		);
	}

	/**
	 * Sanitize cache TTL in seconds.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	public static function sanitize_cache_ttl( $value ): int {
		return \min( 31536000, \absint( $value ) );
	}

	/**
	 * Check whether cache headers are enabled.
	 *
	 * @return bool
	 */
	public static function get_cache_headers_enabled(): bool {
		return \rest_sanitize_boolean( \get_option( 'pronamic_cloudflare_cache_headers_enabled', false ) );
	}

	/**
	 * Cache headers enabled field.
	 *
	 * @return void
	 */
	private function cache_headers_enabled_field(): void {
		?>

		<fieldset>
			<legend class="screen-reader-text"><span><?php \esc_html_e( 'Enable cache headers', 'pronamic-cloudflare' ); ?></span></legend>
			<input type="hidden" name="pronamic_cloudflare_cache_headers_enabled" value="0" />
			<label for="pronamic-cloudflare-cache-enabled"><input type="checkbox" name="pronamic_cloudflare_cache_headers_enabled" id="pronamic-cloudflare-cache-enabled" value="1" <?php \checked( self::get_cache_headers_enabled() ); ?> />
			<?php \esc_html_e( 'Enable cache headers for eligible public responses', 'pronamic-cloudflare' ); ?></label>
			<p class="description"><?php \esc_html_e( 'Existing cache policy headers are respected. Authenticated requests, previews and requests other than GET and HEAD never receive cache headers.', 'pronamic-cloudflare' ); ?></p>
		</fieldset>

		<script>
			document.getElementById( 'pronamic-cloudflare-cache-enabled' ).addEventListener( 'change', function () {
				document.querySelectorAll( '.pronamic-cloudflare-cache-headers-field' ).forEach( ( row ) => {
					row.classList.toggle( 'hidden', ! this.checked );
				} );
			} );
		</script>

		<?php
	}

	/**
	 * Input text.
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	private function input_text( $args ) {
		$id = $args['label_for'];

		$constant_name = $args['constant_name'];

		$attributes = [
			'type'         => 'text',
			'name'         => $id,
			'id'           => $id,
			'value'        => \get_option( $id ),
			'class'        => 'regular-text',
			'autocomplete' => 'off',
		];

		if ( \defined( $constant_name ) ) {
			$attributes['readonly'] = 'readonly';
		}

		$element = new Element( 'input', $attributes );

		$element->output();

		if ( \defined( $constant_name ) ) {
			echo '<p class="description">';

			echo \wp_kses(
				\sprintf(
					/* translators: 1: Constant name, 2: wp-config.php.. */
					\__( 'This value is defined in the named constant %1$s, probably in the WordPress configuration file %2$s.', 'pronamic-cloudflare' ),
					'<code>' . $constant_name . '</code>',
					'<code>wp-config.php</code>'
				),
				[
					'code' => [],
				]
			);

			echo '</p>';
		}
	}

	/**
	 * Input seconds.
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	private function input_seconds( $args ) {
		$id = $args['label_for'];

		$element = new Element(
			'input',
			[
				'type'  => 'number',
				'name'  => $id,
				'id'    => $id,
				'value' => \get_option( $id ),
				'min'   => '0',
				'max'   => '31536000',
				'step'  => '1',
			]
		);

		$element->output();

		echo ' ', \esc_html__( 'seconds', 'pronamic-cloudflare' );

		if ( \array_key_exists( 'description', $args ) ) {
			echo '<p class="description">', \esc_html( $args['description'] ), '</p>';
		}
	}

	/**
	 * Admin menu.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_options_page/
	 * @return void
	 */
	public function admin_menu() {
		\add_options_page(
			\__( 'Pronamic Cloudflare', 'pronamic-cloudflare' ),
			\__( 'Pronamic Cloudflare', 'pronamic-cloudflare' ),
			'manage_options',
			'pronamic_cloudflare',
			function (): void {
				include __DIR__ . '/../admin/page-settings.php';
			}
		);
	}

	/**
	 * Plugin action links.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
	 * @param string[] $actions Plugin action links.
	 * @return string[]
	 */
	private function plugin_action_links( $actions ) {
		$url = \add_query_arg( 'page', 'pronamic_cloudflare', \admin_url( 'options-general.php' ) );

		\array_unshift(
			$actions,
			\sprintf(
				'<a href="%s">%s</a>',
				\esc_url( $url ),
				\esc_html__( 'Settings', 'pronamic-cloudflare' )
			)
		);

		return $actions;
	}
}
