<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Проверяет REST API справочника ТС для dan-catalog.
 */
final class VehicleCatalogRestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_rest_api_requires_its_own_service_key(): void
    {
        config(['services.dan_catalog.read_api_key' => 'catalog-secret']);

        $this->getJson('/api/v1/catalog/manufacturers')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');

        $this->withHeader('X-Service-Key', 'wrong-secret')
            ->getJson('/api/v1/catalog/manufacturers')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');

        $this->withHeader('X-Service-Key', 'catalog-secret')
            ->getJson('/api/v1/catalog/manufacturers')
            ->assertOk();
    }

    public function test_manufacturer_index_returns_only_manufacturers_with_allowed_vehicles(): void
    {
        $skoda = $this->createManufacturer(name: 'Skoda', mfaId: 10);
        $audi = $this->createManufacturer(name: 'Audi', mfaId: 20);
        $hidden = $this->createManufacturer(name: 'Hidden', mfaId: 30);

        $this->createVehicle($skoda, msId: 1001, name: 'Octavia', isAllow: true);
        $this->createVehicle($audi, msId: 1002, name: 'A4', isAllow: true);
        $this->createVehicle($hidden, msId: 1003, name: 'Secret', isAllow: false);

        $this->getJson('/api/v1/catalog/manufacturers')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Audi')
            ->assertJsonPath('data.1.name', 'Skoda')
            ->assertJsonMissing(['name' => 'Hidden']);
    }

    public function test_manufacturer_vehicles_is_a_filtered_nested_resource(): void
    {
        $manufacturer = $this->createManufacturer();
        $allowed = $this->createVehicle($manufacturer, msId: 2001, name: 'Octavia', isAllow: true);
        $this->createVehicle($manufacturer, msId: 2002, name: 'Hidden', isAllow: false);

        $this->getJson("/api/v1/catalog/manufacturers/{$manufacturer->id}/vehicles")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allowed->id)
            ->assertJsonPath('data.0.ms_id', 2001)
            ->assertJsonPath('data.0.manufacturer_id', $manufacturer->id)
            ->assertJsonPath('data.0.type_carcase', CarcaseTypeEnum::HATCHBACK->value)
            ->assertJsonMissing(['name' => 'Hidden']);

        $this->getJson('/api/v1/catalog/manufacturers/999999/vehicles')
            ->assertNotFound()
            ->assertJsonPath('message', 'Manufacturer not found.');
    }

    public function test_vehicle_modifications_and_modification_detail_follow_rest_contract(): void
    {
        $manufacturer = $this->createManufacturer(name: 'Skoda', mfaId: 111);
        $vehicle = $this->createVehicle(
            manufacturer: $manufacturer,
            msId: 3001,
            name: 'Octavia',
            isAllow: true,
        );
        $modificationId = $this->createModification(
            vehicle: $vehicle,
            modId: 7001,
            yearFrom: 2018,
            description: '1.4 TSI',
        );

        $this->getJson("/api/v1/catalog/vehicles/{$vehicle->id}/modifications")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $modificationId)
            ->assertJsonPath('data.0.vehicle_id', $vehicle->id)
            ->assertJsonPath('data.0.mod_id', 7001)
            ->assertJsonPath('data.0.description', '1.4 TSI');

        $this->getJson("/api/v1/catalog/modifications/{$modificationId}")
            ->assertOk()
            ->assertJsonPath('data.manufacturer.id', $manufacturer->id)
            ->assertJsonPath('data.manufacturer.mfa_id', 111)
            ->assertJsonPath('data.vehicle.id', $vehicle->id)
            ->assertJsonPath('data.vehicle.ms_id', 3001)
            ->assertJsonPath('data.vehicle.type_carcase', CarcaseTypeEnum::HATCHBACK->value)
            ->assertJsonPath('data.modification.id', $modificationId);

        $this->getJson('/api/v1/catalog/modifications/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Modification not found.');
    }

    public function test_hidden_vehicle_does_not_expose_modifications(): void
    {
        $manufacturer = $this->createManufacturer();
        $vehicle = $this->createVehicle($manufacturer, msId: 4001, name: 'Hidden', isAllow: false);
        $modificationId = $this->createModification($vehicle, modId: 8001);

        $this->getJson("/api/v1/catalog/vehicles/{$vehicle->id}/modifications")
            ->assertNotFound()
            ->assertJsonPath('message', 'Vehicle not found.');

        $this->getJson("/api/v1/catalog/modifications/{$modificationId}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Modification not found.');
    }

    private function createManufacturer(string $name = 'Skoda', int $mfaId = 10): Manufacturer
    {
        return Manufacturer::query()->create([
            'mfa_id' => $mfaId,
            'name' => $name,
            'provider' => ProviderEnum::TD->value,
        ]);
    }

    private function createVehicle(
        Manufacturer $manufacturer,
        int $msId,
        string $name,
        bool $isAllow,
    ): Vehicle {
        return Vehicle::query()->create([
            'parent_id' => null,
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => $manufacturer->mfa_id,
            'ms_id' => $msId,
            'name' => $name,
            'localized_name' => null,
            'excel_table_id' => null,
            'generation' => 'III',
            'generation_short' => 'A7',
            'generation_year_from' => 2013,
            'generation_year_to' => 2020,
            'type' => VehicleTypeEnum::PC->value,
            'type_carcase' => CarcaseTypeEnum::HATCHBACK->value,
            'provider' => ProviderEnum::OD->value,
            'steering_type' => SteeringTypeEnum::LEFT->value,
            'is_allow' => $isAllow,
        ]);
    }

    private function createModification(
        Vehicle $vehicle,
        int $modId,
        int $yearFrom = 2018,
        string $description = '2.0',
    ): int {
        return (int) DB::table('modifications')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'ms_id' => $vehicle->ms_id,
            'mod_id' => $modId,
            'type' => VehicleTypeEnum::PC->value,
            'year_from' => $yearFrom,
            'year_to' => 2024,
            'description' => $description,
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'capacity_lt' => 2.0,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => json_encode(['year_from', 'year_to']),
        ]);
    }
}
