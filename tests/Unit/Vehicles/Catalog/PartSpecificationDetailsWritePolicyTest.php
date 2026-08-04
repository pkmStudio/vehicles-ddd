<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Catalog;

use App\Modules\Templates\Application\WiperSpecificationService;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Catalog\Application\Services\PartSpecification\PartSpecificationDetailsWritePolicy;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Psr\Log\NullLogger;
use Tests\TestCase;

final class PartSpecificationDetailsWritePolicyTest extends TestCase
{
    public function test_wiper_vehicle_details_remove_position_and_prune_empty_values(): void
    {
        $result = $this->policy()->apply(
            details: [
                'position' => 'front',
                'front' => [
                    'length' => 650,
                    'adapter_type_front' => ['A1', '', null],
                    'comment' => '',
                    'nested' => ['empty' => null],
                ],
            ],
            template: DetailTemplateEnum::WIPER,
            ownerType: PartableTypeEnum::VEHICLE,
            partSpecificationId: 10,
            operationId: 'details-policy-valid',
        );

        $this->assertTrue($result->valid);
        $this->assertSame([
            'front' => [
                'length' => 650,
                'adapter_type_front' => ['A1'],
            ],
        ], $result->details);
    }

    public function test_wiper_vehicle_details_reject_empty_payload(): void
    {
        $result = $this->policy()->apply(
            details: ['position' => 'front', 'front' => []],
            template: DetailTemplateEnum::WIPER,
            ownerType: PartableTypeEnum::VEHICLE,
            partSpecificationId: 11,
            operationId: 'details-policy-empty',
        );

        $this->assertFalse($result->valid);
        $this->assertSame('required', $result->errors[0]['rule']);
    }

    public function test_wiper_vehicle_details_reject_multiple_sides(): void
    {
        $result = $this->policy()->apply(
            details: [
                'front' => ['length' => 650],
                'back' => ['length' => 350],
            ],
            template: DetailTemplateEnum::WIPER,
            ownerType: PartableTypeEnum::VEHICLE,
            partSpecificationId: 12,
            operationId: 'details-policy-multiple-sides',
        );

        $this->assertFalse($result->valid);
        $this->assertSame('single_side', $result->errors[0]['rule']);
    }

    public function test_wiper_vehicle_details_reject_multiple_adapters(): void
    {
        $result = $this->policy()->apply(
            details: [
                'front' => [
                    'length' => 650,
                    'adapter_type_front' => ['A1', 'A2'],
                ],
            ],
            template: DetailTemplateEnum::WIPER,
            ownerType: PartableTypeEnum::VEHICLE,
            partSpecificationId: 13,
            operationId: 'details-policy-multiple-adapters',
        );

        $this->assertFalse($result->valid);
        $this->assertSame('single_adapter', $result->errors[0]['rule']);
    }

    public function test_non_wiper_details_remain_unchanged(): void
    {
        $details = ['thread' => ['diameter' => 'M14']];

        $result = $this->policy()->apply(
            details: $details,
            template: DetailTemplateEnum::SPARK_PLUGS,
            ownerType: PartableTypeEnum::ENGINE,
            partSpecificationId: 14,
            operationId: 'details-policy-non-wiper',
        );

        $this->assertTrue($result->valid);
        $this->assertSame($details, $result->details);
    }

    private function policy(): PartSpecificationDetailsWritePolicy
    {
        return new PartSpecificationDetailsWritePolicy(
            wipers: new WiperSpecificationService,
            logger: new NullLogger,
        );
    }
}
