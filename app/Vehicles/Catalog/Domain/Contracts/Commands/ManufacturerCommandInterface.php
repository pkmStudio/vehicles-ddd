<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Commands;

use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;

interface ManufacturerCommandInterface
{
    public function create(ManufacturerData $data): ManufacturerData;

    public function update(ManufacturerData $data): ManufacturerData;

    public function deleteByMfaId(int $mfaId): void;
}
