<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Contracts\Factories;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Selector-фабрика: выбирает, как собрать details конкретного шаблона Nomenclature из
 * Excel-строки. Симметрична `DetailsDataFactoryInterface` (та — про PartSpecification), но по
 * отдельному enum'у — Nomenclature и PartSpecification описывают разные вещи, даже когда шаблон
 * называется одинаково (см. докблок `NomenclatureDetailTemplateEnum`).
 */
interface NomenclatureDetailsDataFactoryInterface
{
    public function buildFromRow(NomenclatureDetailTemplateEnum $template, array $row, int &$index): AbstractDetailsData;
}
