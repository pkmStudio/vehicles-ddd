<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Commands;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Commands\KitApplicabilityCommandInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\KitApplicability;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use Illuminate\Support\Facades\DB;

final readonly class KitApplicabilityCommand implements KitApplicabilityCommandInterface
{
    public function syncCalculatedTargets(
        int $kitId,
        ApplicabilityTargetTypeEnum $targetType,
        KitApplicabilityAlgorithmEnum $algorithm,
        array $targetIds,
    ): void {
        $targetIds = array_values(array_unique(array_map(static fn (int|string $id): int => (int) $id, $targetIds)));

        DB::transaction(function () use ($kitId, $targetType, $algorithm, $targetIds): void {
            KitApplicability::query()
                ->where('kit_id', $kitId)
                ->where('target_type', $targetType)
                ->where('source', ApplicabilitySourceEnum::CALCULATED)
                ->where('algorithm', $algorithm)
                ->when($targetIds !== [], static fn ($query) => $query->whereNotIn('target_id', $targetIds))
                ->delete();

            foreach ($targetIds as $targetId) {
                $existing = KitApplicability::query()
                    ->where('kit_id', $kitId)
                    ->where('target_type', $targetType)
                    ->where('target_id', $targetId)
                    ->first();

                if ($existing !== null && $existing->source !== ApplicabilitySourceEnum::CALCULATED) {
                    continue;
                }

                KitApplicability::query()->updateOrCreate(
                    [
                        'kit_id' => $kitId,
                        'target_type' => $targetType,
                        'target_id' => $targetId,
                    ],
                    [
                        'source' => ApplicabilitySourceEnum::CALCULATED,
                        'algorithm' => $algorithm,
                    ],
                );
            }
        });
    }
}
