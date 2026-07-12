<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\Contracts;

interface WiperSpecificationServiceInterface
{
    public function detectSide(array $details): ?string;

    public function sideData(array $details, string $side): array;

    public function getVehicleAdapterCount(array $details, string $side): int;

    public function sanitizeDetailsForSide(array $details, ?string $side): array;

    public function splitDetails(array $details): array;

    public function splitSpecification(array $details, ?int $partSpecificationId): array;

    public function mergeForExport(array $frontData, array $backData): array;
}
