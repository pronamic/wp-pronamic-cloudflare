# AGENTS.md

Instructions for AI coding agents working on this repository. See [README.md](README.md) for what the plugin does and how it is configured.

## Project

- WordPress plugin, PHP 8.1+, WordPress 6.8+.
- Entry point: `pronamic-cloudflare.php`.
- Classes in `psr-4/` (namespace `Pronamic\WordPressCloudflare`), admin templates in `admin/`.
- Background work runs through Action Scheduler.

## Commands

```bash
composer phpcs     # Check coding standards
composer phpcbf    # Fix coding standards
composer build     # Build the distribution in build/pronamic-cloudflare/
composer make-pot  # Build, update languages/pronamic-cloudflare.pot and the .po files
```

`npx wp-env start` starts a local WordPress environment (see `.wp-env.json`).

## Code style

- Follow the WordPress coding standards as configured in `phpcs.xml.dist`.
- Write post types, taxonomies, page slugs, hook names, nonce names and option names as inline string literals, not as class constants.
- Write multiline SQL as heredoc (`<<<SQL`) when interpolating, or nowdoc (`<<<'SQL'`) otherwise, with an indented closing marker.
- Do not add `apply_filters()` or `do_action()` hooks unless asked.

## Translations

- Text domain: `pronamic-cloudflare`.
- Dutch translations live in `languages/pronamic-cloudflare-nl_NL.po`. Follow the [Dutch glossary](README.md#dutch-glossary) in the README, and add new terminology choices to it.
- After editing a `.po` file, regenerate the `.mo` file:

  ```bash
  vendor/bin/wp i18n make-mo languages/pronamic-cloudflare-nl_NL.po languages/
  ```

## Distribution

Files and directories listed in `.distignore` (such as this file, `documentation/` and development configuration) are not included in the plugin distribution.
