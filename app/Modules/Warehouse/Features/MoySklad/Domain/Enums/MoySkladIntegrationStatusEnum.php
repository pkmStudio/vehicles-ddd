<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Enums;

/**
 * Локальные статусы связи Warehouse-номенклатуры с товаром МойСклад.
 */
enum MoySkladIntegrationStatusEnum: string
{
    case Pending = 'pending';
    case Synced = 'synced';
    case Failed = 'failed';
    case Deleted = 'deleted';
}
