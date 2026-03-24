<?php

use SineFine\PromImport\Domain\Import\Import;

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var Import $sinefine_promimport_import */
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(__('Edit Import', 'spss12-import-prom-woo')); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=spss12-import-prom-woo')); ?>" class="page-title-action"><?php echo esc_html(__('Back to list', 'spss12-import-prom-woo')); ?></a>
    <hr class="wp-header-end">

    <form id="edit-import-form" method="post">
        <input type="hidden" id="import-id" name="id" value="<?php echo (int)$sinefine_promimport_import->getId(); ?>">
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="import-name"><?php echo esc_html(__('Name', 'spss12-import-prom-woo')); ?></label>
                    </th>
                    <td>
                        <input name="name" type="text" id="import-name" value="<?php echo esc_attr((string)$sinefine_promimport_import->getName()); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="import-url"><?php echo esc_html(__('URL', 'spss12-import-prom-woo')); ?></label>
                    </th>
                    <td>
                        <input name="url" type="url" id="import-url" value="<?php echo esc_attr((string)$sinefine_promimport_import->getUrl()); ?>" class="large-text" required>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php echo esc_html(__('Category Mapping', 'spss12-import-prom-woo')); ?></h2>
        <div class="importer">
            <table class="wp-list-table widefat fixed striped" id="categories-mapping-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html(__('Category Id from XML', 'spss12-import-prom-woo')) ?></th>
                        <th><?php echo esc_html(__('Category Name from XML', 'spss12-import-prom-woo')) ?></th>
                        <th><?php echo esc_html(__('WooCommerce Category', 'spss12-import-prom-woo')) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sinefine_promimport_categories)) : ?>
                    <tr>
                        <td colspan="3"><?php echo esc_html(__('Could not load categories from XML. Please check URL.', 'spss12-import-prom-woo')); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sinefine_promimport_categories as $xml_category): ?>
                        <tr data-external-id="<?php echo esc_attr($xml_category->id()); ?>">
                            <td><?php echo esc_html($xml_category->id()); ?></td>
                            <td><?php echo esc_html($xml_category->name()); ?></td>
                            <td>
                                <select class="category-select" data-external-id="<?php echo esc_attr($xml_category->id()); ?>">
                                    <option value="0"><?php echo esc_html(__('None', 'spss12-import-prom-woo')); ?></option>
                                    <?php foreach ($sinefine_promimport_existing_categories as $woo_category): ?>
                                        <?php 
                                            $saved_id = $sinefine_promimport_saved_categories[$xml_category->id()] ?? 0;
                                            $selected = ((int)$woo_category->term_id === (int)$saved_id) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo esc_attr($woo_category->term_id); ?>" <?php echo $selected; ?>>
                                            <?php echo esc_html($woo_category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p class="submit">
            <button type="submit" name="submit" id="save-edit-import" class="button button-primary"><?php echo esc_html(__('Save Changes', 'spss12-import-prom-woo')); ?></button>
            <span class="spinner"></span>
        </p>
    </form>
</div>
