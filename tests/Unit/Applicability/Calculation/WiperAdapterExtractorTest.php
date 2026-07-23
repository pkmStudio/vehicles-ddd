<?php

declare(strict_types=1);

namespace Tests\Unit\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperAdapterExtractor;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\NomenclatureData;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use Tests\TestCase;

final class WiperAdapterExtractorTest extends TestCase
{
    public function test_put_adapters_are_intersected_by_value_not_numeric_key(): void
    {
        $kit = new KitData(
            id: 10,
            typeId: 3,
            quantityInPackage: 2,
            isActive: true,
            nomenclatures: [
                new NomenclatureData(
                    typeId: 3,
                    quantityInPak: 1,
                    details: ['adapter_type_front' => ['B']],
                    id: 1,
                    sort: 0,
                    template: NomenclatureDetailTemplateEnum::WIPER,
                ),
                new NomenclatureData(
                    typeId: 7,
                    quantityInPak: 1,
                    details: ['adapter_type_front' => ['A', 'B']],
                    id: 2,
                    sort: 1,
                    template: NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
                ),
            ],
            template: NomenclatureDetailTemplateEnum::WIPER,
        );

        $adapters = (new WiperAdapterExtractor)->extract($kit, WiperKitPositionEnum::FRONT);

        $this->assertSame(['B'], $adapters->allAdapters);
        $this->assertSame(['B'], $adapters->putAdapters);
    }
}
