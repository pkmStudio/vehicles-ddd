<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Vehicle;

use App\Vehicles\Domain\Contracts\Imports\VehicleMultiSheetImportInterface;
use App\Vehicles\Domain\Events\Vehicle\VehicleImportCompleted;
use App\Vehicles\Infrastructure\Imports\Vehicle\Sheets\VehicleMainSheetImport;
use App\Vehicles\Infrastructure\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

final class VehicleMultiSheetImport implements ShouldQueue, VehicleMultiSheetImportInterface, WithChunkReading, WithEvents, WithMultipleSheets
{
    public function import(string $path): void
    {
        Excel::import($this, $path);
    }

    public int $importedByUserId;

    private string $cacheKey;

    public function __construct()
    {
        $this->importedByUserId = (int) Auth::id();
        $this->cacheKey = "vehicle_import_failures_{$this->importedByUserId}";
    }

    public function sheets(): array
    {
        return [
            'Основная информация' => app()->makeWith(VehicleMainSheetImport::class, ['userId' => $this->importedByUserId, 'cacheKey' => $this->cacheKey]),
            'Дворники' => app()->makeWith(VehicleWipersSheetImport::class, ['userId' => $this->importedByUserId, 'cacheKey' => $this->cacheKey]),
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

        if ($import->importedByUserId > 0) {
            VehicleImportCompleted::dispatch($import->importedByUserId, $import->cacheKey);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
