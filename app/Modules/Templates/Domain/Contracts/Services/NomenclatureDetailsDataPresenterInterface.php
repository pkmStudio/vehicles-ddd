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
    /** @return array<int, string> */
    public function headingsFor(NomenclatureDetailTemplateEnum $template): array;

    /** @return array<string, list<string>> */
    public function referenceOptionsFor(NomenclatureDetailTemplateEnum $template): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function toExportCells(NomenclatureDetailTemplateEnum $template, array $details): array;
}
