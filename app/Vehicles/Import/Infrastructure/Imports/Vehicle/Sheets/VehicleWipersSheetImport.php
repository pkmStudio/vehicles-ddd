<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Vehicle\Sheets;

use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Template\TemplateDataBuilderInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Vehicles\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use App\Vehicles\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер листа «дворники» (механика): чистит/триммит строку, находит уже импортированное
 * ТС по ms_id, собирает details по шаблону и записывает только спецификацию дворников.
 */
final class VehicleWipersSheetImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    private const int SPEC_START_COLUMN = 20;

    public function __construct(
        string $cacheKey,
        string $lockKey,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly VehicleWiperSpecificationImportServiceInterface $upsertWiperSpec,
        private readonly TemplateDataBuilderInterface $templateDataBuilder,
        private readonly ImportRowValueFormatter $formatter,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = $lockKey;
    }

    /**
     * @throws \Throwable
     * @throws LockTimeoutException
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $row = $row->map(fn ($value) => is_string($value) ? trim($value) : $value);
            $rowValues = $row->toArray();
            try {
                $vehicleId = $this->resolveVehicleId($rowValues, $indexRow + $this->startRow());
                if ($vehicleId === null) {
                    continue;
                }

                DB::transaction(fn () => $this->writeWiperSpec($vehicleId, $rowValues));
            } catch (\Throwable $e) {
                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Дворники',
                        errors: [$e->getMessage()],
                        values: $rowValues,
                    ),
                );
            }
        }
    }

    /**
     * @param  array<int, string|int|float|null>  $row
     *
     * @throws LockTimeoutException
     */
    private function resolveVehicleId(array $row, int $rowNumber): ?int
    {
        $msId = $this->formatter->nullableInt($row[2] ?? null, 'ms_id');
        if ($msId === null) {
            $this->onFailure(new Failure(
                row: $rowNumber,
                attribute: 'ms_id',
                errors: ['Не указан ms_id для записи спецификации дворников.'],
                values: $row,
            ));

            return null;
        }

        $vehicle = $this->vehicles->firstByMsId($msId);
        if ($vehicle?->id === null) {
            $this->onFailure(new Failure(
                row: $rowNumber,
                attribute: 'ms_id',
                errors: ["ТС с ms_id {$msId} не найдено. Сначала импортируйте основной лист."],
                values: $row,
            ));

            return null;
        }

        return $vehicle->id;
    }

    /**
     * Сборка details по шаблону (механика парсинга строки) → запись через сценарий.
     *
     * @throws \Exception
     *
     * @param  array<int, string|int|float|null>  $row
     */
    private function writeWiperSpec(int $vehicleId, array $row): void
    {
        $templateName = $row[17] ?? null;
        if (! $templateName) {
            return;
        }

        $details = $this->templateDataBuilder->buildBySlug($row, self::SPEC_START_COLUMN, $templateName);

        $this->upsertWiperSpec->importForVehicle(
            vehicleId: $vehicleId,
            templateSlug: $templateName,
            details: $details,
            featureValueName: $row[16] ?? null,
            name: $row[18] ?? null,
            text: $row[19] ?? null,
        );
    }

    public function startRow(): int
    {
        return 2;
    }
}
