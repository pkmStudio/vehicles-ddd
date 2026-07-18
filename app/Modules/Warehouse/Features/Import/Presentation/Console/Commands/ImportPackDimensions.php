<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Presentation\Console\Commands;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Тонкая точка входа для ручного/операционного запуска импорта упаковочных размеров Warehouse.
 * userId в контексте — null: оператор в терминале, не HTTP/Rabbit-инициатор (см. ImportRunContextDTO).
 */
final class ImportPackDimensions extends Command
{
    protected $signature = 'warehouse:import-pack-dimensions {path : Путь к Excel-файлу на диске "local"}';

    protected $description = 'Импортирует упаковочные размеры Warehouse из Excel-файла';

    /**
     * Создаёт контекст ручного запуска и делегирует импорт Excel-адаптеру.
     */
    public function handle(PackDimensionImportInterface $import): int
    {
        $context = new ImportRunContextDTO(
            userId: null,
            runId: (string) Str::uuid(),
        );

        $import->import(
            path: $this->argument('path'),
            context: $context,
        );

        $this->info("Импорт упаковочных размеров поставлен в очередь (runId: {$context->runId}).");

        return self::SUCCESS;
    }
}
