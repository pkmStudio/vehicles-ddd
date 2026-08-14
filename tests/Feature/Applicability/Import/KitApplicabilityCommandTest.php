<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Import;

use App\Modules\Applicability\Features\Import\Infrastructure\Commands\KitApplicabilityCommand;
use App\Modules\Applicability\Features\Import\Infrastructure\Models\KitApplicability;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityCreated;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\Applicability\CreatesWarehouseKit;
use Tests\TestCase;

/**
 * Проверяет mutation-события импортированной применяемости.
 */
final class KitApplicabilityCommandTest extends TestCase
{
    use CreatesWarehouseKit;
    use RefreshDatabase;

    public function test_creates_imported_target_and_dispatches_created(): void
    {
        Event::fake();
        $kitId = $this->createWarehouseKit();

        (new KitApplicabilityCommand)->saveImportedModificationTarget(
            kitId: $kitId,
            modificationId: 50,
        );

        $this->assertDatabaseHas('kit_applicabilities', [
            'kit_id' => $kitId,
            'target_type' => ApplicabilityTargetTypeEnum::MODIFICATION->value,
            'target_id' => 50,
            'source' => ApplicabilitySourceEnum::IMPORTED->value,
            'algorithm' => KitApplicabilityAlgorithmEnum::MANUAL_XLSX->value,
        ]);

        Event::assertDispatched(
            KitApplicabilityCreated::class,
            fn (KitApplicabilityCreated $event): bool => $event->kitId === $kitId
                && $event->targetType === ApplicabilityTargetTypeEnum::MODIFICATION
                && $event->targetId === 50
                && $event->source === ApplicabilitySourceEnum::IMPORTED
                && $event->algorithm === KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
        );
    }

    public function test_updates_existing_target_and_dispatches_updated(): void
    {
        Event::fake();
        $kitId = $this->createWarehouseKit();
        KitApplicability::query()->create([
            'kit_id' => $kitId,
            'target_type' => ApplicabilityTargetTypeEnum::MODIFICATION,
            'target_id' => 50,
            'source' => ApplicabilitySourceEnum::CALCULATED,
            'algorithm' => KitApplicabilityAlgorithmEnum::WIPER,
        ]);

        (new KitApplicabilityCommand)->saveImportedModificationTarget(
            kitId: $kitId,
            modificationId: 50,
        );

        $this->assertDatabaseHas('kit_applicabilities', [
            'kit_id' => $kitId,
            'target_type' => ApplicabilityTargetTypeEnum::MODIFICATION->value,
            'target_id' => 50,
            'source' => ApplicabilitySourceEnum::IMPORTED->value,
            'algorithm' => KitApplicabilityAlgorithmEnum::MANUAL_XLSX->value,
        ]);

        Event::assertDispatched(
            KitApplicabilityUpdated::class,
            fn (KitApplicabilityUpdated $event): bool => $event->kitId === $kitId
                && $event->targetType === ApplicabilityTargetTypeEnum::MODIFICATION
                && $event->targetId === 50
                && $event->source === ApplicabilitySourceEnum::IMPORTED
                && $event->algorithm === KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
        );
    }

    public function test_keeps_existing_imported_target_without_events(): void
    {
        Event::fake();
        $kitId = $this->createWarehouseKit();
        KitApplicability::query()->create([
            'kit_id' => $kitId,
            'target_type' => ApplicabilityTargetTypeEnum::MODIFICATION,
            'target_id' => 50,
            'source' => ApplicabilitySourceEnum::IMPORTED,
            'algorithm' => KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
        ]);

        (new KitApplicabilityCommand)->saveImportedModificationTarget(
            kitId: $kitId,
            modificationId: 50,
        );

        Event::assertNotDispatched(KitApplicabilityCreated::class);
        Event::assertNotDispatched(KitApplicabilityUpdated::class);
    }
}
