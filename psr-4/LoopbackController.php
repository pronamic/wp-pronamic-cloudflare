<?php
/**
 * Loopback controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

/**
 * Loopback controller class
 *
 * Sends HTTP requests from WordPress to its own hostname (loopback requests),
 * such as WP-Cron spawns, background processes and cache preheating, directly
 * to the origin server instead of through Cloudflare.
 *
 * @link https://github.com/pronamic/wp-pronamic-cloudflare/issues/71
 */
final class LoopbackController {
	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup() {
		\add_action( 'http_api_curl', $this->http_api_curl( ... ), 10, 3 );
	}

	/**
	 * Get the origin IP address.
	 *
	 * @return string
	 */
	public static function get_origin_ip(): string {
		return self::sanitize_ip( \get_option( 'pronamic_cloudflare_origin_ip', '' ) );
	}

	/**
	 * Sanitize IP address.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function sanitize_ip( $value ): string {
		$value = \trim( (string) $value );

		if ( false === \filter_var( $value, \FILTER_VALIDATE_IP ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * HTTP API cURL.
	 *
	 * Resolves the hostname of the site to the origin IP address with
	 * `CURLOPT_RESOLVE`. The URL, `Host` header and TLS SNI stay the same, so
	 * the origin web server serves the right site and certificate.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/http_api_curl/
	 * @link https://curl.se/libcurl/c/CURLOPT_RESOLVE.html
	 * @param \CurlHandle $handle      The cURL handle.
	 * @param array       $parsed_args The HTTP request arguments.
	 * @param string      $url         The request URL.
	 * @return void
	 */
	private function http_api_curl( $handle, $parsed_args, $url ) {
		$origin_ip = self::get_origin_ip();

		if ( '' === $origin_ip ) {
			return;
		}

		/**
		 * The effective URL of the handle also covers redirects, the `$url`
		 * parameter is always the URL of the initial request.
		 */
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo -- The cURL handle is provided by the `http_api_curl` hook.
		$effective_url = \curl_getinfo( $handle, \CURLINFO_EFFECTIVE_URL );

		if ( \is_string( $effective_url ) && '' !== $effective_url ) {
			$url = $effective_url;
		}

		$parts = \wp_parse_url( $url );

		if ( ! \is_array( $parts ) || ! isset( $parts['scheme'], $parts['host'] ) ) {
			return;
		}

		$scheme = \strtolower( $parts['scheme'] );
		$host   = \strtolower( $parts['host'] );

		if ( ! \in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return;
		}

		$site_hosts = [
			\strtolower( (string) \wp_parse_url( \home_url(), \PHP_URL_HOST ) ),
			\strtolower( (string) \wp_parse_url( \site_url(), \PHP_URL_HOST ) ),
		];

		if ( ! \in_array( $host, $site_hosts, true ) ) {
			return;
		}

		$port = $parts['port'] ?? ( ( 'https' === $scheme ) ? 443 : 80 );

		$address = \str_contains( $origin_ip, ':' ) ? '[' . $origin_ip . ']' : $origin_ip;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- The cURL handle is provided by the `http_api_curl` hook.
		\curl_setopt( $handle, \CURLOPT_RESOLVE, [ $host . ':' . $port . ':' . $address ] );
	}
}
