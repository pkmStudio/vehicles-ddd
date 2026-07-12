<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Enums;

/**
 * Тип Warehouse-каталога, который внешний сервис просит импортировать через RabbitMQ.
 */
enum ImportTypeEnum: string
{
    case Nomenclature = 'nomenclature';
    case PackDimension = 'pack_dimension';
    case Kit = 'kit';
}
