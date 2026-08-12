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
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityUpdated;
use Illuminate\Support\Facades\DB;

/**
 * Синхронизирует рассчитанные цели применяемости набора в `kit_applicabilities`.
 */
final readonly class KitApplicabilityCommand implements KitApplicabilityCommandInterface
{
    /**
     * Обновляет calculated-связи набора и публикует факты создания/изменения/удаления.
     *
     * Шаги:
     * 1. Нормализует target ids к уникальному списку integer.
     * 2. В transaction находит stale calculated-записи текущего kit/type/algorithm.
     * 3. Удаляет stale calculated-записи и публикует `KitApplicabilityDeleted`.
     * 4. Для каждого target id проверяет существующую связь.
     * 5. Не перетирает imported/manual связь, если она уже есть для target.
     * 6. Создает или обновляет calculated-связь.
     * 7. Публикует `KitApplicabilityCreated` или `KitApplicabilityUpdated`, когда состояние изменилось.
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

            $deletedTargets = (clone $deleteQuery)
                ->pluck('target_id')
                ->map(static fn (int|string $targetId): int => (int) $targetId)
                ->all();

            $deleteQuery->delete();

            foreach ($deletedTargets as $targetId) {
                event(new KitApplicabilityDeleted(
                    kitId: $kitId,
                    targetType: $targetType,
                    targetId: $targetId,
                    source: ApplicabilitySourceEnum::CALCULATED,
                    algorithm: $algorithm,
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

                $shouldDispatchUpdated = $existing !== null
                    && (
                        $existing->source !== ApplicabilitySourceEnum::CALCULATED
                        || $existing->algorithm !== $algorithm
                    );

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
                        source: ApplicabilitySourceEnum::CALCULATED,
                        algorithm: $algorithm,
                    ));
                }

                if (! $applicability->wasRecentlyCreated && $shouldDispatchUpdated) {
                    event(new KitApplicabilityUpdated(
                        kitId: $kitId,
                        targetType: $targetType,
                        targetId: $targetId,
                        source: ApplicabilitySourceEnum::CALCULATED,
                        algorithm: $algorithm,
                    ));
                }
            }
        });
    }
}
