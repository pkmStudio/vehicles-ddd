<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Domain\ModelData\Nomenclature\GenericDetailsData;

/**
 * Строит форму шаблона `generic` (тип V_BELT) из Excel-строки — заглушка без полей, курсор не
 * двигается вообще. Простой класс без собственного порта.
 */
final readonly class GenericDetailsBuilder
{
    public function build(DetailsRowCursor $cursor): GenericDetailsData
    {
        return new GenericDetailsData;
    }
}
