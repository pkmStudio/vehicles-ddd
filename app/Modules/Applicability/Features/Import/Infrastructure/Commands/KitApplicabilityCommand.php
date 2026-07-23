<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Commands;

use App\Modules\Applicability\Features\Calculation\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Commands\KitApplicabilityCommandInterface;
use App\Modules\Applicability\Features\Import\Infrastructure\Models\KitApplicability;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;

final readonly class KitApplicabilityCommand implements KitApplicabilityCommandInterface
{
    public function saveImportedModificationTarget(int $kitId, int $modificationId): void
    {
        KitApplicability::query()->updateOrCreate(
            [
                'kit_id' => $kitId,
                'target_type' => ApplicabilityTargetTypeEnum::MODIFICATION,
                'target_id' => $modificationId,
            ],
            [
                'source' => ApplicabilitySourceEnum::IMPORTED,
                'algorithm' => KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
            ],
        );
    }
}
