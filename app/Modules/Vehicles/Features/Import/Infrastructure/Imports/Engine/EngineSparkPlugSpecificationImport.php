<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine;

use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineSparkPlugSpecificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertSparkPlugSpecByModificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineImportCompleted;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
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

/**
 * Excel-адаптер импорта свечей по модификациям (механика): парсит ms_id/mod_id, собирает details
 * по шаблону и на каждую строку зовёт сценарий записи свечей двигателям модификации,
 * транслируя его исход (не найдено / пропущенные двигатели) в отчёт об ошибках.
 */
final class EngineSparkPlugSpecificationImport implements EngineSparkPlugSpecificationImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private const int SPEC_START_COLUMN = 2;

    public ImportRunContextDTO $context;

    public function __construct(
        private readonly UpsertSparkPlugSpecByModificationServiceInterface $service,
        private readonly TemplatesClientInterface $templates,
    ) {}

    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->context = $context;
        $this->cacheKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.engine_import_failures'),
            $context->operationId,
        );
        $this->lockKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.engine_import_failures_lock'),
            $context->operationId,
        );
        Excel::import($this, $path, $disk);
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $rowNumber = $index + $this->startRow();
            $msId = $row[0] ?? null;
            $modId = $row[1] ?? null;

            if (! is_numeric($msId) || ! is_numeric($modId)) {
                $this->onFailure(new Failure(
                    row: $rowNumber,
                    attribute: 'ms_id/mod_id',
                    errors: ['Строка должна содержать числовые ms_id и mod_id.'],
                    values: $row->toArray(),
                ));

                continue;
            }

            try {
                $details = $this->templates->buildVehicleDetails(
                    template: DetailTemplateEnum::SPARK_PLUGS,
                    row: $row->toArray(),
                    startIndex: self::SPEC_START_COLUMN,
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
            } catch (DetailsDataBuildException $e) {
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

        event(new EngineImportCompleted(
            userId: $import->context->userId,
            cacheKey: $import->cacheKey,
            operationId: $import->context->operationId,
        ));
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
