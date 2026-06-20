<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Common\Services;

interface WiperSpecificationServiceInterface
{
    public const string SIDE_FRONT = 'front';

    public const string SIDE_BACK = 'back';

    public function detectSide(array $details): ?string;

    public function sideData(array $details, string $side): array;

    public function normalizeAdapters(mixed $value): array;

    public function sanitizeDetailsForSide(array $details, ?string $side): array;

    public function splitDetails(array $details): array;

    public function mergeForExport(array $frontData, array $backData): array;
}
