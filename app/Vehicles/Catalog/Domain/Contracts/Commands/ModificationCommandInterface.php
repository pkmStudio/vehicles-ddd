<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Commands;

use App\Vehicles\Catalog\Domain\ModelData\ModificationData;

interface ModificationCommandInterface
{
    public function create(ModificationData $data): ModificationData;

    public function update(ModificationData $data): ModificationData;

    public function deleteByModIdAndType(int $modId, string $type): void;
}
