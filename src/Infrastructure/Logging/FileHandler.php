<?php

namespace SineFine\PromImport\Infrastructure\Logging;

class FileHandler implements HandlerInterface
{
    private string $filename;

    public function __construct(
        string $filename,
    ) {
        $dir = wp_upload_dir()['basedir'] . dirname($filename);
        $this->filename = $dir
                          . DIRECTORY_SEPARATOR
                          . pathinfo($filename, PATHINFO_FILENAME)
                          . '.'
                          . pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * @param array<string, string> $vars
     */
    public function handle(array $vars): void
    {
        $output = self::DEFAULT_FORMAT;
        foreach ($vars as $var => $val) {
            $output = str_replace('%' . $var . '%', $val, $output);
        }
        file_put_contents($this->filename, $output . PHP_EOL, FILE_APPEND);
    }
}
