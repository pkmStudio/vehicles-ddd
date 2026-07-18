<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\MoySklad;

use App\Warehouse\MoySklad\Infrastructure\Jobs\DeleteNomenclatureJob;
use App\Warehouse\MoySklad\Infrastructure\Jobs\SyncNomenclatureJob;
use App\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;
use App\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted;
use App\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Проверяет подписку MoySklad-фичи на публичные Warehouse events.
 */
final class NomenclatureEventListenersTest extends TestCase
{
    /**
     * Включает sync feature flag и изолирует очередь.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['warehouse.moysklad.nomenclature_sync.enabled' => true]);
        Queue::fake();
    }

    /**
     * Проверяет, что create/update события ставят sync job.
     */
    public function test_created_and_updated_events_dispatch_sync_job(): void
    {
        event(new NomenclatureCreated(
            userId: 42,
            operationId: 'op-create',
            nomenclature: ['id' => 10],
        ));

        event(new NomenclatureUpdated(
            userId: 42,
            operationId: 'op-update',
            nomenclature: ['id' => 10],
        ));

        Queue::assertPushed(SyncNomenclatureJob::class, 2);
    }

    /**
     * Проверяет, что delete событие ставит job удаления в МойСклад.
     */
    public function test_deleted_event_dispatches_delete_job(): void
    {
        event(new NomenclatureDeleted(
            userId: 42,
            operationId: 'op-delete',
            nomenclatureId: 10,
            partNumber: 'BP-10',
            integrations: [
                [
                    'id' => 5,
                    'provider' => 'moysklad',
                    'external_id' => '33333333-3333-3333-3333-333333333333',
                ],
            ],
        ));

        Queue::assertPushed(DeleteNomenclatureJob::class);
    }
}
