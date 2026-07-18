<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineModificationImportInterface;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\Mappers\EngineModificationCommandRowMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер импорта связи двигатель↔модификация (механика): читает файл по чанкам и на
 * каждую строку зовёт сценарий привязки. Логика строки — в LinkEngineModificationFromRowService.
 */
final class EngineModificationImport implements EngineModificationImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow
{
    public function __construct(
        private readonly LinkEngineModificationFromRowServiceInterface $service,
        private readonly EngineModificationCommandRowMapper $rowMapper,
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
            $rowValues = $row->toArray();
            try {
                $engineModificationRow = $this->rowMapper->map($rowValues);

                $this->service->linkFromRow($engineModificationRow);
            } catch (ValidationException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Связь двигатель-модификация', Arr::flatten($e->errors()), $rowValues));
            } catch (InvalidArgumentException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Связь двигатель-модификация', [$e->getMessage()], $rowValues));
            }
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('EngineModification import failure', [
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
}
