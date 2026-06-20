<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Engine;

use App\Vehicles\Domain\Contracts\Application\Import\Factories\EngineDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineCommandInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine\UpsertEngineFromSheetUseCaseInterface;
use App\Vehicles\Domain\Models\Engine;
use Illuminate\Validation\ValidationException;

/**
 * Use-case: создать/обновить двигатель из строки листа импорта.
 * Бизнес-логика одной строки: маппинг колонок → валидация+сборка (Factory) → запись (Command).
 * Персистентность — только через порт Command, прямого Eloquent в Application нет.
 */
final readonly class UpsertEngineFromSheetUseCase implements UpsertEngineFromSheetUseCaseInterface
{
    public function __construct(
        private EngineCommandInterface $command,
        private EngineDataFactoryInterface $factory,
    ) {}

    /**
     * @param  array<int, mixed>  $row
     *
     * @throws ValidationException
     */
    public function execute(array $row): Engine
    {
        $data = $this->factory->make([
            'eng_id' => $row[0] ?? null,
            'code_engine' => $row[1] ?? null,
            'eng_power_kw_start' => $row[2] ?? null,
            'eng_power_kw_upto' => $row[3] ?? null,
            'eng_power_ps_start' => $row[4] ?? null,
            'eng_power_ps_upto' => $row[5] ?? null,
            'engine_capacity' => $row[6] ?? null,
            'cylinder_diameter' => $row[7] ?? null,
            'cylinder_count' => $row[8] ?? null,
            'eng_number_of_valves' => $row[9] ?? null,
            'eng_fuel_type' => ($row[10] ?? null) ?: null,
        ]);

        return $this->command->upsertByEngId($data);
    }
}
