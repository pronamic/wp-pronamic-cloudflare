<?php
/**
 * Settings controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Moneybird
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

		\add_filter(
			'pre_option_pronamic_cloudflare_api_email',
			function( $value ) {
				return $this->maybe_retrieve_option_from_constant( 'CLOUDFLARE_EMAIL', $value );
			}
		);

		\add_filter(
			'pre_option_pronamic_cloudflare_api_key',
			function( $value ) {
				return $this->maybe_retrieve_option_from_constant( 'CLOUDFLARE_API_KEY', $value );
			}
		);

		\add_filter(
			'pre_option_pronamic_cloudflare_zone_id',
			function( $value ) {
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
			'pronamic_cloudflare_zone_id',
			[
				'type' => 'string',
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
			'pronamic_cloudflare_api_email',
			\__( 'API email', 'pronamic-cloudflare' ),
			function ( $args ) {
				$this->input_text( $args );
			},
			'pronamic_cloudflare',
			'pronamic_cloudflare_general',
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
			'pronamic_cloudflare_general',
			[
				'label_for'     => 'pronamic_cloudflare_api_key',
				'constant_name' => 'CLOUDFLARE_API_KEY',
			]
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
}
