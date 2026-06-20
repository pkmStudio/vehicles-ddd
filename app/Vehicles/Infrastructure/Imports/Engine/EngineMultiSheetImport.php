<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Engine;

use App\Vehicles\Domain\DTOs\EngineImportPlan;
use App\Vehicles\Domain\Enums\EngineImportSheet;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineMultiSheetImportInterface;
use App\Vehicles\Domain\Events\Engine\EngineImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use App\Vehicles\Infrastructure\Imports\Engine\Sheets\EngineMainSheetImport;
use App\Vehicles\Infrastructure\Imports\Engine\Sheets\EngineSparkPlugsSheetImport;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

final class EngineMultiSheetImport implements EngineMultiSheetImportInterface, ShouldQueue, WithChunkReading, WithEvents, WithMultipleSheets
{
    private EngineImportPlan $plan;

    public function import(string $path, ?EngineImportPlan $plan = null): void
    {
        $this->plan = $plan ?? EngineImportPlan::all();
        Excel::import($this, $path);
    }

    private string $cacheKey;

    public int $importedByUserId;

    public function __construct()
    {
        $this->importedByUserId = (int) Auth::id();
        $this->cacheKey = "engine_import_failures_{$this->importedByUserId}";
        $this->plan = EngineImportPlan::all();
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->plan->hasSheet(EngineImportSheet::Main)) {
            $sheets[EngineImportSheet::Main->value] = app()->makeWith(
                EngineMainSheetImport::class,
                ['userId' => $this->importedByUserId, 'cacheKey' => $this->cacheKey],
            );
        }

        if ($this->plan->hasSheet(EngineImportSheet::SparkPlugs)) {
            $sheets[EngineImportSheet::SparkPlugs->value] = app()->makeWith(
                EngineSparkPlugsSheetImport::class,
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
