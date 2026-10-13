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
		\add_action( 'init', [ $this, 'init' ] );

		\add_action( 'admin_init', [ $this, 'admin_init' ] );

		\add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		\add_filter( 'plugin_action_links_' . \plugin_basename( \dirname( __DIR__ ) . '/pronamic-cloudflare.php' ), $this->plugin_action_links( ... ) );

		\add_filter(
			'pre_option_pronamic_cloudflare_api_email',
			function ( $value ) {
				return $this->maybe_retrieve_option_from_constant( 'CLOUDFLARE_EMAIL', $value );
			}
		);

		\add_filter(
			'pre_option_pronamic_cloudflare_api_key',
			function ( $value ) {
				return $this->maybe_retrieve_option_from_constant( 'CLOUDFLARE_API_KEY', $value );
			}
		);

		\add_filter(
			'pre_option_pronamic_cloudflare_api_token',
			function ( $value ) {
				return $this->maybe_retrieve_option_from_constant( 'CLOUDFLARE_API_TOKEN', $value );
			}
		);

		\add_filter(
			'pre_option_pronamic_cloudflare_zone_id',
			function ( $value ) {
				return $this->maybe_retrieve_option_from_constant( 'PRONAMIC_CLOUDFLARE_ZONE_ID', $value );
			}
		);
	}

	/**
	 * Maybe retrieve option from contstant.
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
	 * Initialize.
	 */
	public function init() {
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
			'pronamic_cloudflare_cache_settings',
			[
				'type'              => 'array',
				'default'           => [
					'homepage' => [
						'browser_ttl'              => 30,
						'edge_ttl'                 => 3600,
						'stale_while_revalidating' => 300,
						'stale_if_error'           => 86400,
					],
					'public'   => [
						'browser_ttl'              => 120,
						'edge_ttl'                 => 604800,
						'stale_while_revalidating' => 300,
						'stale_if_error'           => 86400,
					],
				],
				'sanitize_callback' => self::sanitize_cache_settings( ... ),
			]
		);

		\register_setting(
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_headers_enabled',
			[
				'type'              => 'integer',
				'sanitize_callback' => self::sanitize_cache_headers_enabled( ... ),
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
			function () { },
			'pronamic_cloudflare'
		);

		\add_settings_field(
			'pronamic_cloudflare_zone_id',
			\__( 'Zone ID', 'pronamic-cloudflare' ),
			function ( $args ) {
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
			\__( 'API Token (Recommended)', 'pronamic-cloudflare' ),
			function () {
				echo '<p>';
				\esc_html_e( 'Cloudflare recommends using API Tokens for improved security and better access control. API Tokens use Bearer authentication.', 'pronamic-cloudflare' );
				echo '</p>';
			},
			'pronamic_cloudflare'
		);

		\add_settings_field(
			'pronamic_cloudflare_api_token',
			\__( 'API Token', 'pronamic-cloudflare' ),
			function ( $args ) {
				$this->input_text( $args );
			},
			'pronamic_cloudflare',
			'pronamic_cloudflare_api_token',
			[
				'label_for'     => 'pronamic_cloudflare_api_token',
				'constant_name' => 'CLOUDFLARE_API_TOKEN',
			]
		);

		\add_settings_section(
			'pronamic_cloudflare_global_api_key',
			\__( 'Global API Key (Legacy)', 'pronamic-cloudflare' ),
			function () {
				echo '<p>';
				\esc_html_e( 'The Global API Key authentication method is still supported but not recommended. If an API Token is configured, it will be used instead.', 'pronamic-cloudflare' );
				echo '</p>';
			},
			'pronamic_cloudflare'
		);

		\add_settings_field(
			'pronamic_cloudflare_api_email',
			\__( 'API email', 'pronamic-cloudflare' ),
			function ( $args ) {
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
			function ( $args ) {
				$this->input_text( $args );
			},
			'pronamic_cloudflare',
			'pronamic_cloudflare_global_api_key',
			[
				'label_for'     => 'pronamic_cloudflare_api_key',
				'constant_name' => 'CLOUDFLARE_API_KEY',
			]
		);

		\add_settings_section(
			'pronamic_cloudflare_cache_headers',
			\__( 'Cache headers', 'pronamic-cloudflare' ),
			function () {
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

		\add_settings_field(
			'pronamic_cloudflare_cache_settings',
			\__( 'Cache policy', 'pronamic-cloudflare' ),
			$this->cache_settings_field( ... ),
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_headers'
		);
	}

	/**
	 * Sanitize cache settings.
	 *
	 * @param mixed $settings Settings.
	 * @return array
	 */
	public static function sanitize_cache_settings( $settings ): array {
		$defaults = [
			'homepage' => [
				'browser_ttl'              => 30,
				'edge_ttl'                 => 3600,
				'stale_while_revalidating' => 300,
				'stale_if_error'           => 86400,
			],
			'public'   => [
				'browser_ttl'              => 120,
				'edge_ttl'                 => 604800,
				'stale_while_revalidating' => 300,
				'stale_if_error'           => 86400,
			],
		];

		if ( ! \is_array( $settings ) ) {
			$settings = [];
		}

		$sanitized = $defaults;

		foreach ( [ 'homepage', 'public' ] as $profile ) {
			$values = isset( $settings[ $profile ] ) && \is_array( $settings[ $profile ] ) ? $settings[ $profile ] : [];

			foreach ( [ 'browser_ttl', 'edge_ttl', 'stale_while_revalidating', 'stale_if_error' ] as $key ) {
				$value = isset( $values[ $key ] ) && \is_scalar( $values[ $key ] ) ? \absint( $values[ $key ] ) : $defaults[ $profile ][ $key ];

				$sanitized[ $profile ][ $key ] = \min( 31536000, $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize cache headers enabled setting.
	 *
	 * @param mixed $enabled Enabled value.
	 * @return int
	 */
	public static function sanitize_cache_headers_enabled( $enabled ): int {
		return 1 === \absint( $enabled ) ? 1 : 0;
	}

	/**
	 * Check whether cache headers are enabled.
	 *
	 * @return int
	 */
	public static function get_cache_headers_enabled(): int {
		$enabled = \get_option( 'pronamic_cloudflare_cache_headers_enabled', null );

		if ( null === $enabled ) {
			$settings = \get_option( 'pronamic_cloudflare_cache_settings', [] );
			$enabled  = \is_array( $settings ) ? ( $settings['enabled'] ?? 0 ) : 0;
		}

		return self::sanitize_cache_headers_enabled( $enabled );
	}

	/**
	 * Get cache settings.
	 *
	 * @return array
	 */
	public static function get_cache_settings(): array {
		return self::sanitize_cache_settings( \get_option( 'pronamic_cloudflare_cache_settings', false ) );
	}

	/**
	 * Cache settings field.
	 *
	 * @return void
	 */
	private function cache_settings_field(): void {
		$settings = self::get_cache_settings();

		$profiles = [
			'homepage' => \__( 'Homepage', 'pronamic-cloudflare' ),
			'public'   => \__( 'Other public pages', 'pronamic-cloudflare' ),
		];

		$fields = [
			'browser_ttl'              => \__( 'Browser TTL', 'pronamic-cloudflare' ),
			'edge_ttl'                 => \__( 'Cloudflare edge TTL', 'pronamic-cloudflare' ),
			'stale_while_revalidating' => \__( 'Stale while revalidate', 'pronamic-cloudflare' ),
			'stale_if_error'           => \__( 'Stale if error', 'pronamic-cloudflare' ),
		];

		echo '<table class="widefat striped" style="max-width: 960px;">';
		echo '<thead><tr>';
		echo '<th scope="col" style="padding: 8px 10px;">' . \esc_html__( 'Profile', 'pronamic-cloudflare' ) . '</th>';

		foreach ( $fields as $field_label ) {
			echo '<th scope="col" style="padding: 8px 10px;">' . \esc_html( $field_label ) . '</th>';
		}

		echo '</tr></thead><tbody>';

		foreach ( $profiles as $profile => $label ) {
			echo '<tr>';
			echo '<th scope="row" style="padding: 8px 10px; vertical-align: middle;">' . \esc_html( $label ) . '</th>';

			foreach ( $fields as $key => $field_label ) {
				$id   = \sprintf( 'pronamic-cloudflare-%s-%s', $profile, $key );
				$name = \sprintf( 'pronamic_cloudflare_cache_settings[%s][%s]', $profile, $key );

				echo '<td style="padding: 8px 10px;"><label class="screen-reader-text" for="' . \esc_attr( $id ) . '">' . \esc_html( \sprintf( '%1$s: %2$s', $label, $field_label ) ) . '</label>';
				echo '<input type="number" min="0" max="31536000" step="1" name="' . \esc_attr( $name ) . '" id="' . \esc_attr( $id ) . '" value="' . \esc_attr( (string) $settings[ $profile ][ $key ] ) . '" /> s';
				echo '</td>';
			}

			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Cache headers enabled field.
	 *
	 * @return void
	 */
	private function cache_headers_enabled_field(): void {
		?>

		<input type="hidden" name="pronamic_cloudflare_cache_headers_enabled" value="0" />
		<label for="pronamic-cloudflare-cache-enabled">
			<input type="checkbox" name="pronamic_cloudflare_cache_headers_enabled" id="pronamic-cloudflare-cache-enabled" value="1" <?php \checked( 1, self::get_cache_headers_enabled() ); ?> />
			<?php \esc_html_e( 'Enable cache headers for eligible public responses', 'pronamic-cloudflare' ); ?>
		</label>

		<p class="description"><?php \esc_html_e( 'Existing cache policy headers are respected. Authenticated requests, search results and 404 responses will not be cached.', 'pronamic-cloudflare' ); ?></p>

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
			'type'  => 'text',
			'name'  => $id,
			'id'    => $id,
			'value' => \get_option( $id ),
			'class' => 'regular-text',
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
			function () {
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
