<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Exception;

class InvalidSkuException extends DomainException
{
    public static function emptyValue(): self
    {
        return new self('SKU must be a non-empty string');
    }
}
