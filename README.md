# Pronamic Cloudflare

The Pronamic Cloudflare plugin integrates WordPress with Cloudflare. It adds cache headers and cache tags to public responses, invalidates or purges those tags when content changes, supports Automatic Platform Optimization (APO) and offers WP-CLI commands. It works on its own and does not require the official Cloudflare plugin.

## Why Pronamic Cloudflare?

The official [Cloudflare plugin](https://wordpress.org/plugins/cloudflare/) purges a fixed list of URLs whenever a post changes and leaves HTML caching to Automatic Platform Optimization (APO), a paid add-on on the free plan. Pronamic Cloudflare takes a different approach: every page gets **cache tags** that describe the content on it, and when that content changes, only the pages that show it are refreshed. That approach has some clear advantages:

- 🎯 **It purges exactly the right pages.** Each response carries a `Cache-Tag` header with the post, terms, author, archives, dates, feeds and even the posts shown elsewhere on the page (related posts, query loops, widgets). When content changes, every page that shows it is refreshed, not only a guessed list of URLs.
- ⚡ **Saving content stays fast.** Cache actions are collected during the request, deduplicated and handed off to [Action Scheduler](https://actionscheduler.org/) in the background. The official plugin calls the Cloudflare API during the save request, in batches of 30 URLs, and repeats them for `http://` and mobile variants.
- 🔄 **It invalidates instead of purging.** Content that changes but stays public is invalidated, so Cloudflare revalidates it and can keep serving the stale copy with `stale-while-revalidate` and `stale-if-error`. Only deleted, trashed or unpublished content is hard-purged.
- 🌍 **It works with or without APO.** The plugin sends the `cf-edge-cache` header that APO uses, and can also send `Cache-Control` and `Cloudflare-CDN-Cache-Control` headers, so you can cache HTML on any Cloudflare plan with your own Cache Rules.
- 🧩 **It covers more changes.** Besides posts and comments, it reacts to term changes, term assignments, user profile changes, deleted users, block theme templates, template parts and global styles.
- 🛡️ **It helps against 404 floods.** 404 responses are tagged, so you can safely cache them at Cloudflare and protect your origin against attacks with random URLs.
- 🔐 **It is secure by default.** Configure a scoped API token via the `CLOUDFLARE_API_TOKEN` constant and keep secrets out of the database. Visitors with comment author cookies and pages that define `DONOTCACHEPAGE` (WooCommerce cart, checkout and my account) never get cache headers.
- 💻 **It has WP-CLI commands.** List zones, purge or invalidate a zone from the command line or a deployment script.
- 🪶 **It is lightweight.** A small PHP plugin with a classic settings page, without a bundled JavaScript admin application, and an admin bar indicator that shows pending cache actions.

### Comparison

| | Pronamic Cloudflare | Official Cloudflare plugin |
| --- | :---: | :---: |
| Purge strategy | Cache tags | List of URLs |
| Refresh pages that show a changed post elsewhere (related posts, query loops, widgets) | ✅ | ❌ |
| Invalidate (revalidate) instead of hard purge | ✅ | ❌ |
| API calls in the background (Action Scheduler) | ✅ | ❌ (during the save request) |
| Deduplicated cache actions for bulk changes | ✅ | ❌ |
| Purge all cached variants (device type, `http`/`https`, query strings) at once | ✅ | Partly |
| `cf-edge-cache` header for APO | ✅ | ✅ |
| `Cache-Control` and `Cloudflare-CDN-Cache-Control` headers (cache HTML without APO) | ✅ | ❌ |
| Configurable browser TTL, edge TTL, `stale-while-revalidate` and `stale-if-error` | ✅ | ❌ |
| Posts and comments | ✅ | ✅ |
| Terms (create, edit, delete, assign) | ✅ | ❌ |
| Users (profile update, delete) | ✅ | ❌ |
| Theme switch and Customizer | ✅ | ✅ |
| Block theme templates, template parts and global styles | ✅ | ❌ |
| Search results and 404 responses | ✅ | ❌ |
| No cache headers for visitors with comment author cookies and `DONOTCACHEPAGE` pages | ✅ | n/a |
| API token via `wp-config.php` constant | ✅ `CLOUDFLARE_API_TOKEN` | Via `CLOUDFLARE_API_KEY` |
| WP-CLI commands | ✅ | ❌ |
| Admin bar status of pending cache actions | ✅ | ❌ |
| License | GPL-2.0-or-later | BSD-3-Clause |

Do you want your pages cached at Cloudflare and refreshed accurately and efficiently when content changes? Then Pronamic Cloudflare is the better choice.

## Table of contents

- [Why Pronamic Cloudflare?](#why-pronamic-cloudflare)
- [Configuration](#configuration)
- [Cache headers](#cache-headers)
- [Automatic Platform Optimization (APO)](#automatic-platform-optimization-apo)
- [Cache invalidation](#cache-invalidation)
- [404 responses](#404-responses)
- [Loopback requests](#loopback-requests)
- [WP-CLI](#wp-cli)
- [Translations](#translations)
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

### `PRONAMIC_CLOUDFLARE_ORIGIN_IP`

The IP address of the origin server, as configured in the DNS records of the Cloudflare zone. When set, loopback requests are sent directly to the origin server, see [Loopback requests](#loopback-requests).

```php
define( 'PRONAMIC_CLOUDFLARE_ORIGIN_IP', '192.0.2.10' );
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

When enabled, the plugin adds `Cache-Control` for browsers and `Cloudflare-CDN-Cache-Control` for Cloudflare. Existing cache policy headers are left unchanged. Headers are limited to anonymous `GET` and `HEAD` responses with status 200 or 404, including search results, feeds and embeds. Admin requests, previews, redirects, other status codes, authenticated requests, requests from visitors with comment author cookies and pages that define the `DONOTCACHEPAGE` constant (for example the WooCommerce cart, checkout and my account pages) are excluded. WordPress itself sends `no-cache` headers on 404 responses; these are left unchanged, see [404 responses](#404-responses).

The plugin does not create or manage Cloudflare Cache Rules. Cloudflare does not cache HTML by default, so it is up to you to configure Cache Rules in the Cloudflare dashboard that make the desired requests eligible for cache and respect the origin cache headers.

> [!IMPORTANT]
> WordPress pre-fills the comment form with the name, email address and URL of visitors who posted a comment, based on their `comment_author_*` cookies. The plugin does not send cache headers for these visitors, but a Cache Rule that falls back to Cloudflare's default caching behaviour when the origin sends no cache headers may still cache these responses. Exclude these requests from your Cache Rules, for example with:
>
> ```
> not http.cookie contains "comment_author_"
> ```
>
> This also makes sure that commenters do not receive a cached page with an empty comment form. APO already bypasses the cache for requests with `comment_` cookies.

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

## Loopback requests

WordPress regularly sends HTTP requests to its own hostname: to spawn WP-Cron, to start background processes (`admin-ajax.php?action=…`), for the Site Health loopback checks and to preheat a cache, such as the "Preheat cache" feature of [SiteGround Optimizer](https://wordpress.org/plugins/sg-cachepress/). Behind Cloudflare, these requests also go through Cloudflare. They can be answered from the Cloudflare cache, so a cache preheater never reaches the origin, or be blocked by WAF rules, rate limiting or Bot Fight Mode.

Configure the origin IP address under **Settings > Pronamic Cloudflare** or with the [`PRONAMIC_CLOUDFLARE_ORIGIN_IP`](#pronamic_cloudflare_origin_ip) constant to send these requests directly to the origin server. The plugin then uses the [`http_api_curl`](https://developer.wordpress.org/reference/hooks/http_api_curl/) hook to set [`CURLOPT_RESOLVE`](https://curl.se/libcurl/c/CURLOPT_RESOLVE.html) for requests to the hostname of `home_url()` and `site_url()`, the same as the `curl --resolve` option above. The URL, `Host` header and TLS (SNI) stay the same. Requests to other hosts are not affected.

This makes Cloudflare rules that exclude "requests from the server itself" unnecessary. Such rules are risky: a `User-Agent` such as `WordPress/6.8; https://www.example.com` is easy to spoof, and the outgoing IP address of a hosting server is not always the IP address the site is served from.

> [!NOTE]
> WordPress verifies the certificate of the origin server for most requests. The origin server therefore needs a publicly trusted certificate, for example from Let's Encrypt. A [Cloudflare Origin CA certificate](https://developers.cloudflare.com/ssl/origin-configuration/origin-ca/) is only trusted by Cloudflare.

> [!NOTE]
> `499` responses in the access log for `wp-cron.php` and `admin-ajax.php?action=…` loopback requests are expected. WordPress and `WP_Async_Request` send these requests non-blocking with a very short timeout and close the connection right away, which nginx logs as `499 Client Closed Request`.

## WP-CLI

### What is WP-CLI?

For those who have never heard before WP-CLI, here's a brief description extracted from the [official website](https://wp-cli.org/).

> **WP-CLI** is a set of command-line tools for managing WordPress installations. You can update plugins, set up multisite installs and much more, without using a web browser.

### Commands

```bash
$ wp pronamic cloudflare zones
```

```bash
$ wp pronamic cloudflare purge $( wp pronamic cloudflare zones --format=ids )
```

```bash
$ wp pronamic cloudflare invalidate $( wp pronamic cloudflare zones --format=ids )
```

## Translations

### Dutch glossary

Cloudflare and HTTP terms that are common in Dutch technical language stay in English. Compounds with an English part are written as one word, like "webserver", unless a hyphen is needed for readability or because of an abbreviation.

| English | Dutch | Note |
| --- | --- | --- |
| origin | origin | Not "oorsprong"; Cloudflare uses "origin" as a technical term. |
| origin server | originserver | |
| origin IP address | origin-IP-adres | |
| request | verzoek | |
| response | HTTP-response | |
| response header | response-header | |
| header | header | For example "cache-headers" and "de %s-header". |
| cache (noun) | cache | |
| to cache | cachen, gecachet | |
| caching | caching | |
| cache tag | cachetag | |
| cache action | cacheactie | |
| edge cache | edge-cache | |
| edge TTL | edge-TTL | |
| browser cache | browsercache | |
| to purge | legen | |
| invalidation | invalidatie | |
| to bypass | omzeilen | |
| query parameter | queryparameter | |
| loopback request | loopback-verzoek | |
| to preheat a cache | een cache opwarmen | |
| zone, zone ID | zone, zone-ID | |
| API token | API-token | |
| Global API key | globale API-sleutel | |
| `stale-while-revalidate`, `stale-if-error` | Stale while revalidate, Stale if error | Directive names stay in English. |

Product and feature names stay in English, such as Automatic Platform Optimization (APO), Page Rules, Cache Rules and Cloudflare Workers. Paths in the Cloudflare dashboard, such as **Speed → Optimization → Content**, are not translated either, because the dashboard is in English.

## Links

- https://api.cloudflare.com/#zone-list-zones
- https://api.cloudflare.com/#zone-purge-all-files
