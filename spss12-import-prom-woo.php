<?php
/**
 * @package           PromUaImport
 * @version           0.0.1
 *
 * @wordpress-plugin
 * Plugin Name:       Prom.ua Products Importer for WooCommerce
 * Plugin Uri:        https://wordpress.org/plugins/
 * Description:       Import products from prom.ua to woo
 * Version:           0.0.1
 * Author:            spss12
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       spss12-import-prom-woo
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

defined('ABSPATH') or die();

// Composer autoload
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Bootstrap plugin via Plugin class
add_action('plugins_loaded', static function () {
    if (class_exists('SineFine\\PromImport\\Plugin')) {
        (new \SineFine\PromImport\Plugin())->boot();
    }
});
