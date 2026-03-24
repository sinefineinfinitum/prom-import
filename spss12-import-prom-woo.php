<?php
/**
 * @package          Spss12ImportPromWoo
 * @author           spss12
 * @license          https://opensource.org/licenses/gpl-license.php GNU Public License
 * @version          0.0.3
 * @wordpress-plugin
 * Plugin Name:       spss12 Importer from Prom.ua to WooCoommerce
 * Plugin Uri:        https://github.com/sinefineinfinitum/prom-import
 * Description:       Import products from prom.ua to woo
 * Version:           0.0.3
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
    include_once __DIR__ . '/vendor/autoload.php';
} else {
    wp_die( esc_html( __('Composer autoload not found. Please run `composer install`.', 'spss12-import-prom-woo' )));
}

use SineFine\PromImport\Infrastructure\DB\Migrator;
use SineFine\PromImport\Infrastructure\WP\Install;
use SineFine\PromImport\Infrastructure\WP\Uninstall;
use SineFine\PromImport\Plugin;

define( 'SINEFINE_PROMIMPORT_PLUGIN_DIR', basename(plugin_dir_path( __FILE__ )));

// Check php version
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die( esc_html( __('This plugin requires PHP 8.0 or higher to function.', 'spss12-import-prom-woo' )));
}

/**
 * Activate plugin 
 */
function sinefine_promimport_activate(): void
{
    (new Install())->run();
    Migrator::migrate();
}
register_activation_hook( __FILE__, 'sinefine_promimport_activate' );

/**
 * Deactivate plugin on uninstall 
 */
function sinefine_promimport_uninstall(): void
{
    (new Uninstall())->run();
}
register_uninstall_hook( __FILE__, 'sinefine_promimport_uninstall' );

// Bootstrap plugin via Plugin class
add_action(
    'plugins_loaded', static function () {
        if (class_exists('SineFine\\PromImport\\Plugin')) {
            (new Plugin())->boot();
        }
    }
);
