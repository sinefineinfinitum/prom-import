<?php

namespace SineFine\PromImport\Infrastructure\Http;

use Campo\UserAgent;
use Exception;
use WP_Error;

class WpHttpClient
{
	private const CACHE_PROM_RESPONSE = "prom_response_v1_";
	private const CACHE_TIMEOUT_SEC = 60;
    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|WP_Error
     */
    public function get(string $url, array $args = []): array|WP_Error
    {
	    $cacheKey = self::CACHE_PROM_RESPONSE . md5($url);
		$args = array_merge($args,[
		    'timeout' => self::CACHE_TIMEOUT_SEC,
		    'user-agent' => $this->getRandomUserAgent(),
		    'headers' => $this->getHeader(),
	    ]);

	    $response = wp_cache_get($cacheKey);
	    if ($response === false) {
		    $response = wp_remote_get($url, $args);
	    }

	    if (is_wp_error($response)) {
		    if (str_contains($response->get_error_message(), 'cURL error 28')) {
                return new WP_Error(
                    'timeout',
	                esc_html(__('Request timeout. Server respond is too long.', 'spss12-import-prom-woo'))
                );
            }
            return $response;
        }

	    wp_cache_set($cacheKey, $response, '', 3600);

	    return $response;
    }
    
    /**
     * @return array<string, string>
     */
    private function getHeader(): array
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Cache-control' => 'max-age=0',
        ];
    }

	private function getRandomUserAgent(): string|WP_Error
	{
		try {
			return UserAgent::random();
		} catch ( Exception $e ) {
			return new WP_Error(
				'problem with user agent',
				esc_html(__('Problem with user agent generation', 'spss12-import-prom-woo'))
			);
		}
	}
}
