<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\UseCases\External;

use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;

interface StartExternalFileImportUseCaseInterface
{
    /**
     * Запускает внешний импорт файла применяемости комплектов.
     *
     * Шаги:
     * 1. Принимает request DTO с operation id, пользователем и файлом.
     * 2. Сохраняет external import context и загружает файл в рабочее storage.
     * 3. Выбирает import adapter по типу импорта и запускает обработку файла.
     */
    public function execute(ExternalImportFileRequestDTO $request): void;
}
