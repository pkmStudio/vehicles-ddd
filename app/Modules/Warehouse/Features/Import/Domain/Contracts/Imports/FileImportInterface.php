<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;

/**
 * Общий порт Excel-адаптера, который импортирует файл Warehouse-каталога. Один порт обслуживает
 * оба триггера — Artisan-команду (userId=null в контексте) и RabbitMQ (userId из payload).
 */
interface FileImportInterface
{
    /**
     * Импортирует файл на $disk в рамках прогона, описанного $context.
     *
     * Шаги:
     * 1) Принять путь файла, контекст запуска и optional Storage disk.
     * 2) Подготовить adapter к конкретному прогону импорта.
     * 3) Передать файл Laravel Excel для чтения/очереди.
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void;
}
