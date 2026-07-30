<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\VehicleMultiSheetImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\VehicleImportSheet;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleImportCompleted;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets\VehicleMainSheetImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

final class VehicleMultiSheetImport implements ShouldQueue, VehicleMultiSheetImportInterface, WithChunkReading, WithEvents, WithMultipleSheets
{
    public ImportRunContextDTO $context;

    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->context = $context;
        Excel::import($this, $path, $disk);
    }

    public function sheets(): array
    {
        $cacheKey = $this->cacheKey();
        $lockKey = $this->lockKey();

        return [
            VehicleImportSheet::Main->value => app()->makeWith(
                VehicleMainSheetImport::class,
                ['cacheKey' => $cacheKey, 'lockKey' => $lockKey],
            ),
            VehicleImportSheet::Wipers->value => app()->makeWith(
                VehicleWipersSheetImport::class,
                ['cacheKey' => $cacheKey, 'lockKey' => $lockKey],
            ),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    public static function afterImport(AfterImport $event): void
    {
        /** @var VehicleMultiSheetImport $import */
        $import = $event->getConcernable();

        event(new VehicleImportCompleted(
            userId: $import->context->userId,
            cacheKey: $import->cacheKey(),
            operationId: $import->context->operationId,
        ));
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function cacheKey(): string
    {
        return sprintf(
            (string) config('vehicles.import.failures.cache.keys.vehicle_import_failures'),
            $this->context->operationId,
        );
    }

    private function lockKey(): string
    {
        return sprintf(
            (string) config('vehicles.import.failures.cache.keys.vehicle_import_failures_lock'),
            $this->context->operationId,
        );
    }
}
