<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Commands;

use App\Vehicles\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Import\Domain\ModelData\EngineModificationData;
use App\Vehicles\Import\Infrastructure\Models\Engine;
use App\Vehicles\Import\Infrastructure\Models\Modification;

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
