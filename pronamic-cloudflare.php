<?php
/**
 * Pronamic Cloudflare
 *
 * @package   Pronamic\WordPress\CloudflarePlugin
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Pronamic Cloudflare
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-cloudflare/
 * Description: Precise Cloudflare caching with cache tags on every page, background invalidation of exactly the pages that show changed content and WP-CLI commands.
 *
 * Version: 1.6.0
 * Requires at least: 6.8
 * Requires PHP: 8.1
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-cloudflare
 * Domain Path: /languages/
 *
 * License: GPL-2.0-or-later
 *
 * GitHub URI: https://github.com/pronamic/wp-pronamic-cloudflare
 */

namespace Pronamic\WordPressCloudflare;

/**
 * Autoload.
 */
require_once __DIR__ . '/vendor/autoload_packages.php';

require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

/**
 * Bootstrap.
 */
$pronamic_cloudflare_plugin = new Plugin();

$pronamic_cloudflare_plugin->setup();
