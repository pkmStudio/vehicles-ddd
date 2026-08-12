<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\DTOs;

use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;

final readonly class ExportFileRequestDTO
{
    /**
     * Хранит валидированный внешний запрос на создание export-файла.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public ExportTypeEnum $exportType,
        public string $disk,
    ) {}
}
