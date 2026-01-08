<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Exception;

class InvalidImportException extends DomainException
{
	public static function importFromDto( string $message): self
	{
		$exception = new self(__(sprintf('Invalid product data: %s', $message ), 'spss12-import-prom-woo'));
		return $exception;
	}
}
