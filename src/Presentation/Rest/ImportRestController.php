<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation\Rest;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use SineFine\PromImport\Domain\Exception\DomainException;
use SineFine\PromImport\Domain\Exception\InvalidImportException;
use SineFine\PromImport\Domain\Exception\InvalidProductDataException;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use Throwable;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class ImportRestController extends WP_REST_Controller
{
	protected $namespace = 'spss12-prom-import/v1';
	protected $rest_base = 'import';

	public function __construct(
		private ImportService $service,
		private CategoryMappingRepositoryInterface $categoryMappingRepository,
		private LoggerInterface $logger,
	) {}

	/**
	 * Register REST API routes
	 */
	public function register_routes(): void
	{
		// POST /wp-json/spss12-prom-import/v1/import/product
		register_rest_route($this->namespace, '/' . $this->rest_base . '/product', [
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'import_product'],
				'permission_callback' => [$this, 'check_permission'],
				'args'                => $this->get_product_import_args(),
			],
		]);

		// POST /wp-json/spss12-prom-import/v1/import/categories
		register_rest_route($this->namespace, '/' . $this->rest_base . '/categories', [
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'import_categories'],
				'permission_callback' => [$this, 'check_permission'],
				'args'                => $this->get_categories_import_args(),
			],
		]);
	}

	/**
	 * Import single product
	 *
	 * @template T of WP_REST_Request
	 * @param T $request
	 */
	public function import_product(WP_REST_Request $request): WP_REST_Response|WP_Error
	{
		try {
			$sku_id = $request->get_param('product_id');
			$title = $request->get_param('product_title');
			$description = $request->get_param('product_description') ?? '';
			$priceVal = (float) ($request->get_param('product_price') ?? 0.0);
			$externalCategoryId = (int) ($request->get_param('product_category') ?? 0);
			$media = $request->get_param('product_featured_media') ?? [];

			// Validate and sanitize media URLs
			if (is_string($media)) {
				$media = json_decode($media, true);
			}
			$media = is_array($media) ? array_map('esc_url_raw', array_filter($media)) : [];

			$dto = ProductDto::create(
				Sku::create($sku_id),
				sanitize_text_field($title),
				wp_kses_post($description),
				Price::create($priceVal),
				null,
				$media,
			);

			$productId = $this->service->importProductFromDto($dto);
			if (is_wp_error($productId)) {
				throw InvalidImportException::importFromDto($productId->get_error_message());
			}

			if ($externalCategoryId > 0) {
				$this->service->addCategoryForProduct($productId, $externalCategoryId);
			}

			return new WP_REST_Response([
				'success' => true,
				'message' => esc_html(__('Successfully imported', 'spss12-import-prom-woo')),
				'data'    => [
					'product_id' => $productId,
					'edit_url'   => get_edit_post_link($productId, 'raw'),
				],
			], 201);
		} catch ( Throwable $e) {
			return $this->handle_exception($e);
		}
	}

	/**
	 * Import categories mapping
	 *
	 * @template T of WP_REST_Request
	 * @param T $request
	 **/
	public function import_categories(WP_REST_Request $request): WP_REST_Response|WP_Error
	{
		try {
			$categories = $request->get_param('categories');

			if (empty($categories) || !is_array($categories)) {
				return new WP_Error(
					'invalid_categories',
					__('Invalid or missing categories data', 'spss12-import-prom-woo')
				);
			}

			// Validate categories structure
			foreach ($categories as $category) {
				if (!isset($category['id'], $category['selected'])) {
					return new WP_Error(
						'validation_error',
						__('Validation error: missing required fields', 'spss12-import-prom-woo')
					);
				}
				if (!ctype_digit((string)$category['id']) || !ctype_digit((string)$category['selected'])) {
					return new WP_Error(
						'validation_error',
						__('Validation error: invalid numeric values', 'spss12-import-prom-woo')
					);
				}
			}

			$this->categoryMappingRepository->setCategoryMapping($categories);

			return new WP_REST_Response([
				'success' => true,
				'message' => esc_html(__('Successfully imported', 'spss12-import-prom-woo')),
				'data'    => get_option('prom_categories_input'),
			], 200);
		} catch ( Throwable $e) {
			return $this->handle_exception($e);
		}
	}

	/**
	 * Check if user has permission
	 */
	public function check_permission(): bool
	{
		return current_user_can('manage_options');
	}

	/**
	 * Get arguments schema for product import endpoint
	 *
	 * @return array<string, mixed>
	 */
	private function get_product_import_args(): array
	{
		return [
			'product_id' => [
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => function($param) {
					return is_numeric($param) && $param > 0;
				},
				'sanitize_callback' => 'absint',
			],
			'product_title' => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'product_description' => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			],
			'product_price' => [
				'required'          => false,
				'type'              => 'number',
				'validate_callback' => function($param) {
					return is_numeric($param) && $param >= 0;
				},
				'default'           => 0.0,
			],
			'product_category' => [
				'required'          => false,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			],
			'product_featured_media' => [
				'required' => false,
				'type'     => ['array', 'string'],
				'default'  => [],
			],
		];
	}

	/**
	 * Get arguments schema for categories import endpoint
	 *
	 * @return array<string, mixed>
	 */
	private function get_categories_import_args(): array
	{
		return [
			'categories' => [
				'required' => true,
				'type'     => 'array',
				'items'    => [
					'type'       => 'object',
					'properties' => [
						'id'       => ['type' => 'integer'],
						'selected' => ['type' => 'integer'],
					],
				],
			],
		];
	}

	/**
	 * Handle exception and return appropriate REST response
	 */
	private function handle_exception( Throwable $e): WP_Error
	{
		$message = $e instanceof DomainException
			? $e->getUserMessage()
			: $e->getMessage();

		$this->logger->error('REST API Exception: {message}', [
			'message' => $message,
		]);

		return new WP_Error(
			'import_error',
			$message
		);
	}
}
