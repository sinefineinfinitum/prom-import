<?php

namespace SineFine\PromImport\Domain\Import;

use DateTime;
use SineFine\PromImport\Domain\Category\CategoryMapping;

class Import
{
	public function __construct(
		private ?int $id,
		private string $name,
		private string $url,
		private ?CategoryMapping $categoryMapping = null,
		private ?string $path = null,
		private ?DateTime $updated_at = null,
		private ?DateTime $created_at = null,
	) {
	}

	public function getId(): ?int { return $this->id; }
	public function getName(): string { return $this->name; }
	public function getUrl(): string { return $this->url; }
	public function getCategoryMapping(): ?CategoryMapping { return $this->categoryMapping; }
	public function getPath(): ?string { return $this->path; }
	public function getUpdatedAt(): ?DateTime { return $this->updated_at; }
	public function getCreatedAt(): ?DateTime { return $this->created_at; }

    public function setName(string $name): void { $this->name = $name; }
    public function setUrl(string $url): void { $this->url = $url; }
    public function setCategoryMapping(?CategoryMapping $mapping): void { $this->categoryMapping = $mapping; }
    public function setPath(string $path): void { $this->path = $path; }
    public function setUpdatedAt(DateTime $updatedAt): void { $this->updated_at = $updatedAt; }

	public static function create(?int $id, string $name, string $url, ?CategoryMapping $mapping, ?string $path, ?DateTime $updated_at, DateTime $created_at): self
	{
		return new self($id, $name, $url, $mapping, $path, $updated_at, $created_at);
	}
}