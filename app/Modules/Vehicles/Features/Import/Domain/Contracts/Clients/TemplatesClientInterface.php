<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;

interface TemplatesClientInterface
{
    /**
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildVehicleDetails(DetailTemplateEnum $template, array $row, int $startIndex): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, array<string, mixed>>
     */
    public function splitVehicleWiperDetails(array $details): array;

    /** @param array<string, mixed> $details */
    public function detectVehicleWiperSide(array $details): ?string;

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function vehicleWiperSideData(array $details, string $side): array;
}
