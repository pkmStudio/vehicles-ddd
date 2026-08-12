<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Services;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductFolderMetaDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;

final readonly class ProductFolderResolver
{
    /**
     * Получает client port МойСклад для работы с папками товаров.
     * Шаги:
     * 1) Сохранить MoySkladProductClientInterface для поиска/создания product folder meta.
     */
    public function __construct(
        private MoySkladProductClientInterface $client,
    ) {}

    /**
     * Определяет meta папки товара МойСклад по типу номенклатуры.
     * Шаги:
     * 1) Проверить feature flag product_folders и вернуть empty DTO, если папки отключены.
     * 2) Взять непустое имя warehouse type из номенклатуры.
     * 3) Если type name отсутствует, вернуть empty DTO.
     * 4) Найти или создать папку товара через MoySklad client и вернуть её meta.
     */
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
