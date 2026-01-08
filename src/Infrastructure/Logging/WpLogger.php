<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Logging;

use Psr\Log\AbstractLogger;
use Stringable;

class WpLogger extends AbstractLogger
{
	private const DEFAULT_DATETIME_FORMAT = 'c';

	public function __construct(
		private HandlerInterface $handler,
	) {
	}

	/**
	 * @param mixed $level
	 * @param string|\Stringable $message
	 * @param array<string, mixed> $context
	 */

	public function log($level, $message, array $context = []): void
	{
		$this->handler->handle([
			'message' => self::interpolate((string)$message, $context),
			'level' => strtoupper($level),
			'timestamp' => (new \DateTimeImmutable())->format(self::DEFAULT_DATETIME_FORMAT),
		]);
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 */
	protected static function interpolate(string $message, array $context = []): string
	{
		$replace = [];
		foreach ($context as $key => $val) {
			if (is_string($val)) {
				$replace['{' . $key . '}'] = $val;
			}
		}
		return strtr($message, $replace);
	}
}
