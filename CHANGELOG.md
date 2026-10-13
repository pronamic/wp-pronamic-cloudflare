# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).


## [Unreleased]

## [1.3.0] - 2026-10-07

### Added

- Added a "Settings" link to the plugin actions on the Plugins screen. ([cd04da2](https://github.com/pronamic/wp-pronamic-cloudflare/commit/cd04da2fcb145851a156668ccfb7c34521db7213))
- Added Dutch (`nl_NL`) translations. ([08cd4cd](https://github.com/pronamic/wp-pronamic-cloudflare/commit/08cd4cd63c70f4cc90953abe95537e3cac130c42), [caee27f](https://github.com/pronamic/wp-pronamic-cloudflare/commit/caee27f6b2cc235a356837591ff3d783b25cedef))

### Changed

- WordPress 6.8 or higher is now required (because of the Action Scheduler 4 update).
- The cache is now only purged for published posts; saving drafts, autosaves and revisions no longer triggers a purge. ([dbacb79](https://github.com/pronamic/wp-pronamic-cloudflare/commit/dbacb79192f2b21dd167f31a8f80557d4c622c1b))
- Simplified the build process and added a `composer make-pot` script to refresh the translation files. ([c4f64ec](https://github.com/pronamic/wp-pronamic-cloudflare/commit/c4f64ecfa550a2b0bce1880deece997c55648281), [08cd4cd](https://github.com/pronamic/wp-pronamic-cloudflare/commit/08cd4cd63c70f4cc90953abe95537e3cac130c42))

### Fixed

- Cache tags are now purged in chunks of maximum 100 tags per Cloudflare request, the most important tags (post, home, front page, feeds) are purged first. ([#18](https://github.com/pronamic/wp-pronamic-cloudflare/issues/18), [aa56750](https://github.com/pronamic/wp-pronamic-cloudflare/commit/aa56750c7a2dc05a3e3591d811455ffee0c3faf7))
- The cache is now also purged when a published post is permanently deleted. ([52d11b7](https://github.com/pronamic/wp-pronamic-cloudflare/commit/52d11b7f049e656a4f603d652a4525d78e1ef79c))

### Composer

- Changed `woocommerce/action-scheduler` from `3.9.3` to `4.2.0`.
	- 4.0.0: Unique actions now take the action arguments into account, failed actions are automatically purged after 3 months, cleanup runs as a dedicated daily task and WordPress 6.8 is required.
	  Release notes: https://github.com/woocommerce/action-scheduler/releases/tag/4.0.0
	- 4.1.0: Fixes a lock that could get permanently stuck, reduces SQL queries, shows action IDs in the admin list and hardens deserialization of stored schedule data.
	  Release notes: https://github.com/woocommerce/action-scheduler/releases/tag/4.1.0
	- 4.2.0: Unique action inserts are now enforced atomically, several admin notice fixes and support for bootstrapping from a plugin's `uninstall.php`.
	  Release notes: https://github.com/woocommerce/action-scheduler/releases/tag/4.2.0

Full set of changes: [`v1.3.0-rc.1...v1.3.0`][1.3.0]

## [1.3.0-rc.1] - 2026-03-09

### Added

- Add support for Cloudflare API Tokens with Bearer authentication. ([6a2b555](https://github.com/pronamic/wp-pronamic-cloudflare/commit/6a2b555))
- Add response JSON to WP CLI error message. ([37b2d2c](https://github.com/pronamic/wp-pronamic-cloudflare/commit/37b2d2c))

### Changed

- Refactor: DRY auth headers, remove trim, restore Cloudflare's apostrophe. ([45a57dc](https://github.com/pronamic/wp-pronamic-cloudflare/commit/45a57dc))
- Use array unpacking instead of array_merge for headers. ([03e6815](https://github.com/pronamic/wp-pronamic-cloudflare/commit/03e6815))
- Trim trailing whitespace in PHP docs. ([d60bfe3](https://github.com/pronamic/wp-pronamic-cloudflare/commit/d60bfe3))
- Update copyright years to 2005-2026. ([f9d2e77](https://github.com/pronamic/wp-pronamic-cloudflare/commit/f9d2e77))
- Update dependencies and regenerate lockfile. ([c653c34](https://github.com/pronamic/wp-pronamic-cloudflare/commit/c653c34))
- Disable test environment in .wp-env.json. ([877a140](https://github.com/pronamic/wp-pronamic-cloudflare/commit/877a140))

### Fixed

- Sanitize API token by trimming whitespace before use. ([551bf44](https://github.com/pronamic/wp-pronamic-cloudflare/commit/551bf44))
- Improve exception messages to be more specific for debugging. ([ae0b1e7](https://github.com/pronamic/wp-pronamic-cloudflare/commit/ae0b1e7))

Full set of changes: [`v1.2.0...v1.3.0-rc.1`][1.3.0-rc.1]

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


[1.3.0]: https://github.com/pronamic/wp-pronamic-cloudflare/compare/v1.3.0-rc.1...v1.3.0
[1.3.0-rc.1]: https://github.com/pronamic/wp-pronamic-cloudflare/compare/v1.2.0...v1.3.0-rc.1
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
