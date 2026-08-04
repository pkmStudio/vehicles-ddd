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
