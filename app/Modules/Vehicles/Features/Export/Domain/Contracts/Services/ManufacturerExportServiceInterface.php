<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services;

use App\Modules\Vehicles\Features\Export\Domain\ModelData\ManufacturerData;
use Illuminate\Support\Collection;

/**
 * Порт сборки данных Excel-экспорта производителей автомобилей.
 */
interface ManufacturerExportServiceInterface
{
    /**
     * Возвращает строки листа производителей.
     *
     * @return Collection<int, ManufacturerData>
     */
    public function getRows(): Collection;

    /**
     * Возвращает заголовки листа производителей.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array;

    /**
     * Преобразует manufacturer snapshot в строку Excel.
     *
     * @return array{0: int, 1: string, 2: string}
     */
    public function mapRow(ManufacturerData $row): array;
}
