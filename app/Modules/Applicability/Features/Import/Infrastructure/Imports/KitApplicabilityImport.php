<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Imports;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\KitApplicabilityImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\ImportKitApplicabilityRowServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Applicability\Features\Import\Domain\Events\KitApplicabilityImportCompleted;
use App\Modules\Applicability\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Applicability\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
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

final class KitApplicabilityImport implements KitApplicabilityImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private ?int $userId = null;

    private ?string $operationId = null;

    public function __construct(
        private readonly ImportKitApplicabilityRowServiceInterface $service,
        private readonly CacheFactory $cache,
    ) {}

    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->userId = $context->userId;
        $this->operationId = $context->operationId;
        $this->cacheKey = sprintf(
            (string) config('applicability.import.failures.cache.keys.kit_applicability_import_failures'),
            $context->operationId,
        );
        $this->lockKey = sprintf(
            (string) config('applicability.import.failures.cache.keys.kit_applicability_import_failures_lock'),
            $context->operationId,
        );

        Excel::import(
            import: $this,
            filePath: $path,
            disk: $disk,
        );
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();

            if ($this->isEmptyRow($rowValues)) {
                continue;
            }

            try {
                $this->service->importFromRow($rowValues);
            } catch (ImportRowValidationException $exception) {
                $this->onFailure(new Failure(
                    row: $indexRow + $this->startRow(),
                    attribute: 'kit_id',
                    errors: [$exception->getMessage()],
                    values: $rowValues,
                ));
            }
        }
    }

    public function sheets(): array
    {
        return [
            'Колодки' => $this,
            'Масляные фильтры' => $this,
            'Воздушные фильтры' => $this,
        ];
    }

    public function startRow(): int
    {
        return 2;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => fn () => event(new KitApplicabilityImportCompleted(
                userId: $this->userId,
                cacheKey: $this->cacheKey,
                operationId: $this->operationId,
            )),
        ];
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        return trim((string) ($row[0] ?? '')) === ''
            && trim((string) ($row[1] ?? '')) === ''
            && trim((string) ($row[2] ?? '')) === '';
    }

    /**
     * Возвращает cache factory для trait CachesImportFailures.
     */
    protected function cache(): CacheFactory
    {
        return $this->cache;
    }
}
