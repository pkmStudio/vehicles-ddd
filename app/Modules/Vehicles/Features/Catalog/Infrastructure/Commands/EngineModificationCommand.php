<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\EngineModification;
use Illuminate\Support\Facades\DB;

final readonly class EngineModificationCommand implements EngineModificationCommandInterface
{
    /**
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            EngineModification::query()->whereIn('id', $ids)->delete();
        });
    }
}
