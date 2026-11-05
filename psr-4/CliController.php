<?php
/**
 * CLI controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
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
		\add_action( 'cli_init', $this->cli_init( ... ) );
	}

	/**
	 * CLI initialize.
	 */
	private function cli_init() {
		WP_CLI::add_command(
			'pronamic cloudflare zones',
			function ( $args, $assoc_args ): void {
				$headers = CloudflareClient::get_auth_headers();

				if ( null === $headers ) {
					WP_CLI::error( 'The Cloudflare API credentials are not configured. Define either `CLOUDFLARE_API_TOKEN` or both `CLOUDFLARE_EMAIL` and `CLOUDFLARE_API_KEY`.' );
				}

				$url = add_query_arg(
					\array_intersect_key(
						$assoc_args,
						[
							'name'     => true,
							'status'   => true,
							'page'     => true,
							'per_page' => true,
						]
					),
					'https://api.cloudflare.com/client/v4/zones'
				);

				$response = wp_remote_get(
					$url,
					[
						'headers' => [
							'Content-Type' => 'application/json',
							...$headers,
						],
					]
				);

				$data = $this->get_response_data( $response, 'Cloudflare zones could not be retrieved' );

				if ( ! \is_array( $data->result ?? null ) ) {
					WP_CLI::error( 'Cloudflare zones could not be retrieved: the response does not contain a result.' );
				}

				$items = \array_map(
					fn( $item ) => [
						'id'     => $item->id,
						'name'   => $item->name,
						'status' => $item->status,
					],
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
						'type'        => 'assoc',
						'name'        => 'page',
						'description' => 'Page number of paginated results.',
						'optional'    => true,
					],
					[
						'type'        => 'assoc',
						'name'        => 'per_page',
						'description' => 'Number of zones per page (5-50, Cloudflare default: 20).',
						'optional'    => true,
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
			function ( $args ): void {
				$headers = CloudflareClient::get_auth_headers();

				if ( null === $headers ) {
					WP_CLI::error( 'The Cloudflare API credentials are not configured. Define either `CLOUDFLARE_API_TOKEN` or both `CLOUDFLARE_EMAIL` and `CLOUDFLARE_API_KEY`.' );
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
							// phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- WP-CLI command, not a front-end request.
							'timeout' => 30,
							'headers' => [
								'Content-Type' => 'application/json',
								...$headers,
							],
							'body'    => wp_json_encode(
								[
									'purge_everything' => true,
								]
							),
						]
					);

					$this->get_response_data( $response, \sprintf( 'Cloudflare zone `%s` cache not purged', $identifier ) );

					WP_CLI::success( \sprintf( 'Cloudflare zone `%s` cache purged.', $identifier ) );
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

		WP_CLI::add_command(
			'pronamic cloudflare invalidate',
			function ( $args ): void {
				$headers = CloudflareClient::get_auth_headers();

				if ( null === $headers ) {
					WP_CLI::error( 'The Cloudflare API credentials are not configured. Define either `CLOUDFLARE_API_TOKEN` or both `CLOUDFLARE_EMAIL` and `CLOUDFLARE_API_KEY`.' );
				}

				foreach ( $args as $identifier ) {
					$url = strtr(
						'https://api.cloudflare.com/client/v4/zones/:identifier/invalidate_cache',
						[
							':identifier' => $identifier,
						]
					);

					$response = wp_remote_post(
						$url,
						[
							// phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- WP-CLI command, not a front-end request.
							'timeout' => 30,
							'headers' => [
								'Content-Type' => 'application/json',
								...$headers,
							],
							'body'    => wp_json_encode(
								[
									'purge_everything' => true,
								]
							),
						]
					);

					$this->get_response_data( $response, \sprintf( 'Cloudflare zone `%s` cache not invalidated', $identifier ) );

					WP_CLI::success( \sprintf( 'Cloudflare zone `%s` cache invalidated.', $identifier ) );
				}
			},
			[
				'shortdesc' => 'Invalidates ALL files in Cloudflare’s cache, so Cloudflare revalidates the cached responses.',
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

		WP_CLI::add_command(
			'pronamic cloudflare blog invalidate',
			function ( $args ): void {
				$client = $this->get_client();

				$tags = \array_map(
					fn( $blog_id ) => CacheTags::get_blog_tag( $blog_id ),
					$this->get_blog_ids( $args )
				);

				try {
					$client->invalidate_cache_tags( $tags );
				} catch ( \Exception $e ) {
					WP_CLI::error( \sprintf( 'Cloudflare cache tags not invalidated: %s', \html_entity_decode( $e->getMessage(), ENT_QUOTES ) ) );
				}

				foreach ( $tags as $tag ) {
					WP_CLI::log( \sprintf( 'Cache tag `%s` invalidated.', $tag ) );
				}

				WP_CLI::success( \sprintf( 'Cloudflare cache invalidated for %d blog(s).', \count( $tags ) ) );
			},
			[
				'shortdesc' => 'Invalidates all cached HTML of one or more blogs in the configured Cloudflare zone, static files are kept.',
				'synopsis'  => [
					[
						'type'        => 'positional',
						'name'        => 'blog-id',
						'description' => 'One or more blog IDs, defaults to the current blog ID.',
						'optional'    => true,
						'repeating'   => true,
					],
				],
			]
		);

		WP_CLI::add_command(
			'pronamic cloudflare blog purge',
			function ( $args ): void {
				$client = $this->get_client();

				$tags = \array_map(
					fn( $blog_id ) => CacheTags::get_blog_tag( $blog_id ),
					$this->get_blog_ids( $args )
				);

				try {
					$client->purge_cache_tags( $tags );
				} catch ( \Exception $e ) {
					WP_CLI::error( \sprintf( 'Cloudflare cache tags not purged: %s', \html_entity_decode( $e->getMessage(), ENT_QUOTES ) ) );
				}

				foreach ( $tags as $tag ) {
					WP_CLI::log( \sprintf( 'Cache tag `%s` purged.', $tag ) );
				}

				WP_CLI::success( \sprintf( 'Cloudflare cache purged for %d blog(s).', \count( $tags ) ) );
			},
			[
				'shortdesc' => 'Purges all cached HTML of one or more blogs from the configured Cloudflare zone, static files are kept.',
				'synopsis'  => [
					[
						'type'        => 'positional',
						'name'        => 'blog-id',
						'description' => 'One or more blog IDs, defaults to the current blog ID.',
						'optional'    => true,
						'repeating'   => true,
					],
				],
			]
		);

		WP_CLI::add_command(
			'pronamic cloudflare purge-prefix',
			function ( $args ): void {
				$client = $this->get_client();

				try {
					$client->purge_prefixes( $args );
				} catch ( \Exception $e ) {
					WP_CLI::error( \sprintf( 'Cloudflare cache not purged by prefix: %s', \html_entity_decode( $e->getMessage(), ENT_QUOTES ) ) );
				}

				foreach ( $args as $prefix ) {
					WP_CLI::log( \sprintf( 'Purged prefix `%s`.', $prefix ) );
				}

				WP_CLI::success( \sprintf( 'Cloudflare cache purged for %d prefix(es).', \count( $args ) ) );
			},
			[
				'shortdesc' => 'Purges cached files by URL prefix from the configured Cloudflare zone.',
				'synopsis'  => [
					[
						'type'        => 'positional',
						'name'        => 'prefix',
						'description' => 'URL prefix, for example `https://www.example.com/wp-content/themes/example/`.',
						'optional'    => false,
						'repeating'   => true,
					],
				],
			]
		);
	}

	/**
	 * Get the Cloudflare client or exit with an error if it is not configured.
	 *
	 * @return CloudflareClient
	 */
	private function get_client(): CloudflareClient {
		if ( null === CloudflareClient::get_auth_headers() ) {
			WP_CLI::error( 'The Cloudflare API credentials are not configured. Define either `CLOUDFLARE_API_TOKEN` or both `CLOUDFLARE_EMAIL` and `CLOUDFLARE_API_KEY`.' );
		}

		if ( ! CloudflareClient::is_configured() ) {
			WP_CLI::error( 'The Cloudflare zone ID is not configured. Define `PRONAMIC_CLOUDFLARE_ZONE_ID` or set it on the settings page.' );
		}

		return new CloudflareClient();
	}

	/**
	 * Get the blog IDs from the positional arguments or exit with an error.
	 *
	 * @param array $args Positional arguments.
	 * @return int[]
	 */
	private function get_blog_ids( array $args ): array {
		if ( 0 === \count( $args ) ) {
			return [ \get_current_blog_id() ];
		}

		$blog_ids = [];

		foreach ( $args as $arg ) {
			$blog_id = \filter_var( $arg, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );

			if ( false === $blog_id ) {
				WP_CLI::error( \sprintf( 'Invalid blog ID: `%s`.', $arg ) );
			}

			$blog_ids[] = $blog_id;
		}

		return \array_values( \array_unique( $blog_ids ) );
	}

	/**
	 * Get the decoded data of a Cloudflare API response or exit with an error.
	 *
	 * @param array|\WP_Error $response Response.
	 * @param string          $message  Error message prefix.
	 * @return object
	 */
	private function get_response_data( $response, string $message ): object {
		if ( is_wp_error( $response ) ) {
			WP_CLI::error( \sprintf( '%s: %s', $message, $response->get_error_message() ) );
		}

		$body = wp_remote_retrieve_body( $response );

		$data = json_decode( $body );

		if ( ! \is_object( $data ) ) {
			WP_CLI::error(
				\sprintf(
					'%s: unexpected response (HTTP status code %s): %s',
					$message,
					wp_remote_retrieve_response_code( $response ),
					$body
				)
			);
		}

		if ( true !== ( $data->success ?? null ) ) {
			WP_CLI::error(
				\sprintf(
					'%s: %s',
					$message,
					\wp_json_encode( $data->errors ?? $data, JSON_PRETTY_PRINT )
				)
			);
		}

		return $data;
	}
}
