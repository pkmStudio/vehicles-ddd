<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\DeleteModificationRequestDTO;

interface DeleteModificationUseCaseInterface
{
    public function execute(DeleteModificationRequestDTO $request): ?CatalogMutationResultDTO;
}
