<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Templates\Domain\Contracts\Factories\DetailsDataFactoryInterface;
use App\Modules\Templates\Domain\Contracts\Factories\NomenclatureDetailsDataFactoryInterface;
use App\Modules\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Modules\Templates\Domain\Contracts\Services\NomenclatureDetailsDataPresenterInterface;
use App\Modules\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\UnknownTemplateException;

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
        return $this->vehiclePresenter->headingsFor($this->vehicleTemplate($template));
    }

    public function vehicleReferenceOptions(string $template): array
    {
        return $this->vehiclePresenter->referenceOptionsFor($this->vehicleTemplate($template));
    }

    public function renderVehicleDetails(string $template, array $details): array
    {
        return $this->vehiclePresenter->toExportCells($this->vehicleTemplate($template), $details);
    }

    public function buildVehicleDetails(string $template, array $row, int $startIndex): array
    {
        $index = $startIndex;

        return $this->vehicleFactory->make($this->vehicleTemplate($template), $row, $index)->toArray();
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
        return $this->nomenclaturePresenter->headingsFor($this->nomenclatureTemplate($template));
    }

    public function nomenclatureReferenceOptions(string $template): array
    {
        return $this->nomenclaturePresenter->referenceOptionsFor($this->nomenclatureTemplate($template));
    }

    public function renderNomenclatureDetails(string $template, array $details): array
    {
        return $this->nomenclaturePresenter->toExportCells($this->nomenclatureTemplate($template), $details);
    }

    public function buildNomenclatureDetails(string $template, array $row, int $startIndex): array
    {
        $index = $startIndex;

        return $this->nomenclatureFactory->make($this->nomenclatureTemplate($template), $row, $index)->toArray();
    }

    private function vehicleTemplate(string $template): DetailTemplateEnum
    {
        return DetailTemplateEnum::tryFrom($template) ?? throw UnknownTemplateException::vehicle($template);
    }

    private function nomenclatureTemplate(string $template): NomenclatureDetailTemplateEnum
    {
        return NomenclatureDetailTemplateEnum::tryFrom($template)
            ?? throw UnknownTemplateException::nomenclature($template);
    }
}
