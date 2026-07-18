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
     * @param  string  $runId  уникальный идентификатор прогона; используется для идемпотентности, cache-ключей и cleanup
     * @param  ExternalImportTypeEnum  $importType  тип импортного адаптера, который нужно запустить
     * @param  string  $disk  внутренний Laravel Storage disk из конфига, где лежит файл импорта
     * @param  string  $path  относительный путь к файлу внутри disk
     */
    public function __construct(
        public int $userId,
        public string $runId,
        public ExternalImportTypeEnum $importType,
        public string $disk,
        public string $path,
    ) {}
}
