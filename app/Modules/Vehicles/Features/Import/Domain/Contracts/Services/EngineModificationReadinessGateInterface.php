<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services;

interface EngineModificationReadinessGateInterface
{
    public const string FLAG_ENGINES = 'engines_imported';

    public const string FLAG_MODIFICATIONS = 'modifications_imported';

    /**
     * Отметить завершение импорта engines.
     *
     * Шаги:
     * 1) Записать readiness marker engines.
     * 2) Проверить, можно ли запускать dependent engine-modification import.
     */
    public function markEnginesImported(): void;

    /**
     * Отметить завершение импорта modifications.
     *
     * Шаги:
     * 1) Записать readiness marker modifications.
     * 2) Проверить, можно ли запускать dependent engine-modification import.
     */
    public function markModificationsImported(): void;

    /**
     * Сбросить readiness markers каскада import.
     *
     * Шаги:
     * 1) Удалить marker engines.
     * 2) Удалить marker modifications.
     */
    public function reset(): void;
}
