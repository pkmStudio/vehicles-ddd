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
    /**
     * Этот метод читает optional boolean-select ячейку.
     * Шаги:
     * 1) Читает label через `DetailsRowCursor::pullLabel(BooleanOptionEnum::class)`.
     * 2) Для пустой ячейки возвращает null.
     * 3) Для заполненной ячейки возвращает true только для case `TRUE`.
     */
    private function pullBoolLabel(DetailsRowCursor $cursor): ?bool
    {
        $case = $cursor->pullLabel(BooleanOptionEnum::class);

        return $case === null ? null : $case->name === BooleanOptionEnum::TRUE->name;
    }

    /**
     * Этот метод читает обязательную boolean-select ячейку.
     * Шаги:
     * 1) Читает обязательный label через `pullRequiredLabel()`.
     * 2) Возвращает true для case `TRUE`, false для остальных валидных boolean cases.
     */
    private function pullRequiredBoolLabel(DetailsRowCursor $cursor, string $field): bool
    {
        $case = $cursor->pullRequiredLabel(BooleanOptionEnum::class, $field);

        return $case->name === BooleanOptionEnum::TRUE->name;
    }
}
