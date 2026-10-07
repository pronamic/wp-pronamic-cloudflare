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

		\register_setting(
			'pronamic_cloudflare',
			'pronamic_cloudflare_additional_cookies',
			[
				'type'              => 'array',
				'sanitize_callback' => self::sanitize_additional_cookies( ... ),
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

		\add_settings_field(
			'pronamic_cloudflare_additional_cookies',
			\__( 'Additional cookies', 'pronamic-cloudflare' ),
			$this->additional_cookies_field( ... ),
			'pronamic_cloudflare',
			'pronamic_cloudflare_cache_headers'
		);

		\add_settings_field(
			'pronamic_cloudflare_cache_rules',
			\__( 'Cloudflare Cache Rules', 'pronamic-cloudflare' ),
			$this->cache_rules_field( ... ),
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
	 * Sanitize additional cookies.
	 *
	 * @param mixed $cookies Cookies.
	 * @return array
	 */
	public static function sanitize_additional_cookies( $cookies ): array {
		if ( \is_string( $cookies ) ) {
			$cookies = \preg_split( '/\s*,\s*/', $cookies );
		}

		if ( ! \is_array( $cookies ) ) {
			$cookies = [];
		}

		$cookies = \array_filter( $cookies, '\is_scalar' );
		$cookies = \array_map( static fn ( $cookie ) => \sanitize_text_field( (string) $cookie ), $cookies );
		$cookies = \array_filter(
			$cookies,
			static fn ( $cookie ) => 1 === \preg_match( '/\A[a-zA-Z0-9_.-]+\z/', $cookie )
		);

		return \array_values( \array_unique( $cookies ) );
	}

	/**
	 * Get additional cookies.
	 *
	 * @return string[]
	 */
	private static function get_additional_cookies(): array {
		$cookies = \get_option( 'pronamic_cloudflare_additional_cookies', null );

		if ( null === $cookies ) {
			$settings = \get_option( 'pronamic_cloudflare_cache_settings', [] );
			$cookies  = \is_array( $settings ) ? ( $settings['custom_cookies'] ?? [] ) : [];
		}

		return self::sanitize_additional_cookies( $cookies );
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
	 * Get the Cloudflare cache expression.
	 *
	 * @return string
	 */
	public static function get_cache_expression(): string {
		$home_url = \home_url();
		$host     = \wp_parse_url( $home_url, PHP_URL_HOST );

		if ( ! \is_string( $host ) || '' === $host ) {
			return '';
		}

		$hosts = \array_values(
			\array_unique(
				\array_filter(
					[
						$host,
						\wp_parse_url( \site_url(), PHP_URL_HOST ),
					],
					static fn ( $value ) => \is_string( $value ) && '' !== $value
				)
			)
		);

		$host_values = \array_map(
			static fn ( $value ) => '"' . self::escape_expression_value( $value ) . '"',
			$hosts
		);

		$conditions = [
			'http.host in {' . \implode( ' ', $host_values ) . '}',
			'http.request.method eq "GET"',
		];

		foreach ( self::get_cache_bypass_conditions( $host ) as $bypass_condition ) {
			if ( \str_starts_with( $bypass_condition, 'http.request.uri.path eq ' ) ) {
				$conditions[] = \str_replace( ' eq ', ' ne ', $bypass_condition );

				continue;
			}

			$conditions[] = 'not ' . $bypass_condition;
		}

		return '(' . \implode( ' and ', $conditions ) . ')';
	}

	/**
	 * Get the Cloudflare cache bypass expression.
	 *
	 * @return string
	 */
	public static function get_cache_bypass_expression(): string {
		$host = \wp_parse_url( \home_url(), PHP_URL_HOST );

		if ( ! \is_string( $host ) || '' === $host ) {
			return '';
		}

		$conditions = self::get_cache_bypass_conditions( $host );

		foreach ( $conditions as $index => $condition ) {
			$conditions[ $index ] = '(' . $condition . ')';
		}

		return \implode( ' or ', $conditions );
	}

	/**
	 * Get conditions that should bypass Cloudflare caching.
	 *
	 * @param string $host Host name.
	 * @return string[]
	 */
	private static function get_cache_bypass_conditions( string $host ): array {
		$home_path = \wp_parse_url( \home_url(), PHP_URL_PATH );
		$site_path = \wp_parse_url( \site_url(), PHP_URL_PATH );

		$home_path_prefix = \is_string( $home_path ) ? \rtrim( $home_path, '/' ) : '';
		$site_path_prefix = \is_string( $site_path ) ? \rtrim( $site_path, '/' ) : '';

		$conditions = [
			'starts_with(http.request.uri.query, "s=")',
			'http.request.uri.query contains "&s="',
			'http.request.uri.query contains "preview="',
			'http.request.uri.query contains "rest_route="',
			'http.request.uri.query contains "replytocom="',
			'http.request.uri.query contains "customize_changeset_uuid="',
		];

		$path_prefixes = [
			$site_path_prefix . '/wp-admin/',
			$home_path_prefix . '/wp-json/',
		];

		$exact_paths = [
			'/wp-cron.php',
			'/wp-login.php',
			'/xmlrpc.php',
		];

		if ( \class_exists( 'WooCommerce' ) ) {
			$conditions[] = 'http.request.uri.query contains "add-to-cart="';
			$conditions[] = 'http.request.uri.query contains "wc-ajax="';

			if ( \function_exists( 'wc_get_page_permalink' ) ) {
				foreach ( [ 'cart', 'checkout', 'myaccount' ] as $page ) {
					$page_url = \wc_get_page_permalink( $page );

					if ( ! \is_string( $page_url ) ) {
						continue;
					}

					$page_host = \wp_parse_url( $page_url, PHP_URL_HOST );

					if ( $host !== $page_host ) {
						continue;
					}

					$page_path = \wp_parse_url( $page_url, PHP_URL_PATH );

					if ( \is_string( $page_path ) && '' !== $page_path && '/' !== $page_path ) {
						$path_prefixes[] = $page_path;
					}
				}
			}
		}

		foreach ( $path_prefixes as $path ) {
			$conditions[] = 'starts_with(http.request.uri.path, "' . self::escape_expression_value( $path ) . '")';
		}

		foreach ( $exact_paths as $path ) {
			$conditions[] = 'http.request.uri.path eq "' . self::escape_expression_value( $site_path_prefix . $path ) . '"';
		}

		foreach ( self::get_cache_bypass_cookies() as $cookie_name ) {
			$is_prefix = \str_ends_with( $cookie_name, '_' ) || \str_ends_with( $cookie_name, '-' );
			$cookie    = self::escape_expression_value( $cookie_name . ( $is_prefix ? '' : '=' ) );

			$conditions[] = 'http.cookie contains "' . $cookie . '"';
		}

		return $conditions;
	}

	/**
	 * Get cookies that bypass caching.
	 *
	 * @return string[]
	 */
	private static function get_cache_bypass_cookies(): array {
		$cookies = [
			'wordpress_logged_in_',
			'wordpress_sec_',
			'wordpress_',
			'wp-postpass_',
			'wp-settings-',
			'comment_author_',
		];

		if ( \class_exists( 'WooCommerce' ) ) {
			$cookies = \array_merge(
				$cookies,
				[
					'woocommerce_items_in_cart',
					'woocommerce_cart_hash',
					'wp_woocommerce_session_',
				]
			);
		}

		return \array_values( \array_unique( \array_merge( $cookies, self::get_additional_cookies() ) ) );
	}

	/**
	 * Escape a string for use in a Cloudflare expression.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function escape_expression_value( string $value ): string {
		return \str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $value );
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
	 * Additional cookies field.
	 *
	 * @return void
	 */
	private function additional_cookies_field(): void {
		$cookies = \implode( ', ', self::get_additional_cookies() );

		\printf(
			'<input type="text" class="regular-text" name="pronamic_cloudflare_additional_cookies" id="pronamic-cloudflare-additional-cookies" value="%s" /><p class="description">%s</p>',
			\esc_attr( $cookies ),
			\esc_html__( 'Enter cookie names separated by commas. Caching will be bypassed when a request includes any of these cookies.', 'pronamic-cloudflare' )
		);
	}

	/**
	 * Cache expression field.
	 *
	 * @return void
	 */
	private function cache_rules_field(): void {
		$expression        = self::get_cache_expression();
		$bypass_expression = self::get_cache_bypass_expression();

		\printf(
			'<p class="description">%s</p>',
			\esc_html__( 'Add these cache rules in this order in the Cloudflare dashboard.', 'pronamic-cloudflare' )
		);

		$rules = [
			[
				'title'      => \__( 'Bypass cache rule', 'pronamic-cloudflare' ),
				'settings'   => [
					\__( 'Cache eligibility', 'pronamic-cloudflare' ) => \__( 'Bypass cache', 'pronamic-cloudflare' ),
				],
				'expression' => $bypass_expression,
			],
			[
				'title'      => \__( 'Cache rule', 'pronamic-cloudflare' ),
				'settings'   => [
					\__( 'Cache eligibility', 'pronamic-cloudflare' ) => \__( 'Eligible for cache', 'pronamic-cloudflare' ),
					\__( 'Edge TTL', 'pronamic-cloudflare' )          => \__( 'Use cache-control header if present, bypass cache if not', 'pronamic-cloudflare' ),
				],
				'expression' => $expression,
			],
		];

		foreach ( $rules as $rule ) {
			\printf(
				'<p><strong>%s</strong></p>',
				\esc_html( $rule['title'] )
			);

			echo '<ul style="list-style: disc; margin-left: 1.5em;">';

			foreach ( $rule['settings'] as $label => $value ) {
				\printf(
					'<li><strong>%s</strong>: %s</li>',
					\esc_html( $label ),
					\esc_html( $value )
				);
			}

			echo '</ul>';

			\printf(
				'<pre class="pronamic-cloudflare-cache-expression" style="max-width: 960px; white-space: pre-wrap; overflow-wrap: anywhere;">%s</pre>',
				\esc_html( $rule['expression'] )
			);
		}
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
