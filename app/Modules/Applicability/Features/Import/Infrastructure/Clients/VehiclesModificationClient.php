<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Clients;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\VehiclesModificationClientInterface;
use App\Modules\Applicability\Features\Import\Infrastructure\Models\Modification;
use App\Modules\Applicability\Features\Import\Infrastructure\Models\Vehicle;
use RuntimeException;

final readonly class VehiclesModificationClient implements VehiclesModificationClientInterface
{
    public function resolveByMsAndModId(int $msId, int $modId): int
    {
        if ($msId < 0) {
            $vehicle = Vehicle::query()
                ->with('parent')
                ->where('ms_id', $msId)
                ->first();

            if ($vehicle === null) {
                throw new RuntimeException("Модель (ms_id: {$msId}) не найдена.");
            }

            $msId = $vehicle->parent?->ms_id;
            if ($msId === null) {
                throw new RuntimeException("Модель (ms_id: {$vehicle->ms_id}) должна иметь родителя.");
            }
        }

        $modification = Modification::query()
            ->where('ms_id', $msId)
            ->where('mod_id', $modId)
            ->first();

        if ($modification === null) {
            throw new RuntimeException("Модификация (ms_id: {$msId}, mod_id: {$modId}) не найдена.");
        }

        return (int) $modification->id;
    }
}
