<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Contracts\Clients;

/**
 * Public API Templates для синхронных запросов других фич.
 */
interface TemplatesClientInterface
{
    /** @return array<int, string> */
    public function vehicleDetailHeadings(string $template): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function renderVehicleDetails(string $template, array $details): array;

    /**
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildVehicleDetails(string $template, array $row, int $startIndex): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, array<string, mixed>>
     */
    public function splitVehicleWiperDetails(array $details): array;

    /** @param array<string, mixed> $details */
    public function detectVehicleWiperSide(array $details): ?string;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, array<string, mixed>>
     */
    public function splitVehicleWiperSpecification(array $details, ?int $partSpecificationId): array;

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

    /** @return array<int, string> */
    public function nomenclatureDetailHeadings(string $template): array;

    /** @return array<string, list<string>> */
    public function nomenclatureReferenceOptions(string $template): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function renderNomenclatureDetails(string $template, array $details): array;

    /**
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildNomenclatureDetails(string $template, array $row, int $startIndex): array;
}
