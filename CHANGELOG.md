# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]

## [1.2.0] - 2025-08-20

### Added

- Added post content related cache tags (using output buffering). ([d095868](https://github.com/pronamic/wp-pronamic-cloudflare/commit/d09586884c92ad72c531acaaa03da556a25bd2dd))

### Composer

- Changed `woocommerce/action-scheduler` from `3.9.2` to `3.9.3`.
	Release notes: https://github.com/woocommerce/action-scheduler/releases/tag/3.9.3
- Changed `automattic/jetpack-autoloader` from `v5.0.8` to `v5.0.9`.
	Release notes: https://github.com/Automattic/jetpack-autoloader/releases/tag/v5.0.9

Full set of changes: [`1.1.2...1.2.0`][1.2.0]

[1.2.0]: https://github.com/pronamic/wp-pronamic-cloudflare/compare/v1.1.2...v1.2.0

## [1.1.2] - 2025-07-04

### Fixed

- Make sure keys are sequentially numbered for array JSON encoding. ([bb55694](https://github.com/pronamic/wp-pronamic-cloudflare/commit/bb5569414d7fe9efe8aad479eeaf9681c245ce73))
- Fixed "PHP Deprecated:  Optional parameter $taxonomy declared before required parameter $deleted_term is implicitly treated as a required parameter". ([658d549](https://github.com/pronamic/wp-pronamic-cloudflare/commit/658d5499653c9efacff4cbbf8a51422b1b2a1423))

### Changed

- Updated visibility of `get_current_cache_tags()` function. ([f618ed5](https://github.com/pronamic/wp-pronamic-cloudflare/commit/f618ed5936dd458ae57471ba3ae1966ee266a87e))
- Separate cache purge actions for tags and everything. ([7615c76](https://github.com/pronamic/wp-pronamic-cloudflare/commit/7615c76101588954631335533df39c4947376a3f))

Full set of changes: [`1.1.1...1.1.2`][1.1.2]

[1.1.2]: https://github.com/pronamic/wp-pronamic-cloudflare/compare/v1.1.1...v1.1.2

## [1.1.1] - 2025-07-03

### Fixed

- Fixed incomplete action arguments. ([d30f71b](https://github.com/pronamic/wp-pronamic-cloudflare/commit/d30f71bb4f50a41d0f749e138220a340d9e6dbc0))
- Fixed "Uncaught TypeError: Pronamic\WordPressCloudflare\Plugin::purge_cache_by_user(): Argument #1 ($user) must be of type WP_User, int given". ([4706bed](https://github.com/pronamic/wp-pronamic-cloudflare/commit/4706beddd6d725f9d43458053b037c1f7786f80f))
- Prevent sending cache purge request with empty plugin settings. ([909434a](https://github.com/pronamic/wp-pronamic-cloudflare/commit/909434a52ea27cdf246f93540544c35cf0a15e62))

### Composer

- Changed `automattic/jetpack-autoloader` from `v3.1.3` to `v5.0.8`.
	Release notes: https://github.com/Automattic/jetpack-autoloader/releases/tag/v5.0.8

Full set of changes: [`1.1.0...1.1.1`][1.1.1]

[1.1.1]: https://github.com/pronamic/wp-pronamic-cloudflare/compare/v1.1.0...v1.1.1

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
