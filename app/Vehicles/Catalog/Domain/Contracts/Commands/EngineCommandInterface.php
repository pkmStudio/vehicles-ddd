<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Commands;

use App\Vehicles\Catalog\Domain\ModelData\EngineData;

interface EngineCommandInterface
{
    public function create(EngineData $data): EngineData;

    public function update(EngineData $data): EngineData;

    public function deleteByEngId(int $engId): void;
}
