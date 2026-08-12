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
     * Шаги:
     * 1) Передать article во внешний adapter товаров.
     * 2) Вернуть локальный DTO найденного товара или null.
     */
    public function findByArticle(string $article): ?MoySkladProductDTO;

    /**
     * Находит товар МойСклад по externalCode.
     * Шаги:
     * 1) Передать stable externalCode во внешний adapter товаров.
     * 2) Вернуть локальный DTO найденного товара или null.
     */
    public function findByExternalCode(string $externalCode): ?MoySkladProductDTO;

    /**
     * Создаёт товар МойСклад.
     * Шаги:
     * 1) Принять локальный payload DTO товара.
     * 2) Передать payload во внешний adapter create.
     * 3) Вернуть локальный DTO созданного товара.
     */
    public function create(MoySkladProductPayloadDTO $payload): MoySkladProductDTO;

    /**
     * Обновляет товар МойСклад по id.
     * Шаги:
     * 1) Принять id существующего товара и новый payload.
     * 2) Передать данные во внешний adapter update.
     * 3) Вернуть локальный DTO обновлённого товара.
     */
    public function updateById(string $id, MoySkladProductPayloadDTO $payload): MoySkladProductDTO;

    /**
     * Удаляет товар МойСклад по id.
     * Шаги:
     * 1) Принять id товара МойСклад.
     * 2) Передать id во внешний adapter delete.
     * 3) Завершить без return value, потому что это command operation.
     */
    public function deleteById(string $id): void;

    /**
     * Возвращает meta существующей или созданной папки товара по имени.
     * Шаги:
     * 1) Принять имя папки из warehouse type.
     * 2) Найти или создать папку товара во внешнем adapter-е.
     * 3) Вернуть meta DTO для вложения в product payload.
     */
    public function ensureProductFolderMetaByName(string $name): MoySkladProductFolderMetaDTO;

    /**
     * Проверяет, лежит ли товар в ожидаемой папке.
     * Шаги:
     * 1) Принять локальный DTO товара и id ожидаемой папки.
     * 2) Передать проверку во внешний adapter папок товаров.
     * 3) Вернуть boolean результат соответствия.
     */
    public function productMatchesFolder(MoySkladProductDTO $product, string $folderId): bool;
}
