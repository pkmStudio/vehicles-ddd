<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Modification;

use App\Vehicles\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Application\Factories\Modification\ModificationDataFactory;
use App\Vehicles\Domain\Events\Modification\ModificationCommandImported;
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
 * Импортер модификаций (приводит базу к виду TD). Строка -> validate -> DTO -> Command.
 * Enum-поля (type, engine_type, gear_type, drive_type, brake_system_type) валидируются как enum.
 */
class ModificationCommandImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    public function __construct(
        private readonly ModificationCommandInterface $command,
        private readonly ModificationDataFactory $factory,
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

                $data = $this->factory->make([
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
                    'vehicle_id' => $vehicle->id,
                ]);

                $this->command->upsertByModIdAndType($data);
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

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                event(new ModificationCommandImported);
            },
        ];
    }
}
