<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification\UpsertModificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ModificationCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Modification\ModificationCommandImported;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification\Mappers\ModificationCommandRowMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер импорта модификаций (механика): читает файл по чанкам и на каждую строку
 * зовёт построчный сценарий. Бизнес-логика строки — в UpsertModificationFromRowService.
 */
final class ModificationCommandImport implements ModificationCommandImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    public function __construct(
        private readonly UpsertModificationFromRowServiceInterface $service,
        private readonly ModificationCommandRowMapper $rowMapper,
    ) {}

    public function import(string $path): void
    {
        Excel::import($this, $path);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $line = $index + $this->startRow();
            $rowValues = $row->toArray();
            try {
                $modificationRow = $this->rowMapper->map($rowValues);
                $modification = $this->service->upsertFromRow($modificationRow);

                if (! $modification) {
                    $this->fail($line, "ТС ms_id={$modificationRow->msId} не найдено", $rowValues);
                }
            } catch (ImportRowValidationException $e) {
                $this->fail($line, $e->errors(), $rowValues);
            } catch (InvalidArgumentException $e) {
                $this->fail($line, $e->getMessage(), $rowValues);
            }
        }
    }

    private function fail(int $row, string|array $errors, array $values): void
    {
        $this->onFailure(new Failure($row, 'Модификация', (array) $errors, $values));
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Modification import failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    public function startRow(): int
    {
        return 2;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                event(new ModificationCommandImported);
            },
        ];
    }
}
