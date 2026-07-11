<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services;

use App\Vehicles\Export\Domain\DTOs\VehicleExportPlan;
use App\Vehicles\Export\Domain\ModelData\Vehicle\VehicleData;
use Illuminate\Support\Collection;

interface VehicleExportServiceInterface
{
    public function buildExportPlan(bool $isAllow = false, bool $withWipers = true): VehicleExportPlan;

    public function getMainRows(bool $isAllow): Collection;

    public function getMainHeadings(): array;

    public function mapMainRow(VehicleData $row): array;

    public function getWiperRows(bool $isAllow): Collection;

    public function getWiperHeadings(): array;

    public function mapWiperRow(object $row): array;
}
