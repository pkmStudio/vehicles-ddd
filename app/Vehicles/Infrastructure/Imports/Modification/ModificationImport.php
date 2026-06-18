<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Modification;

use App\Vehicles\Infrastructure\Commands\Modification\ModificationCommandInterface;
use App\Vehicles\Application\DTOs\Modification\ModificationData;
use App\Vehicles\Infrastructure\Repositories\Vehicle\VehicleRepositoryInterface;
use App\Vehicles\Application\Validators\Modification\ModificationValidator;
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
 * Импорт модификаций (без события цепочки). Строка -> validate -> DTO -> Command.
 */
class ModificationImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow
{
    public function __construct(
        private readonly ModificationCommandInterface $command,
        private readonly ModificationValidator $validator,
        private readonly VehicleRepositoryInterface $vehicles,
    ) {}

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $line = $index + $this->startRow();
            try {
                $vehicle = $this->vehicles->firstByMsId((int) $row[0]);

                if (! $vehicle) {
                    $this->fail($line, "ТС ms_id={$row[0]} не найдено", $row->toArray());

                    continue;
                }

                $valid = $this->validator->validate([
                    'mod_id' => $row[1] ?? null,
                    'type' => $row[13] ?? null,
                    'ms_id' => $row[0] ?? null,
                    'year_from' => $row[2] ?? null,
                    'year_to' => $row[3] ?? null,
                    'description' => $row[4] ?? null,
                    'power_ps' => $row[5] ?? null,
                    'power_kw' => $row[6] ?? null,
                    'engine_type' => ($row[7] ?? null) ?: null,
                    'gear_type' => ($row[8] ?? null) ?: null,
                    'drive_type' => ($row[9] ?? null) ?: null,
                    'brake_system_type' => ($row[10] ?? null) ?: null,
                    'number_of_cylinders' => $row[11] ?? null,
                    'capacity_lt' => $row[12] ?? null,
                ]);

                $this->command->upsertByModIdAndType(new ModificationData(
                    modId: (int) $valid['mod_id'],
                    type: (string) $valid['type'],
                    vehicleId: $vehicle->id,
                    msId: (int) $valid['ms_id'],
                    yearFrom: isset($valid['year_from']) ? (int) $valid['year_from'] : null,
                    yearTo: isset($valid['year_to']) ? (int) $valid['year_to'] : null,
                    description: $valid['description'] ?? null,
                    powerPs: isset($valid['power_ps']) ? (int) $valid['power_ps'] : null,
                    powerKw: isset($valid['power_kw']) ? (int) $valid['power_kw'] : null,
                    engineType: $valid['engine_type'] ?? null,
                    gearType: $valid['gear_type'] ?? null,
                    driveType: $valid['drive_type'] ?? null,
                    brakeSystemType: $valid['brake_system_type'] ?? null,
                    numberOfCylinders: isset($valid['number_of_cylinders']) ? (int) $valid['number_of_cylinders'] : null,
                    capacityLt: isset($valid['capacity_lt']) ? (float) $valid['capacity_lt'] : null,
                ));
            } catch (ValidationException $e) {
                $this->fail($line, Arr::flatten($e->errors()), $row->toArray());
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
}
