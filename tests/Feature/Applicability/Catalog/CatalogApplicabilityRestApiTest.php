<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Catalog;

use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Проверяет публичный REST API применяемости товаров через Warehouse-комплекты.
 */
final class CatalogApplicabilityRestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicability_api_requires_catalog_service_key(): void
    {
        config(['services.dan_catalog.read_api_key' => 'catalog-secret']);

        $this->getJson('/api/v1/catalog/nomenclatures/DUR-060L/applicability?modification_id=1')
            ->assertUnauthorized();

        $this->withHeader('X-Service-Key', 'catalog-secret')
            ->getJson('/api/v1/catalog/nomenclatures/DUR-060L/applicability?modification_id=1')
            ->assertNotFound();
    }

    public function test_check_validates_inputs_and_missing_entities(): void
    {
        $this->getJson('/api/v1/catalog/nomenclatures/DUR-060L/applicability')
            ->assertBadRequest()
            ->assertJsonPath('message', 'Invalid applicability parameters.');

        $this->getJson('/api/v1/catalog/nomenclatures/DUR-060L/applicability?modification_id=1abc')
            ->assertBadRequest();

        $this->getJson('/api/v1/catalog/nomenclatures/DUR-060L/applicability?modification_id=1&brand_id=0')
            ->assertBadRequest()
            ->assertJsonPath('message', 'Invalid brand parameter.');

        $modificationId = $this->createVehicleContext()['modification_id'];

        $this->getJson("/api/v1/catalog/nomenclatures/UNKNOWN/applicability?modification_id={$modificationId}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Nomenclature not found.');

        $this->getJson('/api/v1/catalog/nomenclatures/UNKNOWN/applicability?modification_id=999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Modification not found.');
    }

    public function test_absent_positive_fact_returns_unknown_as_no_content(): void
    {
        $context = $this->createVehicleContext();
        $nomenclatureId = $this->createNomenclature('DUR-060L');
        $this->attachToKit($nomenclatureId, true);

        $this->getJson('/api/v1/catalog/nomenclatures/dur-060l/applicability?modification_id='.$context['modification_id'])
            ->assertNoContent();
    }

    public function test_direct_modification_fact_returns_compatible_evidence_for_slash_part_number(): void
    {
        $context = $this->createVehicleContext();
        $nomenclatureId = $this->createNomenclature('DUR/060L');
        $kitId = $this->attachToKit($nomenclatureId, true);
        $this->createApplicability(
            kitId: $kitId,
            targetType: ApplicabilityTargetTypeEnum::MODIFICATION,
            targetId: $context['modification_id'],
        );

        $this->getJson('/api/v1/catalog/nomenclatures/DUR%2F060L/applicability?modification_id='.$context['modification_id'])
            ->assertOk()
            ->assertJsonPath('data.part_number', 'DUR/060L')
            ->assertJsonPath('data.status', 'compatible')
            ->assertJsonPath('data.evidence.0.kit_id', $kitId)
            ->assertJsonPath('data.evidence.0.target_type', 'modification')
            ->assertJsonPath('data.evidence.0.source', 'imported')
            ->assertJsonPath('data.evidence.0.algorithm', 'manual_xlsx');
    }

    public function test_engine_and_both_part_specification_targets_are_supported(): void
    {
        $context = $this->createVehicleContext();

        $targets = [
            [ApplicabilityTargetTypeEnum::ENGINE, $context['engine_id']],
            [ApplicabilityTargetTypeEnum::PART_SPECIFICATION, $this->createPartSpecification(PartableTypeEnum::VEHICLE, $context['vehicle_id'])],
            [ApplicabilityTargetTypeEnum::PART_SPECIFICATION, $this->createPartSpecification(PartableTypeEnum::ENGINE, $context['engine_id'])],
        ];

        foreach ($targets as $index => [$targetType, $targetId]) {
            $partNumber = 'DUR-060L-'.$index;
            $nomenclatureId = $this->createNomenclature($partNumber);
            $kitId = $this->attachToKit($nomenclatureId, true);
            $this->createApplicability($kitId, $targetType, $targetId);

            $this->getJson("/api/v1/catalog/nomenclatures/{$partNumber}/applicability?modification_id={$context['modification_id']}")
                ->assertOk()
                ->assertJsonPath('data.status', 'compatible')
                ->assertJsonPath('data.evidence.0.target_type', $targetType->value);
        }
    }

    public function test_inactive_kit_is_excluded_and_is_sale_separately_does_not_affect_compatibility(): void
    {
        $context = $this->createVehicleContext();
        $nomenclatureId = $this->createNomenclature('DUR-060L');
        $inactiveKitId = $this->attachToKit($nomenclatureId, false);
        $this->createApplicability($inactiveKitId, ApplicabilityTargetTypeEnum::MODIFICATION, $context['modification_id']);

        $this->getJson('/api/v1/catalog/nomenclatures/DUR-060L/applicability?modification_id='.$context['modification_id'])
            ->assertNoContent();

        $activeKitId = $this->attachToKit($nomenclatureId, true, true);
        $this->createApplicability($activeKitId, ApplicabilityTargetTypeEnum::MODIFICATION, $context['modification_id']);

        $this->getJson('/api/v1/catalog/nomenclatures/DUR-060L/applicability?modification_id='.$context['modification_id'])
            ->assertOk()
            ->assertJsonPath('data.evidence.0.kit_id', $activeKitId);
    }

    public function test_categories_return_only_distinct_applicable_nomenclatures_for_selected_brand(): void
    {
        $context = $this->createVehicleContext();
        $wipersTypeId = $this->createType('Дворники', 'WB');
        $this->createType('Фильтры', 'OF');
        $danNomenclatureId = $this->createNomenclature('DAN-060L', 3, $wipersTypeId);
        $otherBrandNomenclatureId = $this->createNomenclature('OTHER-060L', 4, $wipersTypeId);
        $kitId = $this->attachToKit($danNomenclatureId, true, false, $wipersTypeId);
        DB::table('kit_nomenclature')->insert([
            'kit_id' => $kitId,
            'nomenclature_id' => $otherBrandNomenclatureId,
            'sort' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createApplicability($kitId, ApplicabilityTargetTypeEnum::MODIFICATION, $context['modification_id']);

        $this->getJson('/api/v1/catalog/modifications/'.$context['modification_id'].'/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $wipersTypeId)
            ->assertJsonPath('data.0.nomenclature_count', 1);

        $this->getJson('/api/v1/catalog/modifications/'.$context['modification_id'].'/categories?brand_id=4')
            ->assertOk()
            ->assertJsonPath('data.0.nomenclature_count', 1);

        $this->getJson('/api/v1/catalog/modifications/999999/categories')
            ->assertNotFound()
            ->assertJsonPath('message', 'Modification not found.');
    }

    public function test_applicable_category_nomenclatures_are_distinct_and_paginated(): void
    {
        $context = $this->createVehicleContext();
        $typeId = $this->createType('Дворники', 'WB');
        $firstNomenclatureId = $this->createNomenclature('DAN-050L', 3, $typeId);
        $secondNomenclatureId = $this->createNomenclature('DAN-060L', 3, $typeId);
        $firstKitId = $this->attachToKit($firstNomenclatureId, true, false, $typeId);
        $secondKitId = $this->attachToKit($secondNomenclatureId, true, false, $typeId);
        $this->createApplicability($firstKitId, ApplicabilityTargetTypeEnum::MODIFICATION, $context['modification_id']);
        $this->createApplicability($secondKitId, ApplicabilityTargetTypeEnum::MODIFICATION, $context['modification_id']);
        $this->createApplicability($secondKitId, ApplicabilityTargetTypeEnum::ENGINE, $context['engine_id']);

        $url = "/api/v1/catalog/modifications/{$context['modification_id']}/categories/{$typeId}/nomenclatures?page=2&page_size=1";
        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.category.id', $typeId)
            ->assertJsonPath('data.items.0.part_number', 'DAN-060L')
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.page', 2)
            ->assertJsonPath('data.page_size', 1)
            ->assertJsonPath('data.page_count', 2);

        $this->getJson("/api/v1/catalog/modifications/{$context['modification_id']}/categories/999999/nomenclatures")
            ->assertNotFound();

        $this->getJson("/api/v1/catalog/modifications/{$context['modification_id']}/categories/{$typeId}/nomenclatures?page=0")
            ->assertBadRequest()
            ->assertJsonPath('message', 'Invalid pagination parameters.');
    }

    /** @return array{vehicle_id: int, modification_id: int, engine_id: int} */
    private function createVehicleContext(): array
    {
        $manufacturerId = DB::table('manufacturers')->insertGetId([
            'mfa_id' => random_int(1000, 999999),
            'name' => 'Toyota',
            'provider' => 'TD',
        ]);
        $vehicleId = DB::table('vehicles')->insertGetId([
            'manufacturer_id' => $manufacturerId,
            'mfa_id' => random_int(1000, 999999),
            'ms_id' => random_int(1000, 999999),
            'name' => 'Corolla',
            'generation' => 'E210',
            'generation_year_from' => 2018,
            'type' => 'PC',
            'type_carcase' => 'седан',
            'provider' => 'TD',
            'steering_type' => 'Левый руль',
            'is_allow' => true,
        ]);
        $modId = random_int(1000, 999999);
        $modificationId = DB::table('modifications')->insertGetId([
            'vehicle_id' => $vehicleId,
            'ms_id' => random_int(1000, 999999),
            'mod_id' => $modId,
            'type' => 'PC',
            'provider' => 'TD',
        ]);
        $engId = random_int(1000, 999999);
        $engineId = DB::table('engines')->insertGetId([
            'eng_id' => $engId,
        ]);
        DB::table('engine_modification')->insert([
            'engine_id' => $engineId,
            'modification_id' => $modificationId,
            'eng_id' => $engId,
            'mod_id' => $modId,
            'type' => 'PC',
        ]);

        return [
            'vehicle_id' => $vehicleId,
            'modification_id' => $modificationId,
            'engine_id' => $engineId,
        ];
    }

    private function createNomenclature(string $partNumber, int $brandId = 3, ?int $typeId = null): int
    {
        $typeId ??= $this->createType('Дворники '.$partNumber, 'WB');
        $this->ensureBrand($brandId);

        return DB::table('nomenclatures')->insertGetId([
            'type_id' => $typeId,
            'brand_id' => $brandId,
            'name' => 'Wiper '.$partNumber,
            'country' => 'Россия',
            'part_number' => $partNumber,
            'color' => 'Черный',
            'weight' => 100,
            'material' => json_encode(['Резина'], JSON_THROW_ON_ERROR),
            'vehicle_type' => json_encode(['Легковые автомобили'], JSON_THROW_ON_ERROR),
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function attachToKit(
        int $nomenclatureId,
        bool $isActive,
        bool $isSaleSeparately = false,
        ?int $typeId = null,
    ): int {
        $typeId ??= (int) DB::table('nomenclatures')->where('id', $nomenclatureId)->value('type_id');
        $packDimensionId = DB::table('pack_dimensions')->insertGetId([
            'name' => 'Box '.random_int(1, 999999),
            'weight' => 1,
            'width' => 1,
            'height' => 1,
            'length' => 1,
            'price' => 1,
            'generated' => false,
            'type_id' => $typeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $kitId = DB::table('kits')->insertGetId([
            'complectation' => 'Kit',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 1,
            'is_sale_separately' => $isSaleSeparately,
            'is_active' => $isActive,
            'pack_dimension_id' => $packDimensionId,
            'type_id' => $typeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('kit_nomenclature')->insert([
            'kit_id' => $kitId,
            'nomenclature_id' => $nomenclatureId,
            'sort' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $kitId;
    }

    private function createApplicability(
        int $kitId,
        ApplicabilityTargetTypeEnum $targetType,
        int $targetId,
    ): void {
        DB::table('kit_applicabilities')->insert([
            'kit_id' => $kitId,
            'target_type' => $targetType->value,
            'target_id' => $targetId,
            'source' => ApplicabilitySourceEnum::IMPORTED->value,
            'algorithm' => KitApplicabilityAlgorithmEnum::MANUAL_XLSX->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPartSpecification(PartableTypeEnum $type, int $ownerId): int
    {
        return DB::table('part_specifications')->insertGetId([
            'partable_type' => $type->value,
            'partable_id' => $ownerId,
            'template' => 'wiper',
            'details' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createType(string $name, string $char): int
    {
        return DB::table('types')->insertGetId([
            'name' => $name,
            'char' => $char,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureBrand(int $brandId): void
    {
        DB::table('brands')->insertOrIgnore([
            'id' => $brandId,
            'name' => $brandId === 3 ? 'DAN' : 'Other',
            'number_sert' => 'CERT-'.$brandId,
            'date_start' => now()->subDay(),
            'date_end' => now()->addYear(),
            'char' => 'D',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
