<?php

declare(strict_types=1);

namespace App\Templates\Application\Clients;

use App\Templates\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Templates\Domain\Contracts\Factories\DetailsDataFactoryInterface;
use App\Templates\Domain\Contracts\Factories\NomenclatureDetailsDataFactoryInterface;
use App\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Templates\Domain\Contracts\Services\NomenclatureDetailsDataPresenterInterface;
use App\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use App\Templates\Domain\Enums\DetailTemplateEnum;
use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

/**
 * Синхронный public API Templates поверх внутренних builders/presenters/services.
 */
final readonly class TemplatesClient implements TemplatesClientInterface
{
    public function __construct(
        private DetailsDataFactoryInterface $vehicleFactory,
        private DetailsDataPresenterInterface $vehiclePresenter,
        private NomenclatureDetailsDataFactoryInterface $nomenclatureFactory,
        private NomenclatureDetailsDataPresenterInterface $nomenclaturePresenter,
        private WiperSpecificationServiceInterface $wiper,
    ) {}

    public function vehicleDetailHeadings(string $template): array
    {
        return $this->vehiclePresenter->headingsFor(DetailTemplateEnum::from($template));
    }

    public function renderVehicleDetails(string $template, array $details): array
    {
        return $this->vehiclePresenter->toExportCells(DetailTemplateEnum::from($template), $details);
    }

    public function buildVehicleDetails(string $template, array $row, int $startIndex): array
    {
        $index = $startIndex;

        return $this->vehicleFactory->buildFromRow(DetailTemplateEnum::from($template), $row, $index)->toArray();
    }

    public function splitVehicleWiperDetails(array $details): array
    {
        return $this->wiper->splitDetails($details);
    }

    public function detectVehicleWiperSide(array $details): ?string
    {
        return $this->wiper->detectSide($details);
    }

    public function splitVehicleWiperSpecification(array $details, ?int $partSpecificationId): array
    {
        return $this->wiper->splitSpecification($details, $partSpecificationId);
    }

    public function vehicleWiperSideData(array $details, string $side): array
    {
        return $this->wiper->sideData($details, $side);
    }

    public function mergeVehicleWiperForExport(array $front, array $back): array
    {
        return $this->wiper->mergeForExport($front, $back);
    }

    public function nomenclatureDetailHeadings(string $template): array
    {
        return $this->nomenclaturePresenter->headingsFor(NomenclatureDetailTemplateEnum::from($template));
    }

    public function nomenclatureReferenceOptions(string $template): array
    {
        return $this->nomenclaturePresenter->referenceOptionsFor(NomenclatureDetailTemplateEnum::from($template));
    }

    public function renderNomenclatureDetails(string $template, array $details): array
    {
        return $this->nomenclaturePresenter->toExportCells(NomenclatureDetailTemplateEnum::from($template), $details);
    }

    public function buildNomenclatureDetails(string $template, array $row, int $startIndex): array
    {
        $index = $startIndex;

        return $this->nomenclatureFactory->buildFromRow(NomenclatureDetailTemplateEnum::from($template), $row, $index)->toArray();
    }
}
