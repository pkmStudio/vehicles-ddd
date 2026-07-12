<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Manufacturer;

use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Передает параметры сценария или результат мутации производителей.
 */
final readonly class ManufacturerMutationRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных производителей.
     */
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateManufacturerRequestDTO|UpdateManufacturerRequestDTO|DeleteManufacturerRequestDTO $request,
    ) {}
}
