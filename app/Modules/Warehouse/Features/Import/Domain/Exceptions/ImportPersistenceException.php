<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Exceptions;

/**
 * Ошибка записи валидной импортной строки в Warehouse-хранилище.
 */
final class ImportPersistenceException extends WarehouseImportException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
