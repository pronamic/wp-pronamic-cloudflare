<?php
/**
 * CLI controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Moneybird
 */

namespace Pronamic\WordPressCloudflare;

use WP_CLI;

/**
 * CLI controller class
 */
final class CliController {
	/**
	 * Setup.
	 */
	public function setup() {
		\add_action( 'cli_init', [ $this, 'cli_init' ] );
	}

	/**
	 * CLI initialize.
	 */
	public function cli_init() {
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
}
