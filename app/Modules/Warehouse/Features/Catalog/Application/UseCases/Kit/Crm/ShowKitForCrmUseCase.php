<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Kit\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\Crm\ShowKitForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmListItemDTO;

/**
 * Оркестрирует CRM read-сценарий detail-снимка комплекта.
 */
final readonly class ShowKitForCrmUseCase implements ShowKitForCrmUseCaseInterface
{
    /**
     * Инициализирует порт репозитория комплектов для CRM detail-сценария.
     *
     * Шаги:
     * 1. Получает порт репозитория owner-слоя Catalog.
     * 2. Сохраняет port для поиск по внутреннему id.
     */
    public function __construct(
        private KitCrmRepositoryInterface $kits,
    ) {}

    /**
     * Возвращает detail-снимок комплекта по id.
     *
     * Шаги:
     * 1. Принимает внутренний id комплекта.
     * 2. Делегирует поиск порт репозитория.
     * 3. Возвращает DTO или `null`, если запись не найдена.
     */
    public function execute(int $id): ?KitCrmListItemDTO
    {
        return $this->kits->findById($id);
    }
}
