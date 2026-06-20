<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Support;

/**
 * Маппинг колонок редактируемого листа двигателей в атрибуты модели Engine.
 */
final readonly class EngineMainSheetImportService
{
    /**
     * Редактируемые колонки: индекс колонки в Excel => поле модели Engine.
     */
    private const array EDITABLE_COLUMNS = [
        2 => 'engine_capacity',
        4 => 'eng_power_ps_start',
        5 => 'eng_power_ps_upto',
        6 => 'cylinder_count',
        7 => 'cylinder_diameter',
        8 => 'eng_number_of_valves',
    ];

    public function extractEditableAttributes(array $row): array
    {
        $attributes = [];

        foreach (self::EDITABLE_COLUMNS as $columnIndex => $field) {
            $attributes[$field] = $row[$columnIndex] ?? null;
        }

        return $attributes;
    }
}
