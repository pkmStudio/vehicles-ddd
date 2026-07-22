<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Services;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Commands\NomenclatureIntegrationCommandInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureIntegrationRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Services\NomenclatureSyncServiceInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Enums\MoySkladIntegrationStatusEnum;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureIntegrationData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Синхронизирует Warehouse-номенклатуру с товарами МойСклад и ведёт integration-state.
 */
final readonly class NomenclatureSyncService implements NomenclatureSyncServiceInterface
{
    /**
     * Получает локальный клиент МойСклад и mapper payload.
     */
    public function __construct(
        private MoySkladProductClientInterface $client,
        private NomenclatureRepositoryInterface $nomenclatures,
        private NomenclatureIntegrationRepositoryInterface $integrations,
        private NomenclatureIntegrationCommandInterface $integrationCommand,
        private NomenclatureProductMapper $mapper,
    ) {}

    /**
     * Синхронизирует одну номенклатуру: грузит модель, создаёт integration и делает upsert товара.
     */
    public function sync(int $nomenclatureId): void
    {
        if (! $this->enabled()) {
            return;
        }

        $nomenclature = $this->nomenclatures->findById($nomenclatureId);

        if ($nomenclature === null) {
            Log::warning('MoySklad: номенклатура для sync не найдена.', [
                'nomenclature_id' => $nomenclatureId,
            ]);

            return;
        }

        $integration = $this->integrations->firstOrCreateForNomenclature($nomenclature->id);

        $productFolderMeta = $this->resolveProductFolderMeta($nomenclature);
        $payload = $this->mapper->map($nomenclature, $productFolderMeta);
        $payloadHash = $this->payloadHash($payload);

        if ($this->shouldSkipUpdate($integration, $payloadHash)) {
            return;
        }

        try {
            $product = $this->upsertProduct($integration, $nomenclature, $payload, $productFolderMeta);
            $this->markSyncSuccess($integration, $product, $payload, $payloadHash);
        } catch (Throwable $e) {
            $this->markSyncFailure($integration, $e);

            Log::error('MoySklad: ошибка синхронизации номенклатуры.', [
                'nomenclature_id' => $nomenclature->id,
                'part_number' => $nomenclature->partNumber,
                'error' => $e->getMessage(),
                'operation' => 'sync_nomenclature',
            ]);

            throw $e;
        }
    }

    /**
     * Удаляет товар МойСклад по сохранённой integration-связке или fallback-поиску.
     */
    public function delete(int $nomenclatureId, string $partNumber, ?string $externalId = null, ?int $integrationId = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        $externalCode = $this->mapper->externalCodeForNomenclatureId($nomenclatureId);
        $integration = $this->integrations->findForDelete($nomenclatureId, $externalCode, $integrationId);
        $productId = $this->resolveDeleteProductId($nomenclatureId, $partNumber, $externalId, $integration);

        if ($productId === null) {
            Log::warning('MoySklad: товар для удаления не найден, операция пропущена.', [
                'nomenclature_id' => $nomenclatureId,
                'part_number' => $partNumber,
            ]);

            $this->integrationCommand->markDeleted($integration);

            return;
        }

        try {
            $this->client->deleteById($productId);

            $this->integrationCommand->markDeleted($integration, $productId);
        } catch (Throwable $e) {
            if ($integration !== null) {
                $this->integrationCommand->markFailed($integration, $e->getMessage());
            }

            Log::error('MoySklad: ошибка удаления номенклатуры.', [
                'nomenclature_id' => $nomenclatureId,
                'part_number' => $partNumber,
                'moysklad_product_id' => $productId,
                'error' => $e->getMessage(),
                'operation' => 'delete_nomenclature',
            ]);

            throw $e;
        }
    }

    /**
     * Рассчитывает hash payload для тестов.
     */
    public function payloadHash(array $payload): string
    {
        return sha1((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Определяет meta папки товара по типу номенклатуры.
     *
     * @return array<string, mixed>
     */
    private function resolveProductFolderMeta(NomenclatureData $nomenclature): array
    {
        if (! (bool) config('warehouse.moysklad.nomenclature_sync.product_folders.enabled', true)) {
            return [];
        }

        $typeName = trim((string) ($nomenclature->type?->name ?? ''));
        if ($typeName === '') {
            return [];
        }

        return Cache::remember(
            'warehouse:moysklad:product_folder_meta:'.md5($typeName),
            (int) config('warehouse.moysklad.nomenclature_sync.product_folders.cache_ttl_seconds', 3600),
            fn (): array => $this->client->ensureProductFolderMetaByName($typeName),
        );
    }

    /**
     * Проверяет, можно ли не отправлять update, если последний успешный payload не изменился.
     */
    private function shouldSkipUpdate(NomenclatureIntegrationData $integration, string $payloadHash): bool
    {
        return $integration->syncStatus === MoySkladIntegrationStatusEnum::Synced->value
            && $integration->payloadHash === $payloadHash
            && is_string($integration->externalId)
            && $integration->externalId !== '';
    }

    /**
     * Создаёт или обновляет товар: сначала по сохранённому external_id, затем через поиск.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $productFolderMeta
     * @return array<string, mixed>
     */
    private function upsertProduct(NomenclatureIntegrationData $integration, NomenclatureData $nomenclature, array $payload, array $productFolderMeta): array
    {
        if (is_string($integration->externalId) && $integration->externalId !== '') {
            return $this->client->updateById($integration->externalId, $payload);
        }

        return $this->findOrCreateProduct($nomenclature, $payload, $productFolderMeta, updateExisting: true);
    }

    /**
     * Находит подходящий товар по артикулу/externalCode или создаёт новый.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $productFolderMeta
     * @return array<string, mixed>
     */
    private function findOrCreateProduct(NomenclatureData $nomenclature, array $payload, array $productFolderMeta, bool $updateExisting = false): array
    {
        $folderId = $this->resolveFolderId($productFolderMeta);

        $existing = $this->client->findByArticle($nomenclature->partNumber);
        if ($existing !== null && $this->matchesFolder($existing, $folderId)) {
            return $updateExisting
                ? $this->client->updateById((string) $existing['id'], $payload)
                : $existing;
        }

        $externalCode = $this->mapper->externalCodeForNomenclatureId((int) $nomenclature->id);
        $existingByExternalCode = $this->client->findByExternalCode($externalCode);
        if ($existingByExternalCode !== null) {
            return $updateExisting
                ? $this->client->updateById((string) $existingByExternalCode['id'], $payload)
                : $existingByExternalCode;
        }

        return $this->client->create($payload);
    }

    /**
     * Записывает успешный результат синхронизации в `nomenclature_integrations`.
     *
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $payload
     */
    private function markSyncSuccess(NomenclatureIntegrationData $integration, array $product, array $payload, string $payloadHash): void
    {
        $externalId = $product['id'] ?? null;
        $externalCode = $product['externalCode'] ?? ($payload['externalCode'] ?? null);

        $this->integrationCommand->markSynced(
            integration: $integration,
            externalId: is_string($externalId) ? $externalId : null,
            externalCode: is_string($externalCode) ? $externalCode : null,
            payloadHash: $payloadHash,
        );
    }

    /**
     * Записывает ошибку последней синхронизации в integration-state.
     */
    private function markSyncFailure(NomenclatureIntegrationData $integration, Throwable $e): void
    {
        $this->integrationCommand->markFailed($integration, $e->getMessage());
    }

    /**
     * Определяет id товара МойСклад для удаления по externalId, integration, externalCode или артикулу.
     */
    private function resolveDeleteProductId(int $nomenclatureId, string $partNumber, ?string $externalId, ?NomenclatureIntegrationData $integration): ?string
    {
        if (is_string($externalId) && $externalId !== '') {
            return $externalId;
        }

        if (is_string($integration?->externalId) && $integration->externalId !== '') {
            return $integration->externalId;
        }

        $externalCode = $this->mapper->externalCodeForNomenclatureId($nomenclatureId);
        $foundByExternalCode = $this->client->findByExternalCode($externalCode);
        $externalCodeId = is_array($foundByExternalCode) ? ($foundByExternalCode['id'] ?? null) : null;
        if (is_string($externalCodeId) && $externalCodeId !== '') {
            return $externalCodeId;
        }

        $foundByArticle = $this->client->findByArticle($partNumber);
        $articleId = is_array($foundByArticle) ? ($foundByArticle['id'] ?? null) : null;

        return is_string($articleId) && $articleId !== '' ? $articleId : null;
    }

    /**
     * Извлекает id папки товара из meta.href.
     *
     * @param  array<string, mixed>  $productFolderMeta
     */
    private function resolveFolderId(array $productFolderMeta): ?string
    {
        $href = $productFolderMeta['meta']['href'] ?? null;
        if (! is_string($href) || $href === '') {
            return null;
        }

        $parts = parse_url($href);
        $path = $parts['path'] ?? null;
        if (! is_string($path) || $path === '') {
            return null;
        }

        $segments = explode('/', trim($path, '/'));
        $id = end($segments);

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Проверяет принадлежность товара ожидаемой папке; без папки любой найденный товар подходит.
     *
     * @param  array<string, mixed>  $product
     */
    private function matchesFolder(array $product, ?string $folderId): bool
    {
        if ($folderId === null) {
            return true;
        }

        return $this->client->productMatchesFolder($product, $folderId);
    }

    /**
     * Возвращает feature flag синхронизации Warehouse/MoySklad.
     */
    private function enabled(): bool
    {
        return (bool) config('warehouse.moysklad.nomenclature_sync.enabled', false);
    }
}
