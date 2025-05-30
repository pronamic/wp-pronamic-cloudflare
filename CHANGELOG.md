# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]

## [1.1.0] - 2025-05-30

### Fixed

- Fixed cache purge from/to status `publish`. ([4350df3](https://github.com/pronamic/wp-pronamic-cloudflare/commit/4350df3be4af4031856dbeec877865189679d547))

### Changed

- Use cache tags instead of URLs for cache purge. ([#3](https://github.com/pronamic/wp-pronamic-cloudflare/issues/3))

### Removed

- Removed dependency on Cloudflare plugin. ([5f153ab](https://github.com/pronamic/wp-pronamic-cloudflare/commit/5f153ab6d444837e4856177daab1ca500654289b))
- Removed `cloudflare_purge_by_url` filter. ([58a6c97](https://github.com/pronamic/wp-pronamic-cloudflare/commit/58a6c97a5f8dc6c1816011e10516c5f82b393d75))

### Composer

- Changed `woocommerce/action-scheduler` from `3.8.2` to `3.9.2`.
	Release notes: https://github.com/woocommerce/action-scheduler/releases/tag/3.9.2
- Changed `pronamic/wp-html` from `v2.2.1` to `v2.2.2`.
	Release notes: https://github.com/pronamic/wp-html/releases/tag/v2.2.2
- Changed `automattic/jetpack-autoloader` from `v3.1.1` to `v3.1.3`.
	Release notes: https://github.com/Automattic/jetpack-autoloader/releases/tag/v3.1.3

Full set of changes: [`1.0.1...1.1.0`][1.1.0]

[1.1.0]: https://github.com/pronamic/wp-pronamic-cloudflare/compare/v1.0.1...v1.1.0

## [1.0.1] - 2024-11-21
- Increased cache purge request timeout to `30` seconds.

## [1.0.0] - 2024-10-14
- Initial release.

[unreleased]: https://github.com/pronamic/wp-pronamic-cloudflare/compare/1.0.1...HEAD
[1.0.1]: https://github.com/pronamic/wp-pronamic-cloudflare/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/pronamic/wp-pronamic-cloudflare/releases/tag/v1.0.0
