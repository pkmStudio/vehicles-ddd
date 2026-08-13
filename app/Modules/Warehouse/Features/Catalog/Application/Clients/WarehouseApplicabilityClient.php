<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\Applicability\WarehouseApplicabilityRepositoryInterface;
use App\Modules\Warehouse\Shared\Domain\Contracts\Clients\WarehouseApplicabilityClientInterface;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;

final readonly class WarehouseApplicabilityClient implements WarehouseApplicabilityClientInterface
{
    /**
     * Получает repository, который строит Warehouse-наборы для расчета применяемости.
     *
     * Шаги:
     * 1) Принять WarehouseApplicabilityRepositoryInterface из DI container.
     * 2) Использовать repository для streaming-read комплектов и проверки их существования.
     */
    public function __construct(
        private WarehouseApplicabilityRepositoryInterface $kits,
    ) {}

    /**
     * @return итерируемый набор<int, WarehouseKitForApplicabilityDTO>
     *
     * Шаги:
     * 1) Принять необязательный kitId и размер чанка.
     * 2) Передать параметры в репозиторий применяемости Warehouse.
     * 3) Вернуть итерируемый набор активных комплектов для расчёта применяемости.
     */
    public function activeApplicabilityKits(?int $kitId = null, int $chunk = 1000): iterable
    {
        return $this->kits->activeKits(
            kitId: $kitId,
            chunk: $chunk,
        );
    }

    /**
     * Проверяет наличие комплекта Warehouse по идентификатору.
     *
     * Шаги:
     * 1) Принять идентификатор комплекта.
     * 2) Делегировать проверку существования репозиторий.
     * 3) Вернуть логический результат без загрузки полного комплекта.
     */
    public function kitExists(int $kitId): bool
    {
        return $this->kits->kitExists($kitId);
    }
}
