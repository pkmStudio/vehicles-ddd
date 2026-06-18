<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Vehicle;

use App\Vehicles\Events\Vehicle\VehicleImportCompleted;
use App\Vehicles\Imports\Vehicle\Sheets\VehicleMainSheetImport;
use App\Vehicles\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;

final class VehicleMultiSheetImport implements ShouldQueue, WithChunkReading, WithEvents, WithMultipleSheets
{
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
        $user = User::find($import->importedByUserId);

        if ($user) {
            VehicleImportCompleted::dispatch($user, $import->cacheKey);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
