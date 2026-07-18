<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Domain\ModelData\Nomenclature\GenericDetailsData;

/** Рендерит форму `generic` (тип V_BELT) — заглушка без полей, нет ни заголовков, ни ячеек. */
final readonly class GenericDetailsPresenter
{
    public function headings(): array
    {
        return [];
    }

    public function cells(GenericDetailsData $data): array
    {
        return [];
    }
}
