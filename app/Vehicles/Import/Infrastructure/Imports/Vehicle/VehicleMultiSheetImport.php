<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Vehicle;

use App\Vehicles\Import\Domain\DTOs\ImportRunContext;
use App\Vehicles\Import\Domain\DTOs\VehicleImportPlan;
use App\Vehicles\Import\Domain\Enums\InOut\Sheets\VehicleImportSheet;
use App\Vehicles\Import\Domain\Contracts\Imports\VehicleMultiSheetImportInterface;
use App\Vehicles\Import\Domain\Events\Vehicle\VehicleImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Vehicles\Import\Infrastructure\Imports\Vehicle\Sheets\VehicleMainSheetImport;
use App\Vehicles\Import\Infrastructure\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

final class VehicleMultiSheetImport implements ShouldQueue, VehicleMultiSheetImportInterface, WithChunkReading, WithEvents, WithMultipleSheets
{
    private VehicleImportPlan $plan;

    public ImportRunContext $context;

    private string $cacheKey;

    public function import(string $path, ImportRunContext $context, ?VehicleImportPlan $plan = null): void
    {
        $this->context = $context;
        $this->cacheKey = "vehicle_import_failures_{$context->runId}";
        $this->plan = $plan ?? VehicleImportPlan::all();
        Excel::import($this, $path);
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->plan->hasSheet(VehicleImportSheet::Main)) {
            $sheets[VehicleImportSheet::Main->value] = app()->makeWith(
                VehicleMainSheetImport::class,
                ['runId' => $this->context->runId, 'cacheKey' => $this->cacheKey],
            );
        }

        if ($this->plan->hasSheet(VehicleImportSheet::Wipers)) {
            $sheets[VehicleImportSheet::Wipers->value] = app()->makeWith(
                VehicleWipersSheetImport::class,
                ['runId' => $this->context->runId, 'cacheKey' => $this->cacheKey],
            );
        }

        return $sheets;
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

        VehicleImportCompleted::dispatch($import->context->userId, $import->cacheKey);
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
