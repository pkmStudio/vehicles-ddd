<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Services;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;

/**
 * Массово ставит задачи синхронизации Warehouse-номенклатуры с МойСклад.
 */
final readonly class NomenclatureBackfillService
{
    /**
     * Получает порт чтения номенклатуры и порт постановки sync-задач.
     * Шаги:
     * 1) Сохранить repository номенклатуры для cursor-чтения по id.
     * 2) Сохранить dispatcher для постановки sync jobs без знания конкретной queue реализации.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private NomenclatureSyncDispatcherInterface $dispatcher,
    ) {}

    /**
     * Ставит sync-задачи для всех номенклатур чанками.
     * Шаги:
     * 1) Нормализовать chunk до значения не меньше 1.
     * 2) Получить cursor Data-снимков номенклатуры из repository.
     * 3) Для каждой номенклатуры поставить sync job по id.
     */
    public function execute(int $chunk = 100): void
    {
        foreach ($this->nomenclatures->cursorById(max(1, $chunk)) as $nomenclature) {
            $this->dispatcher->dispatchSync($nomenclature->id);
        }
    }
}
