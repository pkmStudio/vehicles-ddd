<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Exceptions;

use DomainException;

/**
 * Базовая доменная ошибка Warehouse-импорта, которую можно безопасно показать в отчете.
 */
abstract class WarehouseImportException extends DomainException {}
