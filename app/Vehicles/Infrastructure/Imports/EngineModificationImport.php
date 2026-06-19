<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports;

use App\Vehicles\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Application\Factories\EngineModification\EngineModificationDataFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Импорт связи двигатель <-> модификация (пивот engine_modification).
 * Строка -> Factory->make() (валидация type как enum) -> Command->link().
 */
class EngineModificationImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow
{
    public function __construct(
        private readonly EngineModificationDataFactory $factory,
        private readonly EngineModificationCommandInterface $command,
    ) {}

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            try {
                $data = $this->factory->make([
                    'eng_id' => $row[0] ?? null,
                    'mod_id' => $row[1] ?? null,
                    'type' => $row[2] ?? null,
                ]);

                $this->command->syncWithoutDetaching($data);
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
