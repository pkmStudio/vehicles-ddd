<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine;

use App\Vehicles\Domain\DTOs\ModificationSparkPlugResult;

interface UpsertSparkPlugSpecByModificationUseCaseInterface
{
    public function execute(int $msId, int $modId, array $details): ModificationSparkPlugResult;
}
