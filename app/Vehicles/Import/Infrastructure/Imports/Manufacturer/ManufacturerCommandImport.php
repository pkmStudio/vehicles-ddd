<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Manufacturer;

use App\Vehicles\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromRowServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\ManufacturerCommandImportInterface;
use App\Vehicles\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер импорта производителей (механика): читает файл по чанкам и на каждую строку
 * зовёт построчный сценарий. Бизнес-логика строки — в UpsertManufacturerFromRowService.
 */
final class ManufacturerCommandImport implements ManufacturerCommandImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    public function __construct(
        private readonly UpsertManufacturerFromRowServiceInterface $service,
    ) {}

    public function import(string $path): void
    {
        Excel::import($this, $path);
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            try {
                $this->service->upsertFromRow($row->toArray());
            } catch (ValidationException $e) {
                $this->onFailure(new Failure(
                    $index + $this->startRow(),
                    'Производитель',
                    Arr::flatten($e->errors()),
                    $row->toArray(),
                ));
            }
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Manufacturer import failure', [
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
                event(new ManufacturerCommandImported);
            },
        ];
    }
}
