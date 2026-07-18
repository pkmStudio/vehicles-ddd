<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\WiperExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

interface VehicleExportServiceInterface
{
    public function getMainRows(bool $isAllow): Collection;

    public function getMainHeadings(): array;

    public function mapMainRow(VehicleData $row): array;

    public function getWiperRows(bool $isAllow): Collection;

    public function getWiperHeadings(): array;

    public function mapWiperRow(WiperExportRowDTO $row): array;
}
