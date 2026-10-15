<?php
/**
 * Admin controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

use ActionScheduler;
use ActionScheduler_Store;
use WP_Admin_Bar;

/**
 * Admin controller class
 */
final class AdminController {
	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup() {
		\add_action( 'admin_bar_menu', $this->admin_bar_menu( ... ), 500 );
	}

	/**
	 * Admin bar menu.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar.
	 */
	private function admin_bar_menu( WP_Admin_Bar $admin_bar ) {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$number_pending_actions = $this->count_actions();

		$url = \add_query_arg(
			[
				'page' => 'action-scheduler',
				's'    => 'pronamic_cloudflare',
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
					\__( 'Cache actions (%s)', 'pronamic-cloudflare' ),
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
				'group'  => 'pronamic-cloudflare',
				'status' => ActionScheduler_Store::STATUS_PENDING,
			],
			'count'
		);

		return $number;
	}
}
