<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\UseCases\External;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportFileRequestDTO;

/**
 * Порт внешнего сценария запуска export-файла Vehicles.
 */
interface StartExportUseCaseInterface
{
    /**
     * Запускает экспорт по validated external request.
     *
     * Шаги:
     * 1) Проверить идемпотентность operationId.
     * 2) Построить export-файл выбранного типа.
     * 3) Опубликовать notification с итоговым статусом.
     */
    public function execute(ExportFileRequestDTO $request): void;
}
