<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Imports;

use App\Warehouse\Import\Domain\DTOs\ImportRunContextDTO;

/**
 * Общий порт Excel-адаптера, который импортирует файл Warehouse-каталога. Один порт обслуживает
 * оба триггера — Artisan-команду (userId=null в контексте) и RabbitMQ (userId из payload).
 */
interface FileImportInterface
{
    /**
     * Импортирует файл на $disk в рамках прогона, описанного $context.
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void;
}
