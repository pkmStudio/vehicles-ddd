<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Exports;

use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;

interface FileExportInterface
{
    /**
     * Создает файл экспорта для указанного run context.
     *
     * Шаги:
     * 1. Использует operation id из context для стабильного имени или trace export-файла.
     * 2. Сохраняет файл на переданный disk или на disk по умолчанию реализации.
     * 3. Возвращает путь к файлу для completion notification.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string;
}
