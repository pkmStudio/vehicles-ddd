<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;
use ValueError;

/**
 * Публикует failed-result для payload, несовместимого с текущим wire-контрактом Vehicles Catalog.
 */
final readonly class CatalogMutationContractMismatchReporter
{
    private const string MESSAGE = 'Payload is incompatible with current dan-vehicles contract. Update dan-wire-contracts version.';

    /**
     * Инициализирует publisher failed-result событий мутаций каталога.
     *
     * Шаги:
     * 1. Получает notification port из контейнера.
     * 2. Сохраняет port для последующей публикации результата.
     */
    public function __construct(
        private CatalogMutationNotificationServiceInterface $notifier,
    ) {}

    /**
     * Публикует failed-result, если payload содержит минимальные поля корреляции.
     *
     * Шаги:
     * 1. Извлекает `user_id`, `operation_id` и `operation` из payload.
     * 2. Пропускает публикацию, если по payload нельзя собрать валидный result event.
     * 3. Определяет внешний id сущности по типу catalog entity.
     * 4. Публикует `CatalogMutationResultDTO` со статусом `failed` и reason `contract_mismatch`.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $invalidKeys
     */
    public function report(CatalogEntityEnum $entity, array $payload, array $invalidKeys): void
    {
        $userId = $payload['user_id'] ?? null;
        $operationId = $payload['operation_id'] ?? null;
        $operationValue = $payload['operation'] ?? null;

        if (! is_int($userId) || ! is_string($operationId) || ! is_string($operationValue)) {
            return;
        }

        $operationId = trim($operationId);
        $operation = $this->operationOrNull($operationValue);

        if ($operationId === '' || $operation === null) {
            return;
        }

        $this->notifier->notify(new CatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: CatalogMutationStatusEnum::Failed,
            externalId: $this->externalId($entity, $payload),
            reason: CatalogMutationRejectReasonEnum::ContractMismatch->value,
            errors: [
                'message' => self::MESSAGE,
                'invalid_keys' => $invalidKeys,
            ],
        ));
    }

    /**
     * Достает внешний id из вложенного payload конкретной сущности.
     *
     * Шаги:
     * 1. Выбирает ключ вложенной сущности по catalog entity.
     * 2. Выбирает имя id-поля этой сущности.
     * 3. Возвращает `null`, если payload сущности отсутствует или имеет неверный тип.
     * 4. Нормализует найденное значение id в `int|null`.
     *
     * @param  array<string, mixed>  $payload
     */
    private function externalId(CatalogEntityEnum $entity, array $payload): ?int
    {
        $entityKey = match ($entity) {
            CatalogEntityEnum::Vehicle => 'vehicle',
            CatalogEntityEnum::Manufacturer => 'manufacturer',
            CatalogEntityEnum::Engine => 'engine',
            CatalogEntityEnum::Modification => 'modification',
            CatalogEntityEnum::PartSpecification => 'part_specification',
        };

        $idKey = match ($entity) {
            CatalogEntityEnum::Vehicle => 'ms_id',
            CatalogEntityEnum::Manufacturer => 'mfa_id',
            CatalogEntityEnum::Engine => 'eng_id',
            CatalogEntityEnum::Modification => 'mod_id',
            CatalogEntityEnum::PartSpecification => 'id',
        };

        $entityPayload = $payload[$entityKey] ?? null;

        if (! is_array($entityPayload)) {
            return null;
        }

        $externalId = $entityPayload[$idKey] ?? null;

        return is_int($externalId) ? $externalId : null;
    }

    /**
     * Нормализует operation из payload в enum мутации каталога.
     *
     * Шаги:
     * 1. Приводит входное значение к строке.
     * 2. Пытается собрать `CatalogMutationOperationEnum`.
     * 3. Возвращает `null`, если значение не входит в wire-contract enum.
     */
    private function operationOrNull(?string $operation): ?CatalogMutationOperationEnum
    {
        if ($operation === null || $operation === '') {
            return null;
        }

        try {
            return CatalogMutationOperationEnum::from($operation);
        } catch (ValueError) {
            return null;
        }
    }
}
