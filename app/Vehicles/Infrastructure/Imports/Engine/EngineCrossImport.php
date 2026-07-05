<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Engine;

use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\AssignEngineGroupServiceInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineCrossImportInterface;
use App\Vehicles\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Vehicles\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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

    public int $importedByUserId;

    public function __construct(
        private readonly AssignEngineGroupServiceInterface $service,
    ) {
        $this->importedByUserId = (int) Auth::id();
        $this->cacheKey = "engine_import_failures_{$this->importedByUserId}";
        $this->lockKey = "engine_import_failures_lock_{$this->importedByUserId}";
    }

    public function import(string $path): void
    {
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
                $result = $this->service->execute($code, $groupId);

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

        if ($import->importedByUserId > 0) {
            EngineCrossImportCompleted::dispatch($import->importedByUserId, $import->cacheKey);
        }
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
