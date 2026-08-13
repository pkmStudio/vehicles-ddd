<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Application\UseCases;

use App\Modules\Applicability\Features\Catalog\Domain\Contracts\Repositories\CatalogApplicabilityRepositoryInterface;
use App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases\CheckNomenclatureApplicabilityUseCaseInterface;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicabilityCheckResultDTO;
use App\Modules\Applicability\Features\Catalog\Domain\Enums\ApplicabilityLookupStatusEnum;
use Illuminate\Support\Collection;

/**
 * Проверяет положительные факты применяемости товара через активные комплекты.
 */
final readonly class CheckNomenclatureApplicabilityUseCase implements CheckNomenclatureApplicabilityUseCaseInterface
{
    public function __construct(
        private CatalogApplicabilityRepositoryInterface $repository,
    ) {}

    /**
     * Проверяет существование входных сущностей и читает подтверждения применяемости.
     *
     * Шаги:
     * 1) Проверить модификацию и номенклатуру выбранного бренда.
     * 2) Получить положительные факты через активные комплекты.
     * 3) Вернуть compatible при наличии evidence, иначе unknown.
     */
    public function execute(string $partNumber, int $modificationId, int $brandId): ApplicabilityCheckResultDTO
    {
        if (! $this->repository->modificationExists($modificationId)) {
            return new ApplicabilityCheckResultDTO(
                partNumber: $partNumber,
                modificationId: $modificationId,
                status: ApplicabilityLookupStatusEnum::MODIFICATION_NOT_FOUND,
                evidence: new Collection,
            );
        }

        $canonicalPartNumber = $this->repository->findPartNumber($partNumber, $brandId);

        if ($canonicalPartNumber === null) {
            return new ApplicabilityCheckResultDTO(
                partNumber: $partNumber,
                modificationId: $modificationId,
                status: ApplicabilityLookupStatusEnum::NOMENCLATURE_NOT_FOUND,
                evidence: new Collection,
            );
        }

        $evidence = $this->repository->evidence($canonicalPartNumber, $modificationId, $brandId);
        $status = $evidence->isEmpty()
            ? ApplicabilityLookupStatusEnum::UNKNOWN
            : ApplicabilityLookupStatusEnum::COMPATIBLE;

        return new ApplicabilityCheckResultDTO(
            partNumber: $canonicalPartNumber,
            modificationId: $modificationId,
            status: $status,
            evidence: $evidence,
        );
    }
}
