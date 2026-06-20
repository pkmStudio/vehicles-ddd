<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Export\Services;

use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Support\Collection;

interface VehicleExportServiceInterface
{
    public function getMainRows(bool $isAllow): Collection;

    public function getMainHeadings(): array;

    public function mapMainRow(Vehicle $row): array;

    public function getWiperRows(bool $isAllow): Collection;

    public function getWiperHeadings(): array;

    public function mapWiperRow(object $row): array;
}

