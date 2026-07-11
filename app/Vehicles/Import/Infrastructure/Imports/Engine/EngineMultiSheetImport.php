<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Engine;

use App\Vehicles\Import\Domain\DTOs\ImportRunContext;
use App\Vehicles\Import\Domain\Enums\InOut\Sheets\EngineImportSheet;
use App\Vehicles\Import\Domain\Contracts\Imports\EngineMultiSheetImportInterface;
use App\Vehicles\Import\Domain\Events\Engine\EngineImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Vehicles\Import\Infrastructure\Imports\Engine\Sheets\EngineMainSheetImport;
use App\Vehicles\Import\Infrastructure\Imports\Engine\Sheets\EngineSparkPlugsSheetImport;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

final class EngineMultiSheetImport implements EngineMultiSheetImportInterface, ShouldQueue, WithChunkReading, WithEvents, WithMultipleSheets
{
    public ImportRunContext $context;

    private string $cacheKey;

    public function import(string $path, ImportRunContext $context, ?string $disk = null): void
    {
        $this->context = $context;
        $this->cacheKey = "engine_import_failures_{$context->runId}";
        Excel::import($this, $path, $disk);
    }

    public function sheets(): array
    {
        return [
            EngineImportSheet::Main->value => app()->makeWith(
                EngineMainSheetImport::class,
                ['runId' => $this->context->runId, 'cacheKey' => $this->cacheKey],
            ),
            EngineImportSheet::SparkPlugs->value => app()->makeWith(
                EngineSparkPlugsSheetImport::class,
                ['runId' => $this->context->runId, 'cacheKey' => $this->cacheKey],
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
        /** @var EngineMultiSheetImport $import */
        $import = $event->getConcernable();

        event(new EngineImportCompleted($import->context->userId, $import->cacheKey, $import->context->runId));
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
