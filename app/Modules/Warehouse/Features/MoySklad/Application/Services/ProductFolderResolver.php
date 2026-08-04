<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Services;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductFolderMetaDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;

final readonly class ProductFolderResolver
{
    public function __construct(
        private MoySkladProductClientInterface $client,
    ) {}

    public function resolve(NomenclatureData $nomenclature): MoySkladProductFolderMetaDTO
    {
        if (! (bool) config('warehouse.moysklad.nomenclature_sync.product_folders.enabled', true)) {
            return MoySkladProductFolderMetaDTO::empty();
        }

        $typeName = trim((string) ($nomenclature->type?->name ?? ''));
        if ($typeName === '') {
            return MoySkladProductFolderMetaDTO::empty();
        }

        return $this->client->ensureProductFolderMetaByName($typeName);
    }
}
