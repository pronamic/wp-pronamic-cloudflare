<?php
/**
 * Cache headers controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

use WP_Term;
use WP_User;

/**
 * Cache headers controller class
 *
 * Sends the Cloudflare edge cache, cache policy and `Cache-Tag` headers of
 * front-end responses.
 */
final class CacheHeadersController {
	/**
	 * Related post IDs.
	 *
	 * @var int[]
	 */
	private $related_post_ids = [];

	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup() {
		\add_action( 'template_redirect', $this->setup_cache_tag( ... ), 0 );

		\add_action( 'init', $this->send_edge_cache_header( ... ) );
	}

	/**
	 * Setup cache tag header and output buffering.
	 */
	private function setup_cache_tag(): void {
		\add_action( 'shutdown', $this->send_cache_headers( ... ), 0 );

		/*
		 * We use output buffering and hook into template filters to
		 * include cache tags of posts being used on the page.
		 */
		\ob_start();

		\add_filter( 'the_title', $this->add_related_post_id( ... ), 10, 2 );
		\add_filter( 'the_content', $this->add_related_post_id( ... ), 10, 2 );
		\add_filter( 'get_the_excerpt', $this->add_related_post_id( ... ), 10, 2 );
		\add_filter( 'the_permalink', $this->add_related_post_id( ... ), 10, 2 );

		/*
		 * WordPress flushes all output buffer levels automatically on shutdown.
		 *
		 * @link https://github.com/WordPress/WordPress/blob/6.8.2/wp-includes/default-filters.php#L430
		 * @link https://github.com/WordPress/WordPress/blob/6.8.2/wp-includes/functions.php#L5461-L5473
		 */
	}

	/**
	 * Send the edge cache header for Cloudflare Automatic Platform Optimization (APO).
	 *
	 * Cloudflare APO uses this header to detect that a WordPress plugin is
	 * active and to decide whether a response may be cached at the edge. The
	 * header is the same as in the official Cloudflare plugin.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-cloudflare/issues/29
	 * @link https://github.com/cloudflare/Cloudflare-WordPress/blob/9b2b6549353ba49b886e73514bac22ca076410e0/src/WordPress/Hooks.php#L447-L462
	 * @link https://developers.cloudflare.com/automatic-platform-optimization/
	 * @return void
	 */
	private function send_edge_cache_header(): void {
		// It could be too late to send headers, return early without triggering a warning.
		if ( \headers_sent() ) {
			return;
		}

		if ( \is_user_logged_in() ) {
			\header( 'cf-edge-cache: no-cache' );

			return;
		}

		// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Header value expected by Cloudflare.
		\header( 'cf-edge-cache: cache,platform=wordpress' );
	}

	/**
	 * Send cache HTTP headers.
	 *
	 * @return void
	 */
	private function send_cache_headers(): void {
		$this->send_cache_policy_headers();

		$tags = $this->get_current_cache_tags();

		if ( 0 === \count( $tags ) ) {
			return;
		}

		\header( 'Cache-Tag: ' . implode( ',', $tags ), false );
	}

	/**
	 * Send cache policy headers for eligible public responses.
	 *
	 * @return void
	 */
	private function send_cache_policy_headers(): void {
		if ( ! SettingsController::get_cache_headers_enabled() ) {
			return;
		}

		if ( ! $this->is_cache_header_eligible() ) {
			return;
		}

		if ( $this->has_explicit_cache_policy_header() ) {
			return;
		}

		$browser_ttl            = \absint( \get_option( 'pronamic_cloudflare_cache_browser_ttl' ) );
		$edge_ttl               = \absint( \get_option( 'pronamic_cloudflare_cache_edge_ttl' ) );
		$stale_while_revalidate = \absint( \get_option( 'pronamic_cloudflare_cache_stale_while_revalidate' ) );
		$stale_if_error         = \absint( \get_option( 'pronamic_cloudflare_cache_stale_if_error' ) );

		\header( 'Cache-Control: public, max-age=' . $browser_ttl . ', must-revalidate', true );
		\header( 'Cloudflare-CDN-Cache-Control: public, max-age=' . $edge_ttl . ', stale-while-revalidate=' . $stale_while_revalidate . ', stale-if-error=' . $stale_if_error, true );
	}

	/**
	 * Check whether the current response may receive cache headers.
	 *
	 * @return bool
	 */
	private function is_cache_header_eligible(): bool {
		if ( \headers_sent() ) {
			return false;
		}

		$response_code = \http_response_code();

		if ( false !== $response_code && ! \in_array( $response_code, [ 200, 404 ], true ) ) {
			return false;
		}

		if ( \is_admin() ) {
			return false;
		}

		if ( \is_user_logged_in() ) {
			return false;
		}

		if ( \is_preview() ) {
			return false;
		}

		/*
		 * WordPress pre-fills the comment form with the name, email address
		 * and URL from the comment author cookies, so these responses contain
		 * personal data and must not end up in a shared cache.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-cloudflare/issues/65
		 * @link https://developer.wordpress.org/reference/functions/wp_get_current_commenter/
		 */
		if ( [] !== \array_filter( \wp_get_current_commenter() ) ) {
			return false;
		}

		/*
		 * Plugins such as WooCommerce define `DONOTCACHEPAGE` to signal that
		 * the current page must not be cached by page caches.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-cloudflare/issues/63
		 */
		if ( \defined( 'DONOTCACHEPAGE' ) && \constant( 'DONOTCACHEPAGE' ) ) {
			return false;
		}

		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? \strtoupper( \sanitize_text_field( \wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';

		return \in_array( $request_method, [ 'GET', 'HEAD' ], true );
	}

	/**
	 * Check whether another component already set an explicit cache policy.
	 *
	 * @return bool
	 */
	private function has_explicit_cache_policy_header(): bool {
		$policy_headers = [
			'cache-control',
			'cdn-cache-control',
			'cloudflare-cdn-cache-control',
			'expires',
			'pragma',
			'surrogate-control',
			'location',
		];

		foreach ( \headers_list() as $header ) {
			$parts = \explode( ':', $header, 2 );
			$name  = \strtolower( \trim( $parts[0] ) );

			if ( \in_array( $name, $policy_headers, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get current cache tags.
	 *
	 * @return array
	 */
	private function get_current_cache_tags(): array {
		$tags = [];

		if ( is_singular() ) {
			global $post;

			$tags = \array_merge( $tags, CacheTags::get_post_related_tags( $post ) );
		}

		if ( is_post_type_archive() ) {
			foreach ( (array) get_query_var( 'post_type' ) as $post_type ) {
				$tags[] = 'archive-' . $post_type;
			}
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

		foreach ( $this->related_post_ids as $related_post_id ) {
			$related_post = \get_post( $related_post_id );

			$related_tags = CacheTags::get_post_related_tags( $related_post );

			$tags = \array_merge( $tags, $related_tags );
		}

		return array_unique( $tags );
	}

	/**
	 * Add related post ID.
	 *
	 * @param string            $content Filtered content.
	 * @param \WP_Post|int|null $post    Post.
	 * @return string
	 */
	private function add_related_post_id( $content, $post = null ) {
		$post = \get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return $content;
		}

		if ( \in_array( $post->ID, $this->related_post_ids, true ) ) {
			return $content;
		}

		$this->related_post_ids[] = $post->ID;

		return $content;
	}
}
