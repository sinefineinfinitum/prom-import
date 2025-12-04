<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;
use WP_Error;

class ImportService
{
    public function __construct(
        private ProductRepository $repository,
    ) {
    }

    /**
     * Import or update a single product using DTO.
     * Returns created/updated post ID or WP_Error
     *
     * @return int|WP_Error
     */
    public function importFromDto(ProductDto $dto): int|WP_Error
    {
        if (trim($dto->title) === '') {
            return new WP_Error('has no title', __('Post has no title', 'prom-import'));
        }

        $postId = $this->repository->upsertFromDto($dto);
        if (is_wp_error($postId)) {
            return $postId;
        }

        // Add gallery images (featured image is handled inside repository using the first URL)
        if (! empty($dto->mediaUrls)) {
            $first = $dto->mediaUrls[0] ?? null;
            foreach ($dto->mediaUrls as $url) {
                if ($first !== null && $url === $first) {
                    continue; // already set as featured in repository
                }
                $this->repository->addImageToProductGallery($url, (int) $postId, $dto->title);
            }
        }

        return (int) $postId;
    }
}
