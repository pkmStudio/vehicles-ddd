<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Exceptions;

/**
 * Ошибка валидации конкретной строки импортируемого файла.
 */
final class ImportRowValidationException extends WarehouseImportException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
