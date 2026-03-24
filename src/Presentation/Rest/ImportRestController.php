<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation\Rest;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Exception\DomainException;
use SineFine\PromImport\Domain\Exception\InvalidImportException;
use SineFine\PromImport\Domain\Product\ProductManagerInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
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
        private XmlService $xmlService,
        private ProductManagerInterface $productManager,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Register REST API routes
     */
    public function register_routes(): void
    {
        // POST /wp-json/spss12-prom-import/v1/import/product
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/product', [
                [
                    'methods'             => 'POST',
                    'callback'            => [$this, 'import_product'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args'                => $this->get_product_import_args(),
                ],
            ]
        );
        // POST /wp-json/spss12-prom-import/v1/import/update-prices
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/update-prices', [
                [
                    'methods'             => 'POST',
                    'callback'            => [$this, 'update_prices'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args'                => [],
                ],
            ]
        );
    }

    /**
     * Import single product
     *
     * @template T of WP_REST_Request
     * @param    T $request
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

            $productId = $this->productManager->createProductFromDto($dto);
            if (is_wp_error($productId)) {
                   throw InvalidImportException::importFromDto($productId->get_error_message());
            }
            $this->productManager->addImagesToProductGallery( $dto, $productId );

            if ($externalCategoryId > 0) {
                $this->productManager->addCategoryToProduct($productId, $externalCategoryId);
            }

            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' => esc_html(__('Successfully imported', 'spss12-import-prom-woo')),
                    'data'    => [
                        'product_id' => $productId,
                        'edit_url'   => get_edit_post_link($productId, 'raw'),
                    ],
                ], 201
            );
        } catch ( Throwable $e) {
            return $this->handle_exception($e);
        }
    }
    /**
     * Update all product prices from XML
     *
     * @template T of WP_REST_Request
     * @param    T $request
     */
    public function update_prices(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $xml = $this->xmlService->getXml();
            $products = $this->xmlService->getProductsFromXml($xml);
            $updatedCount = 0;

            foreach ($products as $productDto) {
                $postId = $this->productRepository->updateProductPrice($productDto);
                if ($postId && !is_wp_error($postId)) {
                    $updatedCount++;
                }
            }

            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' =>
                    esc_html(__('Successfully updated prices for products: ', 'spss12-import-prom-woo'))
                    . $updatedCount,
                    'data'    => [
                        'updated_count' => $updatedCount,
                    ],
                ], 200
            );
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

    /**
     * Check if the user has permission
     *
     * @template T of WP_REST_Request
     * @param    T $request
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
    private function get_product_import_args(): array
    {
        return [
            'product_id' => [
                'required'          => true,
                'type'              => 'integer',
                'validate_callback' => fn($param) => is_numeric($param) && $param > 0,
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
                'validate_callback' => fn($param) => is_numeric($param) && $param >= 0,
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
     * Handle exception and return the appropriate REST response
     */
    private function handle_exception( Throwable $e): WP_Error
    {
        $message = $e instanceof DomainException
        ? $e->getUserMessage()
        : $e->getMessage();

        $this->logger->error(
            'REST API Exception: {message}', [
                'message' => $message,
            ]
        );

        return new WP_Error(
            'import_error',
            $message
        );
    }
}
