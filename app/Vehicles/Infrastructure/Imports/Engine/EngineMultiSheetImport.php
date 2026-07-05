<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Engine;

use App\Vehicles\Domain\DTOs\EngineImportPlan;
use App\Vehicles\Domain\DTOs\ImportRunContext;
use App\Vehicles\Domain\Enums\InOut\Sheets\EngineImportSheet;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineMultiSheetImportInterface;
use App\Vehicles\Domain\Events\Engine\EngineImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    public ImportRunContext $context;

    private string $cacheKey;

    public function import(string $path, ImportRunContext $context, ?EngineImportPlan $plan = null): void
    {
        $this->context = $context;
        $this->cacheKey = "engine_import_failures_{$context->runId}";
        $this->plan = $plan ?? EngineImportPlan::all();
        Excel::import($this, $path);
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->plan->hasSheet(EngineImportSheet::Main)) {
            $sheets[EngineImportSheet::Main->value] = app()->makeWith(
                EngineMainSheetImport::class,
                ['runId' => $this->context->runId, 'cacheKey' => $this->cacheKey],
            );
        }

        if ($this->plan->hasSheet(EngineImportSheet::SparkPlugs)) {
            $sheets[EngineImportSheet::SparkPlugs->value] = app()->makeWith(
                EngineSparkPlugsSheetImport::class,
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
        /** @var EngineMultiSheetImport $import */
        $import = $event->getConcernable();

        EngineImportCompleted::dispatch($import->context->userId, $import->cacheKey);
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
