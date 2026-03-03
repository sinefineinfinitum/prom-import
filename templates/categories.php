<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use SineFine\PromImport\Application\Import\Dto\CategoryDto;

?>
<div class="wrap">
	<h1>
		<?php echo esc_html(__('Categories Importer', 'spss12-import-prom-woo')) ?>
	</h1>
	<div class="white-padding importer">
		<ul>
			<li>
				<?php
				/* translators: %s: Total number of categories */
				printf(esc_html(__('Total Categories: %s', 'spss12-import-prom-woo')), esc_html(count($sinefine_promimport_categories)));
				?>
			</li>
            <li>
                <a href="#"
                   id="import-categories"
                   data-nonce="<?php echo esc_attr(wp_create_nonce('sinefine_promimport_nonce')); ?>"
                   class="import-category button-primary">
                    <?php echo esc_html(__('Save category mapping settings', 'spss12-import-prom-woo')) ?>
                </a>
            </li>
		</ul>
		<table class="wp-list-table fixed striped" id="categories-table">
			<thead>
                <tr>
                    <th><?php echo esc_html(__('Category Id from xml', 'spss12-import-prom-woo')) ?></th>
                    <th><?php echo esc_html(__('Category Name from xml', 'spss12-import-prom-woo')) ?></th>
                    <th><?php echo esc_html(__('Category Name from Woo', 'spss12-import-prom-woo')) ?></th>
                </tr>
			</thead>
			<tbody id="append-result">
			<?php
			/** @var CategoryDto $sinefine_promimport_category */
			foreach ($sinefine_promimport_categories as $sinefine_promimport_key => $sinefine_promimport_category):?>
				<tr>
                    <td>
                        <span>
                            <?php echo esc_html($sinefine_promimport_category->id) ?>
                        </span>
                    </td>
					<td>
						<a href="<?php echo esc_url($sinefine_promimport_category->id) ?>" target="_blank">
							<h3><?php echo esc_html($sinefine_promimport_category->name) ?></h3>
						</a>
					</td>
                    <td>
                        <select name="cat"
                                id="category-<?php echo esc_html($sinefine_promimport_key); ?>"
                                class="postform" tabindex="1">
                            <option value="0">None category</option>
                            <?php
                                $sinefine_promimport_is_already_selected = false;
                                foreach ( $sinefine_promimport_existing_categories as $sinefine_promimport_existing_category ) {
                                    $sinefine_promimport_is_saved = $sinefine_promimport_existing_category->cat_ID == ( $sinefine_promimport_saved_categories[$sinefine_promimport_category->id] ?? false);
                                    if ($sinefine_promimport_is_saved) {
                                        $sinefine_promimport_is_already_selected = true;
                                    }
                                    $spssIsEqualName = (sanitize_title(sanitize_title($sinefine_promimport_category->name), '', 'query')
                                                   === $sinefine_promimport_existing_category->slug) && !$sinefine_promimport_is_saved && !$sinefine_promimport_is_already_selected; ?>
                            <option class="level-<?php echo count( get_ancestors($sinefine_promimport_existing_category->id, 'sinefine_promimport_category') ) ?>"
                                    <?php if ( $sinefine_promimport_is_saved OR $spssIsEqualName ): ?>selected="selected"<?php endif; ?>
                                    value="<?php echo esc_html($sinefine_promimport_existing_category->cat_ID); ?>"><?php echo esc_html($sinefine_promimport_existing_category->name); ?></option>
                            <?php } ?>
                        </select>
                    </td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
