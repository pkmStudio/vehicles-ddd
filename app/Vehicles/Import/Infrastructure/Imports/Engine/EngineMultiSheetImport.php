<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Engine;

use App\Vehicles\Import\Domain\Contracts\Imports\External\EngineMultiSheetImportInterface;
use App\Vehicles\Import\Domain\DTOs\ImportRunContextDTO;
use App\Vehicles\Import\Domain\Enums\EngineImportSheet;
use App\Vehicles\Import\Domain\Events\Engine\EngineImportCompleted;
use App\Vehicles\Import\Infrastructure\Imports\Engine\Sheets\EngineMainSheetImport;
use App\Vehicles\Import\Infrastructure\Imports\Engine\Sheets\EngineSparkPlugsSheetImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

final class EngineMultiSheetImport implements EngineMultiSheetImportInterface, ShouldQueue, WithChunkReading, WithEvents, WithMultipleSheets
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
            EngineImportSheet::Main->value => app()->makeWith(
                EngineMainSheetImport::class,
                ['cacheKey' => $cacheKey, 'lockKey' => $lockKey],
            ),
            EngineImportSheet::SparkPlugs->value => app()->makeWith(
                EngineSparkPlugsSheetImport::class,
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
        /** @var EngineMultiSheetImport $import */
        $import = $event->getConcernable();

        event(new EngineImportCompleted($import->context->userId, $import->cacheKey(), $import->context->runId));
    }

    public function chunkSize(): int
    {
        return 100;
    }

    private function cacheKey(): string
    {
        return sprintf(
            (string) config('vehicles-import.failures.cache.keys.engine_import_failures'),
            $this->context->runId,
        );
    }

    private function lockKey(): string
    {
        return sprintf(
            (string) config('vehicles-import.failures.cache.keys.engine_import_failures_lock'),
            $this->context->runId,
        );
    }
}
