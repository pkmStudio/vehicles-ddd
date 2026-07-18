<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients;

/**
 * Локальный порт MoySklad-фичи для операций с товарами и папками товаров.
 */
interface MoySkladProductClientInterface
{
    /**
     * Находит товар МойСклад по артикулу.
     *
     * @return array<string, mixed>|null
     */
    public function findByArticle(string $article): ?array;

    /**
     * Находит товар МойСклад по externalCode.
     *
     * @return array<string, mixed>|null
     */
    public function findByExternalCode(string $externalCode): ?array;

    /**
     * Создаёт товар МойСклад.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array;

    /**
     * Обновляет товар МойСклад по id.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateById(string $id, array $payload): array;

    /**
     * Удаляет товар МойСклад по id.
     */
    public function deleteById(string $id): void;

    /**
     * Возвращает meta существующей или созданной папки товара по имени.
     *
     * @return array<string, mixed>
     */
    public function ensureProductFolderMetaByName(string $name): array;

    /**
     * Проверяет, лежит ли товар в ожидаемой папке.
     *
     * @param  array<string, mixed>  $product
     */
    public function productMatchesFolder(array $product, string $folderId): bool;
}
