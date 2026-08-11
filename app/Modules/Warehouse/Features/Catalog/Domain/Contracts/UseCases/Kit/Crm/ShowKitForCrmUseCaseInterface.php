<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmListItemDTO;

/**
 * Описывает CRM read-сценарий detail-снимка комплекта.
 */
interface ShowKitForCrmUseCaseInterface
{
    /**
     * Возвращает detail-снимок комплекта по id.
     *
     * Шаги:
     * 1. Принять внутренний id комплекта.
     * 2. Вернуть DTO или `null`, если запись не найдена.
     */
    public function execute(int $id): ?KitCrmListItemDTO;
}
