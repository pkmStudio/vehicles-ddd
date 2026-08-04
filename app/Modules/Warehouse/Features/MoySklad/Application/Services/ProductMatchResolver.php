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
    public function __construct(
        private MoySkladProductClientInterface $client,
        private NomenclatureProductMapper $mapper,
    ) {}

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
