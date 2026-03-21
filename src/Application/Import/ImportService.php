<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SineFine\PromImport\Domain\Category\CategoryMapping;
use SineFine\PromImport\Domain\Import\Import;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use DateTime;

class ImportService
{
	public function __construct(
        private ImportRepositoryInterface $importRepository,
    ) {
    }

    /** @return Import[] */
	public function getAllImports(): array
    {
        return $this->importRepository->findAll();
    }

    public function createImport(string $name, string $url): int
    {
        $import = new Import(null, $name, $url);
        return $this->importRepository->save($import);
    }

    public function updateImport(int $id, string $name, string $url): bool
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            return false;
        }

        $import->setName($name);
        $import->setUrl($url);
        $import->setUpdatedAt(new DateTime());

        $this->importRepository->save($import);
        return true;
    }

    public function deleteImport(int $id): bool
    {
        return $this->importRepository->delete($id);
    }

    /**
     * @return array<int, mixed>
     */
    public function getImportCategories(int $id): array
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            return [];
        }
		return $import->getCategoryMapping()?->getMapping() ?? [];
    }

    /**
     * @param int $id
     * @param array<int, mixed> $mapping
     * @return bool
     */
	public function updateImportMapping(int $id, array $mapping): bool
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            return false;
        }

        $import->setCategoryMapping(new CategoryMapping($mapping));
        $import->setUpdatedAt(new DateTime());
        $this->importRepository->save($import);

        return true;
    }

	/**
	* @return array{success: bool, import_id: int, job_id: int|false}
	*/
	public function runImport(int $id): array
    {
	    // @phpstan-ignore function.notFound
		$job_id = as_enqueue_async_action('spss12-import-prom-woo_queue_run_batch', ['import_id' => $id]);

        return [
            'success' => true,
            'import_id' => $id,
	        'job_id' => $job_id,
        ];
    }
}
