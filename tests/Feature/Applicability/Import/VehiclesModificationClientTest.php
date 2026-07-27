<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Import;

use App\Modules\Applicability\Features\Import\Infrastructure\Clients\VehiclesModificationClient;
use App\Modules\Applicability\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Applicability\Features\Import\Infrastructure\Models\Modification;
use App\Modules\Applicability\Features\Import\Infrastructure\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VehiclesModificationClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_modification_on_requested_vehicle_first(): void
    {
        $vehicle = $this->createVehicle(msId: 100);
        $modification = Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => 100,
            'mod_id' => 50,
            'type' => 'PC',
        ]);

        $resolvedId = app(VehiclesModificationClient::class)->resolveByMsAndModId(100, 50);

        $this->assertSame((int) $modification->id, $resolvedId);
    }

    public function test_falls_back_to_parent_modification_when_vehicle_has_no_requested_mod_id(): void
    {
        $parent = $this->createVehicle(msId: 200);
        $child = $this->createVehicle(msId: 201, parentId: $parent->id);
        $modification = Modification::query()->create([
            'vehicle_id' => $parent->id,
            'ms_id' => 200,
            'mod_id' => 70,
            'type' => 'PC',
        ]);

        $resolvedId = app(VehiclesModificationClient::class)->resolveByMsAndModId((int) $child->ms_id, 70);

        $this->assertSame((int) $modification->id, $resolvedId);
    }

    public function test_throws_when_modification_is_absent_on_vehicle_and_parent(): void
    {
        $parent = $this->createVehicle(msId: 300);
        $child = $this->createVehicle(msId: 301, parentId: $parent->id);

        $this->expectException(ImportRowValidationException::class);
        $this->expectExceptionMessage('не найдена ни у модели, ни у родителя');

        app(VehiclesModificationClient::class)->resolveByMsAndModId((int) $child->ms_id, 90);
    }

    private function createVehicle(int $msId, ?int $parentId = null): Vehicle
    {
        $manufacturerId = DB::table('manufacturers')->insertGetId([
            'mfa_id' => $msId,
            'name' => "Manufacturer {$msId}",
            'provider' => 'TD',
        ]);

        return Vehicle::query()->create([
            'parent_id' => $parentId,
            'manufacturer_id' => $manufacturerId,
            'mfa_id' => $msId,
            'ms_id' => $msId,
            'name' => "Vehicle {$msId}",
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
        ]);
    }
}
