<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Services;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductFolderMetaDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductPayloadDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureIntegrationData;

final readonly class ProductMatchResolver
{
    /**
     * Получает client МойСклад и mapper внешних кодов номенклатуры.
     * Шаги:
     * 1) Сохранить MoySkladProductClientInterface для поиска, создания и обновления товаров.
     * 2) Сохранить mapper, который строит stable externalCode для Warehouse-номенклатуры.
     */
    public function __construct(
        private MoySkladProductClientInterface $client,
        private NomenclatureProductMapper $mapper,
    ) {}

    /**
     * Сохраняет товар МойСклад для номенклатуры, обновляя известный товар или создавая новый.
     * Шаги:
     * 1) Если integration уже содержит externalId, обновить товар по этому id.
     * 2) Иначе найти существующий товар по артикулу в ожидаемой папке.
     * 3) Если артикул не дал подходящий товар, найти товар по stable externalCode.
     * 4) Обновить найденный товар или создать новый товар из payload.
     */
    public function saveProduct(
        NomenclatureIntegrationData $integration,
        NomenclatureData $nomenclature,
        MoySkladProductPayloadDTO $payload,
        MoySkladProductFolderMetaDTO $productFolderMeta,
    ): MoySkladProductDTO {
        if (is_string($integration->externalId) && $integration->externalId !== '') {
            return $this->client->updateById($integration->externalId, $payload);
        }

        return $this->findOrCreateProduct($nomenclature, $payload, $productFolderMeta, updateExisting: true);
    }

    /**
     * Определяет id товара МойСклад для удаления.
     * Шаги:
     * 1) Использовать explicit externalId из события удаления, если он передан.
     * 2) Иначе использовать externalId из найденной integration-связи.
     * 3) Иначе найти товар по stable externalCode номенклатуры.
     * 4) Последним fallback-ом найти товар по артикулу.
     * 5) Вернуть id найденного товара или null.
     */
    public function resolveDeleteProductId(
        int $nomenclatureId,
        string $partNumber,
        ?string $externalId,
        ?NomenclatureIntegrationData $integration,
    ): ?string {
        if (is_string($externalId) && $externalId !== '') {
            return $externalId;
        }

        if (is_string($integration?->externalId) && $integration->externalId !== '') {
            return $integration->externalId;
        }

        $externalCode = $this->mapper->externalCodeForNomenclatureId($nomenclatureId);
        $foundByExternalCode = $this->client->findByExternalCode($externalCode);
        if (is_string($foundByExternalCode?->id) && $foundByExternalCode->id !== '') {
            return $foundByExternalCode->id;
        }

        $foundByArticle = $this->client->findByArticle($partNumber);

        return is_string($foundByArticle?->id) && $foundByArticle->id !== '' ? $foundByArticle->id : null;
    }

    private function findOrCreateProduct(
        NomenclatureData $nomenclature,
        MoySkladProductPayloadDTO $payload,
        MoySkladProductFolderMetaDTO $productFolderMeta,
        bool $updateExisting = false,
    ): MoySkladProductDTO {
        $folderId = $productFolderMeta->folderId();

        $existing = $this->client->findByArticle($nomenclature->partNumber);
        $existingMatchesFolder = $existing !== null && $this->matchesFolder($existing, $folderId);

        if ($existingMatchesFolder) {
            return $updateExisting
                ? $this->client->updateById((string) $existing->id, $payload)
                : $existing;
        }

        $externalCode = $this->mapper->externalCodeForNomenclatureId((int) $nomenclature->id);
        $existingByExternalCode = $this->client->findByExternalCode($externalCode);
        if ($existingByExternalCode !== null) {
            return $updateExisting
                ? $this->client->updateById((string) $existingByExternalCode->id, $payload)
                : $existingByExternalCode;
        }

        return $this->client->create($payload);
    }

    private function matchesFolder(MoySkladProductDTO $product, ?string $folderId): bool
    {
        if ($folderId === null) {
            return true;
        }

        return $this->client->productMatchesFolder($product, $folderId);
    }
}
