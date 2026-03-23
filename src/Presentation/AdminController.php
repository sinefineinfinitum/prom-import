<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

use Exception;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Category\CategoryRepositoryInterface;
use SineFine\PromImport\Domain\Common\XmlParserInterface;
use SineFine\PromImport\Domain\Exception\BadRequestHttpException;
use SineFine\PromImport\Domain\Exception\ImportNotFoundException;
use SineFine\PromImport\Domain\Import\Import;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;

class AdminController extends BaseController {
    public function __construct(
		private XmlParserInterface $xmlParser,
		private XmlService $xmlService,
		private ProductRepositoryInterface $productRepository,
		private CategoryRepositoryInterface $categoryRepository,
        private ImportRepositoryInterface $importRepository,
    ) {
    }

    public function imports_page(): void
    {
	    $sinefine_promimport_imports = $this->importRepository->findAll();
        $this->render('imports',
	        compact(
				'sinefine_promimport_imports',
	        ));
    }

	/**
	 * @throws Exception
	 */
	public function edit_import_page(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $sinefine_promimport_import = $this->importRepository->findById($id);
        if (!$sinefine_promimport_import) {
            wp_die(esc_html(__('Import not found', 'spss12-import-prom-woo')));
        }

        $sinefine_promimport_existing_categories = get_categories([
            'taxonomy'     => 'product_cat',
            'show_count'   => 1,
            'pad_counts'   => 0,
            'hierarchical' => 1,
            'hide_empty'   => false,
        ]);

        $sinefine_promimport_categories = [];
        try {
            $xml = $this->xmlService->getXmlFromUrl($sinefine_promimport_import->getUrl());
            $sinefine_promimport_categories = $this->xmlParser->parseCategories($xml);
        } catch (Exception ) {
            // Silently fail or show warning in template
        }

        $sinefine_promimport_saved_categories = $sinefine_promimport_import->getCategoryMapping()?->getMapping() ?? [];

        $this->render('edit-import', compact(
            'sinefine_promimport_import',
            'sinefine_promimport_categories',
            'sinefine_promimport_existing_categories',
            'sinefine_promimport_saved_categories'
        ));
    }


    public function products_importer(): void
    {
        try {
            $importId = isset($_GET['import_id']) ? (int)$_GET['import_id'] : null;
            if (! $importId) {
				throw BadRequestHttpException::argumentMissing('import_id');
            }
            $import = $this->importRepository->findById($importId);
            if (!($import instanceof Import)) {
               throw ImportNotFoundException::withId( $importId);
            }
            $xml = $this->xmlService->getXmlFromUrl($import->getUrl());
        } catch ( Exception $exception ) {
	        wp_send_json_error(['message' => $exception->getMessage()], $exception->getCode());
        }

	    $sinefine_promimport_total_pages    = 1;
	    $sinefine_promimport_total_products = $this->xmlParser->getTotalProducts( $xml );
	    $categories                         = $this->xmlParser->parseCategories( $xml );
	    $sinefine_promimport_products       = $this->xmlParser->parseProducts( $xml, $categories );

	    foreach ( $sinefine_promimport_products as $product ) {
		    $existedId          = $this->productRepository->findIdBySkuId( $product->sku->value() );
		    $product->existedId = $existedId ?: null;
		    $mappedCategoryId   = $product->category && $product->category->id()
			    ? $import->getCategoryMapping()?->getMapping()[ $product->category->id() ]
			    : null;
		    $category           = $this->categoryRepository->getCategoryById( (int) $mappedCategoryId );

		    $product->categoryName = $category ? $category->name() : "None";
	    }

	    $this->render(
		    'products',
		    compact(
			    'sinefine_promimport_products',
			    'sinefine_promimport_total_pages',
			    'sinefine_promimport_total_products'
		    )
	    );
    }
}