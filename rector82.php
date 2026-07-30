<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {

	$rectorConfig->parameters();
	$rectorConfig->paths([
		__DIR__ . '/src',
		__DIR__ . '/languages',
		__DIR__ . '/templates',
	]);
	$rectorConfig->sets([SetList::PHP_84]);
};
