<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Export\Services;

use App\Vehicles\Domain\DTOs\EngineExportPlan;
use App\Vehicles\Domain\Models\Engine;
use Illuminate\Support\Collection;

interface EngineExportServiceInterface
{
    public function buildExportPlan(bool $withSparkPlugs = true): EngineExportPlan;

    public function getMainRows(): Collection;

    public function getMainHeadings(): array;

    public function mapMainRow(Engine $row): array;

    public function getSparkPlugRows(): Collection;

    public function getSparkPlugHeadings(): array;

    public function mapSparkPlugRow(object $row): array;
}
