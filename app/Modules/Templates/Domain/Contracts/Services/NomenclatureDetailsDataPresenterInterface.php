<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Contracts\Services;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

/**
 * Обратная сторона `NomenclatureDetailsDataFactoryInterface`: рендерит уже сохранённые details
 * конкретного шаблона Nomenclature в плоский набор Excel-ячеек экспорта (плюс отдаёт заголовки
 * колонок).
 */
interface NomenclatureDetailsDataPresenterInterface
{
    /**
     * Этот метод должен вернуть Excel-заголовки nomenclature details-шаблона.
     * Шаги:
     * 1) Выбрать presenter по `NomenclatureDetailTemplateEnum`.
     * 2) Вернуть headings в порядке экспортных ячеек.
     *
     * @return array<int, string>
     */
    public function headingsFor(NomenclatureDetailTemplateEnum $template): array;

    /**
     * Этот метод должен вернуть справочники select-полей nomenclature details-шаблона.
     * Шаги:
     * 1) Выбрать enum-справочники, реально используемые колонками шаблона.
     * 2) Вернуть labels для Excel reference sheets.
     *
     * @return array<string, list<string>>
     */
    public function referenceOptionsFor(NomenclatureDetailTemplateEnum $template): array;

    /**
     * Этот метод должен отрендерить сохранённый nomenclature details JSON в Excel-ячейки.
     * Шаги:
     * 1) Выбрать presenter по `NomenclatureDetailTemplateEnum`.
     * 2) Восстановить typed Data из plain details-массива.
     * 3) Вернуть values в порядке `headingsFor()`.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, string|int|float|null>
     */
    public function toExportCells(NomenclatureDetailTemplateEnum $template, array $details): array;
}
