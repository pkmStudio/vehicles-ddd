<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Engine;

use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineSparkPlugSpecificationImportInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Template\TemplateDataBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\UpsertSparkPlugSpecByModificationServiceInterface;
use App\Vehicles\Domain\DTOs\ImportRunContext;
use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use App\Vehicles\Domain\Events\Engine\EngineImportCompleted;
use App\Vehicles\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

/**
 * Excel-адаптер импорта свечей по модификациям (механика): парсит ms_id/mod_id, собирает details
 * по шаблону и на каждую строку зовёт сценарий записи свечей двигателям модификации,
 * транслируя его исход (не найдено / пропущенные двигатели) в отчёт об ошибках.
 */
final class EngineSparkPlugSpecificationImport implements EngineSparkPlugSpecificationImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private const int SPEC_START_COLUMN = 2;

    public ImportRunContext $context;

    public function __construct(
        private readonly UpsertSparkPlugSpecByModificationServiceInterface $service,
        private readonly TemplateDataBuilderInterface $templateDataBuilder,
    ) {}

    public function import(string $path, ImportRunContext $context): void
    {
        $this->context = $context;
        $this->cacheKey = "engine_import_failures_{$context->runId}";
        $this->lockKey = "engine_import_failures_lock_{$context->runId}";
        Excel::import($this, $path);
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $rowNumber = $index + $this->startRow();
            $msId = $row[0] ?? null;
            $modId = $row[1] ?? null;

            if (! is_numeric($msId) || ! is_numeric($modId)) {
                Log::warning('EngineSparkPlugSpecificationImport: неверный формат ms_id/mod_id', [
                    'row' => $rowNumber, 'ms_id' => $msId, 'mod_id' => $modId,
                ]);

                continue;
            }

            try {
                $details = $this->templateDataBuilder->buildByTemplate(
                    $row->toArray(),
                    self::SPEC_START_COLUMN,
                    DetailTemplateEnum::SPARK_PLUGS,
                );
                $result = $this->service->upsertByModification((int) $msId, (int) $modId, $details);

                if (! $result->found) {
                    $this->onFailure(new Failure($rowNumber, 'Свечи', [$result->notFoundReason], $row->toArray()));

                    continue;
                }

                foreach ($result->skippedEngines as $skipped) {
                    $this->onFailure(new Failure(
                        $rowNumber,
                        'Двигатель',
                        ["Двигатель {$skipped['code']} (топливо: {$skipped['fuel']}) не нуждается в свечах."],
                        $row->toArray(),
                    ));
                }
            } catch (Throwable $e) {
                $this->onFailure(new Failure($rowNumber, 'Свечи', [$e->getMessage()], $row->toArray()));
            }
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    public static function afterImport(AfterImport $event): void
    {
        /** @var EngineSparkPlugSpecificationImport $import */
        $import = $event->getConcernable();

        EngineImportCompleted::dispatch($import->context->userId, $import->cacheKey);
    }

    public function startRow(): int
    {
        return 2;
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

}
