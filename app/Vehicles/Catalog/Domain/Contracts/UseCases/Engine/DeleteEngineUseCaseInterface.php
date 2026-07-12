<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\DeleteEngineRequestDTO;

interface DeleteEngineUseCaseInterface
{
    public function execute(DeleteEngineRequestDTO $request): ?CatalogMutationResultDTO;
}
