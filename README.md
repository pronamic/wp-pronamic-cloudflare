# Pronamic Cloudflare

The Pronamic Cloudflare plugin adds a number of features, such as WP-CLI commands, to the Cloudflare plugin.

## Table of contents

- [Configuration](#configuration)
- [Cache headers](#cache-headers)
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

Cache headers are opt-in and disabled by default under **Settings > Pronamic Cloudflare**. The plugin provides separate browser and Cloudflare edge TTLs, `stale-while-revalidate`, and `stale-if-error` values for the homepage and other public pages.

The initial values are:

| Profile | Browser TTL | Cloudflare edge TTL | Stale while revalidate | Stale if error |
| --- | ---: | ---: | ---: | ---: |
| Homepage | 30 seconds | 1 hour | 5 minutes | 24 hours |
| Other public pages | 2 minutes | 7 days | 5 minutes | 24 hours |

When enabled, the plugin adds `Cache-Control` for browsers and `Cloudflare-CDN-Cache-Control` for Cloudflare. Existing cache policy headers are left unchanged. Headers are limited to anonymous public `GET` responses with status 200. Search results, 404s, admin, previews, redirects, trackbacks, and authenticated requests are excluded; feeds and embeds remain eligible.

The settings page generates expressions for two Cloudflare Cache Rules. Add the cache expression to a rule configured to use the origin `Cache-Control` header for Edge TTL when present and bypass cache when absent. Add the bypass expression to a separate rule with Cache eligibility set to **Bypass cache**, above the cache rule. The bypass expression matches WordPress authentication and personalization cookies, protected WordPress routes, and search or preview requests. A logged-in cookie matches on every frontend path, so WordPress can render the page with its admin bar instead of serving a cached response. WooCommerce cookies and routes are included only when WooCommerce is active; additional cookies can be configured. The plugin does not create or manage Cloudflare rules.

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
