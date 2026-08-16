<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineCrmReadQueryDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\EngineCrmResource as WireEngineCrmResource;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\PaginationMeta as WirePaginationMeta;
use Tests\TestCase;

/**
 * Проверяет REST read API двигателей для CRM/Filament.
 */
final class EngineCrmReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_api_requires_service_key_when_configured(): void
    {
        config(['services.dan_vehicles.read_api_key' => 'secret-key']);

        $this->getJson('/api/v1/crm/vehicles/engines')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');

        $this->withHeader('X-Service-Key', 'secret-key')
            ->getJson('/api/v1/crm/vehicles/engines')
            ->assertOk();
    }

    public function test_index_returns_filtered_sorted_paginated_engines(): void
    {
        $czda = $this->createEngine(engId: 5001, codeEngine: 'CZDA', capacity: '1.4');
        $dkza = $this->createEngine(engId: 5002, codeEngine: 'DKZA', capacity: '2.0');
        $this->createEngine(engId: 5003, codeEngine: 'ABC', capacity: '1.6', provider: ProviderEnum::OD);
        $this->attachModification($czda);
        $this->attachModification($czda);
        $this->attachModification($dkza);

        $response = $this->getJson('/api/v1/crm/vehicles/engines?per_page=10&search=Z&sort=-code_engine&filter[provider]=TD');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.code_engine', 'DKZA')
            ->assertJsonPath('data.1.code_engine', 'CZDA')
            ->assertJsonPath('data.1.eng_id', 5001)
            ->assertJsonPath('data.1.engine_capacity', '1.4')
            ->assertJsonPath('data.1.modifications_count', 2);

        $wireEngine = WireEngineCrmResource::fromArray($response->json('data.0'));
        $wireMeta = WirePaginationMeta::fromArray($response->json('meta'));

        self::assertSame($response->json('data.0'), $wireEngine->toArray());
        self::assertSame($response->json('meta'), $wireMeta->toArray());
    }

    public function test_show_returns_engine_details(): void
    {
        $engine = $this->createEngine(engId: 5001, codeEngine: 'CZDA', capacity: '1.4');
        $this->attachModification($engine);

        $response = $this->getJson("/api/v1/crm/vehicles/engines/{$engine->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $engine->id)
            ->assertJsonPath('data.eng_id', 5001)
            ->assertJsonPath('data.code_engine', 'CZDA')
            ->assertJsonPath('data.fuel_type', EngineFuelTypeEnum::PETROL->value)
            ->assertJsonPath('data.provider', ProviderEnum::TD->value)
            ->assertJsonPath('data.allow_change_fields', [])
            ->assertJsonPath('data.modifications_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'eng_id',
                    'code_engine',
                    'engine_capacity',
                    'cylinder_count',
                    'cylinder_diameter',
                    'power_kw_start',
                    'power_kw_upto',
                    'power_ps_start',
                    'power_ps_upto',
                    'number_of_valves',
                    'fuel_type',
                    'group_id',
                    'provider',
                    'allow_change_fields',
                    'modifications_count',
                ],
            ]);
    }

    public function test_show_returns_not_found_for_missing_engine(): void
    {
        $this->getJson('/api/v1/crm/vehicles/engines/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Engine not found.');
    }

    public function test_repository_returns_local_crm_read_dtos(): void
    {
        $engine = $this->createEngine(engId: 5001, codeEngine: 'CZDA', capacity: '1.4');

        $repository = app(EngineCrmRepositoryInterface::class);
        $query = new EngineCrmReadQueryDTO(perPage: 10);
        $page = $repository->paginate($query);
        $detail = $repository->findById((int) $engine->id);

        self::assertInstanceOf(EngineCrmPageDTO::class, $page);
        self::assertInstanceOf(EngineCrmListItemDTO::class, $page->data->first());
        self::assertInstanceOf(EngineCrmListItemDTO::class, $detail);
    }

    private function createEngine(
        int $engId,
        string $codeEngine,
        string $capacity,
        ProviderEnum $provider = ProviderEnum::TD,
    ): Engine {
        return Engine::query()->create([
            'eng_id' => $engId,
            'code_engine' => $codeEngine,
            'engine_capacity' => $capacity,
            'cylinder_count' => 4,
            'cylinder_diameter' => 74.5,
            'power_kw_start' => 110,
            'power_kw_upto' => 110,
            'power_ps_start' => 150,
            'power_ps_upto' => 150,
            'number_of_valves' => 16,
            'fuel_type' => EngineFuelTypeEnum::PETROL->value,
            'provider' => $provider->value,
            'allow_change_fields' => [],
        ]);
    }

    private function attachModification(Engine $engine): void
    {
        static $sequence = 1;

        $manufacturerId = (int) DB::table('manufacturers')->insertGetId([
            'mfa_id' => 9000 + $sequence,
            'name' => 'Skoda',
            'provider' => ProviderEnum::TD->value,
        ]);
        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'manufacturer_id' => $manufacturerId,
            'mfa_id' => 10,
            'ms_id' => 10000 + $sequence,
            'name' => 'Octavia',
            'generation' => 'III',
            'generation_year_from' => 2013,
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
            'steering_type' => 'Левый руль',
            'provider' => ProviderEnum::TD->value,
            'is_allow' => true,
        ]);
        $modificationId = (int) DB::table('modifications')->insertGetId([
            'vehicle_id' => $vehicleId,
            'ms_id' => 1101,
            'mod_id' => 20000 + $sequence,
            'type' => 'PC',
            'year_from' => 2013,
            'description' => '1.4 TSI',
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => json_encode(['year_from', 'year_to']),
        ]);

        DB::table('engine_modification')->insert([
            'engine_id' => $engine->id,
            'modification_id' => $modificationId,
            'eng_id' => $engine->eng_id,
            'mod_id' => 20000 + $sequence,
            'type' => 'PC',
        ]);

        $sequence++;
    }
}
