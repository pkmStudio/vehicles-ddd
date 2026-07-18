<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\External;

/**
 * Safety-net очистка сгенерированных файлов экспорта на output-диске.
 * Основной путь удаления — принимающий сервис, забравший файл по скачиванию;
 * это подстраховка на случай, если он этого не сделал.
 */
interface CleanupStaleExportFilesServiceInterface
{
    /**
     * Удалить файлы экспорта старше retention-порога (vehicles.export.output.retention_hours).
     *
     * @return int количество удалённых файлов
     */
    public function cleanup(): int;
}
