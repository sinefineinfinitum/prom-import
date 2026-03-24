<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Exception;

class BadRequestHttpException extends DomainException
{
    public static function argumentMissing(string $arg): self
    {
        return new self(
            esc_html(__('Argument missing in request: ', 'spss12-import-prom-woo')) . esc_attr($arg),
            400
        );
    }
}
