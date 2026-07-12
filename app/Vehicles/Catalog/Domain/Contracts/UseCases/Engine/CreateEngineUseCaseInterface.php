<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\CreateEngineRequestDTO;

interface CreateEngineUseCaseInterface
{
    public function execute(CreateEngineRequestDTO $request): ?CatalogMutationResultDTO;
}
