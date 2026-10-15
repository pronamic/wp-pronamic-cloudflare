# Pronamic Cloudflare

The Pronamic Cloudflare plugin integrates WordPress with Cloudflare. It adds cache headers and cache tags to public responses, invalidates or purges those tags when content changes, supports Automatic Platform Optimization (APO) and offers WP-CLI commands. It works on its own and does not require the official Cloudflare plugin.

## Table of contents

- [Configuration](#configuration)
- [Cache headers](#cache-headers)
- [Automatic Platform Optimization (APO)](#automatic-platform-optimization-apo)
- [Cache invalidation](#cache-invalidation)
- [404 responses](#404-responses)
- [WP-CLI](#wp-cli)
- [Links](#links)

## Configuration

The Cloudflare credentials and zone ID can be configured under **Settings > Pronamic Cloudflare** or via `wp-config.php` constants. A constant takes precedence over the value stored in the settings, which allows you to manage the credentials securely from the WordPress configuration file.

To configure these options, add the following constants to your `wp-config.php` file. Use either an API token (recommended) or the legacy Global API Key with the account email address. If an API token is configured, it is always used.

### `CLOUDFLARE_API_TOKEN` (recommended)

A Cloudflare API token, used with Bearer authentication. You can create one in your Cloudflare dashboard under **My Profile > API Tokens**. Grant it only the permissions the plugin needs, such as **Zone > Cache Purge > Purge** for the zone, and **Zone > Zone > Read** for the `wp pronamic cloudflare zones` command.

```php
define( 'CLOUDFLARE_API_TOKEN', 'your-cloudflare-api-token' );
```

### `CLOUDFLARE_EMAIL` (legacy)

Your Cloudflare account email address, used together with `CLOUDFLARE_API_KEY` to authenticate API requests.

```php
define( 'CLOUDFLARE_EMAIL', 'your-email@example.com' );
```

### `CLOUDFLARE_API_KEY` (legacy)

The Global API Key of your Cloudflare account. You can find it in your Cloudflare dashboard under **My Profile > API Tokens > API Keys > Global API Key**. The Global API Key grants full access to your account, so an API token is preferred.

```php
define( 'CLOUDFLARE_API_KEY', 'your-cloudflare-global-api-key' );
```

### `PRONAMIC_CLOUDFLARE_ZONE_ID`

The Cloudflare Zone ID for the domain you are managing. You can locate this in your Cloudflare dashboard under the **Overview** tab.

```php
define( 'PRONAMIC_CLOUDFLARE_ZONE_ID', 'your-cloudflare-zone-id' );
```

### Security Note

It's recommended to keep your API token, API key and zone ID confidential by restricting access to the `wp-config.php` file.

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

When enabled, the plugin adds `Cache-Control` for browsers and `Cloudflare-CDN-Cache-Control` for Cloudflare. Existing cache policy headers are left unchanged. Headers are limited to anonymous `GET` and `HEAD` responses with status 200 or 404, including search results, feeds and embeds. Admin requests, previews, redirects, other status codes and authenticated requests are excluded. WordPress itself sends `no-cache` headers on 404 responses; these are left unchanged, see [404 responses](#404-responses).

The plugin does not create or manage Cloudflare Cache Rules. Cloudflare does not cache HTML by default, so it is up to you to configure Cache Rules in the Cloudflare dashboard that make the desired requests eligible for cache and respect the origin cache headers.

## Automatic Platform Optimization (APO)

The plugin sends the `cf-edge-cache` response header that [Cloudflare Automatic Platform Optimization](https://developers.cloudflare.com/automatic-platform-optimization/) uses to detect a WordPress integration and to decide whether a response may be cached at the edge, just like the official Cloudflare plugin:

| Request | Header |
| --- | --- |
| Anonymous visitor | `cf-edge-cache: cache,platform=wordpress` |
| Logged-in user | `cf-edge-cache: no-cache` |

The header has no effect when APO is not enabled for the zone.

APO is enabled per zone in the Cloudflare dashboard (**Speed > Optimization > Content**) and applies its own caching rules, which differ from the regular Cloudflare cache. These are also explained under **Settings > Pronamic Cloudflare**:

- Only `GET` and `HEAD` requests for HTML are cached, and only when the origin responds with status `200` and content type `text/html`.
- URLs with query parameters are not cached, except for a [fixed list of known marketing parameters](https://developers.cloudflare.com/automatic-platform-optimization/reference/query-parameters/) such as `utm_source`, `fbclid` and `gclid`.
- Requests with cookies that start with certain prefixes, such as `wp-` and `woocommerce_`, bypass the cache.
- According to Cloudflare, APO [ignores the origin `Cache-Control` header for edge caching](https://developers.cloudflare.com/automatic-platform-optimization/troubleshooting/faq/) and keeps pages in the edge cache for up to 30 days, so the edge lifetimes from the cache headers settings do not determine how long APO caches a page.
- A Page Rule with `Cache Level: Bypass` that matches the request [also disables APO](https://developers.cloudflare.com/automatic-platform-optimization/reference/page-rule-integration/) for that request.

To [verify that a page is served by APO](https://developers.cloudflare.com/automatic-platform-optimization/get-started/verify-apo-works/), inspect the `cf-cache-status`, `cf-apo-via` and `cf-edge-cache` response headers.

## Cache invalidation

The plugin invalidates cache tags when content changes but remains public, allowing Cloudflare to revalidate its cached response. If the origin supports `ETag` or `Last-Modified`, Cloudflare can reuse the cached response after a `304 Not Modified` response. When stale-serving directives are configured at Cloudflare, invalidation also leaves the cached response available for `stale-while-revalidate` or `stale-if-error`. Deleted, trashed, or unpublished content is hard-purged so it is no longer served from Cloudflare's cache.

## 404 responses

Cloudflare does not cache 404 responses by default: APO only caches `200` responses, and WordPress sends `no-cache, no-store, private` cache directives on every 404. Each request for a non-existent URL therefore reaches the origin server, where WordPress loads the full stack before it responds. Attackers abuse this in (D)DoS attacks by requesting random, non-existent URLs over and over.

A Cloudflare Cache Response Rule that matches `http.response.code eq 404` can remove these directives for Cloudflare only and add `s-maxage`, so Cloudflare serves repeated 404 requests from its edge cache. The plugin tags 404 responses with the `404` cache tag and invalidates that tag when posts or terms change, so a page published later on a cached 404 URL is not hidden.

See [Cache 404 responses at Cloudflare](documentation/cache-404-responses.md) for the rule, how to verify it, and its limitations.

> [!TIP]
> To inspect the headers the origin server sends, bypass Cloudflare with the `curl` option `--resolve`. It connects to the IP address of the origin server while keeping the host name, TLS (SNI) and `Host` header:
>
> ```bash
> curl -I --resolve www.example.com:443:192.0.2.10 https://www.example.com/random-url-123/
> ```
>
> Replace `192.0.2.10` with the IP address of your origin server.

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
