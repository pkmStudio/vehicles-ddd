<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Modification;

use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

final readonly class ModificationMutationRequestDTO
{
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateModificationRequestDTO|UpdateModificationRequestDTO|DeleteModificationRequestDTO $request,
    ) {}
}
