<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs;

use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;

/**
 * DTO входящей команды на запуск импорта файла из общего хранилища.
 */
final readonly class ExternalImportFileRequestDTO
{
    /**
     * @param  int  $userId  внешний идентификатор инициатора импорта; нужен для отчёта об ошибках
     * @param  string  $operationId  уникальный идентификатор прогона; используется для идемпотентности, cache-ключей и cleanup
     * @param  ExternalImportTypeEnum  $importType  тип импортного адаптера, который нужно запустить
     * @param  string  $disk  внутренний Laravel Storage disk из конфига, где лежит файл импорта
     * @param  string  $path  относительный путь к файлу внутри disk
     * @param  bool  $cleanupAfterImport  удалять исходный файл после завершения импорта
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public ExternalImportTypeEnum $importType,
        public string $disk,
        public string $path,
        public bool $cleanupAfterImport = true,
    ) {}
}
