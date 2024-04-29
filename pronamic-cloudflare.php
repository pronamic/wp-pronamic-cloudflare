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

namespace Pronamic\WordPressCloudflare;

use WP_CLI;
use WP_Error;
use WP_HTML_Tag_Processor;
use WP_Post;
use WP_Term;
use WP_User;

class Plugin {
	/**
	 * Setup.
	 * 
	 * @return void
	 */
	public function setup() {
		\add_action(
			'init',
			function () {
				$post = \get_post( 1 );

				$urls = $this->get_post_related_urls( $post );

				var_dump( $urls );


			});
		\add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );
	}

	/**
	 * Transition post status.
	 * 
	 * Please note that the name transition_post_status is misleading.
	 * The hook does not only fire on a post status transition but also when a
	 * post is updated while the status is not changed from one to another at
	 * all.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/transition_post_status/
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/v4.12.7/cloudflare.loader.php#L106-L113
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/v4.12.7/src/WordPress/Hooks.php#L445-L450
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       WordPress post object.
	 * @return void
	 */
	public function transition_post_status( $new_status, $old_status, WP_Post $post ) {
		if ( 'publish' !== $new_status || 'publish' !== $old_status ) {
			return;
		}

		$urls = $this->get_post_related_urls( $post );

		foreach ( $urls as $url ) {
			\as_enqueue_async_action(
				'pronamic_cloudflare_purge_cache_url',
				[
					'url' => $url,
				],
				'pronamic-cloudflare',
				false,
				10
			);
		}
	}

	/**
	 * Paginate URL's.
	 * 
	 * @link https://wpdevelopment.courses/articles/wp-html-tag-processor/
	 * @link https://developer.wordpress.org/reference/classes/wp_html_tag_processor/
	 * @param string $url
	 * @return string[]
	 */
	private function get_paginate_urls( $url ) {
		$items = [];

		$paginate_array = \paginate_links(
			[
				'base'  => \trailingslashit( $url ) . '%_%',
				'total' => 3,
				'type'  => 'array',
			]
		);

		foreach ( $paginate_array as $anchor_html ) {
			$processor = new WP_HTML_Tag_Processor( $anchor_html );

			while ( $processor->next_tag( [ 'tag_name' => 'a', 'tag_closers' => 'skip' ] ) ) {
				$items[] = $processor->get_attribute( 'href' );
			}
		}

		$urls = \array_filter( $items );

		$urls = \array_unique( $urls );

		return $urls;
	}

	/**
	 * Get post related URL's.
	 * 
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/v4.12.7/src/WordPress/Hooks.php#L252-L281
	 * @param WP_Post $post WordPress post object.
	 * @return string[]
	 */
	private function get_term_related_urls( WP_Term $term ) {
		$urls = [];

		if ( ! \is_term_publicly_viewable( $term ) ) {
			return $urls;
		}

		$result = \get_term_link( $term );

		if ( ! \is_wp_error( $result ) ) {
			$urls[] = $result;

			$urls = \array_merge(
				$urls,
				$this->get_paginate_urls( $result )
			);
		}

		$result = \get_term_feed_link( $term );

		if ( false !== $result ) {
			$urls[] = $result;
		}

		return $urls;
	}

	/**
	 * Get post type related URL's.
	 * 
	 * @param string $post_type Post type.
	 * @return string[]
	 */
	private function get_post_type_related_urls( $post_type ) {
		$urls = [];

		$result = \get_post_type_archive_link( $post_type );

		if ( false !== $result ) {
			$urls[] = $result;

			$urls = \array_merge(
				$urls,
				$this->get_paginate_urls( $result )
			);
		}

		$result = \get_post_type_archive_feed_link( $post_type );

		if ( false !== $result ) {
			$urls[] = $result;
		}

		return $urls;
	}

	/**
	 * Get user related URL's.
	 * 
	 * @param WP_User $user User.
	 * @return string[]
	 */
	private function get_user_related_urls( WP_User $user ) {
		$urls = [
			\get_author_posts_url( $user->ID ),
			\get_author_feed_link( $user->ID ),
		];

		return $urls;
	}

	/**
	 * Get feed URL's.
	 * 
	 * @return string[]
	 */
	private function get_feed_urls() {
		$urls = [
			\get_bloginfo_rss( 'rdf_url' ),
			\get_bloginfo_rss( 'rss_url' ),
			\get_bloginfo_rss( 'rss2_url' ),
			\get_bloginfo_rss( 'atom_url' ),
			\get_bloginfo_rss( 'comments_rss2_url' ),
		];

		return $urls;
	}

	/**
	 * Get post related URL's.
	 * 
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/v4.12.7/src/WordPress/Hooks.php#L252-L281
	 * @param WP_Post $post WordPress post object.
	 * @return string[]
	 */
	private function get_post_related_urls( WP_Post $post ) {
		$urls = [];

		/**
		 * Post.
		 */
		$result = \get_permalink( $post );

		if ( false !== $result ) {
			$urls[] = $result;
		}

		$result = \get_post_comments_feed_link( $post->ID );

		if ( '' !== $result ) {
			$urls[] = $result;
		}

		/**
		 * Post type.
		 */
		$post_type = \get_post_type( $post->ID );

		$post_type_urls = $this->get_post_type_related_urls( $post_type );

		$urls = \array_merge( $urls, $post_type_urls );

		/**
		 * Author.
		 */
		$user = \get_user_by( 'id', \get_post_field( 'post_author', $post ) );

		if ( false !== $user ) {
			$user_urls = $this->get_user_related_urls( $user );

			$urls = \array_merge( $urls, $user_urls );
		}

		/**
		 * Terms
		 * 
		 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/v4.12.7/src/WordPress/Hooks.php#L257C11-L281
		 * @link https://developer.wordpress.org/reference/functions/get_terms/
		 * @link https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
		 * @link https://developer.wordpress.org/reference/functions/is_term_publicly_viewable/
		 */
		$terms = \get_terms(
			[
				'object_ids' => [ $post->ID ],
			]
		);

		if ( ! \is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_urls = $this->get_term_related_urls( $term );

				$urls = \array_merge( $urls, $term_urls );
			}
		}

		/**
		 * Feeds.
		 */
		$feed_urls = $this->get_feed_urls();

		$urls = \array_merge( $urls, $feed_urls );

		/**
		 * Home.
		 */
		$urls[] = \home_url( '/' );

		/**
		 * Ok.
		 */
		return $urls;
	}

	/**
	 * Purge cache of post.
	 * 
	 * @param WP_Post $post WordPress post object.
	 * @return void
	 */
	private function purge_cache_of_post( WP_Post $post ) {
		if ( \wp_is_post_autosave( $post->ID ) ) {
			return;
		}

		if ( \wp_is_post_revision( $post->ID ) ) {
			return;
		}

		$post_type = \get_post_type( $post->ID );

		if ( ! \is_post_type_viewable( $post_type ) ) {
			return;
		}

		$urls = $this->get_post_related_urls( $post );
	}
}

$pronamic_cloudflare_plugin = new Plugin();

$pronamic_cloudflare_plugin->setup();

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
