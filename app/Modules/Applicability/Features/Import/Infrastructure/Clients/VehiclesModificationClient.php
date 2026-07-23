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
        $vehicle = Vehicle::query()
            ->with('parent')
            ->where('ms_id', $msId)
            ->first();

        if ($vehicle === null) {
            throw new RuntimeException("Модель (ms_id: {$msId}) не найдена.");
        }

        $modification = $this->findModification((int) $vehicle->ms_id, $modId);
        if ($modification !== null) {
            return (int) $modification->id;
        }

        $parentMsId = $vehicle->parent?->ms_id;
        if ($parentMsId !== null) {
            $modification = $this->findModification((int) $parentMsId, $modId);
            if ($modification !== null) {
                return (int) $modification->id;
            }

            throw new RuntimeException(
                "Модификация (ms_id: {$vehicle->ms_id}, mod_id: {$modId}) не найдена ни у модели, ни у родителя (parent_ms_id: {$parentMsId}).",
            );
        }

        throw new RuntimeException("Модификация (ms_id: {$vehicle->ms_id}, mod_id: {$modId}) не найдена.");
    }

    private function findModification(int $msId, int $modId): ?Modification
    {
        return Modification::query()
            ->where('ms_id', $msId)
            ->where('mod_id', $modId)
            ->first();
    }
}
