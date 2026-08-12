<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationDetailsWriteResultDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Описывает правила записи details спецификации детали в catalog mutations.
 */
interface PartSpecificationDetailsWritePolicyInterface
{
    /**
     * Проверяет и нормализует details перед записью спецификации.
     *
     * Шаги:
     * 1) Принять raw details, template, owner type и operation context.
     * 2) Проверить feature-specific write rules для сохраняемого details contract.
     * 3) Вернуть normalized details или invalid result с field errors.
     *
     * @param  array<string, mixed>  $details
     */
    public function apply(
        array $details,
        DetailTemplateEnum $template,
        PartableTypeEnum $ownerType,
        ?int $partSpecificationId,
        string $operationId,
    ): PartSpecificationDetailsWriteResultDTO;
}
