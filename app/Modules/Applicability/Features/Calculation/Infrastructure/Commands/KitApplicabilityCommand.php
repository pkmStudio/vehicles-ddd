<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Commands;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Commands\KitApplicabilityCommandInterface;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\KitApplicability;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityCreated;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityDeleted;
use Illuminate\Support\Facades\DB;

/**
 * Синхронизирует рассчитанные цели применяемости набора в `kit_applicabilities`.
 */
final readonly class KitApplicabilityCommand implements KitApplicabilityCommandInterface
{
    /**
     * Обновляет calculated-связи набора и публикует факты создания/удаления.
     *
     * Шаги:
     * 1) Нормализовать target ids.
     * 2) Удалить stale calculated-записи текущего алгоритма.
     * 3) Создать недостающие calculated-записи, не перетирая imported/manual.
     */
    public function syncCalculatedTargets(
        int $kitId,
        ApplicabilityTargetTypeEnum $targetType,
        KitApplicabilityAlgorithmEnum $algorithm,
        array $targetIds,
    ): void {
        $toInteger = static fn (int|string $id): int => (int) $id;
        $targetIds = array_values(array_unique(array_map($toInteger, $targetIds)));

        DB::transaction(function () use ($kitId, $targetType, $algorithm, $targetIds): void {
            $deleteQuery = KitApplicability::query()
                ->where('kit_id', $kitId)
                ->where('target_type', $targetType)
                ->where('source', ApplicabilitySourceEnum::CALCULATED)
                ->where('algorithm', $algorithm);

            if ($targetIds !== []) {
                $deleteQuery->whereNotIn('target_id', $targetIds);
            }

            $deletedTargets = $deleteQuery
                ->pluck('target_id')
                ->map(static fn (int|string $targetId): int => (int) $targetId)
                ->all();

            $deleteQuery->delete();

            foreach ($deletedTargets as $targetId) {
                event(new KitApplicabilityDeleted(
                    kitId: $kitId,
                    targetType: $targetType,
                    targetId: $targetId,
                ));
            }

            foreach ($targetIds as $targetId) {
                $existing = KitApplicability::query()
                    ->where('kit_id', $kitId)
                    ->where('target_type', $targetType)
                    ->where('target_id', $targetId)
                    ->first();

                if ($existing !== null && $existing->source !== ApplicabilitySourceEnum::CALCULATED) {
                    continue;
                }

                $applicability = KitApplicability::query()->updateOrCreate(
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

                if ($applicability->wasRecentlyCreated) {
                    event(new KitApplicabilityCreated(
                        kitId: $kitId,
                        targetType: $targetType,
                        targetId: $targetId,
                    ));
                }
            }
        });
    }
}
