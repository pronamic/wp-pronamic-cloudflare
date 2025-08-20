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
 * Description: The Pronamic Cloudflare plugin manages cache purging and adds WP-CLI commands.
 * 
 * Version: 1.2.0
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
