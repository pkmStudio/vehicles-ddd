<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\GenericDetailsData;

/** Рендерит форму `generic` (тип V_BELT) — заглушка без полей, нет ни заголовков, ни ячеек. */
final readonly class GenericDetailsPresenter extends AbstractDetailsPresenter
{
    public function headings(): array
    {
        return [];
    }

    /** @return class-string<GenericDetailsData> */
    protected function dataClass(): string
    {
        return GenericDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, GenericDetailsData::class);

        return [];
    }
}
