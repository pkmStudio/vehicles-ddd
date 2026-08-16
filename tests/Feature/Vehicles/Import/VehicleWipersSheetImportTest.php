<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleWiperSpecificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers\VehicleWiperSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class VehicleWipersSheetImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Базовые 20 колонок листа + 10 колонок спецификации дворников (индексы 20-29), реальные —
     * без мока `DetailsBuilder`/`TemplateDataBuilder` (эти классы удалены вместе со старым DSL,
     * см. plan-refactor.md). Строка гоняется через настоящий `DetailsDataFactory::make()`.
     *
     * @return array<int, mixed>
     */
    private function wiperRow(string $name = 'Octavia'): array
    {
        return [
            'A-1',
            10,
            300,
            'Skoda',
            $name,
            'Octavia localized',
            'A7',
            'III',
            2013,
            2020,
            'Hatchback',
            'PC',
            'OD',
            null,
            'Левый руль',
            'Да',
            'Левый руль',
            DetailTemplateEnum::WIPER->value,
            'Bosch Aerotwin',
            'Описание дворников',
            // --- спецификация дворников (front: length_main, length_second, adapter, count) ---
            500, 550, 450, 500, 'Крючок (Hook / J-Hook)', 2,
            // --- back: length_rear, adapter, count ---
            400, 420, 'RA', 1,
        ];
    }

    /** Ожидаемый `details` для строки из `wiperRow()` — форма `WiperDetailsData::toArray()`. */
    private function expectedWiperDetails(): array
    {
        return [
            'front' => [
                'length_main' => ['min' => 500, 'max' => 550],
                'length_second' => ['min' => 450, 'max' => 500],
                'adapter_type_front' => ['H'],
                'count_wipers' => 2,
            ],
            'back' => [
                'length_rear' => ['min' => 400, 'max' => 420],
                'adapter_type_rear' => ['RA'],
                'count_wipers' => 1,
            ],
        ];
    }

    private function rowMapper(): VehicleWiperSheetRowMapper
    {
        return new VehicleWiperSheetRowMapper(new ImportRowValueFormatter, app(TemplatesClientInterface::class));
    }

    /**
     * Проверяет, что лист дворников — write-only на PartSpecification и никогда не трогает
     * основные поля ТС (name/type/...), даже если файл содержит другие значения этих колонок.
     *
     * Шаги:
     * 1. Создаёт производителя и ТС с исходным name='Octavia'.
     * 2. Мокает UpsertVehicleWiperSpecificationFromRowServiceInterface — ожидает upsertFromRow() с
     *    ожидаемым DTO (msId/templateSlug/featureValueName/name/text/details из строки), где
     *    details реально собраны `DetailsDataFactory::make()`, не подставлены мок-ом.
     * 3. Прогоняет одну строку с другим name ('Changed from wipers sheet') через collection().
     * 4. Проверяет, что в БД у ТС осталось исходное имя, а изменённое — не появилось.
     */
    public function test_wiper_sheet_does_not_update_vehicle_main_fields(): void
    {
        $manufacturer = Manufacturer::query()->create([
            'mfa_id' => 10,
            'name' => 'Skoda',
            'provider' => 'TD',
        ]);
        $vehicle = Vehicle::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => 10,
            'ms_id' => 300,
            'name' => 'Octavia',
            'generation' => 'III',
            'generation_year_from' => 2013,
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
            'provider' => 'OD',
            'steering_type' => 'Левый руль',
            'is_allow' => false,
        ]);

        $row = $this->wiperRow('Changed from wipers sheet');
        $details = $this->expectedWiperDetails();

        $wiperSpec = $this->mock(UpsertVehicleWiperSpecificationFromRowServiceInterface::class);
        $wiperSpec->shouldReceive('upsertFromRow')
            ->once()
            ->with(Mockery::on(fn (VehicleWiperSheetRowDTO $dto): bool => $dto->msId === 300
                && $dto->templateSlug === DetailTemplateEnum::WIPER->value
                && $dto->featureValueName === 'Левый руль'
                && $dto->name === 'Bosch Aerotwin'
                && $dto->text === 'Описание дворников'
                && $dto->details === $details));

        /** @var VehicleWipersSheetImport $import */
        $import = app()->makeWith(VehicleWipersSheetImport::class, [
            'cacheKey' => 'vehicle_wipers_sheet_test',
            'lockKey' => 'vehicle_wipers_sheet_test_lock',
            'rowMapper' => $this->rowMapper(),
        ]);

        $rows = new Collection([new Collection($row)]);

        $import->collection($rows);

        $this->assertSame(1, Vehicle::query()->count());
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'name' => 'Octavia',
        ]);
        $this->assertDatabaseMissing('vehicles', [
            'id' => $vehicle->id,
            'name' => 'Changed from wipers sheet',
        ]);
    }

    /**
     * Проверяет, что лист дворников не создаёт ТС «на лету» — если ms_id из строки не
     * найден, строка проваливается по исключению из Application-сервиса (SkipsOnFailure), а
     * не тихо заводит новую запись.
     *
     * Шаги:
     * 1. Мокает UpsertVehicleWiperSpecificationFromRowServiceInterface так, чтобы upsertFromRow()
     *    бросал ImportRowValidationException «ТС не найдено».
     * 2. Прогоняет одну строку (без предварительного создания ТС) через collection().
     * 3. Проверяет, что в БД ТС так и не появилось (count === 0).
     */
    public function test_wiper_sheet_does_not_create_missing_vehicle(): void
    {
        $row = $this->wiperRow();

        $wiperSpec = $this->mock(UpsertVehicleWiperSpecificationFromRowServiceInterface::class);
        $wiperSpec->shouldReceive('upsertFromRow')
            ->once()
            ->with(Mockery::on(fn (VehicleWiperSheetRowDTO $dto): bool => $dto->msId === 300))
            ->andThrow(ImportRowValidationException::fromMessage('ТС с ms_id 300 не найдено. Сначала импортируйте основной лист.'));

        /** @var VehicleWipersSheetImport $import */
        $import = app()->makeWith(VehicleWipersSheetImport::class, [
            'cacheKey' => 'vehicle_wipers_missing_vehicle_test',
            'lockKey' => 'vehicle_wipers_missing_vehicle_test_lock',
            'rowMapper' => $this->rowMapper(),
        ]);

        $rows = new Collection([new Collection($row)]);

        $import->collection($rows);

        $this->assertSame(0, Vehicle::query()->count());
    }
}
