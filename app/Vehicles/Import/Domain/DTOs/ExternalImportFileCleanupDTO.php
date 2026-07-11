<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\DTOs;

/**
 * DTO инструкции отложенной очистки исходного файла после завершения импорта.
 */
final readonly class ExternalImportFileCleanupDTO
{
    /**
     * @param  string  $disk  Laravel Storage disk, из которого нужно удалить исходный файл
     * @param  string  $path  относительный путь исходного файла внутри disk
     */
    public function __construct(
        public string $disk,
        public string $path,
    ) {}

    /**
     * Сериализует DTO для хранения в cache между стартом и завершением queued-импорта.
     *
     * @return array{disk: string, path: string}
     */
    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
        ];
    }
}
