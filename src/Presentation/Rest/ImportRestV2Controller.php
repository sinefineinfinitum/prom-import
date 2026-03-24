<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation\Rest;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Domain\Exception\DomainException;
use SineFine\PromImport\Domain\Import\Import;
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
        private ImportService $importService,
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
	}

	/**
	 * @template T of WP_REST_Request
	 * @param T $request
	 **/
    public function get_imports(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $imports = $this->importService->getAllImports();
            $data = array_map(fn(Import $import) => [
                'id' => $import->getId(),
                'name' => $import->getName(),
                'url' => $import->getUrl(),
                'category_mapping' => $import->getCategoryMapping(),
                'path' => $import->getPath(),
                'updated_at' => $import->getUpdatedAt()?->format( 'Y-m-d H:i:s' ),
                'created_at' => $import->getCreatedAt()?->format( 'Y-m-d H:i:s' ),
            ], $imports);

            return new WP_REST_Response($data, 200);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

	/**
	 * @template T of WP_REST_Request
	 * @param T $request
	 **/
    public function create_import(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $name = $request->get_param('name');
            $url = $request->get_param('url');

            $id = $this->importService->createImport($name, $url);

            return new WP_REST_Response([
                'success' => true,
                'id' => $id,
                'message' => esc_html(__('Import created successfully', 'spss12-import-prom-woo')),
            ], 201);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

	/**
	 * @template T of WP_REST_Request
	 * @param T $request
	 **/
    public function update_import(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $name = $request->get_param('name');
            $url = $request->get_param('url');

            $success = $this->importService->updateImport($id, $name, $url);

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

	/**
	 * @template T of WP_REST_Request
	 * @param T $request
	 **/
    public function delete_import(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $success = $this->importService->deleteImport($id);

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

	/**
	 * @template T of WP_REST_Request
	 * @param T $request
	 **/
    public function run_import(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $result = $this->importService->runImport($id);

            return new WP_REST_Response($result, 200);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

	/**
	 * @template T of WP_REST_Request
	 * @param T $request
	 **/
    public function get_import_categories(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $categories = $this->importService->getImportCategories($id);
            
            $data = array_map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ], $categories);

            return new WP_REST_Response($data, 200);
        } catch (Throwable $e) {
            return $this->handle_exception($e);
        }
    }

	/**
	 * @template T of WP_REST_Request
	 * @param T $request
	 **/
    public function update_import_mapping(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = (int) $request->get_param('id');
            $mapping = $request->get_param('mapping');

            if (!is_array($mapping)) {
                return new WP_Error('invalid_mapping', __('Invalid mapping data', 'spss12-import-prom-woo'), ['status' => 400]);
            }

            $success = $this->importService->updateImportMapping($id, $mapping);

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

	/**
	 * Get arguments schema for import endpoint
	 *
	 * @return array<string, mixed>
	 */
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
                'validate_callback' => fn($param) => filter_var($param, FILTER_VALIDATE_URL) !== false,
            ],
        ];
    }

	/**
	 * Check if the user has permission
	 * @template T of WP_REST_Request
	 * @param T $request
	 */
	public function check_permission(WP_REST_Request $request): bool
	{
		return current_user_can('manage_options');
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
