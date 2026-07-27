<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Clients;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;

interface TemplatesClientInterface
{
    /** @return array<int, string> */
    public function vehicleDetailHeadings(DetailTemplateEnum $template): array;

    /** @return array<string, list<string>> */
    public function vehicleReferenceOptions(DetailTemplateEnum $template): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function renderVehicleDetails(DetailTemplateEnum $template, array $details): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function vehicleWiperSideData(array $details, string $side): array;

    /**
     * @param  array<string, mixed>  $front
     * @param  array<string, mixed>  $back
     * @return array<string, mixed>
     */
    public function mergeVehicleWiperForExport(array $front, array $back): array;
}
