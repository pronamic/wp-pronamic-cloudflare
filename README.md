# Pronamic Cloudflare

The Pronamic Cloudflare plugin adds a number of features, such as WP-CLI commands, to the Cloudflare plugin.

## Table of contents

- [Configuration](#configuration)
- [Cache headers](#cache-headers)
- [Automatic Platform Optimization (APO)](#automatic-platform-optimization-apo)
- [Cache invalidation](#cache-invalidation)
- [WP-CLI](#wp-cli)
- [Links](#links)

## Configuration

This plugin offers advanced configuration options via `wp-config.php` constants for integrating with Cloudflare. These settings allow you to securely manage your Cloudflare credentials and zone ID directly from the WordPress configuration file.

To configure these options, add the following constants to your `wp-config.php` file:

### `CLOUDFLARE_EMAIL`

Your Cloudflare account email address used to authenticate API requests.

```php
define( 'CLOUDFLARE_EMAIL', 'your-email@example.com' );
```

### `CLOUDFLARE_API_KEY`

The Cloudflare API key associated with your account. You can find this in your Cloudflare dashboard under **API Tokens**.

```php
define( 'CLOUDFLARE_API_KEY', 'your-cloudflare-api-key' );
```

### `PRONAMIC_CLOUDFLARE_ZONE_ID`

The Cloudflare Zone ID for the domain you are managing. You can locate this in your Cloudflare dashboard under the **Overview** tab.

```php
define( 'PRONAMIC_CLOUDFLARE_ZONE_ID', 'your-cloudflare-zone-id' );
```

### Security Note

It's recommended to keep your API key and zone ID confidential by restricting access to the `wp-config.php` file.

## Cache headers

Cache headers are opt-in and disabled by default under **Settings > Pronamic Cloudflare**. The plugin provides four global settings, each stored as its own option, that apply to all eligible public responses.

The initial values are:

| Setting | Option | Default |
| --- | --- | ---: |
| Browser TTL | `pronamic_cloudflare_cache_browser_ttl` | 5 minutes |
| Cloudflare edge TTL | `pronamic_cloudflare_cache_edge_ttl` | 7 days |
| Stale while revalidate | `pronamic_cloudflare_cache_stale_while_revalidate` | 24 hours |
| Stale if error | `pronamic_cloudflare_cache_stale_if_error` | 24 hours |

Browser caches cannot be purged, so keep the browser TTL low. The edge cache is purged when content changes; if public pages contain nonces (for example in forms), keep the edge TTL below 12 hours.

When enabled, the plugin adds `Cache-Control` for browsers and `Cloudflare-CDN-Cache-Control` for Cloudflare. Existing cache policy headers are left unchanged. Headers are limited to anonymous public `GET` responses with status 200. Search results, 404s, admin, previews, redirects, embeds, trackbacks, and authenticated requests are excluded; feeds remain eligible.

The plugin does not create or manage Cloudflare Cache Rules. Cloudflare does not cache HTML by default, so it is up to you to configure Cache Rules in the Cloudflare dashboard that make the desired requests eligible for cache and respect the origin cache headers.

## Automatic Platform Optimization (APO)

The plugin sends the `cf-edge-cache` response header that [Cloudflare Automatic Platform Optimization](https://developers.cloudflare.com/automatic-platform-optimization/) uses to detect a WordPress integration and to decide whether a response may be cached at the edge, just like the official Cloudflare plugin:

| Request | Header |
| --- | --- |
| Anonymous visitor | `cf-edge-cache: cache,platform=wordpress` |
| Logged-in user | `cf-edge-cache: no-cache` |

The header has no effect when APO is not enabled for the zone.

## Cache invalidation

The plugin invalidates cache tags when content changes but remains public, allowing Cloudflare to revalidate its cached response. If the origin supports `ETag` or `Last-Modified`, Cloudflare can reuse the cached response after a `304 Not Modified` response. When stale-serving directives are configured at Cloudflare, invalidation also leaves the cached response available for `stale-while-revalidate` or `stale-if-error`. Deleted, trashed, or unpublished content is hard-purged so it is no longer served from Cloudflare's cache.

## WP-CLI

### What is WP-CLI?

For those who have never heard before WP-CLI, here's a brief description extracted from the [official website](https://wp-cli.org/).

> **WP-CLI** is a set of command-line tools for managing WordPress installations. You can update plugins, set up multisite installs and much more, without using a web browser.

### Commands

```bash
$ wp pronamic cloudflare zones
```

```bash
$ wp pronamic cloudflare purge $( wp pronamic cloudflare zones )
```

## Links

- https://api.cloudflare.com/#zone-list-zones
- https://api.cloudflare.com/#zone-purge-all-files
