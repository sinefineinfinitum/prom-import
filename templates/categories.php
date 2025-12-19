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
				printf(esc_html(__('Total Categories: %s', 'spss12-import-prom-woo')), esc_html(count($categories)));
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
			/** @var \SineFine\PromImport\Application\Import\Dto\CategoryDto $category */
			foreach ($categories as $key => $category):?>
				<tr>
                    <td>
                        <span>
                            <?php echo esc_html($category->id) ?>
                        </span>
                    </td>
					<td>
						<a href="<?php echo esc_url($category->id) ?>" target="_blank">
							<h3><?php echo esc_html($category->name) ?></h3>
						</a>
					</td>
                    <td>
                        <select name="cat" id="category-<?php echo $key; ?>" class="postform" tabindex="1">
                            <option value="0">None category</option>
                            <?php foreach ( $existingCategories as $existingCategory ) {
                                $isSaved = $existingCategory->cat_ID == ($savedCategories[$category->id] ?? false);
                                $isEqualName = sanitize_title(sanitize_title($category->name), '', 'query')
                                               === $existingCategory->slug && !$isSaved; ?>
                            <option class="level-<?php echo count( get_ancestors($existingCategory->id, 'category') ) ?>"
                                    <?php if ( $isSaved OR $isEqualName ): ?>selected="selected"<?php endif; ?>
                                    value="<?php echo $existingCategory->cat_ID; ?>"><?php echo $existingCategory->name ?></option>
                            <?php }; ?>
                        </select>
                    </td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
