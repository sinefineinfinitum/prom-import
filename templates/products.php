<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1>
        <?php echo esc_html(__('Products Importer', 'spss12-import-prom-woo')) ?>
    </h1>
    <div class="white-padding importer">
        <ul>
            <li>
                <?php
                /* translators: %s: Total number of products */
                printf(esc_html(__('Total Products: %s', 'spss12-import-prom-woo')), esc_html($sinefine_promimport_total_products ?? 0));
                ?>
            </li>
            <li>
                <?php
                /* translators: %s: Total number of pages */
                printf(esc_html(__('Total Pages: %s', 'spss12-import-prom-woo')), esc_html($sinefine_promimport_total_pages ?? 1));
                ?>
            </li>
        </ul>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th><?php echo esc_html(__('Thumbnails', 'spss12-import-prom-woo')) ?></th>
                <th><?php echo esc_html(__('Title', 'spss12-import-prom-woo')) ?></th>
                <th><?php echo esc_html(__('Category', 'spss12-import-prom-woo')) ?></th>
                <th><?php echo esc_html(__('Description', 'spss12-import-prom-woo')) ?></th>
                <th><?php echo esc_html(__('Price', 'spss12-import-prom-woo')) ?></th>
                <th><?php echo esc_html(__('Action', 'spss12-import-prom-woo')) ?></th>
            </tr>
            </thead>
            <tbody id="append-result">
            <?php
            /** @var \SineFine\PromImport\Application\Import\Dto\ProductDto $sinefine_promimport_product */
            foreach ($sinefine_promimport_products as $sinefine_promimport_product):?>
                <tr>
                    <td class="text-center" width="150">
                        <?php if (!empty($sinefine_promimport_product->mediaUrls)): ?>
                            <?php foreach ($sinefine_promimport_product->mediaUrls as $spssImage):?>
                            <img src="<?php echo esc_url($spssImage); ?>"
                                 loading="lazy"
                                 alt="<?php echo esc_attr($sinefine_promimport_product->title) ?>"
                                 width="100"
                                 style="height: auto;">
                            <?php endforeach;?>

                        <?php else: ?>
                            <div style="width: 100px; height: 100px; background: #f1f1f1; display: flex; align-items: center; justify-content: center;">
                                <?php esc_html_e('No spssImage', 'spss12-import-prom-woo'); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url($sinefine_promimport_product->link) ?>" target="_blank">
                            <h3><?php echo esc_html($sinefine_promimport_product->title) ?></h3>
                        </a>
                    </td>
                    <td class="text-center">
                        <?php
                        if (!empty($sinefine_promimport_product->categoryName)) {
                            echo esc_html($sinefine_promimport_product->categoryName);
                        } else {
                            echo esc_html(__('None', 'spss12-import-prom-woo'));
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        if (!empty($sinefine_promimport_product->description)) {
                            echo esc_html(wp_trim_words(wp_strip_all_tags($sinefine_promimport_product->description)));
                        } else {
                            echo esc_html(__('None', 'spss12-import-prom-woo'));
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        if (!empty($sinefine_promimport_product->price->amount())) {
                            echo esc_html( $sinefine_promimport_product->price->amount() . ' ' . $sinefine_promimport_product->price->currency());
                        } else {
                            echo esc_html(__('None', 'spss12-import-prom-woo'));
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        if ($sinefine_promimport_product->existedId) { ?>
                            <a href="<?php echo esc_url(get_edit_post_link($sinefine_promimport_product->existedId)); ?>"
                               style="background:green;color: white;"
                               class="button">
                                <?php echo esc_html(__('Edit Imported', 'spss12-import-prom-woo')) ?>
                            </a>
                        <?php } else { ?>
                            <a href="#"
                               data-id="<?php echo esc_attr($sinefine_promimport_product->sku->value()); ?>"
                               data-title="<?php echo esc_attr($sinefine_promimport_product->title); ?>"
                               data-description="<?php echo esc_attr($sinefine_promimport_product->description); ?>"
                               data-price="<?php echo esc_attr($sinefine_promimport_product->price->amount()); ?>"
                               data-category="<?php echo esc_attr($sinefine_promimport_product->category ? $sinefine_promimport_product->category->id : 0); ?>"
                               data-featured-media="<?php echo esc_attr(json_encode($sinefine_promimport_product->mediaUrls)); ?>"
                               data-nonce="<?php echo esc_attr(wp_create_nonce('sinefine_promimport_nonce')); ?>"
                               class="import-product button-primary">
                                <?php echo esc_html(__('Import', 'spss12-import-prom-woo')) ?>
                            </a>
                        <?php } ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
       <?php require_once 'pagination.php'; ?>
<?php