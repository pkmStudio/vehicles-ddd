<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Services;

use App\Modules\Applicability\Features\Export\Domain\DTOs\VehicleKitApplicabilityRowDTO;
use Illuminate\Support\Collection;

interface VehicleKitApplicabilityExportServiceInterface
{
    /**
     * Возвращает DTO-строки основного листа export-файла.
     *
     * Шаги:
     * 1. Получает строки из read boundary применяемости.
     * 2. Возвращает коллекцию, которую Excel adapter передаст в mapping.
     */
    public function getRows(): Collection;

    /**
     * Преобразует одну DTO-строку применяемости в массив Excel-ячеек.
     *
     * Шаги:
     * 1. Принимает строку из Excel iteration.
     * 2. Раскладывает значения в порядке заголовков основного листа.
     *
     * @return array<int, mixed>
     */
    public function mapRow(VehicleKitApplicabilityRowDTO $row): array;

    /**
     * Возвращает заголовки основного листа применяемости.
     *
     * Шаги:
     * 1. Описывает пользовательские названия колонок.
     * 2. Сохраняет порядок, которому соответствует `mapRow()`.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array;

    /**
     * Возвращает строки справочного листа export-файла.
     *
     * Шаги:
     * 1. Получает справочник допустимых значений из локального provider-а.
     * 2. Возвращает строки в формате Excel collection.
     */
    public function getReferenceRows(): Collection;

    /**
     * Возвращает заголовки справочного листа.
     *
     * Шаги:
     * 1. Описывает колонки справочных значений.
     * 2. Сохраняет порядок, которому соответствуют reference rows.
     *
     * @return array<int, string>
     */
    public function getReferenceHeadings(): array;
}
