<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmRelationPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\PaginationMeta as WirePaginationMeta;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\VehicleCrmModificationEngineResource as WireVehicleCrmModificationEngineResource;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\VehicleCrmModificationResource as WireVehicleCrmModificationResource;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\VehicleCrmPartSpecificationResource as WireVehicleCrmPartSpecificationResource;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\VehicleCrmResource as WireVehicleCrmResource;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\VehicleCrmSearchItem as WireVehicleCrmSearchItem;
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

        $this->withHeader('X-Service-Key', 'wrong-key')
            ->getJson('/api/v1/crm/vehicles')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');

        $this->withHeader('X-Service-Key', 'secret-key')
            ->getJson('/api/v1/crm/vehicles')
            ->assertOk();
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
     * Проверяет, что REST list response совместим с опубликованным wire DTO.
     */
    public function test_index_response_matches_published_wire_contract(): void
    {
        $manufacturer = $this->createManufacturer(name: 'Skoda');
        $vehicle = $this->createVehicle(
            msId: 1004,
            manufacturer: $manufacturer,
            name: 'Karoq',
            isAllow: true,
        );

        $response = $this->getJson('/api/v1/crm/vehicles?per_page=10&filter[is_allow]=1');

        $response->assertOk();

        $wireVehicle = WireVehicleCrmResource::fromArray($response->json('data.0'));
        $wireMeta = WirePaginationMeta::fromArray($response->json('meta'));

        self::assertSame($response->json('data.0'), $wireVehicle->toArray());
        self::assertSame($response->json('meta'), $wireMeta->toArray());
        self::assertSame($vehicle->id, $wireVehicle->id);
    }

    /**
     * Проверяет, что detail endpoint возвращает только плоский снимок автомобиля.
     */
    public function test_show_returns_flat_vehicle_details(): void
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
            ->assertJsonMissingPath('data.modifications')
            ->assertJsonMissingPath('data.part_specifications');
    }

    /**
     * Проверяет отдельный endpoint модификаций автомобиля.
     */
    public function test_modifications_endpoint_returns_vehicle_modifications_without_engines(): void
    {
        $manufacturer = $this->createManufacturer();
        $vehicle = $this->createVehicle(msId: 1102, manufacturer: $manufacturer, name: 'Octavia');
        $modificationId = $this->createModification(vehicleId: (int) $vehicle->id);

        $response = $this->getJson("/api/v1/crm/vehicles/{$vehicle->id}/modifications?per_page=10&sort=year_from");

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $modificationId)
            ->assertJsonPath('data.0.vehicle_id', $vehicle->id)
            ->assertJsonPath('data.0.description', '1.4 TSI')
            ->assertJsonPath('data.0.allow_change_fields.0', 'year_from')
            ->assertJsonMissingPath('data.0.engines');

        $wireModification = WireVehicleCrmModificationResource::fromArray($response->json('data.0'));
        $wireMeta = WirePaginationMeta::fromArray($response->json('meta'));

        self::assertSame($response->json('data.0'), $wireModification->toArray());
        self::assertSame($response->json('meta'), $wireMeta->toArray());
    }

    /**
     * Проверяет отдельный endpoint двигателей автомобиля с id модификации для сборки формы CRM.
     */
    public function test_engines_endpoint_returns_vehicle_engines_with_modification_id(): void
    {
        $manufacturer = $this->createManufacturer();
        $vehicle = $this->createVehicle(msId: 1103, manufacturer: $manufacturer, name: 'Octavia');
        $engineId = $this->createEngine();
        $modificationId = $this->createModification(vehicleId: (int) $vehicle->id);
        $this->attachEngineToModification(
            engineId: $engineId,
            modificationId: $modificationId,
        );

        $response = $this->getJson("/api/v1/crm/vehicles/{$vehicle->id}/engines?per_page=10&sort=code_engine");

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $engineId)
            ->assertJsonPath('data.0.modification_id', $modificationId)
            ->assertJsonPath('data.0.code_engine', 'CZDA')
            ->assertJsonPath('data.0.relation_provider', ProviderEnum::TD->value)
            ->assertJsonPath('data.0.allow_change_fields', []);

        $wireEngine = WireVehicleCrmModificationEngineResource::fromArray($response->json('data.0'));
        $wireMeta = WirePaginationMeta::fromArray($response->json('meta'));

        self::assertSame($response->json('data.0'), $wireEngine->toArray());
        self::assertSame($response->json('meta'), $wireMeta->toArray());
    }

    /**
     * Проверяет отдельный endpoint спецификаций деталей автомобиля.
     */
    public function test_part_specifications_endpoint_returns_vehicle_part_specifications(): void
    {
        $manufacturer = $this->createManufacturer();
        $vehicle = $this->createVehicle(msId: 1104, manufacturer: $manufacturer, name: 'Octavia');
        $this->createPartSpecification(vehicleId: (int) $vehicle->id);

        $response = $this->getJson("/api/v1/crm/vehicles/{$vehicle->id}/part-specifications?per_page=10&sort=template");

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.partable_type', 'vehicle')
            ->assertJsonPath('data.0.partable_id', $vehicle->id)
            ->assertJsonPath('data.0.template', 'wiper')
            ->assertJsonPath('data.0.details.front.adapter_type_front.0', 'A1');

        $wireSpecification = WireVehicleCrmPartSpecificationResource::fromArray($response->json('data.0'));
        $wireMeta = WirePaginationMeta::fromArray($response->json('meta'));

        self::assertSame($response->json('data.0'), $wireSpecification->toArray());
        self::assertSame($response->json('meta'), $wireMeta->toArray());
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
     * Проверяет, что REST search response совместим с опубликованным wire DTO.
     */
    public function test_search_response_matches_published_wire_contract(): void
    {
        $manufacturer = $this->createManufacturer(name: 'Skoda');
        $vehicle = $this->createVehicle(msId: 1204, manufacturer: $manufacturer, name: 'Kodiaq');

        $response = $this->getJson('/api/v1/crm/vehicles/search?q=Kodiaq&limit=1');

        $response->assertOk();

        $wireItem = WireVehicleCrmSearchItem::fromArray($response->json('data.0'));

        self::assertSame($response->json('data.0'), $wireItem->toArray());
        self::assertSame($vehicle->id, $wireItem->id);
    }

    /**
     * Проверяет option endpoints для CRM-формы.
     */
    public function test_option_endpoints_return_features_values_and_detail_templates(): void
    {
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
        self::assertInstanceOf(VehicleCrmListItemDTO::class, $detail);
        self::assertInstanceOf(VehicleCrmRelationPageDTO::class, $repository->modifications((int) $vehicle->id, $query));
        self::assertInstanceOf(VehicleCrmRelationPageDTO::class, $repository->engines((int) $vehicle->id, $query));
        self::assertInstanceOf(VehicleCrmRelationPageDTO::class, $repository->partSpecifications((int) $vehicle->id, $query));
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
            'power_kw_start' => 110,
            'power_ps_start' => 150,
            'fuel_type' => EngineFuelTypeEnum::PETROL->value,
            'engine_capacity' => 1.4,
            'cylinder_count' => 4,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => json_encode([]),
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
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => json_encode(['year_from', 'year_to']),
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
            'provider' => ProviderEnum::TD->value,
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
