<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Traits;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Domain\Enums\BooleanOptionEnum;

/**
 * Читает булево поле, представленное в Excel как select ("Да"/"Нет"), сразу как `?bool` — не
 * `BooleanOptionEnum`, он наружу не течёт (см. докблок enum'а). Симметрично
 * `FormatsExportCells::boolToLabelCell()` на стороне презентера.
 */
trait ParsesBooleanCells
{
    private function pullBoolLabel(DetailsRowCursor $cursor): ?bool
    {
        $case = $cursor->pullLabel(BooleanOptionEnum::class);

        return $case === null ? null : $case->name === BooleanOptionEnum::TRUE->name;
    }
}
