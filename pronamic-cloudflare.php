<?php
/**
 * Pronamic Cloudflare
 *
 * @package   Pronamic\WordPress\CloudflarePlugin
 * @author    Pronamic
 * @copyright 2022 Pronamic
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Pronamic Cloudflare
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-cloudflare/
 * Description: The Pronamic Cloudflare plugin adds a number of features, such as WP-CLI commands, to the Cloudflare plugin.
 * 
 * Requires Plugins: cloudflare
 * 
 * Version: 1.0.0
 * Requires at least: 6.1
 * 
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 * 
 * Text Domain: pronamic-cloudflare
 * Domain Path: /languages/
 * 
 * License: GPL
 * 
 * GitHub URI: https://github.com/pronamic/wp-pronamic-cloudflare
 */

/**
 * WP-CLI.
 * 
 * @link https://make.wordpress.org/cli/handbook/guides/commands-cookbook/#include-in-a-plugin-or-theme
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'pronamic cloudflare zones',
		function( $args, $assoc_args ) {
			if ( ! defined( 'CLOUDFLARE_EMAIL' ) ) {
				WP_CLI::error( 'The constant `CLOUDFLARE_EMAIL` is not defined.' );
			}

			if ( ! defined( 'CLOUDFLARE_API_KEY' ) ) {
				WP_CLI::error( 'The constant `CLOUDFLARE_API_KEY` is not defined.' );
			}

			$url = add_query_arg(
				$assoc_args,
				'https://api.cloudflare.com/client/v4/zones'
			);

			$response = wp_remote_get(
				$url,
				[
					'headers' => [
						'Content-Type' => 'application/json',
						'X-Auth-Email' => CLOUDFLARE_EMAIL,
						'X-Auth-Key'   => CLOUDFLARE_API_KEY,
					],
				]
			);

			if ( is_wp_error( $response ) ) {
				WP_CLI::error( $response->get_error_message() );
			}

			$body = wp_remote_retrieve_body( $response );

			$data = json_decode( $body );

			$items = \array_map(
				function( $item ) {
					return [
						'id'     => $item->id,
						'name'   => $item->name,
						'status' => $item->status,
					];
				},
				$data->result
			);

			if ( 'ids' === $assoc_args['format'] ) {
				$items = \wp_list_pluck( $items, 'id' );
			}

			$formatter = new \WP_CLI\Formatter(
				$assoc_args,
				[
					'id',
					'name',
					'status',
				] 
			);

			$formatter->display_items( $items );
		},
		[
			'shortdesc' => 'Lists, searches, sorts, and filters your zones.',
			'synopsis'  => [
				[
					'type'        => 'assoc',
					'name'        => 'name',
					'description' => 'A domain name.',
					'optional'    => true,
				],
				[
					'type'        => 'assoc',
					'name'        => 'status',
					'description' => 'Status of the zone.',
					'optional'    => true,
					'options'     => [
						'active',
						'pending',
						'initializing',
						'moved',
						'deleted',
						'deactivated',
					],
				],
				[
					'name'        => 'format',
					'type'        => 'assoc',
					'description' => 'Render response in a particular format.',
					'optional'    => true,
					'default'     => 'table',
					'options'     => [
						'table',
						'ids',
					],
				],
			],
		]
	);

	WP_CLI::add_command(
		'pronamic cloudflare purge',
		function( $args, $assoc_args ) {
			if ( ! defined( 'CLOUDFLARE_EMAIL' ) ) {
				WP_CLI::error( 'The constant `CLOUDFLARE_EMAIL` is not defined.' );
			}

			if ( ! defined( 'CLOUDFLARE_API_KEY' ) ) {
				WP_CLI::error( 'The constant `CLOUDFLARE_API_KEY` is not defined.' );
			}

			foreach ( $args as $identifier ) {
				$url = strtr(
					'https://api.cloudflare.com/client/v4/zones/:identifier/purge_cache',
					[
						':identifier' => $identifier,
					]
				);

				$response = wp_remote_post(
					$url,
					[
						'headers' => [
							'Content-Type' => 'application/json',
							'X-Auth-Email' => CLOUDFLARE_EMAIL,
							'X-Auth-Key'   => CLOUDFLARE_API_KEY,
						],
						'body'    => wp_json_encode(
							[
								'purge_everything' => true,
							]
						),
					]
				);

				if ( is_wp_error( $response ) ) {
					WP_CLI::error( $response->get_error_message() );
				}

				$body = wp_remote_retrieve_body( $response );

				$data = json_decode( $body );

				if ( true === $data->success ) {
					WP_CLI::success( sprintf( 'Cloudflare zone `%s` cache purged.', $identifier ) );
				}

				if ( true !== $data->success ) {
					WP_CLI::error( sprintf( 'Cloudflare zone `%s` cache not purged.', $identifier ) );
				}
			}
		},
		[
			'shortdesc' => 'Removes ALL files from Cloudflare’s cache.',
			'synopsis'  => [
				[
					'type'        => 'positional',
					'name'        => 'identifier',
					'description' => 'Cloudflare zone identifier.',
					'optional'    => false,
					'repeating'   => true,
				],
			],
		]
	);
}
