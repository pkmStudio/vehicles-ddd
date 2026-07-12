<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Application\Services\Rows;

use App\Warehouse\Export\Domain\Contracts\Services\Rows\NomenclatureExportRowInterface;
use App\Warehouse\Export\Domain\ModelData\NomenclatureData;

/**
 * Формирует базовые колонки строки Warehouse-номенклатуры без detail-полей.
 */
final readonly class NomenclatureExportRow implements NomenclatureExportRowInterface
{
    private const array MATERIAL_LABELS = [
        'NICKEL' => 'Никель',
        'PLATINUM' => 'Платина',
        'IRIDIUM' => 'Иридий',
        'DOUBLE_IRIDIUM' => 'Двойной иридий',
        'DOUBLE_PLATINUM' => 'Двойная платина',
        'DOUBLE_NICKEL' => 'Двойной никель',
        'STEEL' => 'Сталь',
        'METAL' => 'Металл',
        'ABS_PLASTIC' => 'ABS пластик',
        'PAPER' => 'Бумага',
        'RUBBER' => 'Резина',
        'PLASTIC' => 'Пластик',
    ];

    private const array VEHICLE_TYPE_LABELS = [
        'CAR' => 'Легковые автомобили',
        'TRUCK' => 'Коммерческий транспорт',
        'SUV' => 'Внедорожники',
        'BUS' => 'Грузовые автомобили и автобусы',
    ];

    /**
     * Возвращает базовые заголовки, общие для всех типов номенклатуры.
     *
     * @return array<int, string>
     */
    public function getBaseHeadings(): array
    {
        return [
            'ID',
            'Тип товара',
            'Бренд',
            'Название (От производителя)',
            'Страна',
            'Артикул',
            'Цвет',
            'Вес (грамм)',
            'Материал',
            'Вид техники / автотранспорта',
            'Кол-во упаковок',
            'Кол-во шт в упаковке',
        ];
    }

    /**
     * Собирает базовые значения номенклатуры до добавления template-specific detail-колонок.
     *
     * @return array<int, mixed>
     */
    public function getBaseData(NomenclatureData $nomenclature): array
    {
        return [
            $nomenclature->id,
            $nomenclature->type?->name,
            $nomenclature->brand?->name,
            $nomenclature->name,
            $nomenclature->country,
            $nomenclature->partNumber,
            $nomenclature->color,
            $nomenclature->weight,
            $this->formatList(
                values: $nomenclature->material,
                labels: self::MATERIAL_LABELS,
            ),
            $this->formatList(
                values: $nomenclature->vehicleType,
                labels: self::VEHICLE_TYPE_LABELS,
            ),
            $nomenclature->quantityPak,
            $nomenclature->quantityInPak,
        ];
    }

    /**
     * Преобразует массив сохранённых enum-ключей в строку Excel-лейблов через точку с запятой.
     *
     * @param  array<int, mixed>  $values
     * @param  array<string, string>  $labels
     */
    private function formatList(array $values, array $labels): string
    {
        $result = [];

        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $key = is_string($value) ? mb_strtoupper(trim($value)) : (string) $value;
            $result[] = $labels[$key] ?? (string) $value;
        }

        return implode(';', $result);
    }
}
