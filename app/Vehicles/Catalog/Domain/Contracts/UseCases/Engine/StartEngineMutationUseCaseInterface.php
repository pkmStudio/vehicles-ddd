<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\EngineMutationRequestDTO;

interface StartEngineMutationUseCaseInterface
{
    public function execute(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO;
}
