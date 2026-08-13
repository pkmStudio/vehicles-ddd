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
    /**
     * Получает порты чтения упаковок и типов номенклатуры.
     *
     * Шаги:
     * 1) Принять repository упаковочных размеров как источник основного листа.
     * 2) Принять repository типов как источник справочного листа.
     * 3) Сохранить зависимости для методов подготовки экспорта.
     */
    public function __construct(
        private PackDimensionRepositoryInterface $packDimensions,
        private TypeRepositoryInterface $types,
    ) {}

    /**
     * Возвращает упаковочные размеры для листа данных.
     *
     * Шаги:
     * 1) Запросить все упаковочные размеры через read-порт.
     * 2) Вернуть коллекцию PackDimensionData для Excel-адаптера.
     *
     * @return Collection<int, PackDimensionData>
     */
    public function getRows(): Collection
    {
        return $this->packDimensions->all();
    }

    /**
     * Возвращает заголовки листа упаковочных размеров.
     *
     * Шаги:
     * 1) Зафиксировать порядок базовых колонок упаковки.
     * 2) Добавить колонки связанного типа и флага автогенерации.
     * 3) Вернуть заголовки в порядке, который использует mapRow().
     *
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
     * Преобразует упаковочный размер в строку Excel.
     *
     * Шаги:
     * 1) Разложить базовые числовые и текстовые поля упаковки.
     * 2) Подставить название и код типа из связи, если она загружена.
     * 3) Преобразовать boolean-флаг generated в русское значение.
     *
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
     * Возвращает строки справочника типов для второго листа.
     *
     * Шаги:
     * 1) Получить все типы номенклатуры через read-порт.
     * 2) Преобразовать каждый тип в строку ID, код, название.
     * 3) Сбросить ключи коллекции для последовательной Excel-выгрузки.
     *
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
