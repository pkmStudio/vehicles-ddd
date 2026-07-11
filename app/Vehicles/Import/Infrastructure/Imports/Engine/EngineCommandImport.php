<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Engine;

use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpsertEngineFromSheetServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\Command\EngineCommandImportInterface;
use App\Vehicles\Import\Domain\Events\Engine\EngineCommandImported;
use App\Vehicles\Import\Infrastructure\Imports\Engine\Mappers\EngineSheetRowMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use InvalidArgumentException;
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
 * Excel-адаптер импорта двигателей (механика): читает файл по чанкам и на каждую строку
 * зовёт построчный сценарий. Бизнес-логика строки — в UpsertEngineFromSheetService.
 */
final class EngineCommandImport implements EngineCommandImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    public function __construct(
        private readonly UpsertEngineFromSheetServiceInterface $service,
        private readonly EngineSheetRowMapper $rowMapper,
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
            $rowValues = $row->toArray();
            try {
                $engineRow = $this->rowMapper->map($rowValues);

                $this->service->upsertFromRow($engineRow);
            } catch (ValidationException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Двигатель', Arr::flatten($e->errors()), $rowValues));
            } catch (InvalidArgumentException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Двигатель', [$e->getMessage()], $rowValues));
            }
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Engine import failure', [
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
                event(new EngineCommandImported);
            },
        ];
    }
}
