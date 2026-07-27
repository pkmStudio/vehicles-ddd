<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Enums;

/**
 * Тип Warehouse-каталога, который внешний сервис просит выгрузить через RabbitMQ.
 */
enum ExportTypeEnum: string
{
    case NomenclatureByType = 'nomenclature_by_type';
    case PackDimension = 'pack_dimension';
    case Kit = 'kit';
    case WiperAdapterAudit = 'wiper_adapter_audit';
}
