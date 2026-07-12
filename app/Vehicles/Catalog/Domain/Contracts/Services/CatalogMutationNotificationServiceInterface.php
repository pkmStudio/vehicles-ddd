<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Services;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;

interface CatalogMutationNotificationServiceInterface
{
    public function notify(CatalogMutationResultDTO $result): void;
}
