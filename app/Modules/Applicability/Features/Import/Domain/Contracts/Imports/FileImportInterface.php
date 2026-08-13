<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Imports;

use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportRunContextDTO;

interface FileImportInterface
{
    /**
     * Запускает импорт файла из заданного path/disk.
     *
     * Шаги:
     * 1. Получает путь к файлу и run context внешнего workflow.
     * 2. Читает файл с переданного disk или disk по умолчанию реализации.
     * 3. Делегирует обработку строк конкретному import adapter-у.
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void;
}
