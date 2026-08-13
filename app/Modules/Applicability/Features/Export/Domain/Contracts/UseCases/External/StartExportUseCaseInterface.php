<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\UseCases\External;

use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportFileRequestDTO;

interface StartExportUseCaseInterface
{
    /**
     * Запускает export-файл по внешнему RabbitMQ-запросу.
     *
     * Шаги:
     * 1. Проверяет идемпотентность operation id.
     * 2. Делегирует создание файла adapter-у выбранного export type.
     * 3. Публикует итоговый completion payload во внешний контур.
     */
    public function execute(ExportFileRequestDTO $request): void;
}
