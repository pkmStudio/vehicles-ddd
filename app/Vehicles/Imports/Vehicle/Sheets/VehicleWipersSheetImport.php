<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Vehicle\Sheets;

use App\Vehicles\Commands\Contracts\PartSpecificationCommandInterface;
use App\Vehicles\Commands\Contracts\VehicleCommandInterface;
use App\Vehicles\DTOs\PartSpecificationData;
use App\Vehicles\Enums\DetailTemplateEnum;
use App\Vehicles\Models\Vehicle;
use App\Vehicles\Repositories\Contracts\FeatureValueRepositoryInterface;
use App\Vehicles\Templates\Vehicle\VehicleTemplateFactory;
use App\Vehicles\Traits\BuildDetails;
use App\Vehicles\Traits\CachesImportFailures;
use App\Vehicles\Traits\HasVehicleImportBaseData;
use App\Vehicles\Validators\VehicleValidator;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

final class VehicleWipersSheetImport implements SkipsOnFailure, ToCollection, WithStartRow, SkipsEmptyRows
{
    use BuildDetails;
    use CachesImportFailures;
    use HasVehicleImportBaseData;

    public function __construct(
        private readonly int $userId,
        string $cacheKey,
        private readonly PartSpecificationCommandInterface $partSpecs,
        private readonly FeatureValueRepositoryInterface $featureValues,
        private readonly VehicleCommandInterface $vehicleCommand,
        private readonly VehicleValidator $vehicleValidator,
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
                $vehicle = $this->createOrUpdateVehicle(row: $row->toArray());
                $this->createOrUpdatePartSpecification(vehicleId: $vehicle->id, row: $row->toArray());
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
     * @throws \Exception
     */
    private function createOrUpdatePartSpecification(int $vehicleId, array $row): void
    {
        $templateName = $row[17] ?? null;
        if (! $templateName) {
            return;
        }

        [$templateSlug, $details] = $this->getDetailsData($templateName, $row);
        $featureValue = ! empty($row[16]) ? $this->featureValues->firstByName($row[16]) : null;

        $this->partSpecs->upsert(new PartSpecificationData(
            partableType: Vehicle::class,
            partableId: $vehicleId,
            template: DetailTemplateEnum::from($templateSlug),
            details: $details,
            featureValueId: $featureValue?->id,
            name: $row[18] ?? null,
            text: $row[19] ?? null,
        ));
    }

    /**
     * @throws \Exception
     */
    private function getDetailsData(string $templateName, array $row): array
    {
        $startIndex = 20;
        $template = VehicleTemplateFactory::make($templateName)->getArrayTemplate();
        $details = $this->buildDetails($row, $startIndex, $template);

        return [$templateName, $details];
    }

    public function startRow(): int
    {
        return 2;
    }
}
