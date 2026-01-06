 [![PHPStan ](https://img.shields.io/badge/PHPStan-Level%208%20-2a5ea7.svg)](https://github.com/szepeviktor/phpstan-wordpress)


## Description

A plugin for importing products from Prom.ua xml feed to WooCommerce.
Supports basic product import, list display, and management via the WordPress admin panel.

## Installation & Usage

1. Install the plugin in the /wp-content/plugins/ directory.
2. Activate the plugin via the Plugins menu in the WordPress admin panel.
3. Specify the url to xml feed to Prom.ua in the plugin settings.
4. Run the import manually or configure automatic import.

## Roadmap / Development Plan

Next tasks and improvements (public plan):

### Pagination
- Pagination in the admin UI for the list of imported products/import results (improved UX for large volumes).
- Support for pagination of products.
- Display of progress indicators and the number of pages/elements.

### Bulk import
- Background batch import (batch/queue) with chunking and rate limiting.
- Retry of failed requests with backoff, error logging, and results reporting.
- WP-CLI command for bulk import (for cron/CI).

### SKU — optional addition
- Setting: add/hide SKU when creating a product.
- SKU missing behavior options: auto-generation, skipping, or writing to the meta field.
- SKU uniqueness validation and conflict strategy.

### Static analyzers and code quality
- [x] PHPStan (phpstan.neon config already added) — increase strictness level, cover exceptions.
- Psalm — add and integrate into CI.
- PHP_CodeSniffer/PHPCS + WordPress Coding Standards.

### Testing and CI
- [x] Unit tests.
- WooCommerce integration tests (using a test database).
- [x] GitHub Actions: running tests, PHPStan/Psalm/PHPCS, collecting release artifacts.

### Performance and stability
- Streaming processing of large responses (minimal memory usage).
- [x] Caching of immutable reference categories.
- Timeout control, limiting simultaneous requests.

### UX and management
- Interrupt/resume imports.
- [x] Detailed logs and report export (CSV/JSON).
- Filters for selective imports (categories, price ranges, availability).

### Localization


## Changelog

= 0.0.5 (2026-01-06): =
- PHPStan strictness level increased to 8 and warnings fixed
- Unit tests added
- GitHub Actions added
- DI Container added
- Logging added

= 0.0.3 (2025-12-19): =

- Save category mapping configuration 

= 0.0.2 (2025-12-15): =

- WordPress tested 6.9
- WooCommerce tested  10.3.6
- PHPStan warning fixed

= 0.0.1 (2025-12-04): =
- First plugin's version