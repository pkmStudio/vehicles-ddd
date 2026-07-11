<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\DTOs;

use App\Vehicles\Export\Domain\Enums\ExportTypeEnum;

/**
 * DTO входящей команды на запуск экспорта каталога, симметрично
 * Import\Domain\DTOs\ExternalImportFileRequestDTO.
 */
final readonly class ExportFileRequestDTO
{
    /**
     * @param  int  $userId  внешний идентификатор инициатора экспорта; уходит в уведомление о завершении
     * @param  string  $runId  уникальный идентификатор прогона; идемпотентность + имя файла
     * @param  ExportTypeEnum  $exportType  какой каталог экспортировать
     * @param  bool  $isAllow  бизнес-фильтр «только допущенные» (используется только Vehicle)
     * @param  string  $disk  Laravel Storage disk, куда пишется сгенерированный файл
     */
    public function __construct(
        public int $userId,
        public string $runId,
        public ExportTypeEnum $exportType,
        public bool $isAllow,
        public string $disk,
    ) {}
}
