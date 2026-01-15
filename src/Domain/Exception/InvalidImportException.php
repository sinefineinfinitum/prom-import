<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Exception;

class InvalidImportException extends DomainException
{
	public static function importFromDto( string $message): self
	{
		$exception = new self(sprintf(__('Invalid product data: %s', 'spss12-import-prom-woo'), $message ));
		return $exception;
	}
}
