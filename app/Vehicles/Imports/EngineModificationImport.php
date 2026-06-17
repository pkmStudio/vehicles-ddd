<?php

declare(strict_types=1);

namespace App\Vehicles\Imports;

use App\Vehicles\Models\Engine;
use App\Vehicles\Models\Modification;
use App\Vehicles\Validators\EngineModificationValidator;
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
 * type валидируется как enum (VehicleTypeEnum) перед записью.
 */
class EngineModificationImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow
{
    public function __construct(
        private readonly EngineModificationValidator $validator,
    ) {}

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            try {
                $valid = $this->validator->validate([
                    'eng_id' => $row[0] ?? null,
                    'mod_id' => $row[1] ?? null,
                    'type' => $row[2] ?? null,
                ]);
            } catch (ValidationException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Связь двигатель-модификация', Arr::flatten($e->errors()), $row->toArray()));

                continue;
            }

            $engine = Engine::query()->where('eng_id', $valid['eng_id'])->first();
            $modification = Modification::query()
                ->where('mod_id', $valid['mod_id'])
                ->where('type', $valid['type'])
                ->first();

            if ($engine && $modification) {
                $engine->modifications()->syncWithoutDetaching([
                    $modification->id => [
                        'eng_id' => $valid['eng_id'],
                        'mod_id' => $valid['mod_id'],
                        'type' => $valid['type'],
                    ],
                ]);
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
