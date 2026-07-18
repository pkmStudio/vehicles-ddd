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
    /** @return array<int, string> */
    public function headingsFor(DetailTemplateEnum $template): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function toExportCells(DetailTemplateEnum $template, array $details): array;
}
