<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Vehicle;

use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Vehicle\UpsertVehicleFromTdRowUseCaseInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\VehicleCommandImportInterface;
use App\Vehicles\Domain\Events\Vehicle\VehicleCommandImported;
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
 * Excel-адаптер импорта ТС (механика): читает файл по чанкам и на каждую строку зовёт
 * построчный сценарий. Бизнес-логика строки — в UpsertVehicleFromTdRowUseCase.
 */
final class VehicleCommandImport implements ShouldQueue, SkipsOnFailure, ToCollection, VehicleCommandImportInterface, WithChunkReading, WithEvents, WithStartRow
{
    public function __construct(
        private readonly UpsertVehicleFromTdRowUseCaseInterface $useCase,
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
            $line = $index + $this->startRow();
            try {
                $vehicle = $this->useCase->execute($row->toArray());

                if (! $vehicle) {
                    $this->fail($line, "Производитель mfa_id={$row[0]} не найден", $row->toArray());
                }
            } catch (ValidationException $e) {
                $this->fail($line, Arr::flatten($e->errors()), $row->toArray());
            }
        }
    }

    private function fail(int $row, string|array $errors, array $values): void
    {
        $this->onFailure(new Failure($row, 'ТС', (array) $errors, $values));
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Vehicle import failure', [
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
                event(new VehicleCommandImported);
            },
        ];
    }
}
