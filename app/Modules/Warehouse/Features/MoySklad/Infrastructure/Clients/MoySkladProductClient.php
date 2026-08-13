<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Clients;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductFolderMetaDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductPayloadDTO;
use Illuminate\Support\Facades\Cache;
use PkmStudio\MoySkladClient\Endpoints\ProductEndpoint;
use PkmStudio\MoySkladClient\Endpoints\ProductFolderEndpoint;

/**
 * Adapter локального MoySklad-порта к публичному API пакета `pkmstudio/moysklad-client`.
 */
final readonly class MoySkladProductClient implements MoySkladProductClientInterface
{
    /**
     * Получает endpoint товаров и endpoint папок товаров из пакета.
     * Шаги:
     * 1) Сохранить ProductEndpoint для CRUD операций с товарами.
     * 2) Сохранить ProductFolderEndpoint для папок товаров и проверки принадлежности.
     */
    public function __construct(
        private ProductEndpoint $products,
        private ProductFolderEndpoint $productFolders,
    ) {}

    /**
     * Делегирует поиск товара по артикулу в пакетный ProductEndpoint.
     * Шаги:
     * 1) Передать article во внешний ProductEndpoint.
     * 2) Преобразовать null или package array в локальный MoySkladProductDTO.
     */
    public function findByArticle(string $article): ?MoySkladProductDTO
    {
        return $this->productFromArray($this->products->findByArticle($article));
    }

    /**
     * Делегирует поиск товара по externalCode в пакетный ProductEndpoint.
     * Шаги:
     * 1) Передать externalCode во внешний ProductEndpoint.
     * 2) Преобразовать null или package array в локальный MoySkladProductDTO.
     */
    public function findByExternalCode(string $externalCode): ?MoySkladProductDTO
    {
        return $this->productFromArray($this->products->findByExternalCode($externalCode));
    }

    /**
     * Делегирует создание товара в пакетный ProductEndpoint.
     * Шаги:
     * 1) Преобразовать локальный payload DTO в shape MoySklad package API.
     * 2) Выполнить create во внешнем endpoint.
     * 3) Преобразовать ответ package array обратно в локальный DTO.
     */
    public function create(MoySkladProductPayloadDTO $payload): MoySkladProductDTO
    {
        return MoySkladProductDTO::fromArray($this->products->create($payload->toArray()));
    }

    /**
     * Делегирует обновление товара по id в пакетный ProductEndpoint.
     * Шаги:
     * 1) Преобразовать локальный payload DTO в package array.
     * 2) Обновить товар во внешнем endpoint по MoySklad id.
     * 3) Преобразовать ответ в локальный MoySkladProductDTO.
     */
    public function updateById(string $id, MoySkladProductPayloadDTO $payload): MoySkladProductDTO
    {
        return MoySkladProductDTO::fromArray($this->products->updateById($id, $payload->toArray()));
    }

    /**
     * Делегирует удаление товара по id в пакетный ProductEndpoint.
     * Шаги:
     * 1) Передать MoySklad id во внешний ProductEndpoint.
     * 2) Не возвращать результат, потому что delete endpoint используется как command boundary.
     */
    public function deleteById(string $id): void
    {
        $this->products->deleteById($id);
    }

    /**
     * Создаёт/находит папку товара по имени и возвращает её meta-блок.
     * Шаги:
     * 1) Собрать cache key из имени папки.
     * 2) Взять TTL из warehouse.moysklad config с fallback 3600 секунд.
     * 3) Через Cache::remember выполнить uncached resolve только при cache miss.
     * 4) Преобразовать сохраненный meta array в MoySkladProductFolderMetaDTO.
     * 5) Для неожиданного cache value вернуть empty DTO.
     */
    public function ensureProductFolderMetaByName(string $name): MoySkladProductFolderMetaDTO
    {
        $resolveProductFolderMeta = fn (): array => $this->resolveProductFolderMetaByName($name);

        $meta = Cache::remember(
            'warehouse:moysklad:product_folder_meta:'.md5($name),
            (int) config('warehouse.moysklad.nomenclature_sync.product_folders.cache_ttl_seconds', 3600),
            $resolveProductFolderMeta,
        );

        return is_array($meta) ? MoySkladProductFolderMetaDTO::fromArray($meta) : MoySkladProductFolderMetaDTO::empty();
    }

    /**
     * Создаёт/находит папку товара по имени и возвращает её meta-блок без cache.
     * Шаги:
     * 1) Через ProductFolderEndpoint найти или создать папку по имени.
     * 2) Если внешний ответ не содержит id папки, вернуть пустой meta array.
     * 3) По id папки запросить meta-блок, пригодный для payload товара.
     */
    private function resolveProductFolderMetaByName(string $name): array
    {
        $folder = $this->productFolders->ensureByName($name);
        if (! is_array($folder) || empty($folder['id'])) {
            return [];
        }

        return $this->productFolders->productFolderMeta((string) $folder['id']);
    }

    /**
     * Проверяет принадлежность товара ожидаемой папке через пакетный ProductFolderEndpoint.
     * Шаги:
     * 1) Преобразовать локальный product DTO в package array.
     * 2) Передать product array и folder id во внешний endpoint.
     * 3) Вернуть boolean результат проверки вложенности.
     */
    public function productMatchesFolder(MoySkladProductDTO $product, string $folderId): bool
    {
        return $this->productFolders->containsProduct($product->toArray(), $folderId);
    }

    /**
     * Преобразует nullable package product array в локальный DTO.
     * Шаги:
     * 1) Вернуть null для отсутствующего товара.
     * 2) Для найденного товара выполнить локальную fromArray() нормализацию.
     *
     * @param  array<string, mixed>|null  $product
     */
    private function productFromArray(?array $product): ?MoySkladProductDTO
    {
        return $product === null ? null : MoySkladProductDTO::fromArray($product);
    }
}
