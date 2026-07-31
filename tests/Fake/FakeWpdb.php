<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests\Fake;

class FakeWpdb
{
	public string $prefix = 'wp_';
	public int $insert_id = 1;

	/** @var array<int, array{0: string, 1: array<string, mixed>}> */
	public array $inserts = [];

	/** @var array<int, array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}> */
	public array $updates = [];

	/** @var array<int, string> */
	public array $queries = [];

	public function insert(string $table, array $data): int
	{
		$this->inserts[] = [$table, $data];
		return 1;
	}

	public function update(string $table, array $data, array $where): int
	{
		$this->updates[] = [$table, $data, $where];
		return 1;
	}

	public function query(string $sql): int
	{
		$this->queries[] = $sql;
		return 1;
	}

	public function prepare(string $sql, ...$args): string
	{
		return $sql;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_row(string $sql, string $output = OBJECT): ?array
	{
		return null;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_results(string $sql, string $output = OBJECT): array
	{
		return [];
	}
}