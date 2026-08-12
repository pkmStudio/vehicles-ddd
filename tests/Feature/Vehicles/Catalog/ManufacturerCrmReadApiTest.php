<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerCrmReadQueryDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Проверяет REST read API производителей для CRM/Filament.
 */
final class ManufacturerCrmReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_api_requires_service_key_when_configured(): void
    {
        config(['services.dan_vehicles.read_api_key' => 'secret-key']);

        $this->getJson('/api/v1/crm/vehicles/manufacturers')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');

        $this->withHeader('X-Service-Key', 'secret-key')
            ->getJson('/api/v1/crm/vehicles/manufacturers')
            ->assertOk();
    }

    public function test_index_returns_filtered_sorted_paginated_manufacturers(): void
    {
        $skoda = $this->createManufacturer(name: 'Skoda', mfaId: 10, provider: ProviderEnum::TD);
        $toyota = $this->createManufacturer(name: 'Toyota', mfaId: 20, provider: ProviderEnum::TD);
        $this->createManufacturer(name: 'Local Brand', mfaId: -1, provider: ProviderEnum::OD);
        $this->createVehicle($skoda, msId: 1001);
        $this->createVehicle($skoda, msId: 1002);
        $this->createVehicle($toyota, msId: 2001);

        $response = $this->getJson('/api/v1/crm/vehicles/manufacturers?per_page=10&search=o&sort=-name&filter[provider]=TD');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Toyota')
            ->assertJsonPath('data.1.name', 'Skoda')
            ->assertJsonPath('data.1.mfa_id', 10)
            ->assertJsonPath('data.1.provider', 'TD')
            ->assertJsonPath('data.1.vehicles_count', 2);
    }

    public function test_show_returns_manufacturer_details(): void
    {
        $manufacturer = $this->createManufacturer(name: 'Skoda', mfaId: 10, provider: ProviderEnum::TD);
        $this->createVehicle($manufacturer, msId: 1001);

        $response = $this->getJson("/api/v1/crm/vehicles/manufacturers/{$manufacturer->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $manufacturer->id)
            ->assertJsonPath('data.mfa_id', 10)
            ->assertJsonPath('data.name', 'Skoda')
            ->assertJsonPath('data.provider', 'TD')
            ->assertJsonPath('data.vehicles_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'mfa_id',
                    'name',
                    'provider',
                    'vehicles_count',
                ],
            ]);
    }

    public function test_show_returns_not_found_for_missing_manufacturer(): void
    {
        $this->getJson('/api/v1/crm/vehicles/manufacturers/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Manufacturer not found.');
    }

    public function test_repository_returns_local_crm_read_dtos(): void
    {
        $manufacturer = $this->createManufacturer(name: 'Skoda', mfaId: 10, provider: ProviderEnum::TD);

        $repository = app(ManufacturerCrmRepositoryInterface::class);
        $query = new ManufacturerCrmReadQueryDTO(perPage: 10);
        $page = $repository->paginate($query);
        $detail = $repository->findById((int) $manufacturer->id);

        self::assertInstanceOf(ManufacturerCrmPageDTO::class, $page);
        self::assertInstanceOf(ManufacturerCrmListItemDTO::class, $page->data->first());
        self::assertInstanceOf(ManufacturerCrmListItemDTO::class, $detail);
    }

    private function createManufacturer(string $name, int $mfaId, ProviderEnum $provider): Manufacturer
    {
        return Manufacturer::query()->create([
            'mfa_id' => $mfaId,
            'name' => $name,
            'provider' => $provider->value,
        ]);
    }

    private function createVehicle(Manufacturer $manufacturer, int $msId): Vehicle
    {
        return Vehicle::query()->create([
            'parent_id' => null,
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => $manufacturer->mfa_id,
            'ms_id' => $msId,
            'name' => 'Octavia',
            'localized_name' => null,
            'excel_table_id' => null,
            'generation' => 'III',
            'generation_short' => 'A7',
            'generation_year_from' => 2013,
            'generation_year_to' => 2020,
            'type_carcase' => CarcaseTypeEnum::LIFTBACK->value,
            'type' => VehicleTypeEnum::PC->value,
            'steering_type' => SteeringTypeEnum::LEFT->value,
            'is_allow' => true,
            'provider' => ProviderEnum::TD->value,
        ]);
    }
}
