<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Engine;

use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

final readonly class EngineMutationRequestDTO
{
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateEngineRequestDTO|UpdateEngineRequestDTO|DeleteEngineRequestDTO $request,
    ) {}
}
