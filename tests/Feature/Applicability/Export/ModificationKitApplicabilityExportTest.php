<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Export;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\ModificationKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Applicability\Features\Export\Infrastructure\Models\Kit;
use App\Modules\Applicability\Features\Export\Infrastructure\Models\KitApplicability;
use App\Modules\Applicability\Features\Export\Infrastructure\Models\Modification;
use App\Modules\Applicability\Features\Export\Infrastructure\Models\Type;
use App\Modules\Applicability\Features\Export\Infrastructure\Models\Vehicle;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class ModificationKitApplicabilityExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_imported_manual_modification_applicability_by_kit_type_sheets(): void
    {
        Storage::fake('local');

        $brakePadKit = $this->kit('Колодки передние', 'BP');
        $oilFilterKit = $this->kit('Фильтр масляный', 'OF');
        $airFilterKit = $this->kit('Фильтр воздушный', 'AF');
        $wiperKit = $this->kit('Щетки', 'WB');
        $modification = $this->modification(msId: 1001, modId: 2001);

        $this->applicability($brakePadKit, $modification);
        $this->applicability($oilFilterKit, $modification);
        $this->applicability($airFilterKit, $modification);
        $this->applicability(
            $wiperKit,
            $modification,
            source: ApplicabilitySourceEnum::CALCULATED,
            algorithm: KitApplicabilityAlgorithmEnum::WIPER,
        );

        $path = app(ModificationKitApplicabilityExportInterface::class)->export(
            context: new ExportRunContextDTO(
                userId: 1,
                operationId: 'modification-applicability-export',
            ),
            disk: 'local',
        );

        Storage::disk('local')->assertExists($path);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));

        $this->assertSame(3, $spreadsheet->getSheetCount());
        $this->assertSame('Колодки', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Масляные фильтры', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('Воздушные фильтры', $spreadsheet->getSheet(2)->getTitle());

        $this->assertSame(['ms_id', 'mod_id', 'kit_id'], $spreadsheet->getSheet(0)->toArray()[0]);
        $this->assertSame([1001, 2001, $brakePadKit->id], $spreadsheet->getSheet(0)->toArray()[1]);
        $this->assertSame([1001, 2001, $oilFilterKit->id], $spreadsheet->getSheet(1)->toArray()[1]);
        $this->assertSame([1001, 2001, $airFilterKit->id], $spreadsheet->getSheet(2)->toArray()[1]);

        foreach (range(0, 2) as $sheetIndex) {
            $kitIds = array_column(array_slice($spreadsheet->getSheet($sheetIndex)->toArray(), 1), 2);

            $this->assertNotContains($wiperKit->id, $kitIds);
        }
    }

    private function kit(string $complectation, string $typeChar): Kit
    {
        $type = Type::query()->create([
            'name' => $complectation,
            'char' => $typeChar,
        ]);

        $packDimensionId = DB::table('pack_dimensions')->insertGetId([
            'name' => 'Box '.$typeChar,
            'weight' => 100,
            'width' => 10,
            'height' => 20,
            'length' => 30,
            'price' => 100,
            'generated' => true,
            'type_id' => $type->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Kit::query()->create([
            'complectation' => $complectation,
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 100,
            'is_sale_separately' => false,
            'is_active' => true,
            'pack_dimension_id' => $packDimensionId,
            'type_id' => $type->id,
        ]);
    }

    private function modification(int $msId, int $modId): Modification
    {
        $manufacturerId = DB::table('manufacturers')->insertGetId([
            'mfa_id' => 500,
            'name' => 'BMW',
            'provider' => ProviderEnum::TD->value,
        ]);

        $vehicle = Vehicle::query()->create([
            'manufacturer_id' => $manufacturerId,
            'mfa_id' => 500,
            'ms_id' => $msId,
            'name' => 'X5',
            'generation' => 'G05',
            'generation_year_from' => 2019,
            'generation_year_to' => null,
            'type' => VehicleTypeEnum::PC->value,
            'type_carcase' => CarcaseTypeEnum::SUV->value,
            'provider' => ProviderEnum::TD->value,
            'steering_type' => SteeringTypeEnum::LEFT->value,
            'is_allow' => true,
        ]);

        return Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => $msId,
            'mod_id' => $modId,
            'year_from' => 2020,
            'year_to' => null,
            'description' => 'xDrive 30d',
            'type' => VehicleTypeEnum::PC->value,
            'power_ps' => 249,
            'power_kw' => 183,
            'engine_type' => EngineTypeEnum::DIESEL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ]);
    }

    private function applicability(
        Kit $kit,
        Modification $modification,
        ApplicabilitySourceEnum $source = ApplicabilitySourceEnum::IMPORTED,
        KitApplicabilityAlgorithmEnum $algorithm = KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
    ): void {
        KitApplicability::query()->create([
            'kit_id' => $kit->id,
            'target_type' => ApplicabilityTargetTypeEnum::MODIFICATION,
            'target_id' => $modification->id,
            'source' => $source,
            'algorithm' => $algorithm,
        ]);
    }
}
