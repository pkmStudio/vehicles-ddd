<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application\Factories\Builders;

use App\Vehicles\Templates\Application\Factories\DetailsRowCursor;
use App\Vehicles\Templates\Domain\Enums\Filter\FormEnum;
use App\Vehicles\Templates\Domain\ModelData\Engine\AirFilterDetailsData;

/**
 * Строит форму шаблона `airFilter` из Excel-строки (не подключена ни к одному Import/Export
 * сценарию — см. докблок `AirFilterDetailsData`, портируется без покрытия тестами). Простой
 * класс без собственного порта — вызывается только из `DetailsDataFactory`.
 */
final readonly class AirFilterDetailsBuilder
{
    public function build(DetailsRowCursor $cursor): AirFilterDetailsData
    {
        return new AirFilterDetailsData(
            form: $cursor->pullLabel(FormEnum::class)?->name,
            length: $cursor->pullFloatArray(),
            width: $cursor->pullFloatArray(),
            height: $cursor->pullFloatArray(),
            diameter: $cursor->pullFloatCell(),
        );
    }
}
