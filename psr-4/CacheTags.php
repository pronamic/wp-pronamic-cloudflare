<?php
/**
 * Cache tags
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

use WP_Comment;
use WP_Post;
use WP_Term;
use WP_User;

/**
 * Cache tags class
 *
 * Provides the cache tags related to WordPress objects. These tags are used
 * both in the `Cache-Tag` response header and to purge or invalidate the
 * cache when these objects change.
 */
final class CacheTags {
	/**
	 * Get comment related tags.
	 *
	 * @param WP_Comment|null $comment Comment object.
	 * @return string[]
	 */
	public static function get_comment_related_tags( $comment ) {
		$tags = [];

		if ( ! ( $comment instanceof WP_Comment ) ) {
			return $tags;
		}

		if ( $comment->user_id ) {
			$user = \get_user_by( 'id', $comment->user_id );

			if ( false !== $user ) {
				$tags = \array_merge( $tags, self::get_user_related_tags( $user ) );
			}
		}

		if ( $comment->comment_post_ID ) {
			$post = \get_post( $comment->comment_post_ID );

			if ( false !== $post ) {
				$tags = \array_merge( $tags, self::get_post_related_tags( $post ) );
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
	public static function get_term_related_tags( WP_Term $term ) {
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
	public static function get_post_type_related_tags( $post_type ) {
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
	public static function get_user_related_tags( WP_User $user ) {
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
	public static function get_post_related_tags( WP_Post $post ) {
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
		 * Author.
		 */
		$user = \get_user_by( 'id', \get_post_field( 'post_author', $post ) );

		if ( false !== $user ) {
			$tags = \array_merge( $tags, self::get_user_related_tags( $user ) );
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
				$tags = \array_merge( $tags, self::get_term_related_tags( $term ) );
			}
		}

		/**
		 * Ok.
		 */
		return $tags;
	}
}
