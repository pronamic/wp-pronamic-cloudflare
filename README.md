# Pronamic Cloudflare

The Pronamic Cloudflare plugin adds a number of features, such as WP-CLI commands, to the Cloudflare plugin.

## Table of contents

- [Configuration](#configuration)
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
