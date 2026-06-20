<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Engine;

use App\Events\Warehouse\KitImportCompleted;
use App\Imports\Warehouse\KitImport;
use App\Models\User;
use App\Vehicles\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Imports\EngineSparkPlugSpecificationImportInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Enums\EngineFuelTypeEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Models\Modification;
use App\Vehicles\Domain\Models\Vehicle;
use App\Vehicles\Domain\Templates\Engine\Templates\SparkPlugTemplate;
use App\Vehicles\Infrastructure\Imports\Support\DetailsBuilder;
use App\Vehicles\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

class EngineSparkPlugSpecificationImport implements EngineSparkPlugSpecificationImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    public function import(string $path): void
    {
        Excel::import($this, $path);
    }

    use CachesImportFailures;

    public int $importedByUserId;

    private bool $dryRun;

    public function __construct(
        private readonly PartSpecificationCommandInterface $partSpecs,
        private readonly DetailsBuilder $detailsBuilder,
        bool $dryRun = false,
    ) {
        $this->importedByUserId = (int) Auth::id();
        $this->cacheKey = "engine_import_failures_{$this->importedByUserId}";
        $this->lockKey = "engine_import_failures_lock_{$this->importedByUserId}";
        $this->dryRun = $dryRun;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $startDetailIndex = 2;
            $msId = $row[0] ?? null;
            $modId = $row[1] ?? null;
            $rowNumber = $index + $this->startRow();

            if (! is_numeric($msId) || ! is_numeric($modId)) {
                Log::warning('Ошибка формата', [
                    'row' => $rowNumber,
                    'mod_id' => $modId,
                    'ms_id' => $msId,
                ]);

                continue;
            }

            $msId = (int) $msId;
            $modId = (int) $modId;

            try {
                $modification = $this->getModification($msId, $modId);
                $engines = $modification->engines;

                foreach ($engines as $engine) {
                    $result = in_array(
                        $engine->eng_fuel_type,
                        [
                            EngineFuelTypeEnum::PETROL->value,
                            EngineFuelTypeEnum::GAS->value,
                            EngineFuelTypeEnum::ALCOHOL->value,
                            EngineFuelTypeEnum::HYDROGEN->value,
                            EngineFuelTypeEnum::PETROL_ALCOHOL->value,
                            EngineFuelTypeEnum::PETROL_GAS->value,
                            EngineFuelTypeEnum::PETROL_ALCOHOL_GAS->value,
                        ]
                    );

                    if (! $result) {
                        $engines->unset($engine);
                        $this->onFailure(
                            new Failure(
                                row: $rowNumber,
                                attribute: 'Двигатель',
                                errors: ["У этой модификации: {$modId}
                                    двигатель: {$engine->code_engine} не нуждается в свечах.
                                    Его топливо: {$engine->eng_fuel_type}",
                                ],
                                values: $row->toArray(),
                            ),
                        );
                    }
                }

                $details = $this->detailsBuilder->buildDetails($row->toArray(), $startDetailIndex, app(SparkPlugTemplate::class)->getArrayTemplate());

                foreach ($engines as $engine) {
                    $this->partSpecs->upsert(new PartSpecificationData(
                        partableType: Engine::class,
                        partableId: $engine->id,
                        template: DetailTemplateEnum::SPARK_PLUGS,
                        details: $details,
                    ));
                }
            } catch (\Throwable $e) {
                $this->onFailure(
                    new Failure(
                        row: $rowNumber,
                        attribute: 'Свечи',
                        errors: [$e->getMessage()],
                        values: $row->toArray(),
                    ),
                );
                Log::warning('Ошибка', [
                    'row' => $rowNumber,
                    'mod_id' => $modId,
                    'ms_id' => $msId,
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]);
            }
        }
    }

    /**
     * Ищет модификацию автомобиля
     *
     * @throws \Exception
     */
    private function getModification(int $msId, int $modId): Modification
    {
        if ($msId < 0) {
            /** @var Vehicle|null $vehicle */
            $vehicle = Vehicle::query()->with('parent')->where('ms_id', $msId)->first();

            if (! $vehicle) {
                throw new \Exception("Модель (ms_id: {$msId}, не найдена.");
            }

            $msId = $vehicle->parent
                ? $vehicle->parent->ms_id
                : null;

            if (! $msId) {
                throw new \Exception("Модель (ms_id: {$msId}, должна иметь родителя.");
            }
        }

        /** @var Modification|null $modification */
        $modification = Modification::where('ms_id', $msId)
            ->where('mod_id', $modId)
            ->has('engines')
            ->with('engines')
            ->first();

        if (! $modification) {
            throw new \Exception("Модификация (ms_id: {$msId}, mod_id: {$modId}) не найдена.");
        }

        return $modification;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    public static function afterImport(AfterImport $event): void
    {
        $import = $event->getConcernable();
        $user = User::find($import->importedByUserId);

        if ($user) {
            KitImportCompleted::dispatch($user, $import->cacheKey);
        }
    }

    public function startRow(): int
    {
        return 2;
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }
}
