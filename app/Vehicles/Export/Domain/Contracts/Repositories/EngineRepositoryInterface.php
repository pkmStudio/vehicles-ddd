<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Repositories;

use App\Vehicles\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

interface EngineRepositoryInterface
{
    /** @return Collection<int, EngineData> */
    public function all(): Collection;

    /**
     * Для листа свечей (со спецификациями шаблона sparkPlugs).
     *
     * @return Collection<int, EngineData>
     */
    public function forSparkPlugSheet(): Collection;
}
