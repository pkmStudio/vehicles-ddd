<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Application\Services\Rows;

use App\Warehouse\Export\Domain\Contracts\Services\Rows\KitExportRowInterface;
use App\Warehouse\Export\Domain\ModelData\KitData;

/**
 * Формирует заголовки и значения строки листа Warehouse-наборов.
 */
final readonly class KitExportRow implements KitExportRowInterface
{
    /**
     * Возвращает заголовки старого формата экспорта наборов.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array
    {
        return [
            'ID комплекта',
            'Состав',
            'Может продаваться отдельно',
            'Активен',
        ];
    }

    /**
     * Собирает строку набора с составом, отсортированным Repository по pivot-полю sort.
     *
     * @return array<int, mixed>
     */
    public function getData(KitData $kit): array
    {
        return [
            $kit->id,
            $kit->nomenclatures?->pluck('partNumber')->implode(';') ?? '',
            $kit->isSaleSeparately ? 'Да' : 'Нет',
            $kit->isActive ? 'Да' : 'Нет',
        ];
    }
}
