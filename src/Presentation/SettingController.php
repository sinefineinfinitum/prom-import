<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

class SettingController extends BaseController
{
	public function importer_section_callback(): void
	{
		echo '<p>'
		     . esc_html__( 'Please enter valid Prom.ua export URL you want to import from.', 'spss12-import-prom-woo' )
		     . '</p>';
	}

	public function url_setting_callback(): void
	{
		?>
		<label>
			<input type='url'
			       class="regular-text"
			       name="prom_domain_url_input"
			       value="<?php echo esc_url( get_option( 'prom_domain_url_input' ) ); ?>"
			       placeholder="https://prom.ua/products_feed.xml?...">
		</label>
		<p class="description">
			<?php echo esc_html__( 'Enter Prom.ua export URL you want to import from', 'spss12-import-prom-woo' ); ?>
		</p>
		<?php
	}

	public function prom_settings_page_content(): void
    {
		$this->checkUserPermission();

		$this->render( 'settings' );
	}
}