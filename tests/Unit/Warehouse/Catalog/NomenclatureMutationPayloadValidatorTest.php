<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\NomenclatureMutationPayloadValidator;
use Tests\TestCase;

final class NomenclatureMutationPayloadValidatorTest extends TestCase
{
    public function test_create_payload_requires_material_and_vehicle_type_values(): void
    {
        $validator = app(NomenclatureMutationPayloadValidator::class);

        $result = $validator->make([
            ...$this->validCreatePayload(),
            'nomenclature' => [
                ...$this->validCreatePayload()['nomenclature'],
                'material' => [],
                'vehicle_type' => [],
            ],
        ]);

        self::assertTrue($result->fails());
        self::assertArrayHasKey('nomenclature.material', $result->errors()->messages());
        self::assertArrayHasKey('nomenclature.vehicle_type', $result->errors()->messages());
    }

    public function test_create_payload_accepts_material_and_vehicle_type_values(): void
    {
        $validator = app(NomenclatureMutationPayloadValidator::class);

        self::assertFalse($validator->make($this->validCreatePayload())->fails());
    }

    /**
     * @return array<string, mixed>
     */
    private function validCreatePayload(): array
    {
        return [
            'user_id' => 1,
            'operation_id' => 'test-operation',
            'operation' => WarehouseCatalogMutationOperationEnum::Create->value,
            'nomenclature' => [
                'type_id' => 1,
                'brand_id' => 1,
                'name' => 'Test',
                'country' => 'CN',
                'part_number' => 'TEST-1',
                'color' => 'black',
                'weight' => 100,
                'material' => ['rubber'],
                'vehicle_type' => ['PC'],
                'quantity_pak' => 1,
                'quantity_in_pak' => 1,
                'details' => [],
            ],
        ];
    }
}
