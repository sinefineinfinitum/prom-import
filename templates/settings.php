<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1>
        <?php echo esc_html(__('Prom.ua Settings', 'spss12-import-prom-woo')) ?>
    </h1>
    <ul>
        <li>
            <label for="url">Xml url:</label>
            <input type="text"
                   id="url"
                   value="<?php echo esc_url($spssUrl); ?>"
                   placeholder="https://prom.ua/products_feed.xml?..."
                   name="url">
            <a href="#"
               id="import-config"
               data-nonce="<?php echo esc_attr(wp_create_nonce('prom_importer_nonce')); ?>"
               class="import-config button-primary">
                <?php echo esc_html(__('Save xml url and update cache', 'spss12-import-prom-woo')) ?>
            </a>
        </li>
    </ul>
</div>