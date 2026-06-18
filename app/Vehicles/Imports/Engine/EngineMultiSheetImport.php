<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Engine;

use App\Vehicles\Events\Engine\EngineImportCompleted;
use App\Vehicles\Imports\Engine\Sheets\EngineMainSheetImport;
use App\Vehicles\Imports\Engine\Sheets\EngineSparkPlugsSheetImport;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;

final class EngineMultiSheetImport implements ShouldQueue, WithChunkReading, WithEvents, WithMultipleSheets
{
    private string $cacheKey;

    public int $importedByUserId;

    public function __construct()
    {
        $this->importedByUserId = (int) Auth::id();
        $this->cacheKey = "engine_import_failures_{$this->importedByUserId}";
    }

    public function sheets(): array
    {
        return [
            'Двигатели' => new EngineMainSheetImport($this->importedByUserId, $this->cacheKey),
            'Свечи зажигания' => app()->makeWith(EngineSparkPlugsSheetImport::class, ['userId' => $this->importedByUserId, 'cacheKey' => $this->cacheKey]),
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
        /** @var EngineMultiSheetImport $import */
        $import = $event->getConcernable();
        $user = User::find($import->importedByUserId);

        if ($user) {
            EngineImportCompleted::dispatch($user, $import->cacheKey);
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
