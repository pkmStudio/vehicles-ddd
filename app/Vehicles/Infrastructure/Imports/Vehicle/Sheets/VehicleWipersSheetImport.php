<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Vehicle\Sheets;

use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Vehicle\UpsertVehicleFromSheetUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Vehicle\UpsertVehicleWiperSpecUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Support\TemplateDataBuilderInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\Sheets\VehicleWipersSheetImportInterface;
use App\Vehicles\Traits\CachesImportFailures;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер листа «дворники» (механика): чистит/триммит строку, собирает details по шаблону
 * и на каждую строку зовёт сценарии upsert ТС и записи спецификации дворников.
 */
final class VehicleWipersSheetImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithStartRow, VehicleWipersSheetImportInterface
{
    use CachesImportFailures;

    private const int SPEC_START_COLUMN = 20;

    public function __construct(
        private readonly int $userId,
        string $cacheKey,
        private readonly UpsertVehicleFromSheetUseCaseInterface $upsertVehicle,
        private readonly UpsertVehicleWiperSpecUseCaseInterface $upsertWiperSpec,
        private readonly TemplateDataBuilderInterface $templateDataBuilder,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = "vehicle_import_failures_lock_{$this->userId}";
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
            DB::beginTransaction();
            try {
                $vehicle = $this->upsertVehicle->execute($row->toArray());
                $this->writeWiperSpec($vehicle->id, $row->toArray());
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();

                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Дворники',
                        errors: [$e->getMessage()],
                        values: $row->toArray(),
                    ),
                );
            }
        }
    }

    /**
     * Сборка details по шаблону (механика парсинга строки) → запись через сценарий.
     *
     * @throws \Exception
     */
    private function writeWiperSpec(int $vehicleId, array $row): void
    {
        $templateName = $row[17] ?? null;
        if (! $templateName) {
            return;
        }

        $details = $this->templateDataBuilder->buildBySlug($row, self::SPEC_START_COLUMN, $templateName);

        $this->upsertWiperSpec->execute(
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
