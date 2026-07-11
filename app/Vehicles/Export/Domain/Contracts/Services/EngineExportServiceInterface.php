<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services;

use App\Vehicles\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Vehicles\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

interface EngineExportServiceInterface
{
    public function getMainRows(): Collection;

    public function getMainHeadings(): array;

    public function mapMainRow(EngineData $row): array;

    public function getSparkPlugRows(): Collection;

    public function getSparkPlugHeadings(): array;

    public function mapSparkPlugRow(PartSpecificationExportRowDTO $row): array;
}
