<?php
/**
 * Invalidation controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

use WP_Post;

/**
 * Invalidation controller class
 *
 * Collects the cache tags of changed posts, comments, terms and users,
 * schedules background actions to purge or invalidate them on shutdown and
 * handles these actions with the Cloudflare client.
 */
final class InvalidationController {
	/**
	 * Cloudflare client.
	 *
	 * @var CloudflareClient
	 */
	private $client;

	/**
	 * Tags to purge.
	 *
	 * @var string[]
	 */
	private $purge_tags = [];

	/**
	 * Tags to invalidate.
	 *
	 * @var string[]
	 */
	private $invalidate_tags = [];

	/**
	 * Purge everything.
	 *
	 * @var bool
	 */
	private $purge_everything = false;

	/**
	 * Construct invalidation controller.
	 *
	 * @param CloudflareClient $client Cloudflare client.
	 */
	public function __construct( CloudflareClient $client ) {
		$this->client = $client;
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup() {
		\add_action( 'pronamic_cloudflare_purge_cache_tags', $this->client->purge_cache_tags( ... ) );
		\add_action( 'pronamic_cloudflare_invalidate_cache_tags', $this->client->invalidate_cache_tags( ... ) );
		\add_action( 'pronamic_cloudflare_purge_everything', $this->client->purge_everything( ... ) );

		\add_action( 'shutdown', $this->shutdown( ... ) );

		// Post actions.
		\add_action( 'transition_post_status', $this->transition_post_status( ... ), 10, 3 );
		\add_action( 'before_delete_post', $this->purge_cache_by_deleted_post( ... ), 10, 2 );

		// Comment actions.
		\add_action( 'comment_post', $this->invalidate_cache_by_comment( ... ), 10, 1 );
		\add_action( 'edit_comment', $this->invalidate_cache_by_comment( ... ), 10, 1 );
		\add_action( 'transition_comment_status', $this->transition_comment_status( ... ), 10, 3 );
		\add_action( 'delete_comment', $this->purge_cache_by_deleted_comment( ... ), 10, 2 );

		// Term actions.
		\add_action( 'created_term', $this->invalidate_cache_by_term( ... ), 10, 3 );
		\add_action( 'edited_term', $this->invalidate_cache_by_term( ... ), 10, 3 );
		\add_action( 'delete_term', $this->purge_cache_by_deleted_term( ... ), 10, 4 );
		\add_action( 'set_object_terms', $this->set_object_terms( ... ), 10, 6 );

		// User actions.
		\add_action( 'profile_update', $this->invalidate_cache_by_user( ... ), 10, 1 );
		\add_action( 'deleted_user', $this->purge_cache_by_deleted_user( ... ), 10, 3 );

		// Purge everything actions.
		\add_action( 'switch_theme', $this->purge_everything( ... ) );
		\add_action( 'customize_save_after', $this->purge_everything( ... ) );
		\add_action( 'save_post_wp_template', $this->purge_everything( ... ) );
		\add_action( 'save_post_wp_template_part', $this->purge_everything( ... ) );
		\add_action( 'save_post_wp_global_styles', $this->purge_everything( ... ) );
	}

	/**
	 * Invalidate cache by post.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return void
	 */
	private function invalidate_cache_by_post( $post_id ): void {
		$this->cache_by_post( $post_id, true );
	}

	/**
	 * Purge cache by post.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return void
	 */
	private function purge_cache_by_post( $post_id ): void {
		$this->cache_by_post( $post_id, false );
	}

	/**
	 * Process cache tags related to a post.
	 *
	 * @param int  $post_id    WordPress post ID.
	 * @param bool $invalidate Whether to invalidate instead of purge.
	 * @return void
	 */
	private function cache_by_post( $post_id, bool $invalidate ): void {
		$post = \get_post( $post_id );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return;
		}

		$tags = CacheTags::get_post_related_tags( $post );

		$post_type = \get_post_type( $post->ID );

		/**
		 * Post type.
		 */
		$tags = \array_merge( $tags, CacheTags::get_post_type_related_tags( $post_type ) );

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
		 * Search.
		 */
		$tags[] = 'search';

		/**
		 * 404.
		 */
		$tags[] = '404';

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

		if ( $invalidate ) {
			$this->invalidate_by_tags( $tags );

			return;
		}

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
	 * This hook therefore also handles updates of published posts and moving
	 * published posts to and from the trash. We don't use the `save_post` hook
	 * because it also fires for drafts, autosaves and revisions, which don't
	 * require a cache purge.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/transition_post_status/
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/v4.12.7/cloudflare.loader.php#L106-L113
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/v4.12.7/src/WordPress/Hooks.php#L445-L450
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       WordPress post object.
	 * @return void
	 */
	private function transition_post_status( $new_status, $old_status, WP_Post $post ): void {
		if ( \is_post_status_viewable( $new_status ) ) {
			$this->invalidate_cache_by_post( $post->ID );

			return;
		}

		if ( \is_post_status_viewable( $old_status ) ) {
			$this->purge_cache_by_post( $post->ID );
		}
	}

	/**
	 * Invalidate cache by comment.
	 *
	 * @param int $comment_id Comment ID.
	 * @return void
	 */
	private function invalidate_cache_by_comment( $comment_id ): void {
		$this->cache_by_comment( $comment_id, true );
	}

	/**
	 * Handle a comment status transition.
	 *
	 * @param string      $new_status New status.
	 * @param string      $old_status Old status.
	 * @param \WP_Comment $comment    Comment object.
	 * @return void
	 */
	private function transition_comment_status( $new_status, $old_status, \WP_Comment $comment ): void {
		if ( 'approved' === $new_status ) {
			$this->invalidate_cache_by_comment( $comment->comment_ID );

			return;
		}

		if ( 'approved' === $old_status ) {
			$this->purge_cache_by_comment( $comment->comment_ID );
		}
	}

	/**
	 * Purge cache by deleted post.
	 *
	 * Permanently deleting a published post, for example with
	 * `wp_delete_post( $post_id, true )`, does not trigger a post status
	 * transition. We use the `before_delete_post` hook because the term
	 * relationships are already removed when the `delete_post` hook fires.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/before_delete_post/
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object.
	 * @return void
	 */
	private function purge_cache_by_deleted_post( $post_id, $post ): void {
		if ( ! ( $post instanceof WP_Post ) || ! \is_post_status_viewable( $post->post_status ) ) {
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
	private function purge_cache_by_comment( $comment_id ): void {
		$this->cache_by_comment( $comment_id, false );
	}

	/**
	 * Purge cache by deleted comment.
	 *
	 * Only approved comments are publicly visible, so deleting an
	 * unapproved, spam or trashed comment does not require a purge.
	 *
	 * @param int         $comment_id Comment ID.
	 * @param \WP_Comment $comment    Comment object.
	 * @return void
	 */
	private function purge_cache_by_deleted_comment( $comment_id, $comment ): void {
		if ( ! ( $comment instanceof \WP_Comment ) || 1 !== (int) $comment->comment_approved ) {
			return;
		}

		$this->purge_cache_by_comment( $comment_id );
	}

	/**
	 * Process cache tags related to a comment.
	 *
	 * @param int  $comment_id Comment ID.
	 * @param bool $invalidate Whether to invalidate instead of purge.
	 * @return void
	 */
	private function cache_by_comment( $comment_id, bool $invalidate ): void {
		$comment = \get_comment( $comment_id );

		if ( ! ( $comment instanceof \WP_Comment ) ) {
			return;
		}

		if ( $invalidate && 1 !== (int) $comment->comment_approved ) {
			return;
		}

		$tags = CacheTags::get_comment_related_tags( $comment );

		if ( $invalidate ) {
			$this->invalidate_cache_by_post( $comment->comment_post_ID );
			$this->invalidate_by_tags( $tags );

			return;
		}

		$this->purge_cache_by_post( $comment->comment_post_ID );
		$this->purge_by_tags( $tags );
	}

	/**
	 * Invalidate cache by term.
	 *
	 * @param int         $term_id  Term ID.
	 * @param int|null    $tt_id    Term taxonomy ID.
	 * @param string|null $taxonomy Taxonomy slug.
	 * @return void
	 */
	private function invalidate_cache_by_term( $term_id, $tt_id = null, $taxonomy = null ): void {
		$this->cache_by_term( $term_id, $tt_id, $taxonomy, true );
	}

	/**
	 * Purge cache by term.
	 *
	 * @param int         $term_id  Term ID.
	 * @param int|null    $tt_id    Term taxonomy ID.
	 * @param string|null $taxonomy Taxonomy slug.
	 * @return void
	 */
	private function purge_cache_by_term( $term_id, $tt_id = null, $taxonomy = null ): void {
		$this->cache_by_term( $term_id, $tt_id, $taxonomy, false );
	}

	/**
	 * Process cache tags related to a term.
	 *
	 * @param int         $term_id    Term ID.
	 * @param int|null    $tt_id      Term taxonomy ID.
	 * @param string|null $taxonomy   Taxonomy slug.
	 * @param bool        $invalidate Whether to invalidate instead of purge.
	 * @return void
	 */
	private function cache_by_term( $term_id, $tt_id, $taxonomy, bool $invalidate ): void {
		$term = null;

		if ( $term_id && $taxonomy ) {
			$term = \get_term( $term_id, $taxonomy );
		}

		if ( ! ( $term instanceof \WP_Term ) ) {
			return;
		}

		$tags = CacheTags::get_term_related_tags( $term );

		/**
		 * 404.
		 */
		$tags[] = '404';

		if ( $invalidate ) {
			$this->invalidate_by_tags( $tags );

			return;
		}

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
	private function purge_cache_by_deleted_term( $term_id, $tt_id, $taxonomy, $deleted_term ): void {
		if ( ! ( $deleted_term instanceof \WP_Term ) ) {
			return;
		}

		$tags = CacheTags::get_term_related_tags( $deleted_term );

		$this->purge_by_tags( $tags );
	}

	/**
	 * Invalidate cache when object terms are assigned or changed.
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
		$post = \get_post( $object_id );

		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		/**
		 * The `set_object_terms` action also fires for taxonomies on other
		 * object types, such as users or links. Only handle posts of a post
		 * type the taxonomy is registered for.
		 */
		if ( ! \is_object_in_taxonomy( $post->post_type, $taxonomy ) ) {
			return;
		}

		if ( ! \is_post_status_viewable( $post->post_status ) ) {
			return;
		}

		$tags = [];

		foreach ( $terms as $term_id ) {
			$term = \get_term( $term_id, $taxonomy );

			if ( ! ( $term instanceof \WP_Term ) ) {
				continue;
			}

			$tags = \array_merge( $tags, CacheTags::get_term_related_tags( $term ) );
		}

		foreach ( $old_tt_ids as $tt_id ) {
			$term = \get_term_by( 'term_taxonomy_id', $tt_id, $taxonomy );

			if ( ! ( $term instanceof \WP_Term ) ) {
				continue;
			}

			$tags = \array_merge( $tags, CacheTags::get_term_related_tags( $term ) );
		}

		$tags = array_unique( $tags );

		$this->invalidate_by_tags( $tags );
	}

	/**
	 * Invalidate cache by user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	private function invalidate_cache_by_user( int $user_id ): void {
		$user = get_user_by( 'ID', $user_id );

		if ( ! ( $user instanceof \WP_User ) ) {
			return;
		}

		$tags = CacheTags::get_user_related_tags( $user );

		$this->invalidate_by_tags( $tags );
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
		$tags = CacheTags::get_user_related_tags( $user );

		$this->purge_by_tags( $tags );
	}

	/**
	 * Purge everything.
	 *
	 * @return void
	 */
	private function purge_everything(): void {
		$this->purge_everything = true;
	}

	/**
	 * Schedule invalidation action for tags.
	 *
	 * @param string[] $tags Tags to invalidate.
	 * @return void
	 */
	private function invalidate_by_tags( $tags ): void {
		if ( 0 === \count( $tags ) || true === $this->purge_everything ) {
			return;
		}

		$tags = \array_values( \array_diff( $tags, $this->purge_tags ) );

		if ( 0 === \count( $tags ) ) {
			return;
		}

		$this->invalidate_tags = \array_values( \array_unique( \array_merge( $this->invalidate_tags, $tags ) ) );
	}

	/**
	 * Schedule purge cache action for tags.
	 *
	 * @param string[] $tags Tags to purge.
	 * @return void
	 */
	private function purge_by_tags( $tags ) {
		if ( 0 === \count( $tags ) || true === $this->purge_everything ) {
			return;
		}

		$this->invalidate_tags = \array_values( \array_diff( $this->invalidate_tags, $tags ) );

		$updated_tags = \array_merge( $this->purge_tags, $tags );

		$updated_tags = \array_unique( $updated_tags );

		// Make sure keys are sequentially numbered for JSON encoding as array.
		$updated_tags = \array_values( $updated_tags );

		$this->purge_tags = $updated_tags;
	}

	/**
	 * Schedule cache actions on shutdown.
	 *
	 * @return void
	 */
	private function shutdown(): void {
		/**
		 * Without credentials or zone ID the scheduled actions would always
		 * fail and fill the Action Scheduler tables, so don't schedule them.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-cloudflare/issues/55
		 */
		if ( ! CloudflareClient::is_configured() ) {
			return;
		}

		// Purge everything.
		if ( true === $this->purge_everything ) {
			// Remove all scheduled tag actions as purge everything supersedes them.
			\as_unschedule_all_actions(
				'pronamic_cloudflare_purge_cache_tags',
				null,
				'pronamic-cloudflare'
			);

			\as_unschedule_all_actions(
				'pronamic_cloudflare_invalidate_cache_tags',
				null,
				'pronamic-cloudflare'
			);

			\as_enqueue_async_action(
				'pronamic_cloudflare_purge_everything',
				[],
				'pronamic-cloudflare',
				true
			);

			return;
		}

		$this->enqueue_tags_actions( 'pronamic_cloudflare_invalidate_cache_tags', $this->invalidate_tags );
		$this->enqueue_tags_actions( 'pronamic_cloudflare_purge_cache_tags', $this->purge_tags );
	}

	/**
	 * Enqueue async actions for tags.
	 *
	 * @param string   $hook Action hook.
	 * @param string[] $tags Tags.
	 * @return void
	 */
	private function enqueue_tags_actions( $hook, $tags ): void {
		/**
		 * Put the term tags last, so the most important tags (post, home,
		 * front page, feed, etc.) end up in the first action.
		 */
		\usort(
			$tags,
			fn( $a, $b ) => \str_starts_with( $a, 'term-' ) <=> \str_starts_with( $b, 'term-' )
		);

		/**
		 * Cloudflare allows a maximum of 100 tags per purge or invalidate
		 * request, so we schedule a separate action for each chunk of 100 tags.
		 *
		 * @link https://developers.cloudflare.com/cache/how-to/purge-cache/purge-by-tags/
		 * @link https://github.com/pronamic/wp-pronamic-cloudflare/issues/18
		 */
		foreach ( \array_chunk( $tags, 100 ) as $chunk ) {
			$args = [
				$chunk,
			];

			\as_enqueue_async_action(
				$hook,
				$args,
				'pronamic-cloudflare',
				true
			);
		}
	}
}
