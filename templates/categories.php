<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
	<h1>
		<?php echo esc_html(__('Categories Importer', 'spss12-import-prom-woo')) ?>
	</h1>
	<div class="white-padding importer">
		<ul>
			<li>
				<?php
				/* translators: %s: Total number of categories */
				printf(esc_html(__('Total Categories: %s', 'spss12-import-prom-woo')), esc_html(count($spssCategories)));
				?>
			</li>
            <li>
                <a href="#"
                   id="import-categories"
                   data-nonce="<?php echo esc_attr(wp_create_nonce('prom_importer_nonce')); ?>"
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
			/** @var \SineFine\PromImport\Application\Import\Dto\CategoryDto $spssCategory */
			foreach ($spssCategories as $spssKey => $spssCategory):?>
				<tr>
                    <td>
                        <span>
                            <?php echo esc_html($spssCategory->id) ?>
                        </span>
                    </td>
					<td>
						<a href="<?php echo esc_url($spssCategory->id) ?>" target="_blank">
							<h3><?php echo esc_html($spssCategory->name) ?></h3>
						</a>
					</td>
                    <td>
                        <select name="cat" id="category-<?php echo esc_html($spssKey); ?>" class="postform" tabindex="1">
                            <option value="0">None category</option>
                            <?php foreach ( $spssExistingCategories as $existingCategory ) {
                                $spssIsSaved     = $existingCategory->cat_ID == ( $spssSavedCategories[$spssCategory->id] ?? false);
                                $spssIsEqualName = sanitize_title(sanitize_title($spssCategory->name), '', 'query')
                                                   === $existingCategory->slug && !$spssIsSaved; ?>
                            <option class="level-<?php echo count( get_ancestors($existingCategory->id, 'spssCategory') ) ?>"
                                    <?php if ( $spssIsSaved OR $spssIsEqualName ): ?>selected="selected"<?php endif; ?>
                                    value="<?php echo esc_html($existingCategory->cat_ID); ?>"><?php echo esc_html($existingCategory->name); ?></option>
                            <?php }; ?>
                        </select>
                    </td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
