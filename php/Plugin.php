<?php
/**
 * Plugin
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

use ActionScheduler;
use ActionScheduler_Store;
use WP_Admin_Bar;
use WP_CLI;
use WP_Error;
use WP_HTML_Tag_Processor;
use WP_Post;
use WP_Term;
use WP_User;

/**
 * Plugin class
 */
final class Plugin {
	/**
	 * Controllers.
	 * 
	 * @var array
	 */
	private $controllers;

	/**
	 * Construct plugin
	 */
	public function __construct() {
		$this->controllers = [
			new CliController(),
			new SettingsController(),
		];
	}

	/**
	 * Setup.
	 * 
	 * @return void
	 */
	public function setup() {
		\add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );

		\add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 500 );

		\add_action( 'pronamic_cloudflare_purge_cache', [ $this, 'purge_cache' ] );

		\add_filter( 'cloudflare_purge_by_url', [ $this, 'cloudflare_purge_by_url' ] );

		\add_action( 'send_headers', $this->send_header_cache_tags( ... ) );

		foreach ( $this->controllers as $controller ) {
			$controller->setup();
		}
	}

	/**
	 * Admin bar menu.
	 * 
	 * @param WP_Admin_Bar $admin_bar Admin bar.
	 */
	public function admin_bar_menu( WP_Admin_Bar $admin_bar ) {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$number_pending_actions = $this->count_actions();

		$url = \add_query_arg(
			[
				'page' => 'action-scheduler',
				's'    => 'pronamic_cloudflare_purge_cache',
			],
			\admin_url( 'tools.php' )
		);

		$admin_bar->add_menu(
			[
				'id'     => 'pronamic-cloudflare',
				'parent' => null,
				'group'  => null,
				'title'  => \sprintf(
					/* translators: %s: Emoji. */
					\__( 'Cloudflare %s', 'pronamic-cloudflare' ),
					$number_pending_actions > 0 ? '🔄' : '✅'
				),
				'href'   => $url,
				'meta'   => [
					'title' => \__( 'Pronamic Cloudflare', 'pronamic-cloudflare' ),
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'pronamic-cloudflare-purge-cache-actions',
				'parent' => 'pronamic-cloudflare',
				'group'  => null,
				'title'  => \sprintf(
					/* translators: %s: Number pending actions. */
					\__( 'Purge cache actions (%s)', 'pronamic-cloudflare' ),
					$number_pending_actions
				),
				'href'   => \add_query_arg( 'status', 'pending', $url ),
			]
		);
	}

	/**
	 * Count actions.
	 * 
	 * @return int
	 */
	private function count_actions() {
		$store = ActionScheduler::store();

		$number = $store->query_actions(
			[ 
				'hook'   => 'pronamic_cloudflare_purge_cache',
				'group'  => 'pronamic-cloudflare',
				'status' => ActionScheduler_Store::STATUS_PENDING,
			],
			'count'
		);

		return $number;
	}

	/**
	 * Send Cache-Tag HTTP header.
	 *
	 * @return void
	 */
	private function send_header_cache_tags() {
		$tags = $this->get_current_cache_tags();

		if ( 0 === \count( $tags ) ) {
			return;
		}

		\header( 'Cache-Tag: ' . implode( ',', $tags ), false );
	}

	/**
	 * Get current cache tags.
	 *
	 * @return array
	 */
	public function get_current_cache_tags(): array {
		$tags = [];

		if ( is_singular() ) {
			$tags[] = 'post-' . get_the_ID();
		}

		if ( is_post_type_archive() ) {
			$tags[] = 'archive-' . get_post_type();
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				$tags[] = 'term-' . $term->term_id;
			}
		}

		if ( is_author() ) {
			$author = get_queried_object();

			if ( $author instanceof WP_User ) {
				$tags[] = 'author-' . $author->ID;
			}
		}

		if ( is_year() ) {
			$tags[] = 'date-' . get_query_var( 'year' );
		}

		if ( is_month() ) {
			$tags[] = \sprintf(
				'date-%02d-%02d',
				get_query_var( 'year' ),
				get_query_var( 'monthnum' )
			);
		}

		if ( is_day() ) {
			$tags[] = \sprintf(
				'date-%02d-%02d-%02d',
				get_query_var( 'year' ),
				get_query_var( 'monthnum' ),
				get_query_var( 'day' )
			);
		}

		if ( is_search() ) {
			$tags[] = 'search';
		}

		if ( is_404() ) {
			$tags[] = '404';
		}

		if ( is_home() ) {
			$tags[] = 'home';
		}

		if ( is_front_page() ) {
			$tags[] = 'front-page';
		}

		if ( is_feed() && ! is_archive() && ! is_author() && ! is_search() ) {
			$tags[] = 'feed';
		}

		return array_unique( $tags );
	}

	/**
	 * Cloudflare purge by URL.
	 * 
	 * This plugin takes over the cache purging from the Cloudflare plugin.
	 *
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/58db13b91fbd5e8613a8599d58cf05d04914d7e6/src/WordPress/Hooks.php#L140-L241
	 * @param string[] $urls URLs.
	 * @return string[]
	 */
	public function cloudflare_purge_by_url( $urls ) {
		$urls = [];

		return $urls;
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

		$this->purge_cache_of_post( $post );
	}

	/**
	 * Purge cache URL.
	 * 
	 * @link https://developers.cloudflare.com/api/operations/zone-purge#purge-cached-content-by-url
	 * @param string[] $files Files.
	 * @return void
	 * @throws \Exception Throws exception if purge cache action fails.
	 */
	public function purge_cache( $files ) {
		$api_email = \get_option( 'pronamic_cloudflare_api_email' );
		$api_key   = \get_option( 'pronamic_cloudflare_api_key' );
		$zone_id   = \get_option( 'pronamic_cloudflare_zone_id' );

		$url = strtr(
			'https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache',
			[
				'{zone_id}' => $zone_id,
			]
		);

		$response = \wp_remote_post(
			$url,
			[
				'headers' => [
					'Content-Type' => 'application/json',
					'X-Auth-Email' => $api_email,
					'X-Auth-Key'   => $api_key,
				],
				'body'    => \wp_json_encode(
					[
						'files' => $files,
					]
				),
			]
		);

		if ( \is_wp_error( $response ) ) {
			throw new \Exception( 'Cloudflare purge cache action went wrong: ' . $response->get_error_message() );
		}

		$response_code = (string) \wp_remote_retrieve_response_code( $response );

		if ( '200' !== $response_code ) {
			$response_body = \wp_remote_retrieve_body( $response );

			throw new \Exception(
				\sprintf(
					'Cloudflare purge cache action failed with code %s: %s',
					$response_code,
					$response_body
				)
			);
		}
	}

	/**
	 * Paginate URL's.
	 * 
	 * @link https://wpdevelopment.courses/articles/wp-html-tag-processor/
	 * @link https://developer.wordpress.org/reference/classes/wp_html_tag_processor/
	 * @param string $url   URL.
	 * @param int    $total Total.
	 * @return string[]
	 */
	private function get_paginate_urls( $url, $total = 10 ) {
		global $wp_rewrite;

		$urls = [];

		if ( ! $wp_rewrite->using_permalinks() ) {
			return $urls;
		}

		$urls = [];

		foreach ( \range( 2, $total ) as $page ) {
			$urls[] = \trailingslashit( $url ) . $wp_rewrite->pagination_base . '/' . $page . '/';
		}

		return $urls;
	}

	/**
	 * Get post related actions.
	 * 
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/v4.12.7/src/WordPress/Hooks.php#L252-L281
	 * @param WP_Term $term WordPress term object.
	 * @return PurgeCacheAction[]
	 */
	private function get_term_related_actions( WP_Term $term ) {
		$actions = [];

		if ( ! \is_term_publicly_viewable( $term ) ) {
			return $actions;
		}

		$result = \get_term_link( $term );

		if ( ! \is_wp_error( $result ) ) {
			$actions[] = new PurgeCacheAction( 'term', [ $result ] );

			$actions[] = new PurgeCacheAction( 'term-pages', $this->get_paginate_urls( $result ) );
		}

		$result = \get_term_feed_link( $term );

		if ( false !== $result ) {
			$actions[] = new PurgeCacheAction( 'term-feed', [ $result ] );
		}

		return $actions;
	}

	/**
	 * Get post type related actions.
	 * 
	 * @param string $post_type Post type.
	 * @return PurgeCacheAction[]
	 */
	private function get_post_type_related_actions( $post_type ) {
		$actions = [];

		$result = \get_post_type_archive_link( $post_type );

		if ( false !== $result ) {
			$url = \trailingslashit( $result );

			$actions[] = new PurgeCacheAction( 'post-archive', [ $url ] );

			$paginate_urls = $this->get_paginate_urls( $url );

			$actions[] = new PurgeCacheAction( 'post-archive-pages', $paginate_urls );
		}

		$result = \get_post_type_archive_feed_link( $post_type );

		if ( false !== $result ) {
			$actions[] = new PurgeCacheAction( 'post-archive-feed', [ $result ] );
		}

		return $actions;
	}

	/**
	 * Get user related actions.
	 * 
	 * @param WP_User $user User.
	 * @return string[]
	 */
	private function get_user_related_actions( WP_User $user ) {
		$actions = [
			new PurgeCacheAction(
				'author',
				[
					\get_author_posts_url( $user->ID ),
					\get_author_feed_link( $user->ID ),
				]
			),
		];

		return $actions;
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
	private function get_post_related_actions( WP_Post $post ) {
		$actions = [];

		if ( \wp_is_post_autosave( $post->ID ) ) {
			return $actions;
		}

		if ( \wp_is_post_revision( $post->ID ) ) {
			return $actions;
		}

		$post_type = \get_post_type( $post->ID );

		if ( ! \is_post_type_viewable( $post_type ) ) {
			return $actions;
		}

		/**
		 * Post.
		 */
		$result = \get_permalink( $post );

		if ( false !== $result ) {
			$actions[] = new PurgeCacheAction( 'post', [ $result ] );
		}

		$result = \get_post_comments_feed_link( $post->ID );

		if ( '' !== $result ) {
			$actions[] = new PurgeCacheAction( 'post-comments-feed', [ $result ] );
		}

		/**
		 * Post type.
		 */
		$post_type = \get_post_type( $post->ID );

		$post_type_actions = $this->get_post_type_related_actions( $post_type );

		$actions = \array_merge( $actions, $post_type_actions );

		/**
		 * Author.
		 */
		$user = \get_user_by( 'id', \get_post_field( 'post_author', $post ) );

		if ( false !== $user ) {
			$user_actions = $this->get_user_related_actions( $user );

			$actions = \array_merge( $actions, $user_actions );
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
				$term_actions = $this->get_term_related_actions( $term );

				$actions = \array_merge( $actions, $term_actions );
			}
		}

		/**
		 * Feeds.
		 */
		$actions[] = new PurgeCacheAction( 'feeds', $this->get_feed_urls() );

		/**
		 * Home.
		 */
		$actions[] = new PurgeCacheAction( 'home', [ \home_url( '/' ) ] );

		/**
		 * Ok.
		 */
		return $actions;
	}

	/**
	 * Purge cache of post.
	 * 
	 * @param WP_Post $post WordPress post object.
	 * @return void
	 */
	private function purge_cache_of_post( WP_Post $post ) {
		$actions = $this->get_post_related_actions( $post );

		foreach ( $actions as $action ) {
			$scheduled = \as_has_scheduled_action(
				'pronamic_cloudflare_purge_cache',
				[
					'files' => $action->files,
					'type'  => $action->type,
				],
				'pronamic-cloudflare',
			);

			if ( $scheduled ) {
				continue;
			}

			\as_schedule_single_action(
				$action->get_timestamp(),
				'pronamic_cloudflare_purge_cache',
				[
					'files' => $action->files,
					'type'  => $action->type,
				],
				'pronamic-cloudflare',
				false,
				$action->get_priority()
			);
		}
	}
}
