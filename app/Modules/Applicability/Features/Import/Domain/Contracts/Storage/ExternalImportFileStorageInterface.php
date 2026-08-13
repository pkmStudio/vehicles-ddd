<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Storage;

interface ExternalImportFileStorageInterface
{
    /**
     * Удаляет внешний import-файл после завершения workflow.
     *
     * Шаги:
     * 1. Выбирает storage disk по сохраненной cleanup metadata.
     * 2. Удаляет файл по исходному path.
     */
    public function delete(string $disk, string $path): void;
}
