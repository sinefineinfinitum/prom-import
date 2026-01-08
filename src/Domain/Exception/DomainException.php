<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Exception;

use Exception;

/**
 * Base exception for all domain-level errors
 */
abstract class DomainException extends Exception
{
	/**
	 * Get user-friendly error message for display
	 */
	public function getUserMessage(): string
	{
		return $this->getMessage();
	}

	/**
	 * Get error context data for logging
	 * @return array<string, mixed>
	 */
	public function getContext(): array
	{
		return [];
	}
}
