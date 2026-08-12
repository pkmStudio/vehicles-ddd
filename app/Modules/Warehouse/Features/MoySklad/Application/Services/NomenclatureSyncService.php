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
     * Шаги:
     * 1) Сохранить client port внешних операций с товарами МойСклад.
     * 2) Сохранить read/write ports Warehouse integration-state.
     * 3) Сохранить mapper, resolver папок, resolver совпадений товара и hasher payload.
     * 4) Сохранить logger для actionable ошибок sync/delete workflow.
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
     * Шаги:
     * 1) Проверить feature flag MoySklad sync и выйти без действий, если он выключен.
     * 2) Загрузить Warehouse-номенклатуру; если её нет, записать warning и завершить сценарий.
     * 3) Найти integration-state или создать pending-запись для номенклатуры.
     * 4) Определить папку товара, собрать payload и посчитать hash payload.
     * 5) Пропустить update, если последний успешный payload не изменился и externalId сохранен.
     * 6) Создать или обновить товар через ProductMatchResolver.
     * 7) На успехе записать synced state, на ошибке записать failed state, залогировать и пробросить exception.
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
     * Шаги:
     * 1) Проверить feature flag MoySklad sync и выйти без действий, если он выключен.
     * 2) Построить expected externalCode по id номенклатуры.
     * 3) Найти integration-state для удаления по id/code/integrationId.
     * 4) Определить productId через сохраненную связь, externalId или fallback поиск по артикулу.
     * 5) Если товар не найден, отметить integration удаленной и завершить сценарий.
     * 6) Удалить товар во внешнем client и отметить integration deleted.
     * 7) На ошибке отметить integration failed, залогировать контекст и пробросить exception.
     */
    public function delete(int $nomenclatureId, string $partNumber, ?string $externalId = null, ?int $integrationId = null): void
    {
        $syncEnabled = $this->enabled();

        if (! $syncEnabled) {
            return;
        }

        $externalCode = $this->mapper->externalCodeForNomenclatureId($nomenclatureId);
        $integration = $this->integrations->findForDeletion($nomenclatureId, $externalCode, $integrationId);
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
     * Шаги:
     * 1) Передать array или DTO payload в ProductPayloadHasher.
     * 2) Вернуть stable hash, который используется для idempotent skip update.
     *
     * @param  array<string, mixed>|MoySkladProductPayloadDTO  $payload
     */
    public function payloadHash(array|MoySkladProductPayloadDTO $payload): string
    {
        return $this->payloadHasher->hash($payload);
    }

    /**
     * Проверяет, можно ли не отправлять update, если последний успешный payload не изменился.
     * Шаги:
     * 1) Убедиться, что integration находится в статусе synced.
     * 2) Сравнить сохраненный payloadHash с текущим hash.
     * 3) Проверить, что externalId сохранен непустой строкой.
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
     * Шаги:
     * 1) Взять externalId из ответа МойСклад.
     * 2) Взять externalCode из ответа или fallback-нуться на code отправленного payload.
     * 3) Передать integration, external identifiers и payloadHash в command port markSynced().
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
     * Шаги:
     * 1) Извлечь message исходного Throwable.
     * 2) Передать integration и message в command port markFailed().
     */
    private function markSyncFailure(NomenclatureIntegrationData $integration, Throwable $e): void
    {
        $this->integrationCommand->markFailed($integration, $e->getMessage());
    }

    /**
     * Возвращает feature flag синхронизации Warehouse/MoySklad.
     * Шаги:
     * 1) Прочитать warehouse.moysklad.nomenclature_sync.enabled из config.
     * 2) Вернуть false по умолчанию, чтобы интеграция была opt-in.
     */
    private function enabled(): bool
    {
        return (bool) config('warehouse.moysklad.nomenclature_sync.enabled', false);
    }
}
