<?php
/**
 * Admin Automatic Platform Optimization (APO) explanation
 *
 * @link https://developers.cloudflare.com/automatic-platform-optimization/
 * @package Pronamic\WordPressCloudflare
 */

$allowed_html = [
	'a'    => [
		'href' => [],
	],
	'code' => [],
];

$links = [
	'https://developers.cloudflare.com/automatic-platform-optimization/'                                 => __( 'Automatic Platform Optimization overview', 'pronamic-cloudflare' ),
	'https://developers.cloudflare.com/automatic-platform-optimization/about/'                           => __( 'About APO: when a request is cached or bypassed', 'pronamic-cloudflare' ),
	'https://developers.cloudflare.com/automatic-platform-optimization/get-started/verify-apo-works/'    => __( 'Verify APO works', 'pronamic-cloudflare' ),
	'https://developers.cloudflare.com/automatic-platform-optimization/reference/query-parameters/'      => __( 'Query parameters and cached responses', 'pronamic-cloudflare' ),
	'https://developers.cloudflare.com/automatic-platform-optimization/reference/page-rule-integration/' => __( 'Page Rule integration with APO', 'pronamic-cloudflare' ),
	'https://developers.cloudflare.com/automatic-platform-optimization/reference/cache-device-type/'     => __( 'Cache by device type', 'pronamic-cloudflare' ),
	'https://developers.cloudflare.com/automatic-platform-optimization/troubleshooting/'                 => __( 'Troubleshooting', 'pronamic-cloudflare' ),
	'https://developers.cloudflare.com/automatic-platform-optimization/troubleshooting/faq/'             => __( 'FAQs', 'pronamic-cloudflare' ),
];

?>
<p>
	<?php

	echo wp_kses(
		sprintf(
			/* translators: %s: Cloudflare dashboard URL. */
			__( 'Automatic Platform Optimization (APO) is an optional Cloudflare feature that caches the HTML of WordPress pages on the Cloudflare edge network using Cloudflare Workers. APO is enabled per zone in the Cloudflare dashboard under <a href="%s">Speed → Optimization → Content</a>, it cannot be enabled or configured from this plugin.', 'pronamic-cloudflare' ),
			esc_url( 'https://dash.cloudflare.com/?to=/:account/:zone/speed/optimization/content' )
		),
		$allowed_html
	);

	?>
</p>

<p>
	<?php

	echo wp_kses(
		sprintf(
			/* translators: %s: cf-edge-cache header name. */
			__( 'This plugin always sends the %s response header. Cloudflare uses this header to detect the WordPress integration ("WordPress plugin successfully detected" in the Cloudflare dashboard) and to decide whether the HTTP response may be cached by APO. The header has no effect when APO is not enabled for the zone.', 'pronamic-cloudflare' ),
			'<code>cf-edge-cache</code>'
		),
		$allowed_html
	);

	?>
</p>

<table class="widefat striped" style="max-width: 50em;">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'Request', 'pronamic-cloudflare' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Header', 'pronamic-cloudflare' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td><?php esc_html_e( 'Anonymous visitor', 'pronamic-cloudflare' ); ?></td>
			<?php // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Header value expected by Cloudflare. ?>
			<td><code>cf-edge-cache: cache,platform=wordpress</code></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Logged-in user', 'pronamic-cloudflare' ); ?></td>
			<td><code>cf-edge-cache: no-cache</code></td>
		</tr>
	</tbody>
</table>

<p>
	<?php esc_html_e( 'APO applies its own caching rules, which differ from the regular Cloudflare cache:', 'pronamic-cloudflare' ); ?>
</p>

<ul class="ul-disc">
	<li>
		<?php

		echo wp_kses(
			sprintf(
				/* translators: 1: GET, 2: HEAD, 3: 200, 4: text/html. */
				__( 'Only %1$s and %2$s requests for HTML are cached, and only when the origin responds with status %3$s and content type %4$s.', 'pronamic-cloudflare' ),
				'<code>GET</code>',
				'<code>HEAD</code>',
				'<code>200</code>',
				'<code>text/html</code>'
			),
			$allowed_html
		);

		?>
	</li>
	<li>
		<?php

		echo wp_kses(
			sprintf(
				/* translators: 1: utm_source, 2: fbclid, 3: gclid. */
				__( 'URLs with query parameters are not cached and are always fetched from the origin. The only exception is a fixed list of known marketing parameters, such as %1$s, %2$s and %3$s.', 'pronamic-cloudflare' ),
				'<code>utm_source</code>',
				'<code>fbclid</code>',
				'<code>gclid</code>'
			),
			$allowed_html
		);

		?>
	</li>
	<li>
		<?php

		echo wp_kses(
			sprintf(
				/* translators: 1: wp-, 2: woocommerce_. */
				__( 'Requests with cookies that start with certain prefixes, such as %1$s and %2$s, bypass the cache. Logged-in users and visitors with a shopping cart therefore always receive a fresh response from the origin.', 'pronamic-cloudflare' ),
				'<code>wp-</code>',
				'<code>woocommerce_</code>'
			),
			$allowed_html
		);

		?>
	</li>
	<li>
		<?php

		echo wp_kses(
			sprintf(
				/* translators: %s: Cache-Control. */
				__( 'According to Cloudflare, APO ignores the origin %s header for edge caching and keeps pages in the edge cache for up to 30 days. The edge lifetimes from the "Cache headers" settings above therefore do not determine how long APO caches a page; the headers are still passed on to the browser.', 'pronamic-cloudflare' ),
				'<code>Cache-Control</code>'
			),
			$allowed_html
		);

		?>
	</li>
	<li>
		<?php

		echo wp_kses(
			sprintf(
				/* translators: %s: Cache Level: Bypass. */
				__( 'A Page Rule with %s that matches the request also disables APO for that request.', 'pronamic-cloudflare' ),
				'<code>Cache Level: Bypass</code>'
			),
			$allowed_html
		);

		?>
	</li>
</ul>

<p>
	<?php

	echo wp_kses(
		sprintf(
			/* translators: 1: cf-cache-status, 2: cf-apo-via, 3: cf-edge-cache. */
			__( 'To check whether a page is served by APO, inspect the response headers %1$s, %2$s and %3$s. The %2$s header also indicates why a request bypassed APO.', 'pronamic-cloudflare' ),
			'<code>cf-cache-status</code>',
			'<code>cf-apo-via</code>',
			'<code>cf-edge-cache</code>'
		),
		$allowed_html
	);

	?>
</p>

<p>
	<?php esc_html_e( 'Cloudflare documentation:', 'pronamic-cloudflare' ); ?>
</p>

<ul class="ul-disc">
	<?php foreach ( $links as $url => $label ) : ?>

		<li>
			<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
		</li>

	<?php endforeach; ?>
</ul>
