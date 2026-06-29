<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\EngineModification;

use App\Vehicles\Domain\Contracts\Application\Import\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineModificationImportInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
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
            try {
                $this->useCase->execute($row->toArray());
            } catch (ValidationException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Связь двигатель-модификация', Arr::flatten($e->errors()), $row->toArray()));
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
