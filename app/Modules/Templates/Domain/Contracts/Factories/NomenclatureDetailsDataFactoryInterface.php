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
    /**
     * Этот метод должен собрать typed nomenclature details из Excel-строки выбранного шаблона.
     * Шаги:
     * 1) Использовать `$template` для выбора номенклатурного builder-а.
     * 2) Читать значения из `$row`, начиная с текущего `$index`.
     * 3) Сдвинуть `$index` на позицию после прочитанных details-колонок.
     * 4) Вернуть типизированный `AbstractDetailsData`.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function make(NomenclatureDetailTemplateEnum $template, array $row, int &$index): AbstractDetailsData;
}
