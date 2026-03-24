<?php

use SineFine\PromImport\Domain\Import\Import;

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var Import[] $sinefine_promimport_imports */
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(__('Imports', 'spss12-import-prom-woo')); ?></h1>
    <button type="button" class="page-title-action" id="open-create-import-modal"><?php echo esc_html(__('Add New', 'spss12-import-prom-woo')); ?></button>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped table-view-list" id="imports-table">
        <thead>
        <tr>
            <th><?php echo esc_html(__('Name', 'spss12-import-prom-woo')); ?></th>
            <th><?php echo esc_html(__('URL', 'spss12-import-prom-woo')); ?></th>
            <th><?php echo esc_html(__('Updated At', 'spss12-import-prom-woo')); ?></th>
            <th><?php echo esc_html(__('Created At', 'spss12-import-prom-woo')); ?></th>
            <th><?php echo esc_html(__('Actions', 'spss12-import-prom-woo')); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($sinefine_promimport_imports)) : ?>
            <tr>
                <td colspan="5"><?php echo esc_html(__('No imports found', 'spss12-import-prom-woo')); ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ($sinefine_promimport_imports as $sinefine_promimport_import): ?>
                <tr data-id="<?php echo (int)$sinefine_promimport_import->getId(); ?>">
                    <td class="import-name"><?php echo esc_html((string)$sinefine_promimport_import->getName()); ?></td>
                    <td class="import-url"><?php echo esc_html((string)$sinefine_promimport_import->getUrl()); ?></td>
                    <td><?php echo esc_html($sinefine_promimport_import->getUpdatedAt() ? $sinefine_promimport_import->getUpdatedAt()->format('Y-m-d H:i:s') : '-'); ?></td>
                    <td><?php echo esc_html($sinefine_promimport_import->getCreatedAt() ? $sinefine_promimport_import->getCreatedAt()->format('Y-m-d H:i:s') : '-'); ?></td>
                    <td>
                        <button type="button" class="button run-import" 
                                data-id="<?php echo (int)$sinefine_promimport_import->getId(); ?>">
                            <?php echo esc_html(__('Run Import', 'spss12-import-prom-woo')); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=prom-products-importer&import_id=' . (int)$sinefine_promimport_import->getId())); ?>" class="button">
                            <?php echo esc_html(__('Manual Import', 'spss12-import-prom-woo')); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=prom-edit-import&id=' . (int)$sinefine_promimport_import->getId())); ?>" class="button edit-import">
                            <?php echo esc_html(__('Edit', 'spss12-import-prom-woo')); ?>
                        </a>
                        <button type="button" class="button button-link-delete delete-import" 
                                data-id="<?php echo (int)$sinefine_promimport_import->getId(); ?>">
                            <?php echo esc_html(__('Delete', 'spss12-import-prom-woo')); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Modal for Create -->
    <div id="import-modal" style="display:none; position:fixed; z-index:100000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
        <div style="background-color:#fff; margin:10% auto; padding:20px; border:1px solid #888; width:400px; position:relative;">
            <span id="close-modal" style="position:absolute; right:10px; top:10px; cursor:pointer; font-size:20px;">&times;</span>
            <h2 id="modal-title"><?php echo esc_html(__('Add New Import', 'spss12-import-prom-woo')); ?></h2>
            <form id="import-form">
                <input type="hidden" id="import-id" name="id">
                <p>
                    <label for="import-name-input"><?php echo esc_html(__('Name', 'spss12-import-prom-woo')); ?></label><br>
                    <input type="text" id="import-name-input" name="name" style="width:100%;" required>
                </p>
                <p>
                    <label for="import-url-input"><?php echo esc_html(__('URL', 'spss12-import-prom-woo')); ?></label><br>
                    <input type="url" id="import-url-input" name="url" style="width:100%;" required>
                </p>
                <p>
                    <button type="submit" class="button button-primary" id="save-import-btn"><?php echo esc_html(__('Save', 'spss12-import-prom-woo')); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>
