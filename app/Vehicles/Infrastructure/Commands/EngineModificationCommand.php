<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands;

use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineModificationCommandInterface;
use App\Vehicles\Domain\ModelData\EngineModification\EngineModificationData;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Models\Modification;

final readonly class EngineModificationCommand implements EngineModificationCommandInterface
{
    public function syncWithoutDetaching(EngineModificationData $data): void
    {
        $engine = Engine::query()
            ->where('eng_id', $data->engId)
            ->first();

        $modification = Modification::query()
            ->where('mod_id', $data->modId)
            ->where('type', $data->type)
            ->first();

        if ($engine && $modification) {
            $engine->modifications()->syncWithoutDetaching([
                $modification->id => $data->toArray(),
            ]);
        }
    }
}
