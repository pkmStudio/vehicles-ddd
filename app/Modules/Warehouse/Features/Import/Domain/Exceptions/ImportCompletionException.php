<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Exceptions;

/**
 * Ошибка определения результата завершённого Warehouse-импорта.
 */
final class ImportCompletionException extends WarehouseImportException
{
    public static function unknownEvent(string $eventClass): self
    {
        return new self('Неизвестное событие завершения Warehouse-импорта: '.$eventClass);
    }
}
