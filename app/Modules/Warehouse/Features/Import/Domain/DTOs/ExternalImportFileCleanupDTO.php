<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\DTOs;

/**
 * Отложенное задание на удаление исходного файла внешнего импорта после его завершения.
 */
final readonly class ExternalImportFileCleanupDTO
{
    /**
     * Хранит disk и path файла, который нужно удалить после того, как импорт реально закончится.
     */
    public function __construct(
        public string $disk,
        public string $path,
    ) {}
}
