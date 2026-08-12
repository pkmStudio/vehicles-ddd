<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * Выбирает адаптер экспорта по типу из входящего сообщения.
 */
interface ExportFileFactoryInterface
{
    /**
     * Возвращает concrete export adapter для запрошенного типа файла.
     *
     * Шаги:
     * 1) Сопоставить export type с поддерживаемым adapter contract.
     * 2) Передать runtime-флаги adapter-у, если они нужны конкретному типу.
     * 3) Вернуть общий file export contract.
     */
    public function make(ExportTypeEnum $type, bool $isAllow = false): FileExportInterface;
}
