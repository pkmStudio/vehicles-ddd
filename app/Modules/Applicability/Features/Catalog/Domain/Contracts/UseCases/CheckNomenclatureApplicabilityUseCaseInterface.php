<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases;

use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicabilityCheckResultDTO;

/**
 * Описывает проверку применяемости артикула к выбранной модификации.
 */
interface CheckNomenclatureApplicabilityUseCaseInterface
{
    public function execute(string $partNumber, int $modificationId, int $brandId): ApplicabilityCheckResultDTO;
}
