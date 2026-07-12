<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Enums;

/**
 * Тип Warehouse-каталога, который внешний сервис просит выгрузить через RabbitMQ.
 */
enum ExportTypeEnum: string
{
    case NomenclatureByType = 'nomenclature_by_type';
    case Kit = 'kit';
    case WiperAdapterAudit = 'wiper_adapter_audit';
}
