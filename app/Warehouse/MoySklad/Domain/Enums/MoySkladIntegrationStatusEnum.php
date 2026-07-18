<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Domain\Enums;

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
