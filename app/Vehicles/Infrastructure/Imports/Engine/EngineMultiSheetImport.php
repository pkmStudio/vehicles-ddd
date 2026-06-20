<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Engine;

use App\Vehicles\Domain\Contracts\Imports\EngineMultiSheetImportInterface;
use App\Vehicles\Domain\Events\Engine\EngineImportCompleted;
use App\Vehicles\Infrastructure\Imports\Engine\Sheets\EngineMainSheetImport;
use App\Vehicles\Infrastructure\Imports\Engine\Sheets\EngineSparkPlugsSheetImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

final class EngineMultiSheetImport implements EngineMultiSheetImportInterface, ShouldQueue, WithChunkReading, WithEvents, WithMultipleSheets
{
    public function import(string $path): void
    {
        Excel::import($this, $path);
    }

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
            'Двигатели' => app()->makeWith(EngineMainSheetImport::class, ['userId' => $this->importedByUserId, 'cacheKey' => $this->cacheKey]),
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

        if ($import->importedByUserId > 0) {
            EngineImportCompleted::dispatch($import->importedByUserId, $import->cacheKey);
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
