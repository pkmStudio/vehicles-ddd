<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

interface EngineExportServiceInterface
{
    public function getMainRows(): Collection;

    public function getMainHeadings(): array;

    public function mapMainRow(EngineData $row): array;

    public function getSparkPlugRows(): Collection;

    public function getSparkPlugHeadings(): array;

    public function mapSparkPlugRow(PartSpecificationExportRowDTO $row): array;

    public function getReferenceRows(): Collection;

    public function getReferenceHeadings(): array;
}
