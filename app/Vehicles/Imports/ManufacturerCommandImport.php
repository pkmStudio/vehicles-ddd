<?php

declare(strict_types=1);

namespace App\Vehicles\Imports;

use App\Vehicles\Commands\Manufacturer\ManufacturerCommandInterface;
use App\Vehicles\DTOs\Manufacturer\ManufacturerData;
use App\Vehicles\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Validators\Manufacturer\ManufacturerValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Импортер производителей. Строка -> validate -> DTO -> Command.
 */
class ManufacturerCommandImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    public function __construct(
        private readonly ManufacturerCommandInterface $command,
        private readonly ManufacturerValidator $validator,
    ) {}

    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            try {
                $valid = $this->validator->validate([
                    'mfa_id' => $row[0] ?? null,
                    'name' => $row[1] ?? null,
                    'provider' => 'TD',
                ]);

                $this->command->upsertByMfaId(new ManufacturerData(
                    mfaId: (int) $valid['mfa_id'],
                    name: (string) $valid['name'],
                    provider: (string) $valid['provider'],
                ));
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
