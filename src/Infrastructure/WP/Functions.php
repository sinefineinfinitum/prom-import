<?php

namespace SineFine\PromImport\Infrastructure\WP;

class Functions
{
    public static function parseUrl(string $url, int $key): mixed
    {
        return wp_parse_url($url, $key);
    }
}