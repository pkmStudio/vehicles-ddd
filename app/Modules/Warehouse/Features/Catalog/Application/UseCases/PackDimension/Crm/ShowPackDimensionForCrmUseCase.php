<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\PackDimension\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmListItemDTO;

/**
 * Оркестрирует CRM read-сценарий detail-снимка упаковочного размера.
 */
final readonly class ShowPackDimensionForCrmUseCase
{
    /**
     * Инициализирует порт репозитория упаковочных размеров для CRM detail-сценария.
     *
     * Шаги:
     * 1. Получает порт репозитория owner-слоя Catalog.
     * 2. Сохраняет port для поиск по внутреннему id.
     */
    public function __construct(
        private PackDimensionCrmRepositoryInterface $packDimensions,
    ) {}

    /**
     * Возвращает detail-снимок упаковочного размера по id.
     *
     * Шаги:
     * 1. Принимает внутренний id упаковочного размера.
     * 2. Делегирует поиск порт репозитория.
     * 3. Возвращает DTO или `null`, если запись не найдена.
     */
    public function execute(int $id): ?PackDimensionCrmListItemDTO
    {
        return $this->packDimensions->findById($id);
    }
}
