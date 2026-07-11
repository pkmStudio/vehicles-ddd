<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Engine;

use App\Vehicles\Import\Domain\Contracts\Services\Engine\AssignEngineGroupServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\EngineCrossImportInterface;
use App\Vehicles\Import\Domain\DTOs\ImportRunContext;
use App\Vehicles\Import\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Vehicles\Import\Infrastructure\Traits\CachesImportFailures;
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
use Throwable;

/**
 * Excel-адаптер привязки двигателей к группам (механика): парсит коды из ячейки и на каждый код
 * зовёт сценарий назначения группы, транслируя его исход в отчёт об ошибках.
 */
final class EngineCrossImport implements EngineCrossImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    public ImportRunContext $context;

    public function __construct(
        private readonly AssignEngineGroupServiceInterface $service,
    ) {}

    public function import(string $path, ImportRunContext $context): void
    {
        $this->context = $context;
        $this->cacheKey = "engine_import_failures_{$context->runId}";
        $this->lockKey = "engine_import_failures_lock_{$context->runId}";
        Excel::import($this, $path);
    }

    /**
     * @throws Throwable
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            $this->processRow($indexRow, $row->toArray());
        }
    }

    private function processRow(int $indexRow, array $row): void
    {
        $groupId = isset($row[0]) && $row[0] !== '' ? (int) $row[0] : null;
        $rawCodes = isset($row[1]) ? (string) $row[1] : null;

        if (empty($groupId) || empty($rawCodes)) {
            return;
        }

        foreach ($this->parseCodes($rawCodes) as $code) {
            try {
                $result = $this->service->assignGroup($code, $groupId);

                if (! $result->found) {
                    $this->onFailure(new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'code_engine',
                        errors: ["Код двигателя '{$code}' не найден"],
                        values: ['group_id' => $groupId, 'code' => $code],
                    ));

                    continue;
                }

                if ($result->reassigned) {
                    $this->onFailure(new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'group_id',
                        errors: ["Группа для '{$code}' изменена с {$result->previousGroupId} на {$groupId}"],
                        values: ['code' => $code, 'old_group' => $result->previousGroupId, 'new_group' => $groupId],
                    ));
                }
            } catch (Throwable $e) {
                $this->onFailure(new Failure(
                    row: $indexRow + $this->startRow(),
                    attribute: 'system',
                    errors: [$e->getMessage()],
                    values: $row,
                ));
            }
        }
    }

    private function parseCodes(string $rawCell): array
    {
        return array_filter(array_map('trim', explode(';', $rawCell)));
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
        /** @var EngineCrossImport $import */
        $import = $event->getConcernable();

        event(new EngineCrossImportCompleted($import->context->userId, $import->cacheKey));
    }

    public function startRow(): int
    {
        return 1;
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }
}
