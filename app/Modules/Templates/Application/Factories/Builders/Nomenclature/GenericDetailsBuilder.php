<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Domain\ModelData\Nomenclature\GenericDetailsData;

/**
 * Строит форму шаблона `generic` (тип V_BELT) из Excel-строки — заглушка без полей, курсор не
 * двигается вообще. Простой класс без собственного порта.
 */
final readonly class GenericDetailsBuilder
{
    /**
     * Этот метод строит пустой details-объект для generic-шаблона.
     * Шаги:
     * 1) Не читает ячейки из курсора, потому что у шаблона нет details-полей.
     * 2) Возвращает пустой `GenericDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): GenericDetailsData
    {
        return new GenericDetailsData;
    }
}
