<div class="wrap">
    <h1>
        <?php echo esc_html(__('Products Importer', 'prom-import')) ?>
    </h1>
    <div class="white-padding importer">
        <ul>
            <li>
                <?php
                /* translators: %s: Total number of products */
                printf(esc_html(__('Total Products: %s', 'prom-import')), esc_html($total_products));
                ?>
            </li>
            <li>
                <?php
                /* translators: %s: Total number of pages */
                printf(esc_html(__('Total Pages: %s', 'prom-import')), esc_html($totalpages));
                ?>
            </li>
        </ul>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th><?php echo esc_html(__('Thumbnail', 'prom-import')) ?></th>
                <th><?php echo esc_html(__('Title', 'prom-import')) ?></th>
                <th><?php echo esc_html(__('Category', 'prom-import')) ?></th>
                <th><?php echo esc_html(__('Description', 'prom-import')) ?></th>
                <th><?php echo esc_html(__('Price', 'prom-import')) ?></th>
                <th><?php echo esc_html(__('Action', 'prom-import')) ?></th>
            </tr>
            </thead>
            <tbody id="append-result">
            <?php
            /** @var \SineFine\PromImport\Application\Import\Dto\ProductDto $product */
            foreach ($products as $key => $product):?>
                <tr>
                    <td class="text-center" width="150">
                        <?php if (!empty($product->mediaUrls)): ?>
                            <?php foreach ($product->mediaUrls as $image):?>
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($product->title) ?>"
                                 width="100" style="height: auto;">
                            <?php endforeach;?>

                        <?php else: ?>
                            <div style="width: 100px; height: 100px; background: #f1f1f1; display: flex; align-items: center; justify-content: center;">
                                <?php esc_html_e('No image', 'prom-import'); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url($product->link) ?>" target="_blank">
                            <h3><?php echo esc_html($product->title) ?></h3>
                        </a>
                    </td>
                    <td class="text-center">
                        <?php
                        if (!empty($product->category)) {
                            echo esc_html($product->category);
                        } else {
                            echo esc_html(__('Uncategorized', 'prom-import'));
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        if (!empty($product->description)) {
                            echo esc_html($product->description);
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        if (!empty($product->price->amount())) {
                            echo esc_html($product->price->amount() . ' ' . $product->price->currency());
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        if ($product->existedId) { ?>
                            <a href="<?php echo esc_url(get_edit_post_link($product->existedId)); ?>"
                               style="background:green;color: white;"
                               class="button">
                                <?php echo esc_html(__('Edit Imported', 'prom-import')) ?>
                            </a>
                        <?php } else { ?>
                            <a href="#"

                               data-id="<?php echo esc_attr($product->sku->value()); ?>"
                               data-title="<?php echo esc_attr($product->title); ?>"
                               data-description="<?php echo esc_attr($product->description); ?>"
                               data-price="<?php echo esc_attr($product->price->amount()); ?>"
                               data-category="<?php echo esc_attr($product->category); ?>"
                               data-featured-media="<?php echo esc_attr(json_encode($product->mediaUrls)); ?>"

                               data-nonce="<?php echo esc_attr(wp_create_nonce('prom_importer_nonce')); ?>"
                               class="import-product button-primary">
                                <?php echo esc_html(__('Import', 'prom-import')) ?>
                            </a>
                        <?php } ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
       <?php require_once 'pagination.php'; ?>
<?php