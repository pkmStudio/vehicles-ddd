<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Vehicle;

use App\Vehicles\Commands\Contracts\VehicleCommandInterface;
use App\Vehicles\DTOs\VehicleData;
use App\Vehicles\Enums\SteeringTypeEnum;
use App\Vehicles\Events\VehicleCommandImported;
use App\Vehicles\Repositories\Contracts\ManufacturerRepositoryInterface;
use App\Vehicles\Validators\VehicleValidator;
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
 * Импортер ТС (приводит базу к виду TD). Строка -> validate -> DTO -> Command.
 * Enum-поля (type, type_carcase) валидируются как enum в VehicleValidator (сырое значение).
 */
class VehicleCommandImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow, WithEvents
{
    public function __construct(
        private readonly VehicleCommandInterface $command,
        private readonly VehicleValidator $validator,
        private readonly ManufacturerRepositoryInterface $manufacturers,
    ) {}

    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $line = $index + $this->startRow();
            try {
                $manufacturer = $this->manufacturers->firstByMfaId((int) $row[0]);

                if (! $manufacturer) {
                    $this->fail($line, "Производитель mfa_id={$row[0]} не найден", $row->toArray());

                    continue;
                }

                $valid = $this->validator->validate([
                    'ms_id' => $row[1] ?? null,
                    'mfa_id' => $row[0] ?? null,
                    'name' => $row[2] ?? null,
                    'type' => $row[7] ?? null,
                    'type_carcase' => ($row[4] ?? null) ?: null,
                    'generation' => $row[3] ?? null,
                    'generation_year_from' => $row[5] ?? null,
                    'generation_year_to' => $row[6] ?? null,
                ]);

                $this->command->upsertByMsId(new VehicleData(
                    msId: (int) $valid['ms_id'],
                    mfaId: (int) $valid['mfa_id'],
                    manufacturerId: $manufacturer->id,
                    name: (string) $valid['name'],
                    type: (string) $valid['type'],
                    steeringType: SteeringTypeEnum::LEFT->value,
                    generation: $valid['generation'] ?? null,
                    typeCarcase: $valid['type_carcase'] ?? null,
                    generationYearFrom: isset($valid['generation_year_from']) ? (int) $valid['generation_year_from'] : null,
                    generationYearTo: isset($valid['generation_year_to']) ? (int) $valid['generation_year_to'] : null,
                ));
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
                event(new VehicleCommandImported());
            },
        ];
    }
}
