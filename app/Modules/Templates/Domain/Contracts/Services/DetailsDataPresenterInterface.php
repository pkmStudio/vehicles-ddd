<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Contracts\Services;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;

/**
 * Обратная сторона `DetailsDataFactoryInterface`: рендерит уже сохранённые details конкретного
 * шаблона в плоский набор Excel-ячеек экспорта (плюс отдаёт заголовки колонок).
 */
interface DetailsDataPresenterInterface
{
    /**
     * Этот метод должен вернуть Excel-заголовки vehicle details-шаблона.
     * Шаги:
     * 1) Выбрать presenter по `DetailTemplateEnum`.
     * 2) Вернуть headings в том же порядке, в котором `toExportCells()` отдаёт значения.
     *
     * @return array<int, string>
     */
    public function headingsFor(DetailTemplateEnum $template): array;

    /**
     * Этот метод должен вернуть справочники select-полей vehicle details-шаблона.
     * Шаги:
     * 1) Выбрать enum-справочники, реально используемые колонками шаблона.
     * 2) Вернуть labels, пригодные для Excel reference sheets.
     *
     * @return array<string, list<string>>
     */
    public function referenceOptionsFor(DetailTemplateEnum $template): array;

    /**
     * Этот метод должен отрендерить сохранённый details JSON в Excel-ячейки.
     * Шаги:
     * 1) Выбрать presenter по `DetailTemplateEnum`.
     * 2) Восстановить typed Data из plain details-массива.
     * 3) Вернуть values в порядке `headingsFor()`.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, string|int|float|null>
     */
    public function toExportCells(DetailTemplateEnum $template, array $details): array;
}
