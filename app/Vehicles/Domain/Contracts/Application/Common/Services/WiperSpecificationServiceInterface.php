<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Common\Services;

use App\Vehicles\Domain\Models\PartSpecification;

interface WiperSpecificationServiceInterface
{
    public function detectSide(array $details): ?string;

    public function detectSideByPartSpecification(PartSpecification $partSpecification): ?string;

    public function sideData(array $details, string $side): array;

    public function normalizeAdapters(mixed $value): array;

    public function normalizeVehicleAdapters(PartSpecification $partSpecification, string $side, mixed $rawAdapters): array;

    public function normalizeSideDetails(array $sideDetails, PartSpecification $partSpecification, string $side): array;

    public function getVehicleAdapterCount(array $details, string $side): int;

    public function sanitizeDetailsForSide(array $details, ?string $side): array;

    public function splitDetails(array $details): array;

    public function splitSpecification(PartSpecification $partSpecification): array;

    public function mergeForExport(array $frontData, array $backData): array;
}
