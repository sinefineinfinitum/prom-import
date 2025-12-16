<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1>
        <?php echo esc_html(__('Prom.ua Settings', 'spss12-import-prom-woo')) ?>
    </h1>

    <form action='options.php' method='post'>
        <?php
        settings_fields('prom_importer_group');
        do_settings_sections('prom_importer_settings');
        submit_button();
        ?>
    </form>
</div>