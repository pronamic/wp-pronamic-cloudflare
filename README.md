# Pronamic Cloudflare

The Pronamic Cloudflare plugin adds a number of features, such as WP-CLI commands, to the Cloudflare plugin.

## Table of contents

- [WP-CLI](#wp-cli)
- [Links](#links)

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
