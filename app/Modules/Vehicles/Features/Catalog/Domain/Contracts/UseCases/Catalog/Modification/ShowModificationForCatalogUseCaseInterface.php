<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;

/**
 * Use case port REST-detail модификации публичного каталога.
 */
interface ShowModificationForCatalogUseCaseInterface
{
    public function execute(int $modificationId): ?CatalogModificationContextDTO;
}
