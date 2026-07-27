<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Application\Services;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\PackDimensionExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\ModelData\PackDimensionData;
use Illuminate\Support\Collection;

/**
 * Координирует чтение упаковок и справочника типов для Excel-экспорта.
 */
final readonly class PackDimensionExportService implements PackDimensionExportServiceInterface
{
    public function __construct(
        private PackDimensionRepositoryInterface $packDimensions,
        private TypeRepositoryInterface $types,
    ) {}

    /**
     * @return Collection<int, PackDimensionData>
     */
    public function getRows(): Collection
    {
        return $this->packDimensions->all();
    }

    /**
     * @return array<int, string>
     */
    public function getHeadings(): array
    {
        return [
            'ID',
            'Название коробки',
            'Вес',
            'Ширина',
            'Высота',
            'Длина',
            'Цена',
            'Тип товара',
            'Код типа',
            'Сгенерирована автоматически',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function mapRow(PackDimensionData $row): array
    {
        return [
            $row->id,
            $row->name,
            $row->weight,
            $row->width,
            $row->height,
            $row->length,
            $row->price,
            $row->type?->name ?? $row->typeId,
            $row->type?->char,
            $row->generated ? 'Да' : 'Нет',
        ];
    }

    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function getReferenceRows(): Collection
    {
        return $this->types->all()
            ->map(fn ($type): array => [
                $type->id,
                $type->char,
                $type->name,
            ])
            ->values();
    }
}
