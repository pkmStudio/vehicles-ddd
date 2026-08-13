<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Commands;

interface KitApplicabilityCommandInterface
{
    /**
     * Сохраняет imported-применяемость комплекта к модификации.
     *
     * Шаги:
     * 1. Находит или создает связь kit/modification.
     * 2. Фиксирует source как imported и algorithm как manual XLSX.
     * 3. Публикует факт создания или изменения применяемости.
     */
    public function saveImportedModificationTarget(int $kitId, int $modificationId): void;
}
