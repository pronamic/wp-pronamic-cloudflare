<?php
/**
 * Plugin
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

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
			new AdminController(),
			new CacheHeadersController(),
			new CliController(),
			new InvalidationController( new CloudflareClient() ),
			new LoopbackController(),
			new SettingsController(),
		];
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup() {
		foreach ( $this->controllers as $controller ) {
			$controller->setup();
		}
	}
}
