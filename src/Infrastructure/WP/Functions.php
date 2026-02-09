<?php

namespace SineFine\PromImport\Infrastructure\WP;

class Functions
{
    public static function parseUrl(string $url, ?string $key): array|string
    {
        return wp_parse_url($url, $key);
    }
}