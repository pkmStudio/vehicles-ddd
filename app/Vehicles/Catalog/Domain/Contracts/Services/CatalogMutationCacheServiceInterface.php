<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Services;

interface CatalogMutationCacheServiceInterface
{
    public function accept(string $operationId): bool;

    public function forgetAccepted(string $operationId): void;
}
