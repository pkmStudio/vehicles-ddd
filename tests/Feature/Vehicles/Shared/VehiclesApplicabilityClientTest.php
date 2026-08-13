<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Shared;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Shared\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VehiclesApplicabilityClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_wiper_specifications_are_returned_as_support_collection(): void
    {
        $vehicleId = $this->createVehicle();
        DB::table('part_specifications')->insert([
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'partable_id' => $vehicleId,
            'template' => DetailTemplateEnum::WIPER->value,
            'details' => json_encode([
                'front' => [
                    'length_main' => ['min' => 500, 'max' => 500],
                    'length_second' => ['min' => 450, 'max' => 450],
                    'count_wipers' => 2,
                    'adapter_type_front' => ['A'],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $specifications = app(VehiclesApplicabilityClientInterface::class)
            ->frontWiperSpecifications(lengthMain: 500, lengthSecond: 450, countWipers: 2);

        $this->assertNotInstanceOf(EloquentCollection::class, $specifications);
        $this->assertCount(1, $specifications);
    }

    private function createVehicle(): int
    {
        $manufacturerId = (int) DB::table('manufacturers')->insertGetId([
            'mfa_id' => 100,
            'name' => 'Toyota',
        ]);

        return (int) DB::table('vehicles')->insertGetId([
            'manufacturer_id' => $manufacturerId,
            'mfa_id' => 100,
            'ms_id' => 200,
            'name' => 'Corolla',
            'generation' => 'E150',
            'generation_year_from' => 2006,
            'generation_year_to' => 2013,
            'type' => VehicleTypeEnum::PC->value,
            'type_carcase' => CarcaseTypeEnum::SALOON->value,
            'is_allow' => true,
        ]);
    }
}
