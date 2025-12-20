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
                printf(esc_html(__('Total Products: %s', 'spss12-import-prom-woo')), esc_html($totalProducts ?? 0));
                ?>
            </li>
            <li>
                <?php
                /* translators: %s: Total number of pages */
                printf(esc_html(__('Total Pages: %s', 'spss12-import-prom-woo')), esc_html($totalPages ?? 1));
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
            /** @var \SineFine\PromImport\Application\Import\Dto\ProductDto $spssProduct */
            foreach ($spssProducts as $spssProduct):?>
                <tr>
                    <td class="text-center" width="150">
                        <?php if (!empty($spssProduct->mediaUrls)): ?>
                            <?php foreach ($spssProduct->mediaUrls as $spssImage):?>
                            <img src="<?php echo esc_url($spssImage); ?>"
                                 loading="lazy"
                                 alt="<?php echo esc_attr($spssProduct->title) ?>"
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
                        <a href="<?php echo esc_url($spssProduct->link) ?>" target="_blank">
                            <h3><?php echo esc_html($spssProduct->title) ?></h3>
                        </a>
                    </td>
                    <td class="text-center">
                        <?php
                        if (!empty($spssProduct->categoryName)) {
                            echo esc_html($spssProduct->categoryName);
                        } else {
                            echo esc_html(__('None', 'spss12-import-prom-woo'));
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        if (!empty($spssProduct->description)) {
                            echo esc_html(wp_trim_words(wp_strip_all_tags($spssProduct->description)));
                        } else {
                            echo esc_html(__('None', 'spss12-import-prom-woo'));
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        if (!empty($spssProduct->price->amount())) {
                            echo esc_html( $spssProduct->price->amount() . ' ' . $spssProduct->price->currency());
                        } else {
                            echo esc_html(__('None', 'spss12-import-prom-woo'));
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        if ($spssProduct->existedId) { ?>
                            <a href="<?php echo esc_url(get_edit_post_link($spssProduct->existedId)); ?>"
                               style="background:green;color: white;"
                               class="button">
                                <?php echo esc_html(__('Edit Imported', 'spss12-import-prom-woo')) ?>
                            </a>
                        <?php } else { ?>
                            <a href="#"
                               data-id="<?php echo esc_attr($spssProduct->sku->value()); ?>"
                               data-title="<?php echo esc_attr($spssProduct->title); ?>"
                               data-description="<?php echo esc_attr($spssProduct->description); ?>"
                               data-price="<?php echo esc_attr($spssProduct->price->amount()); ?>"
                               data-category="<?php echo esc_attr($spssProduct->category ? $spssProduct->category->id : 0); ?>"
                               data-featured-media="<?php echo esc_attr(json_encode($spssProduct->mediaUrls)); ?>"
                               data-nonce="<?php echo esc_attr(wp_create_nonce('prom_importer_nonce')); ?>"
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