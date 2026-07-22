<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands;

interface EngineModificationCommandInterface
{
    /**
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void;
}
