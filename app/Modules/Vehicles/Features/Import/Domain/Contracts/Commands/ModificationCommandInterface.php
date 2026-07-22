<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

interface ModificationCommandInterface
{
    public function create(ModificationData $data): ModificationData;

    public function updateByModIdAndType(ModificationData $data): ModificationData;
}
