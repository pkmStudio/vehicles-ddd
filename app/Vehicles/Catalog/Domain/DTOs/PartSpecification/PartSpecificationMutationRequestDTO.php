<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\PartSpecification;

use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Передает общий запрос мутации PartSpecification и конкретный DTO операции.
 */
final readonly class PartSpecificationMutationRequestDTO
{
    /**
     * Инициализирует immutable-снимок запроса мутации спеки.
     */
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreatePartSpecificationRequestDTO|UpdatePartSpecificationRequestDTO|DeletePartSpecificationRequestDTO $request,
    ) {}
}
