<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\UseCases\External;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;

/**
 * Порт сценария запуска Warehouse-импорта из внешнего RabbitMQ-запроса.
 */
interface StartExternalFileImportUseCaseInterface
{
    /**
     * Выполняет сценарий импорта по валидированному DTO запроса.
     */
    public function execute(ExternalImportFileRequestDTO $request): void;
}
