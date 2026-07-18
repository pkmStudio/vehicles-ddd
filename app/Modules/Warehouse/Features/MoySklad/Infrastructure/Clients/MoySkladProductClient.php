<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Clients;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use PkmStudio\MoySkladClient\Endpoints\ProductEndpoint;
use PkmStudio\MoySkladClient\Endpoints\ProductFolderEndpoint;

/**
 * Adapter локального MoySklad-порта к публичному API пакета `pkmstudio/moysklad-client`.
 */
final readonly class MoySkladProductClient implements MoySkladProductClientInterface
{
    /**
     * Получает endpoint товаров и endpoint папок товаров из пакета.
     */
    public function __construct(
        private ProductEndpoint $products,
        private ProductFolderEndpoint $productFolders,
    ) {}

    /**
     * Делегирует поиск товара по артикулу в пакетный ProductEndpoint.
     */
    public function findByArticle(string $article): ?array
    {
        return $this->products->findByArticle($article);
    }

    /**
     * Делегирует поиск товара по externalCode в пакетный ProductEndpoint.
     */
    public function findByExternalCode(string $externalCode): ?array
    {
        return $this->products->findByExternalCode($externalCode);
    }

    /**
     * Делегирует создание товара в пакетный ProductEndpoint.
     */
    public function create(array $payload): array
    {
        return $this->products->create($payload);
    }

    /**
     * Делегирует обновление товара по id в пакетный ProductEndpoint.
     */
    public function updateById(string $id, array $payload): array
    {
        return $this->products->updateById($id, $payload);
    }

    /**
     * Делегирует удаление товара по id в пакетный ProductEndpoint.
     */
    public function deleteById(string $id): void
    {
        $this->products->deleteById($id);
    }

    /**
     * Создаёт/находит папку товара по имени и возвращает её meta-блок.
     */
    public function ensureProductFolderMetaByName(string $name): array
    {
        $folder = $this->productFolders->ensureByName($name);
        if (! is_array($folder) || empty($folder['id'])) {
            return [];
        }

        return $this->productFolders->productFolderMeta((string) $folder['id']);
    }

    /**
     * Проверяет принадлежность товара ожидаемой папке через пакетный ProductFolderEndpoint.
     */
    public function productMatchesFolder(array $product, string $folderId): bool
    {
        return $this->productFolders->containsProduct($product, $folderId);
    }
}
