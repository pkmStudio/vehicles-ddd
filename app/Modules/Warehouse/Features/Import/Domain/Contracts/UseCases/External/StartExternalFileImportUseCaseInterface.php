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
     *
     * Шаги:
     * 1) Принять внешний DTO RabbitMQ import request.
     * 2) Проверить идемпотентность operationId.
     * 3) Выбрать импортный adapter по типу и запустить Excel import.
     */
    public function execute(ExternalImportFileRequestDTO $request): void;
}
