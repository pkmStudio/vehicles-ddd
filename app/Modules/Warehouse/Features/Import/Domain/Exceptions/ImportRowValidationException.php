<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Exceptions;

/**
 * Ошибка валидации конкретной строки импортируемого файла.
 */
final class ImportRowValidationException extends WarehouseImportException
{
    /**
     * Создаёт ошибку валидации строки с готовым доменным сообщением.
     */
    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
