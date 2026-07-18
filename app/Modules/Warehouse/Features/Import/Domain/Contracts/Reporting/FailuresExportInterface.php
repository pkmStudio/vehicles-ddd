<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting;

/**
 * Маркер Excel-адаптера отчёта об ошибках Warehouse-импорта — подменяемая точка для
 * `app()->makeWith(...)` в ImportFailureReporter, самого контракта на методы не несёт
 * (see Vehicles\Import для того же паттерна).
 */
interface FailuresExportInterface {}
