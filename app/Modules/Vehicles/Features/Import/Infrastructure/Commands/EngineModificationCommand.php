<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineModificationData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Modification;

final readonly class EngineModificationCommand implements EngineModificationCommandInterface
{
    /**
     * Идемпотентно привязать engine к modification.
     *
     * Шаги:
     * 1) Найти engine по eng_id из import data.
     * 2) Найти modification по mod_id/type из import data.
     * 3) Добавить pivot-связь без отсоединения существующих связей.
     */
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
                $modification->id => [
                    'eng_id' => $data->engId,
                    'mod_id' => $data->modId,
                    'type' => $data->type,
                ],
            ]);
        }
    }
}
