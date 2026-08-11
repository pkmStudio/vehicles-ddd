<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmListItemDTO;

/**
 * Описывает CRM read-сценарий detail-снимка упаковочного размера.
 */
interface ShowPackDimensionForCrmUseCaseInterface
{
    /**
     * Возвращает detail-снимок упаковочного размера по id.
     *
     * Шаги:
     * 1. Принять внутренний id упаковочного размера.
     * 2. Вернуть DTO или `null`, если запись не найдена.
     */
    public function execute(int $id): ?PackDimensionCrmListItemDTO;
}
