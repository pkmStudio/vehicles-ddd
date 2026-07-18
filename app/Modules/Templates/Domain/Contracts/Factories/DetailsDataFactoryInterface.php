<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Contracts\Factories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Selector-фабрика: выбирает, как собрать details конкретного шаблона из Excel-строки.
 * Симметрична `ExternalFileImportFactory::make(ExternalImportTypeEnum): FileImportInterface` —
 * только `match` по enum, без валидации/сборки на уровне интерфейса (сборка — в реализации).
 *
 * Возвращает типизированный `AbstractDetailsData`, не `array` — вызывающий сам решает, когда
 * и если вообще превращать в массив (`->toArray()`) перед тем, как положить в
 * `PartSpecificationData::$details`.
 */
interface DetailsDataFactoryInterface
{
    public function buildFromRow(DetailTemplateEnum $template, array $row, int &$index): AbstractDetailsData;
}
