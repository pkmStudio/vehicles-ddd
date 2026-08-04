<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Проверяет REST read API Vehicles/Catalog для CRM.
 */
final class VehicleCrmReadApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что read API закрывается service key, когда он задан в конфиге.
     */
    public function test_read_api_requires_service_key_when_configured(): void
    {
        config(['services.dan_vehicles.read_api_key' => 'secret-key']);

        $response = $this->getJson('/api/v1/crm/vehicles');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');
    }

    /**
     * Проверяет список ТС: фильтры, сортировку и meta пагинации.
     */
    public function test_index_returns_filtered_sorted_paginated_vehicles(): void
    {
        $manufacturer = $this->createManufacturer(name: 'Skoda');
        $this->createVehicle(
            msId: 1001,
            manufacturer: $manufacturer,
            name: 'Octavia',
            isAllow: true,
        );
        $this->createVehicle(
            msId: 1002,
            manufacturer: $manufacturer,
            name: 'Fabia',
            isAllow: true,
        );
        $this->createVehicle(
            msId: 1003,
            manufacturer: $manufacturer,
            name: 'Superb',
            isAllow: false,
        );

        $response = $this->getJson('/api/v1/crm/vehicles?per_page=10&filter[is_allow]=1&sort=-name');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Octavia')
            ->assertJsonPath('data.1.name', 'Fabia')
            ->assertJsonPath('data.0.manufacturer_name', 'Skoda');
    }

    /**
     * Проверяет detail endpoint с вложенными модификациями, двигателями и спецификациями.
     */
    public function test_show_returns_vehicle_details_with_nested_read_data(): void
    {
        $manufacturer = $this->createManufacturer();
        $vehicle = $this->createVehicle(msId: 1101, manufacturer: $manufacturer, name: 'Octavia');
        $engineId = $this->createEngine();
        $modificationId = $this->createModification(vehicleId: (int) $vehicle->id);
        $this->attachEngineToModification(
            engineId: $engineId,
            modificationId: $modificationId,
        );
        $this->createPartSpecification(vehicleId: (int) $vehicle->id);

        $response = $this->getJson("/api/v1/crm/vehicles/{$vehicle->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $vehicle->id)
            ->assertJsonPath('data.ms_id', 1101)
            ->assertJsonPath('data.modifications.0.id', $modificationId)
            ->assertJsonPath('data.modifications.0.engines.0.id', $engineId)
            ->assertJsonPath('data.part_specifications.0.template', 'wiper')
            ->assertJsonPath('data.part_specifications.0.details.front.adapter_type_front.0', 'A1');
    }

    /**
     * Проверяет 404 для отсутствующего ТС.
     */
    public function test_show_returns_not_found_for_missing_vehicle(): void
    {
        $response = $this->getJson('/api/v1/crm/vehicles/999999');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Vehicle not found.');
    }

    /**
     * Проверяет search endpoint и ограничение limit.
     */
    public function test_search_returns_limited_vehicle_options(): void
    {
        $manufacturer = $this->createManufacturer(name: 'Skoda');
        $this->createVehicle(msId: 1201, manufacturer: $manufacturer, name: 'Octavia Scout');
        $this->createVehicle(msId: 1202, manufacturer: $manufacturer, name: 'Octavia RS');
        $this->createVehicle(msId: 1203, manufacturer: $manufacturer, name: 'Fabia');

        $response = $this->getJson('/api/v1/crm/vehicles/search?q=Octavia&limit=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', '1202 | Skoda Octavia RS III |  (2013-2020)')
            ->assertJsonPath('data.0.ms_id', 1202);
    }

    /**
     * Проверяет option endpoints для CRM-формы.
     */
    public function test_option_endpoints_return_features_values_and_detail_templates(): void
    {
        $manufacturer = $this->createManufacturer(
            name: 'Skoda',
            mfaId: 111,
        );
        $featureId = $this->createFeature();

        $this->createFeatureValue($featureId);

        $this->getJson('/api/v1/crm/vehicles/options/features')
            ->assertOk()
            ->assertJsonPath('data.0.id', $featureId)
            ->assertJsonPath('data.0.label', 'Крепление');

        $this->getJson("/api/v1/crm/vehicles/options/feature-values?feature_id={$featureId}")
            ->assertOk()
            ->assertJsonPath('data.0.short_code', 'HOOK');

        $this->getJson('/api/v1/crm/vehicles/options/detail-templates')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'wiper');

        $this->getJson('/api/v1/crm/vehicles/options/manufacturers?q=Skoda')
            ->assertOk()
            ->assertJsonPath('data.0.id', $manufacturer->id)
            ->assertJsonPath('data.0.mfa_id', 111)
            ->assertJsonPath('data.0.label', 'Skoda');
    }

    /**
     * Проверяет, что CRM read repository отдаёт локальные DTO, а не raw arrays.
     */
    public function test_repository_returns_local_crm_read_dtos(): void
    {
        $manufacturer = $this->createManufacturer(name: 'Skoda');
        $vehicle = $this->createVehicle(
            msId: 1301,
            manufacturer: $manufacturer,
            name: 'Kodiaq',
        );
        $featureId = $this->createFeature();
        $this->createFeatureValue($featureId);

        $repository = app(VehicleCrmRepositoryInterface::class);
        $query = new VehicleCrmReadQueryDTO(perPage: 10);
        $page = $repository->paginate($query);
        $detail = $repository->findById((int) $vehicle->id);
        $search = $repository->search('Kodiaq');
        $features = $repository->featureOptions();

        self::assertInstanceOf(VehicleCrmPageDTO::class, $page);
        self::assertInstanceOf(VehicleCrmDetailDTO::class, $detail);
        self::assertInstanceOf(VehicleCrmSearchItemDTO::class, $search->first());
        self::assertInstanceOf(VehicleCrmFeatureOptionDTO::class, $features->first());
    }

    /**
     * Создаёт производителя для read API тестов.
     */
    private function createManufacturer(string $name = 'Skoda', int $mfaId = 10): Manufacturer
    {
        return Manufacturer::query()->create([
            'mfa_id' => $mfaId,
            'name' => $name,
            'provider' => ProviderEnum::TD->value,
        ]);
    }

    /**
     * Создаёт feature для option endpoint тестов.
     */
    private function createFeature(): int
    {
        return (int) DB::table('features')->insertGetId([
            'entity_type' => 'vehicle',
            'name' => 'Крепление',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Создаёт feature value для option endpoint тестов.
     */
    private function createFeatureValue(int $featureId): void
    {
        DB::table('feature_values')->insert([
            'feature_id' => $featureId,
            'name' => 'Крючок',
            'short_code' => 'HOOK',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Создаёт ТС для read API тестов.
     */
    private function createVehicle(
        int $msId,
        Manufacturer $manufacturer,
        string $name,
        bool $isAllow = false,
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

    /**
     * Создаёт двигатель и возвращает его id.
     */
    private function createEngine(): int
    {
        return (int) DB::table('engines')->insertGetId([
            'eng_id' => 5001,
            'code_engine' => 'CZDA',
            'engine_capacity' => '1.4',
            'cylinder_count' => 4,
        ]);
    }

    /**
     * Создаёт модификацию и возвращает её id.
     */
    private function createModification(int $vehicleId): int
    {
        return (int) DB::table('modifications')->insertGetId([
            'vehicle_id' => $vehicleId,
            'ms_id' => 1101,
            'mod_id' => 7001,
            'type' => VehicleTypeEnum::PC->value,
            'year_from' => 2013,
            'year_to' => 2020,
            'description' => '1.4 TSI',
        ]);
    }

    /**
     * Связывает двигатель с модификацией.
     */
    private function attachEngineToModification(int $engineId, int $modificationId): void
    {
        DB::table('engine_modification')->insert([
            'engine_id' => $engineId,
            'modification_id' => $modificationId,
            'eng_id' => 5001,
            'mod_id' => 7001,
            'type' => VehicleTypeEnum::PC->value,
        ]);
    }

    /**
     * Создаёт спецификацию дворников для ТС.
     */
    private function createPartSpecification(int $vehicleId): void
    {
        DB::table('part_specifications')->insert([
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'partable_id' => $vehicleId,
            'template' => 'wiper',
            'name' => 'front',
            'details' => json_encode(['front' => ['adapter_type_front' => ['A1']]]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
