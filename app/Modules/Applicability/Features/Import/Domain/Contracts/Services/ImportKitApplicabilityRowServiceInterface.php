<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Services;

use App\Modules\Applicability\Features\Import\Domain\DTOs\KitApplicabilityImportRowDTO;

interface ImportKitApplicabilityRowServiceInterface
{
    /**
     * Импортирует одну строку XLSX применяемости комплекта.
     *
     * Шаги:
     * 1. Валидирует обязательные идентификаторы строки.
     * 2. Проверяет внешние справочники kit и modification.
     * 3. Делегирует запись связи применяемости command boundary.
     */
    public function importFromRow(KitApplicabilityImportRowDTO $row): void;
}
