<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Services;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Commands\NomenclatureIntegrationCommandInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureIntegrationRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Массово восстанавливает/создаёт связи `nomenclature_integrations` для МойСклад.
 */
final readonly class NomenclatureBackfillService
{
    /**
     * Получает порты чтения/записи и сервис синхронизации одной номенклатуры.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private NomenclatureIntegrationRepositoryInterface $integrations,
        private NomenclatureIntegrationCommandInterface $integrationCommand,
        private NomenclatureSyncService $sync,
    ) {}

    /**
     * Выполняет backfill чанками и возвращает счётчики результата.
     *
     * @return array{processed:int,synced:int,failed:int,skipped:int}
     */
    public function execute(int $chunk = 100): array
    {
        $stats = [
            'processed' => 0,
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $this->nomenclatures->chunkById(max(1, $chunk), function (Collection $nomenclatures) use (&$stats): void {
            foreach ($nomenclatures as $nomenclature) {
                $this->syncItem($nomenclature, $stats);
            }
        });

        return $stats;
    }

    /**
     * Синхронизирует одну строку backfill и обновляет счётчики результата.
     *
     * @param  array{processed:int,synced:int,failed:int,skipped:int}  $stats
     */
    private function syncItem(NomenclatureData $nomenclature, array &$stats): void
    {
        $stats['processed']++;

        $integration = $this->integrations->firstForNomenclature($nomenclature->id);

        if ($integration !== null && is_string($integration->externalId) && $integration->externalId !== '') {
            $stats['skipped']++;

            return;
        }

        try {
            $synced = $this->sync->backfillItem($nomenclature);
            $synced ? $stats['synced']++ : $stats['skipped']++;
        } catch (Throwable $e) {
            $stats['failed']++;

            $this->integrationCommand->markBackfillFailed(
                nomenclatureId: $nomenclature->id,
                externalCode: "nomenclature:{$nomenclature->id}",
                error: $e->getMessage(),
            );

            Log::error('MoySklad backfill: ошибка синхронизации номенклатуры.', [
                'nomenclature_id' => $nomenclature->id,
                'part_number' => $nomenclature->partNumber,
                'error' => $e->getMessage(),
                'operation' => 'backfill_nomenclature_integration',
            ]);
        }
    }
}
