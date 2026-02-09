<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Exception;

class InvalidImportException extends DomainException
{
	public static function importFromDto( string $message): self
	{
		return new self(esc_html(__('Invalid product data: ', 'spss12-import-prom-woo')) . esc_html($message));
	}
}
