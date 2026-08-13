<?php

declare(strict_types=1);

namespace Tests\Unit\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperAdapterExtractor;
use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperDataExtractor;
use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperLengthExtractor;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Exceptions\InvalidWiperKitDataException;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\NomenclatureData;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use Tests\TestCase;

final class WiperInvalidKitDataExceptionTest extends TestCase
{
    public function test_missing_wiper_position_throws_typed_exception(): void
    {
        $extractor = new WiperDataExtractor(
            lengthExtractor: new WiperLengthExtractor,
            adapterExtractor: new WiperAdapterExtractor,
        );

        $this->expectException(InvalidWiperKitDataException::class);
        $this->expectExceptionMessage('No wiper position found for kit 10');

        $extractor->extractPosition(new KitData(
            id: 10,
            typeId: 3,
            quantityInPackage: 2,
            isActive: true,
            nomenclatures: [],
            template: NomenclatureDetailTemplateEnum::WIPER,
        ));
    }

    public function test_missing_sorted_wiper_throws_typed_exception(): void
    {
        $extractor = new WiperLengthExtractor;

        $this->expectException(InvalidWiperKitDataException::class);
        $this->expectExceptionMessage('Wiper nomenclature with sort 1 not found for kit 11');

        $extractor->extract(
            kit: new KitData(
                id: 11,
                typeId: 3,
                quantityInPackage: 2,
                isActive: true,
                nomenclatures: [
                    new NomenclatureData(
                        typeId: 3,
                        quantityInPak: 1,
                        details: ['length_main' => 600, 'length_rear' => 300, 'position' => 'front'],
                        id: 1,
                        sort: 0,
                        template: NomenclatureDetailTemplateEnum::WIPER,
                    ),
                ],
                template: NomenclatureDetailTemplateEnum::WIPER,
            ),
            position: WiperKitPositionEnum::FRONT,
        );
    }
}
