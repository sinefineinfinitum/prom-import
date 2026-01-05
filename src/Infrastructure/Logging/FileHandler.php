<?php

namespace SineFine\PromImport\Infrastructure\Logging;
use UnexpectedValueException;

class FileHandler implements HandlerInterface
{
	private string $filename;

	public function __construct(string $filename)
	{
		$dir = wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . dirname($filename);
		if (!file_exists($dir)) {
			$status = mkdir($dir, 0777, true);
			if ($status === false && !is_dir($dir)) {
				throw new UnexpectedValueException(sprintf('There is no existing directory at "%s"', $dir));
			}
		}
		$this->filename = $dir
		                  . DIRECTORY_SEPARATOR
		                  . pathinfo($filename, PATHINFO_FILENAME)
		                  . '.'
		                  . pathinfo($filename, PATHINFO_EXTENSION);
	}

	public function handle(array $vars): void
	{
		$output = self::DEFAULT_FORMAT;
		foreach ($vars as $var => $val) {
			$output = str_replace('%' . $var . '%', $val, $output);
		}
		file_put_contents($this->filename, $output . PHP_EOL, FILE_APPEND);
	}
}