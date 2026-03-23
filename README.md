 [![PHPStan ](https://img.shields.io/badge/PHPStan-Level%208%20-2a5ea7.svg)](https://github.com/szepeviktor/phpstan-wordpress)

 ## Description

**spss12 Importer from Prom.ua to WooCommerce** is an effective solution for automatically importing products from Prom.ua XML feeds to your WooCommerce online store. The plugin simplifies catalog synchronization, allowing you to flexibly configure import rules and category mapping.

### Key features:
* **Import by link:** Automatically download data from Prom.ua XML exports.
* **Category mapping:** Conveniently map source categories to your store's categories.
* **SKU control:** Prevent duplicates and update existing products by SKU.
* **Flexible management:** Ability to manually launch or configure automatic import queues.
* **Background processes:** Process large volumes of data in the background.
* **Reporting:** Detailed logs and import history.

## Installation and Usage

1. Upload the plugin to the /wp-content/plugins/ directory.
2. Activate the plugin via the "Plugins" menu in the WordPress admin panel.
3. Go to the **Product Import** section to create a new import profile.
4. Enter the XML feed URL and configure category mapping.
5. Run the import manually or wait for the scheduled task to complete.

## System Requirements
* PHP 8.1+
* WordPress 5.5+
* WooCommerce

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
