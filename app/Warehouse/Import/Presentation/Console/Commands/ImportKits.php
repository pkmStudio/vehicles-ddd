<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Presentation\Console\Commands;

use App\Warehouse\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Warehouse\Import\Domain\DTOs\ImportRunContextDTO;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Тонкая точка входа для ручного/операционного запуска импорта Warehouse-наборов (Kit). userId в
 * контексте — null: оператор в терминале, не HTTP/Rabbit-инициатор (см. ImportRunContextDTO).
 */
final class ImportKits extends Command
{
    protected $signature = 'warehouse:import-kits {path : Путь к Excel-файлу на диске "local"}';

    protected $description = 'Импортирует Warehouse-наборы (Kit) из Excel-файла';

    /**
     * Создаёт контекст ручного запуска и делегирует импорт Excel-адаптеру.
     */
    public function handle(KitImportInterface $import): int
    {
        $context = new ImportRunContextDTO(
            userId: null,
            runId: (string) Str::uuid(),
        );

        $import->import(
            path: $this->argument('path'),
            context: $context,
        );

        $this->info("Импорт наборов поставлен в очередь (runId: {$context->runId}).");

        return self::SUCCESS;
    }
}
