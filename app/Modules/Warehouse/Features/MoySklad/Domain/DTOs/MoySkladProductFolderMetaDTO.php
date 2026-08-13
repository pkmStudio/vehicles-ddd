<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\DTOs;

final readonly class MoySkladProductFolderMetaDTO
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private array $data = [],
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Проверяет, что meta папки товара не содержит данных.
     */
    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    /**
     * Извлекает id папки товара из meta.href, если он доступен.
     */
    public function folderId(): ?string
    {
        $href = $this->data['meta']['href'] ?? null;
        if (! is_string($href) || $href === '') {
            return null;
        }

        $parts = parse_url($href);
        $path = $parts['path'] ?? null;
        if (! is_string($path) || $path === '') {
            return null;
        }

        $segments = explode('/', trim($path, '/'));
        $id = end($segments);

        return is_string($id) && $id !== '' ? $id : null;
    }
}
