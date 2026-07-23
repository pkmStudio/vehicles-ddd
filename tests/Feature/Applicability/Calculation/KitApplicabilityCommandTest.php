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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Проверяет mutation-события и правила синхронизации calculated-применяемости.
 */
final class KitApplicabilityCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_calculated_targets_and_dispatches_created(): void
    {
        Event::fake();
        $kitId = $this->createKit();

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
                && $event->targetId === 10,
        );
    }

    public function test_deletes_stale_calculated_targets_and_dispatches_deleted(): void
    {
        Event::fake();
        $kitId = $this->createKit();
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
                && $event->targetId === 10,
        );
    }

    public function test_updates_existing_calculated_target_algorithm_and_dispatches_updated(): void
    {
        Event::fake();
        $kitId = $this->createKit();
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
                && $event->targetId === 10,
        );
    }

    public function test_does_not_touch_imported_targets(): void
    {
        Event::fake();
        $kitId = $this->createKit();
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

    private function createKit(): int
    {
        $typeId = DB::table('types')->insertGetId([
            'name' => 'ЩЕТКИ СТЕКЛООЧИСТИТЕЛЯ',
            'char' => 'WB',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $packDimensionId = DB::table('pack_dimensions')->insertGetId([
            'name' => 'Box',
            'weight' => 1,
            'width' => 1,
            'height' => 1,
            'length' => 1,
            'price' => 1,
            'generated' => false,
            'type_id' => $typeId,
        ]);

        return DB::table('kits')->insertGetId([
            'complectation' => 'Kit',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 1,
            'is_sale_separately' => false,
            'is_active' => true,
            'pack_dimension_id' => $packDimensionId,
            'type_id' => $typeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
