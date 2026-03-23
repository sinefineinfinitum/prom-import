 [![PHPStan ](https://img.shields.io/badge/PHPStan-Level%208%20-2a5ea7.svg)](https://github.com/szepeviktor/phpstan-wordpress)


## Description

A plugin for importing products from Prom.ua xml feed to WooCommerce.
Supports basic product import, list display, and management via the WordPress admin panel.
Exist both ways to import: manual and automatic.

## Installation & Usage

1. Install the plugin in the /wp-content/plugins/ directory.
2. Activate the plugin via the Plugins menu in the WordPress admin panel.
3. Specify the url to xml feed to Prom.ua in the plugin settings.
4. Run the import manually or configure automatic import.

## Changelog

- version 0.1.0: add ability to have many different imports and now imports running in the background.

## Roadmap / Development Plan

Next tasks and improvements (public plan):

### Functionality
- Import price only.
- Logging event, errors and warnings of import.

### Pagination
- Pagination in the admin UI for the list of imported products/import results (improved UX for large volumes).
- Display of progress indicators and the number of pages/elements.

### Bulk import
- [x] Background batch import (batch/queue) with chunking and rate limiting.
- Retry of failed requests with backoff, error logging, and results reporting.
- WP-CLI command for bulk import (for cron/CI).

### Static analyzers and code quality
- [x] PHPStan (phpstan.neon config already added) — increase strictness level, cover exceptions.
- Psalm — add and integrate into CI.
- PHP_CodeSniffer/PHPCS + WordPress Coding Standards.

### Testing and CI
- [x] Unit tests.
- [x] WooCommerce integration tests (using a test database).
- [x] GitHub Actions: running tests, PHPStan/Psalm/PHPCS, collecting release artifacts.

### Performance and stability
- [x] Caching of immutable reference categories.
- Timeout control, limiting simultaneous requests.

### UX and management
- Interrupt/resume imports.
- [x] Detailed logs and report export (CSV/JSON).
- Filters for selective imports (categories, price ranges, availability).
