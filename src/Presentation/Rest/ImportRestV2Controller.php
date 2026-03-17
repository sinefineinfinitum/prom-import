<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation\Rest;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\ImportApplicationService;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Category\Category;
use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;
use SineFine\PromImport\Domain\Exception\DomainException;
use SineFine\PromImport\Domain\Exception\InvalidImportException;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use Throwable;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class ImportRestV2Controller extends WP_REST_Controller
{
	protected $namespace = 'spss12-prom-import/v2';
	protected $rest_base = 'import';

	public function __construct(
		private ImportService $service,
        private ImportApplicationService $importAppService,
		private CategoryMappingRepositoryInterface $categoryMappingRepository,
        private XmlService $xmlService,
        private OptionRepositoryInterface $optionRepository,
		private ProductRepositoryInterface $productRepository,
		private LoggerInterface $logger,
	) {}

	/**
	 * Register REST API routes
	 */
	public function register_routes(): void
	{
        // GET /wp-json/spss12-prom-import/v2/imports
        register_rest_route($this->namespace, '/imports', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_imports'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // POST /wp-json/spss12-prom-import/v2/imports
        register_rest_route($this->namespace, '/imports', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'create_import'],
                'permission_callback' => [$this, 'check_permission'],
                'args'                => $this->get_import_args(),
            ],
        ]);

        // PATCH /wp-json/spss12-prom-import/v2/imports/(?P<id>\d+)
        register_rest_route($this->namespace, '/imports/(?P<id>\d+)', [
            [
                'methods'             => 'PATCH',
                'callback'            => [$this, 'update_import'],
                'permission_callback' => [$this, 'check_permission'],
                'args'                => $this->get_import_args(),
            ],
        ]);

        // DELETE /wp-json/spss12-prom-import/v2/imports/(?P<id>\d+)
        register_rest_route($this->namespace, '/imports/(?P<id>\d+)', [
            [
                'methods'             => 'DELETE',
                'callback'            => [$this, 'delete_import'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // POST /wp-json/spss12-prom-import/v2/imports/(?P<id>\d+)/run
        register_rest_route($this->namespace, '/imports/(?P<id>\d+)/run', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'run_import'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // GET /wp-json/spss12-prom-import/v2/imports/(?P<id>\d+)/categories
        register_rest_route($this->namespace, '/imports/(?P<id>\d+)/categories', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_import_categories'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // PATCH /wp-json/spss12-prom-import/v2/imports/(?P<id>\d+)/mapping
        register_rest_route($this->namespace, '/imports/(?P<id>\d+)/mapping', [
            [
                'methods'             => 'PATCH',
                'callback'            => [$this, 'update_import_mapping'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

		// POST /wp-json/spss12-prom-import/v2/import/create
		register_rest_route($this->namespace, '/' . $this->rest_base . '/create', [
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'create'],
				'permission_callback' => [$this, 'check_permission'],
				'args'                => $this->get_create_import_args(),
			],
		]);

		// POST /wp-json/spss12-prom-import/v2/import/update
		register_rest_route($this->namespace, '/' . $this->rest_base . '/update', [
			[
				'methods'             => 'PATCH',
				'callback'            => [$this, 'update'],
				'permission_callback' => [$this, 'check_permission'],
				'args'                => $this->get_update_import_args(),
			],
		]);

        // POST /wp-json/spss12-prom-import/v2/import/delete
        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete', [
            [
                'methods'             => 'DELETE',
                'callback'            => [$this, 'delete'],
                'permission_callback' => [$this, 'check_permission'],
                'args'                => $this->get_delete_import_args(),
            ],
        ]);

	}

    public function get_imports(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $imports = $this->importAppService->getAllImports();
            $data = array_map(function($import) {
                return [
                    'id' => $import->getId(),
                    'name' => $import->getName(),
                    'url' => $import->getUrl(),
                    'category_mapping' => $import->getCategoryMapping(),
                    'path' => $import->getPath(),
                    'updated_at' => $import->getUpdatedAt() ? $import->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                    'created_at' => $import->getCreatedAt() ? $import->getCreatedAt()->format('Y-m-d H:i:s') : null,
                ];
            }, $imports);

            return new WP_REST_Response($data, 200);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

    public function create_import(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $name = $request->get_param('name');
            $url = $request->get_param('url');

            $id = $this->importAppService->createImport($name, $url);

            return new WP_REST_Response([
                'success' => true,
                'id' => $id,
                'message' => esc_html(__('Import created successfully', 'spss12-import-prom-woo')),
            ], 201);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

    public function update_import(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $name = $request->get_param('name');
            $url = $request->get_param('url');

            $success = $this->importAppService->updateImport($id, $name, $url);

            if (!$success) {
                return new WP_Error('not_found', __('Import not found', 'spss12-import-prom-woo'), ['status' => 404]);
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => esc_html(__('Import updated successfully', 'spss12-import-prom-woo')),
            ], 200);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

    public function delete_import(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $success = $this->importAppService->deleteImport($id);

            if (!$success) {
                return new WP_Error('not_found', __('Import not found', 'spss12-import-prom-woo'), ['status' => 404]);
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => esc_html(__('Import deleted successfully', 'spss12-import-prom-woo')),
            ], 200);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

    public function run_import(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $result = $this->importAppService->runImport($id);

            return new WP_REST_Response($result, 200);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

    public function get_import_categories(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $categories = $this->importAppService->getImportCategories($id);
            
            $data = array_map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            }, $categories);

            return new WP_REST_Response($data, 200);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

    public function update_import_mapping(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $mapping = $request->get_param('mapping');

            if (!is_array($mapping)) {
                return new WP_Error('invalid_mapping', __('Invalid mapping data', 'spss12-import-prom-woo'), ['status' => 400]);
            }

            $success = $this->importAppService->updateImportMapping($id, $mapping);

            if (!$success) {
                return new WP_Error('not_found', __('Import not found', 'spss12-import-prom-woo'), ['status' => 404]);
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => esc_html(__('Mapping updated successfully', 'spss12-import-prom-woo')),
            ], 200);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

    private function get_import_args(): array
    {
        return [
            'name' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'url' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_url',
                'validate_callback' => function($param) {
                    return filter_var($param, FILTER_VALIDATE_URL) !== false;
                },
            ],
        ];
    }

	/**
	 * Import single product
	 *
	 * @template T of WP_REST_Request
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create(WP_REST_Request $request): WP_REST_Response|WP_Error
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
			$this->service->addImagesToProductGallery( $dto, $productId );

			if ($externalCategoryId > 0) {
				$this->service->addCategoryToProduct($productId, $externalCategoryId);
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
	 * @param WP_REST_Request $request
	 **/
	public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
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
				'data'    => $this->optionRepository->getOption( Category::SINEFINE_PROMIMPORT_CATEGORIES_OPTION),
			], 200);
		} catch ( Throwable $e) {
			return $this->handle_exception($e);
		}
	}

    /**
     * Import config
     *
     * @template T of WP_REST_Request
     * @param WP_REST_Request $request
     **/
    public function delete(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $url = $request->get_param('url');

            if (empty($url)) {
                return new WP_Error(
                    'invalid_config',
                    __('Invalid or missing config url', 'spss12-import-prom-woo')
                );
            }
            $url = $this->xmlService->validateUrl($url);
            $url = $this->xmlService->validateDownloadAndSaveXml($url);

            return new WP_REST_Response([
                'success' => true,
                'message' => esc_html(__('Successfully saved config', 'spss12-import-prom-woo')),
                'data'    => $url,
            ], 200);
        } catch ( Throwable $e) {
            return $this->handle_exception($e);
        }
    }

	/**
	 * Check if the user has permission
	 * @template T of WP_REST_Request
	 *
	 * @param WP_REST_Request $request
	 */
	public function check_permission(WP_REST_Request $request): bool
	{
		return current_user_can('manage_options');
	}

	/**
	 * Get arguments schema for product import endpoint
	 *
	 * @return array<string, mixed>
	 */
	private function get_create_import_args(): array
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
	private function get_update_import_args(): array
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
     * @return array<string, mixed>
     */
    private function get_delete_import_args(): array
    {
        return [
            'url' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_url',
            ],
        ];
    }

	/**
	 * Handle exception and return the appropriate REST response
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
