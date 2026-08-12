<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\UseCases\External;

use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportFileRequestDTO;

/**
 * Порт сценария запуска Warehouse-экспорта из внешнего RabbitMQ-запроса.
 */
interface StartExportUseCaseInterface
{
    /**
     * Выполняет сценарий экспорта по валидированному DTO запроса.
     *
     * Шаги:
     * 1) Принять валидированный внешний запрос на Warehouse-экспорт.
     * 2) Запустить создание файла выбранного типа.
     * 3) Опубликовать итоговый статус выполнения.
     */
    public function execute(ExportFileRequestDTO $request): void;
}
