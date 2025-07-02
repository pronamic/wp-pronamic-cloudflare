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
	 * Tags to purge.
	 * 
	 * @var string[]
	 */
	private $purge_tags = [];

	/**
	 * Purge everything.
	 * 
	 * @var bool
	 */
	private $purge_everything = false;

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
		\add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 500 );
		\add_action( 'pronamic_cloudflare_purge_cache', [ $this, 'purge_cache' ] );

		// Post actions.
		\add_action( 'save_post', $this->purge_cache_by_post( ... ), 10, 1 );
		\add_action( 'delete_post', $this->purge_cache_by_post( ... ), 10, 1 );
		\add_action( 'trashed_post', $this->purge_cache_by_post( ... ), 10, 1 );
		\add_action( 'untrashed_post', $this->purge_cache_by_post( ... ), 10, 1 );
		\add_action( 'transition_post_status', $this->transition_post_status( ... ), 10, 3 );

		// Comment actions.
		\add_action( 'comment_post', $this->purge_cache_by_comment( ... ), 10, 1 );
		\add_action( 'edit_comment', $this->purge_cache_by_comment( ... ), 10, 1 );
		\add_action( 'delete_comment', $this->purge_cache_by_comment( ... ), 10, 1 );

		// Term actions.
		\add_action( 'created_term', $this->purge_cache_by_term( ... ), 10, 3 );
		\add_action( 'edited_term', $this->purge_cache_by_term( ... ), 10, 3 );
		\add_action( 'delete_term', $this->purge_cache_by_deleted_term( ... ), 10, 4 );
		\add_action( 'set_object_terms', $this->set_object_terms( ... ), 10, 6 );

		// User actions.
		\add_action( 'profile_update', $this->purge_cache_by_user( ... ), 10, 1 );
		\add_action( 'deleted_user', $this->purge_cache_by_deleted_user( ... ), 10, 3 );

		// Purge everything actions.
		\add_action( 'switch_theme', $this->purge_everything( ... ) );
		\add_action( 'customize_save_after', $this->purge_everything( ... ) );
		\add_action( 'save_post_wp_template', $this->purge_everything( ... ) );
		\add_action( 'save_post_wp_template_part', $this->purge_everything( ... ) );
		\add_action( 'save_post_wp_global_styles', $this->purge_everything( ... ) );

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
	 * Purge cache.
	 *
	 * @link https://developers.cloudflare.com/api/resources/cache/methods/purge/
	 * @param array $args Arguments for purge cache request.
	 * @return void
	 * @throws \Exception Throws exception if purge cache action fails.
	 */
	public function purge_cache( $args ) {
		$api_email = (string) \get_option( 'pronamic_cloudflare_api_email' );
		$api_key   = (string) \get_option( 'pronamic_cloudflare_api_key' );
		$zone_id   = (string) \get_option( 'pronamic_cloudflare_zone_id' );

		if ( '' === $api_email || '' === $api_key || '' === $zone_id ) {
			throw new \Exception( \esc_html( 'Pronamic Cloudflare plugin settings are invalid.' ) );
		}

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
				'body'    => \wp_json_encode( $args ),
			]
		);

		if ( \is_wp_error( $response ) ) {
			throw new \Exception( \esc_html( 'Cloudflare purge cache action went wrong: ' . $response->get_error_message() ) );
		}

		$response_code = (string) \wp_remote_retrieve_response_code( $response );

		if ( '200' !== $response_code ) {
			$response_body = \wp_remote_retrieve_body( $response );

			throw new \Exception(
				\sprintf(
					'Cloudflare purge cache action failed with code %s: %s',
					\esc_html( $response_code ),
					\esc_html( $response_body )
				)
			);
		}
	}

	/**
	 * Purge cache by post.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return void
	 */
	private function purge_cache_by_post( $post_id ): void {
		$post = \get_post( $post_id );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return;
		}

		$tags = $this->get_post_related_tags( $post );

		$this->purge_by_tags( $tags );
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
	public function transition_post_status( $new_status, $old_status, WP_Post $post ): void {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}

		$this->purge_cache_by_post( $post );
	}

	/**
	 * Purge cache by comment.
	 *
	 * @param int $comment_id Comment ID.
	 * @return void
	 */
	private function purge_cache_by_comment( $comment_id ) {
		$comment = \get_comment( $comment_id );

		if ( ! ( $comment instanceof \WP_Comment ) ) {
			return;
		}

		$tags = $this->get_comment_related_tags( $comment );

		$this->purge_by_tags( $tags );
	}

	/**
	 * Purge cache by comment.
	 *
	 * @param int         $term_id  Term ID.
	 * @param int|null    $tt_id    Term taxonomy ID.
	 * @param string|null $taxonomy Taxonomy slug.
	 * @return void
	 */
	private function purge_cache_by_term( $term_id, $tt_id = null, $taxonomy = null ) {
		$term = null;

		if ( $term_id && $taxonomy ) {
			$term = \get_term( $term_id, $taxonomy );
		}

		if ( ! ( $term instanceof \WP_Term ) ) {
			return;
		}

		$tags = $this->get_term_related_tags( $term );

		$this->purge_by_tags( $tags );
	}

	/**
	 * Purge cache by deleted term.
	 *
	 * @param int      $term_id      Term ID.
	 * @param int      $tt_id        Term taxonomy ID.
	 * @param string   $taxonomy     Taxonomy slug.
	 * @param \WP_Term $deleted_term Deleted term object.
	 * @return void
	 */
	private function purge_cache_by_deleted_term( $term_id, $tt_id = null, $taxonomy = null, $deleted_term ): void {
		if ( ! ( $deleted_term instanceof \WP_Term ) ) {
			return;
		}

		$tags = $this->get_term_related_tags( $deleted_term );

		$this->purge_by_tags( $tags );
	}

	/**
	 * Purge cache by deleted term.
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object term IDs or slugs.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 * @return void
	 */
	private function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ): void {
		$tags = [];

		foreach ( $terms as $term_id ) {
			$term = \get_term( $term_id, $taxonomy );

			if ( ! ( $term instanceof \WP_Term ) ) {
				continue;
			}

			$tags = \array_merge( $tags, $this->get_term_related_tags( $term ) );
		}

		foreach ( $old_tt_ids as $tt_id ) {
			$term = \get_term_by( 'term_taxonomy_id', $tt_id, $taxonomy );

			if ( ! ( $term instanceof \WP_Term ) ) {
				continue;
			}

			$tags = \array_merge( $tags, $this->get_term_related_tags( $term ) );
		}

		$tags = array_unique( $tags );

		$this->purge_by_tags( $tags );
	}

	/**
	 * Purge cache by user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	private function purge_cache_by_user( int $user_id ): void {
		$user = get_user_by( 'ID', $user_id );

		if ( ! ( $user instanceof \WP_User ) ) {
			return;
		}

		$tags = $this->get_user_related_tags( $user );

		$this->purge_by_tags( $tags );
	}

	/**
	 * Purge cache by deleted user.
	 *
	 * @param int      $id        ID of the deleted user.
	 * @param int|null $reassign  ID of the user to reassign posts and links to.
	 *                            Default null, for no reassignment.
	 * @param \WP_User $user     WP_User object of the deleted user.
	 * @return void
	 */
	private function purge_cache_by_deleted_user( $id, $reassign, \WP_User $user ): void {
		$tags = $this->get_user_related_tags( $user );

		$this->purge_by_tags( $tags );
	}

	/**
	 * Purge everything.
	 * 
	 * @return void
	 */
	private function purge_everything(): void {
		$this->purge_everything = true;

		if ( ! \has_action( 'shutdown', $this->shutdown( ... ) ) ) {
			\add_action( 'shutdown', $this->shutdown( ... ) );
		}
	}

	/**
	 * Get comment related tags.
	 *
	 * @param \WP_Comment|null $comment Comment object.
	 * @return string[]
	 */
	private function get_comment_related_tags( $comment ) {
		$tags = [];

		if ( ! ( $comment instanceof \WP_Comment ) ) {
			return $tags;
		}

		$tags[] = 'comment-' . $comment->comment_ID;

		if ( $comment->user_id ) {
			$user = \get_user_by( 'id', $comment->user_id );

			if ( false !== $user ) {
				$tags = \array_merge( $tags, $this->get_user_related_tags( $user ) );
			}
		}

		if ( $comment->comment_post_ID ) {
			$post = \get_post( $comment->comment_post_ID );

			if ( false !== $post ) {
				$tags = \array_merge( $tags, $this->get_post_related_tags( $post ) );
			}
		}

		return $tags;
	}

	/**
	 * Get term related tags.
	 *
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/v4.12.7/src/WordPress/Hooks.php#L252-L281
	 * @param WP_Term $term WordPress term object.
	 * @return string[]
	 */
	private function get_term_related_tags( WP_Term $term ) {
		$tags = [];

		if ( ! \is_term_publicly_viewable( $term ) ) {
			return $tags;
		}

		return [
			'term-' . $term->term_id,
		];
	}

	/**
	 * Get post type related tags.
	 *
	 * @param string $post_type Post type.
	 * @return string[]
	 */
	private function get_post_type_related_tags( $post_type ) {
		return [
			'archive-' . $post_type,
		];
	}

	/**
	 * Get user related tags.
	 *
	 * @param WP_User $user User.
	 * @return string[]
	 */
	private function get_user_related_tags( WP_User $user ) {
		return [
			'author-' . $user->ID,
		];
	}

	/**
	 * Get post related tags.
	 *
	 * @param WP_Post $post WordPress post object.
	 * @return string[]
	 */
	private function get_post_related_tags( WP_Post $post ) {
		$tags = [];

		if ( \wp_is_post_autosave( $post->ID ) ) {
			return $tags;
		}

		if ( \wp_is_post_revision( $post->ID ) ) {
			return $tags;
		}

		$post_type = \get_post_type( $post->ID );

		if ( ! \is_post_type_viewable( $post_type ) ) {
			return $tags;
		}

		/**
		 * Post.
		 */
		$tags[] = 'post-' . $post->ID;

		/**
		 * Post type.
		 */
		$post_type = \get_post_type( $post->ID );

		$tags = \array_merge( $tags, $this->get_post_type_related_tags( $post_type ) );

		/**
		 * Author.
		 */
		$user = \get_user_by( 'id', \get_post_field( 'post_author', $post ) );

		if ( false !== $user ) {
			$tags = \array_merge( $tags, $this->get_user_related_tags( $user ) );
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
				$tags = \array_merge( $tags, $this->get_term_related_tags( $term ) );
			}
		}

		/**
		 * Date.
		 */
		$tags[] = 'date-' . \get_the_date( 'Y', $post );
		$tags[] = 'date-' . \get_the_date( 'Y-m', $post );
		$tags[] = 'date-' . \get_the_date( 'Y-m-d', $post );

		/**
		 * Feeds.
		 */
		$tags[] = 'feed';

		/**
		 * Front page.
		 */
		$tags[] = 'front-page';

		/**
		 * Blog home.
		 */
		if ( 'post' === $post_type ) {
			$tags[] = 'home';
		}

		/**
		 * Ok.
		 */
		return $tags;
	}

	/**
	 * Schedule purge cache action for tags.
	 *
	 * @param string[] $tags Tags to purge.
	 * @return void
	 */
	private function purge_by_tags( $tags ) {
		if ( 0 === count( $tags ) ) {
			return;
		}

		$updated_tags = \array_merge( $this->purge_tags, $tags );

		$updated_tags = \array_unique( $updated_tags );

		$this->purge_tags = $updated_tags;

		if ( ! \has_action( 'shutdown', $this->shutdown( ... ) ) ) {
			\add_action( 'shutdown', $this->shutdown( ... ) );
		}
	}

	/**
	 * Schedule purge cache action.
	 *
	 * @param array $args Arguments for purge cache action.
	 * @return void
	 */
	private function schedule_purge_cache_action( $args ): void {
		if ( 0 === count( $args ) ) {
			return;
		}

		$args = [ $args ];

		$scheduled = \as_has_scheduled_action(
			'pronamic_cloudflare_purge_cache',
			$args,
			'pronamic-cloudflare',
		);

		if ( $scheduled ) {
			return;
		}

		\as_enqueue_async_action(
			'pronamic_cloudflare_purge_cache',
			$args,
			'pronamic-cloudflare',
			false
		);
	}

	/**
	 * Schedule purge cache action on shutdown.
	 *
	 * @return void
	 */
	private function shutdown(): void {
		if ( 0 === count( $this->purge_tags ) && false === $this->purge_everything ) {
			return;
		}

		$args = [
			'tags' => $this->purge_tags,
		];

		if ( true === $this->purge_everything ) {
			$args = [
				'purge_everything' => true,
			];

			\as_unschedule_all_actions(
				'pronamic_cloudflare_purge_cache',
				null,
				'pronamic-cloudflare'
			);
		}

		$this->schedule_purge_cache_action( $args );
	}
}
