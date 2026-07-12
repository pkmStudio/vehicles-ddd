<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Contracts\UseCases\External;

use App\Warehouse\Export\Domain\DTOs\ExportFileRequestDTO;

/**
 * Порт сценария запуска Warehouse-экспорта из внешнего RabbitMQ-запроса.
 */
interface StartExportUseCaseInterface
{
    /**
     * Выполняет сценарий экспорта по валидированному DTO запроса.
     */
    public function execute(ExportFileRequestDTO $request): void;
}
