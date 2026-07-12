<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Engine;

use App\Vehicles\Import\Domain\Contracts\Services\Engine\AssignEngineGroupServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\External\EngineCrossImportInterface;
use App\Vehicles\Import\Domain\DTOs\ImportRunContextDTO;
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
 *
 * @deprecated Фича группировки двигателей по кросс-кодам ещё на большой бизнес-доработке —
 *   правила группировки не финальны. Рабочий код, живой Rabbit-триггер, не удалять — просто не
 *   удивляться, если логика назначения группы поменяется целиком.
 */
final class EngineCrossImport implements EngineCrossImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    public ImportRunContextDTO $context;

    public function __construct(
        private readonly AssignEngineGroupServiceInterface $service,
    ) {}

    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->context = $context;
        $this->cacheKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.engine_import_failures'),
            $context->runId,
        );
        $this->lockKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.engine_import_failures_lock'),
            $context->runId,
        );
        Excel::import($this, $path, $disk);
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

        event(new EngineCrossImportCompleted(
            userId: $import->context->userId,
            cacheKey: $import->cacheKey,
            runId: $import->context->runId,
        ));
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
