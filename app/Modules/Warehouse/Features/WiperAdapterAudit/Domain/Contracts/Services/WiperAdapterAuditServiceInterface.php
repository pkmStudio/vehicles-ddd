<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\DTOs\WiperAdapterAuditRowDTO;
use Illuminate\Support\Collection;

/**
 * Порт расчёта строк отчёта о несовпадении адаптеров дворников.
 */
interface WiperAdapterAuditServiceInterface
{
    /**
     * Возвращает готовые строки отчёта.
     * Шаги:
     * 1) Загрузить kits-кандидаты через repository.
     * 2) Сравнить адаптеры из состава комплекта с адаптерами, заявленными в details щёток.
     * 3) Вернуть строки отчёта только для найденных расхождений.
     *
     * @return Collection<int, WiperAdapterAuditRowDTO>
     */
    public function rows(): Collection;
}
