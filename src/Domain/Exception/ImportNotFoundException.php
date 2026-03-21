<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Exception;

class ImportNotFoundException extends DomainException
{
	public static function withId(int $id): self
	{
		return new self(
			esc_html(__('Import not found with id: ', 'spss12-import-prom-woo')) . esc_attr((string)$id),
			404
		);
	}
}
