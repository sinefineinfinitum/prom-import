<?php

namespace SineFine\PromImport\Infrastructure\Persistence;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Domain\Product\ImageAttachable;

class ImageProductService implements ImageAttachable
{
	public function __construct(
		public LoggerInterface $logger,
	) {
		include_once ABSPATH . 'wp-admin/includes/media.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/image.php';
	}
	public function assignFeatureImageToProduct(string $url, int $postId, string $title = ''): void
	{
		$attachmentId = media_sideload_image($url, $postId, $title, 'id');
		if (! is_wp_error($attachmentId) && is_numeric($attachmentId)) {
			set_post_thumbnail($postId, (int) $attachmentId);
		} elseif (is_wp_error($attachmentId)) {
			$this->logger->error('Failed to sideload featured image from {url} for product {post_id}: {error}', [
				'url' => $url,
				'post_id' => $postId,
				'error' => $attachmentId->get_error_message()
			]);
		}
	}

	public function addImageToProductGallery(string $url, int $postId, string $title = ''): void
	{
		$attachmentId = media_sideload_image($url, $postId, $title, 'id');
		if (is_wp_error($attachmentId)) {
			$this->logger->error('Failed to sideload gallery image from {url} for product {post_id}: {error}', [
				'url' => $url,
				'post_id' => $postId,
				'error' => $attachmentId->get_error_message()
			]);
			return;
		}

		if ( is_null( get_post_meta( $postId, "_product_image_gallery" ) ) ) {
			add_post_meta( $postId, "_product_image_gallery", $attachmentId );
		} else {
			$images_meta = get_post_meta( $postId, "_product_image_gallery", true );
			update_post_meta( $postId, "_product_image_gallery", $images_meta . "," . $attachmentId );
		}
	}
}