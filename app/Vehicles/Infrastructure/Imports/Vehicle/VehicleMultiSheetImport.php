<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Vehicle;

use App\Vehicles\Domain\DTOs\VehicleImportPlan;
use App\Vehicles\Domain\Enums\VehicleImportSheet;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\VehicleMultiSheetImportInterface;
use App\Vehicles\Domain\Events\Vehicle\VehicleImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use App\Vehicles\Infrastructure\Imports\Vehicle\Sheets\VehicleMainSheetImport;
use App\Vehicles\Infrastructure\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

final class VehicleMultiSheetImport implements ShouldQueue, VehicleMultiSheetImportInterface, WithChunkReading, WithEvents, WithMultipleSheets
{
    private VehicleImportPlan $plan;

    public function import(string $path, ?VehicleImportPlan $plan = null): void
    {
        $this->plan = $plan ?? VehicleImportPlan::all();
        Excel::import($this, $path);
    }

    public int $importedByUserId;

    private string $cacheKey;

    public function __construct()
    {
        $this->importedByUserId = (int) Auth::id();
        $this->cacheKey = "vehicle_import_failures_{$this->importedByUserId}";
        $this->plan = VehicleImportPlan::all();
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->plan->hasSheet(VehicleImportSheet::Main)) {
            $sheets[VehicleImportSheet::Main->value] = app()->makeWith(
                VehicleMainSheetImport::class,
                ['userId' => $this->importedByUserId, 'cacheKey' => $this->cacheKey],
            );
        }

        if ($this->plan->hasSheet(VehicleImportSheet::Wipers)) {
            $sheets[VehicleImportSheet::Wipers->value] = app()->makeWith(
                VehicleWipersSheetImport::class,
                ['userId' => $this->importedByUserId, 'cacheKey' => $this->cacheKey],
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

        if ($import->importedByUserId > 0) {
            VehicleImportCompleted::dispatch($import->importedByUserId, $import->cacheKey);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
