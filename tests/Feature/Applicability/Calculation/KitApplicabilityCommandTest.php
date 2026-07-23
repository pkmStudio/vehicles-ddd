<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Infrastructure\Commands\KitApplicabilityCommand;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\KitApplicability;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityCreated;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityDeleted;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\Applicability\CreatesWarehouseKit;
use Tests\TestCase;

/**
 * Проверяет mutation-события и правила синхронизации calculated-применяемости.
 */
final class KitApplicabilityCommandTest extends TestCase
{
    use RefreshDatabase;
    use CreatesWarehouseKit;

    public function test_creates_calculated_targets_and_dispatches_created(): void
    {
        Event::fake();
        $kitId = $this->createWarehouseKit();

        (new KitApplicabilityCommand)->syncCalculatedTargets(
            kitId: $kitId,
            targetType: ApplicabilityTargetTypeEnum::PART_SPECIFICATION,
            algorithm: KitApplicabilityAlgorithmEnum::WIPER,
            targetIds: [10],
        );

        $this->assertDatabaseHas('kit_applicabilities', [
            'kit_id' => $kitId,
            'target_type' => ApplicabilityTargetTypeEnum::PART_SPECIFICATION->value,
            'target_id' => 10,
            'source' => ApplicabilitySourceEnum::CALCULATED->value,
            'algorithm' => KitApplicabilityAlgorithmEnum::WIPER->value,
        ]);

        Event::assertDispatched(
            KitApplicabilityCreated::class,
            fn (KitApplicabilityCreated $event): bool => $event->kitId === $kitId
                && $event->targetType === ApplicabilityTargetTypeEnum::PART_SPECIFICATION
                && $event->targetId === 10
                && $event->source === ApplicabilitySourceEnum::CALCULATED
                && $event->algorithm === KitApplicabilityAlgorithmEnum::WIPER,
        );
    }

    public function test_deletes_stale_calculated_targets_and_dispatches_deleted(): void
    {
        Event::fake();
        $kitId = $this->createWarehouseKit();
        $this->createApplicability(
            kitId: $kitId,
            targetId: 10,
            source: ApplicabilitySourceEnum::CALCULATED,
            algorithm: KitApplicabilityAlgorithmEnum::WIPER,
        );

        (new KitApplicabilityCommand)->syncCalculatedTargets(
            kitId: $kitId,
            targetType: ApplicabilityTargetTypeEnum::PART_SPECIFICATION,
            algorithm: KitApplicabilityAlgorithmEnum::WIPER,
            targetIds: [],
        );

        $this->assertDatabaseMissing('kit_applicabilities', [
            'kit_id' => $kitId,
            'target_type' => ApplicabilityTargetTypeEnum::PART_SPECIFICATION->value,
            'target_id' => 10,
        ]);

        Event::assertDispatched(
            KitApplicabilityDeleted::class,
            fn (KitApplicabilityDeleted $event): bool => $event->kitId === $kitId
                && $event->targetType === ApplicabilityTargetTypeEnum::PART_SPECIFICATION
                && $event->targetId === 10
                && $event->source === ApplicabilitySourceEnum::CALCULATED
                && $event->algorithm === KitApplicabilityAlgorithmEnum::WIPER,
        );
    }

    public function test_updates_existing_calculated_target_algorithm_and_dispatches_updated(): void
    {
        Event::fake();
        $kitId = $this->createWarehouseKit();
        $this->createApplicability(
            kitId: $kitId,
            targetId: 10,
            source: ApplicabilitySourceEnum::CALCULATED,
            algorithm: KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
        );

        (new KitApplicabilityCommand)->syncCalculatedTargets(
            kitId: $kitId,
            targetType: ApplicabilityTargetTypeEnum::PART_SPECIFICATION,
            algorithm: KitApplicabilityAlgorithmEnum::WIPER,
            targetIds: [10],
        );

        $this->assertDatabaseHas('kit_applicabilities', [
            'kit_id' => $kitId,
            'target_type' => ApplicabilityTargetTypeEnum::PART_SPECIFICATION->value,
            'target_id' => 10,
            'source' => ApplicabilitySourceEnum::CALCULATED->value,
            'algorithm' => KitApplicabilityAlgorithmEnum::WIPER->value,
        ]);

        Event::assertDispatched(
            KitApplicabilityUpdated::class,
            fn (KitApplicabilityUpdated $event): bool => $event->kitId === $kitId
                && $event->targetType === ApplicabilityTargetTypeEnum::PART_SPECIFICATION
                && $event->targetId === 10
                && $event->source === ApplicabilitySourceEnum::CALCULATED
                && $event->algorithm === KitApplicabilityAlgorithmEnum::WIPER,
        );
    }

    public function test_does_not_touch_imported_targets(): void
    {
        Event::fake();
        $kitId = $this->createWarehouseKit();
        $this->createApplicability(
            kitId: $kitId,
            targetId: 10,
            source: ApplicabilitySourceEnum::IMPORTED,
            algorithm: KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
        );

        (new KitApplicabilityCommand)->syncCalculatedTargets(
            kitId: $kitId,
            targetType: ApplicabilityTargetTypeEnum::PART_SPECIFICATION,
            algorithm: KitApplicabilityAlgorithmEnum::WIPER,
            targetIds: [10],
        );

        $this->assertDatabaseHas('kit_applicabilities', [
            'kit_id' => $kitId,
            'target_type' => ApplicabilityTargetTypeEnum::PART_SPECIFICATION->value,
            'target_id' => 10,
            'source' => ApplicabilitySourceEnum::IMPORTED->value,
            'algorithm' => KitApplicabilityAlgorithmEnum::MANUAL_XLSX->value,
        ]);

        Event::assertNotDispatched(KitApplicabilityUpdated::class);
    }

    private function createApplicability(
        int $kitId,
        int $targetId,
        ApplicabilitySourceEnum $source,
        KitApplicabilityAlgorithmEnum $algorithm,
    ): void {
        KitApplicability::query()->create([
            'kit_id' => $kitId,
            'target_type' => ApplicabilityTargetTypeEnum::PART_SPECIFICATION,
            'target_id' => $targetId,
            'source' => $source,
            'algorithm' => $algorithm,
        ]);
    }
}
