<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductFolderMetaDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductPayloadDTO;

/**
 * Локальный порт MoySklad-фичи для операций с товарами и папками товаров.
 */
interface MoySkladProductClientInterface
{
    /**
     * Находит товар МойСклад по артикулу.
     */
    public function findByArticle(string $article): ?MoySkladProductDTO;

    /**
     * Находит товар МойСклад по externalCode.
     */
    public function findByExternalCode(string $externalCode): ?MoySkladProductDTO;

    /**
     * Создаёт товар МойСклад.
     */
    public function create(MoySkladProductPayloadDTO $payload): MoySkladProductDTO;

    /**
     * Обновляет товар МойСклад по id.
     */
    public function updateById(string $id, MoySkladProductPayloadDTO $payload): MoySkladProductDTO;

    /**
     * Удаляет товар МойСклад по id.
     */
    public function deleteById(string $id): void;

    /**
     * Возвращает meta существующей или созданной папки товара по имени.
     */
    public function ensureProductFolderMetaByName(string $name): MoySkladProductFolderMetaDTO;

    /**
     * Проверяет, лежит ли товар в ожидаемой папке.
     */
    public function productMatchesFolder(MoySkladProductDTO $product, string $folderId): bool;
}
