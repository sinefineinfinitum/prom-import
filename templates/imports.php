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
        <?php if (empty($imports)): ?>
            <tr>
                <td colspan="5"><?php echo esc_html(__('No imports found', 'spss12-import-prom-woo')); ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ($imports as $import): ?>
                <tr data-id="<?php echo esc_attr($import->getId()); ?>">
                    <td class="import-name"><?php echo esc_html($import->getName()); ?></td>
                    <td class="import-url"><?php echo esc_html($import->getUrl()); ?></td>
                    <td><?php echo esc_html($import->getUpdatedAt() ? $import->getUpdatedAt()->format('Y-m-d H:i:s') : '-'); ?></td>
                    <td><?php echo esc_html($import->getCreatedAt() ? $import->getCreatedAt()->format('Y-m-d H:i:s') : '-'); ?></td>
                    <td>
                        <button type="button" class="button run-import" 
                                data-id="<?php echo esc_attr($import->getId()); ?>">
                            <?php echo esc_html(__('Run', 'spss12-import-prom-woo')); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=prom-products-importer&import_id=' . $import->getId())); ?>" class="button">
                            <?php echo esc_html(__('Manual Import', 'spss12-import-prom-woo')); ?>
                        </a>
                        <button type="button" class="button edit-import" 
                                data-id="<?php echo esc_attr($import->getId()); ?>"
                                data-name="<?php echo esc_attr($import->getName()); ?>"
                                data-url="<?php echo esc_attr($import->getUrl()); ?>"
                                data-mapping='<?php echo esc_attr(json_encode($import->getCategoryMapping() ?: new stdClass())); ?>'>
                            <?php echo esc_html(__('Edit', 'spss12-import-prom-woo')); ?>
                        </button>
                        <button type="button" class="button button-link-delete delete-import" 
                                data-id="<?php echo esc_attr($import->getId()); ?>">
                            <?php echo esc_html(__('Delete', 'spss12-import-prom-woo')); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Modal for Create/Edit -->
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
                <div id="mapping-container" style="display:none;">
                    <h3><?php echo esc_html(__('Category Mapping', 'spss12-import-prom-woo')); ?></h3>
                    <div id="mapping-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                        <!-- Mapping rows will be loaded here -->
                    </div>
                </div>
                <p>
                    <button type="submit" class="button button-primary" id="save-import-btn"><?php echo esc_html(__('Save', 'spss12-import-prom-woo')); ?></button>
                </p>
            </form>
        </div>
    </div>
    
    <script>
        window.sinefineWooCategories = <?php 
            $cats = get_categories(['taxonomy' => 'product_cat', 'hide_empty' => false]);
            echo json_encode(array_map(fn($c) => ['id' => $c->term_id, 'name' => $c->name], $cats));
        ?>;
    </script>
</div>
