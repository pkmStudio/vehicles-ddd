<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

use App\Support\Http\Contracts\HttpArraySerializableInterface;

/**
 * Сценарный снимок плоской категории номенклатуры публичного каталога.
 */
final readonly class CatalogCategoryDTO implements HttpArraySerializableInterface
{
    /**
     * Хранит поля категории и число доступных позиций выбранного бренда.
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code,
        public int $nomenclatureCount,
    ) {}

    /**
     * Возвращает HTTP-представление категории.
     *
     * @return array{id: int, name: string, code: string|null, nomenclature_count: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'nomenclature_count' => $this->nomenclatureCount,
        ];
    }
}
