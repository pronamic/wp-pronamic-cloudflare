<?php
/**
 * Cloudflare client
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

/**
 * Cloudflare client class
 */
final class CloudflareClient {
	/**
	 * Get authentication headers for Cloudflare API.
	 *
	 * This method checks for API Token first (recommended), then falls back to
	 * Global API Key authentication. It uses WordPress options which can be
	 * overridden by constants via the `pre_option_*` filters in SettingsController.
	 *
	 * @return array|null Returns array of auth headers or null if no credentials are configured.
	 */
	public static function get_auth_headers() {
		$api_token = (string) \get_option( 'pronamic_cloudflare_api_token' );
		$api_email = (string) \get_option( 'pronamic_cloudflare_api_email' );
		$api_key   = (string) \get_option( 'pronamic_cloudflare_api_key' );

		if ( '' !== $api_token ) {
			return [
				'Authorization' => 'Bearer ' . $api_token,
			];
		}

		if ( '' !== $api_email && '' !== $api_key ) {
			return [
				'X-Auth-Email' => $api_email,
				'X-Auth-Key'   => $api_key,
			];
		}

		return null;
	}

	/**
	 * Check if the Cloudflare API credentials and zone ID are configured.
	 *
	 * @return bool
	 */
	public static function is_configured(): bool {
		if ( null === self::get_auth_headers() ) {
			return false;
		}

		if ( '' === (string) \get_option( 'pronamic_cloudflare_zone_id' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Send cache request.
	 *
	 * @link https://developers.cloudflare.com/api/resources/cache/methods/purge/
	 * @link https://developers.cloudflare.com/cache/guides/invalidate-cache/#invalidate-using-the-api
	 * @param array $args Arguments for the cache request.
	 * @param bool  $invalidate Whether to invalidate instead of purge.
	 * @return void
	 * @throws \Exception Throws exception if the cache action fails.
	 */
	private function send_request( $args, bool $invalidate = false ) {
		$zone_id = (string) \get_option( 'pronamic_cloudflare_zone_id' );

		$auth_headers = self::get_auth_headers();

		if ( null === $auth_headers ) {
			throw new \Exception( \esc_html( 'Cloudflare API credentials are not configured. Please set the API Token or Global API Key.' ) );
		}

		if ( '' === $zone_id ) {
			throw new \Exception( \esc_html( 'Cloudflare Zone ID is not configured.' ) );
		}

		$headers = [
			'Content-Type' => 'application/json',
			...$auth_headers,
		];

		$url = strtr(
			'https://api.cloudflare.com/client/v4/zones/{zone_id}/{action}',
			[
				'{zone_id}' => $zone_id,
				'{action}'  => $invalidate ? 'invalidate_cache' : 'purge_cache',
			]
		);

		$response = \wp_remote_post(
			$url,
			[
				'headers' => $headers,
				'body'    => \wp_json_encode( $args ),
			]
		);

		if ( \is_wp_error( $response ) ) {
			throw new \Exception( \esc_html( 'Cloudflare cache action went wrong: ' . $response->get_error_message() ) );
		}

		$response_code = (string) \wp_remote_retrieve_response_code( $response );

		if ( '200' !== $response_code ) {
			$response_body = \wp_remote_retrieve_body( $response );

			throw new \Exception(
				\sprintf(
					'Cloudflare cache action failed with code %s: %s',
					\esc_html( $response_code ),
					\esc_html( $response_body )
				)
			);
		}
	}

	/**
	 * Purge cache tags.
	 *
	 * @param string[] $tags Tags to purge.
	 * @return void
	 */
	public function purge_cache_tags( array $tags ): void {
		/**
		 * Cloudflare allows a maximum of 100 tags per purge request. Actions
		 * scheduled by older versions of this plugin can contain more tags.
		 *
		 * @link https://developers.cloudflare.com/cache/how-to/purge-cache/purge-by-tags/
		 * @link https://github.com/pronamic/wp-pronamic-cloudflare/issues/18
		 */
		foreach ( \array_chunk( $tags, 100 ) as $chunk ) {
			$args = [
				'tags' => $chunk,
			];

			$this->send_request( $args );
		}
	}

	/**
	 * Invalidate cache tags.
	 *
	 * @link https://developers.cloudflare.com/cache/guides/invalidate-cache/#invalidate-using-the-api
	 * @param string[] $tags Tags to invalidate.
	 * @return void
	 */
	public function invalidate_cache_tags( array $tags ): void {
		/**
		 * Cloudflare allows a maximum of 100 tags per invalidate request.
		 * Actions scheduled by older versions of this plugin can contain
		 * more tags.
		 *
		 * @link https://developers.cloudflare.com/cache/how-to/purge-cache/purge-by-tags/
		 * @link https://github.com/pronamic/wp-pronamic-cloudflare/issues/57
		 */
		foreach ( \array_chunk( $tags, 100 ) as $chunk ) {
			$args = [
				'tags' => $chunk,
			];

			$this->send_request( $args, true );
		}
	}

	/**
	 * Purge everything.
	 *
	 * @return void
	 */
	public function purge_everything(): void {
		$args = [
			'purge_everything' => true,
		];

		$this->send_request( $args );
	}
}
