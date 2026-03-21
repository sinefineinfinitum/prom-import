<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

global $wp_test_hooks;
define( 'ABSPATH', '/');

if(!is_array($wp_test_hooks)) {
	$wp_test_hooks = [];
}

// Minimal WordPress function shims for unit testing environment
if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $url): string
    {
        // Keep it simple for tests: trim and return as-is
        return trim($url);
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return $text;
    }
}

if (!function_exists('sanitize_title')) {
	function sanitize_title(string $title): string
	{
		return $title;
	}
}

if (!function_exists('esc_html__')) {
	function esc_html__(string $text): string
	{
		return $text;
	}
}

if (!class_exists('WP_Term')) {
    class WP_Term
    {
        public int $term_id;
        public function __construct(int $id)
        {
            $this->term_id = $id;
        }
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        public string $code;
        public string $message;

        public function __construct(string $code = '', string $message = '', $args = [])
        {
            $this->code = $code;
            $this->message = $message;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error(mixed $thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response): string
    {
        return is_array($response) && $response['body'] ? $response['body'] : '';
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
	function wp_remote_retrieve_response_code($response): int
	{
		return is_array($response) && $response['response']['code']
			? (int) $response['response']['code']
			: 200;
	}
}

if (!function_exists('get_option')) {
    function get_option(string $option, $default = false)
    {
        global $wp_options;
        return $wp_options[$option] ?? $default;
    }
}

if (!function_exists('wp_die')) {
    function wp_die(string $message = '', string $title = '', $args = [])
    {
        throw new Exception($message ?: 'wp_die called');
    }
}

if (!function_exists('wp_parse_url')) {
	function wp_parse_url(string $url, int $component = -1): mixed
	{
		return parse_url($url, $component);
	}
}

if(!function_exists('update_option')) {
    function update_option(string $option, mixed $value, mixed $autoload = null): bool
    {
        global $wp_options;
        $wp_options[$option] = $value;
        return true;
    }
}

// No-op shims to avoid fatal errors if accidentally called in tests
if (!function_exists('wp_set_object_terms')) {
    function wp_set_object_terms(int $object_id, array $terms, string $taxonomy): void
    {
        // no-op in unit tests
    }
}

if (!function_exists('term_exists')) {
    function term_exists(int $term, string $taxonomy): bool
    {
        return false;
    }
}

if(!function_exists('add_filter')) {
	function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ): bool
	{
		global $wp_test_hooks;

		$wp_test_hooks[$hook_name][] = [
			$callback,
			$priority,
			$accepted_args,
		];

		return true;
	}
}

if(!function_exists('add_action')) {
	function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ): bool
	{
		return add_filter( $hook_name, $callback, $priority, $accepted_args );
	}
}

if(!function_exists('do_action')){
	function do_action( $hook_name, ...$arg ): void
	{
		global $wp_test_hooks;
		if(array_key_exists($hook_name, $wp_test_hooks)){
			foreach($wp_test_hooks[$hook_name] as $parts){
				list($callback, $priority, $accepted_args) = $parts;
				$callback(...$arg);
			}
		}
	}
}

if (!function_exists('as_enqueue_async_action')) {
	function as_enqueue_async_action(string $action, array $data): int|false
	{
		return 1;
	}
}