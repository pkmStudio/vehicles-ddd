<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Services;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Commands\NomenclatureIntegrationCommandInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureIntegrationRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Services\NomenclatureSyncServiceInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductPayloadDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\Enums\MoySkladIntegrationStatusEnum;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureIntegrationData;
use Psr\Log\LoggerInterface;
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
        private ProductFolderResolver $productFolderResolver,
        private ProductMatchResolver $productMatchResolver,
        private ProductPayloadHasher $payloadHasher,
        private LoggerInterface $logger,
    ) {}

    /**
     * Синхронизирует одну номенклатуру: грузит модель, создаёт integration и отправляет товар.
     */
    public function sync(int $nomenclatureId): void
    {
        $syncEnabled = $this->enabled();

        if (! $syncEnabled) {
            return;
        }

        $nomenclature = $this->nomenclatures->findById($nomenclatureId);

        if ($nomenclature === null) {
            $this->logger->warning('MoySklad: номенклатура для sync не найдена.', [
                'nomenclature_id' => $nomenclatureId,
            ]);

            return;
        }

        $integration = $this->integrations->findByNomenclatureId($nomenclature->id)
            ?? $this->integrationCommand->createPendingForNomenclature($nomenclature->id);

        $productFolderMeta = $this->productFolderResolver->resolve($nomenclature);
        $payload = $this->mapper->map($nomenclature, $productFolderMeta);
        $payloadHash = $this->payloadHash($payload);
        $shouldSkipUpdate = $this->shouldSkipUpdate($integration, $payloadHash);

        if ($shouldSkipUpdate) {
            return;
        }

        try {
            $product = $this->productMatchResolver->saveProduct($integration, $nomenclature, $payload, $productFolderMeta);
            $this->markSyncSuccess($integration, $product, $payload, $payloadHash);
        } catch (Throwable $e) {
            $this->markSyncFailure($integration, $e);

            $this->logger->error('MoySklad: ошибка синхронизации номенклатуры.', [
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
        $syncEnabled = $this->enabled();

        if (! $syncEnabled) {
            return;
        }

        $externalCode = $this->mapper->externalCodeForNomenclatureId($nomenclatureId);
        $integration = $this->integrations->findForDelete($nomenclatureId, $externalCode, $integrationId);
        $productId = $this->productMatchResolver->resolveDeleteProductId($nomenclatureId, $partNumber, $externalId, $integration);

        if ($productId === null) {
            $this->logger->warning('MoySklad: товар для удаления не найден, операция пропущена.', [
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

            $this->logger->error('MoySklad: ошибка удаления номенклатуры.', [
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
     *
     * @param  array<string, mixed>|MoySkladProductPayloadDTO  $payload
     */
    public function payloadHash(array|MoySkladProductPayloadDTO $payload): string
    {
        return $this->payloadHasher->hash($payload);
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
     * Записывает успешный результат синхронизации в `nomenclature_integrations`.
     */
    private function markSyncSuccess(
        NomenclatureIntegrationData $integration,
        MoySkladProductDTO $product,
        MoySkladProductPayloadDTO $payload,
        string $payloadHash,
    ): void {
        $externalId = $product->id;
        $externalCode = $product->externalCode ?? $payload->externalCode;

        $this->integrationCommand->markSynced(
            integration: $integration,
            externalId: $externalId,
            externalCode: $externalCode,
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
     * Возвращает feature flag синхронизации Warehouse/MoySklad.
     */
    private function enabled(): bool
    {
        return (bool) config('warehouse.moysklad.nomenclature_sync.enabled', false);
    }
}
