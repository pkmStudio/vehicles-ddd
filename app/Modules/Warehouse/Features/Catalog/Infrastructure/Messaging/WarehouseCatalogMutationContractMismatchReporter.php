<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use ValueError;

/**
 * Публикует failed-result для данные сообщения, несовместимого с текущим wire-контрактом Warehouse Catalog.
 */
final readonly class WarehouseCatalogMutationContractMismatchReporter
{
    private const string MESSAGE = 'Payload is incompatible with current dan-vehicles contract. Update dan-wire-contracts version.';

    /**
     * Инициализирует publisher failed-result событий Warehouse Catalog.
     *
     * Шаги:
     * 1. Получает notification port из контейнера.
     * 2. Сохраняет port для последующей публикации результата.
     */
    public function __construct(
        private WarehouseCatalogMutationNotificationServiceInterface $notifier,
    ) {}

    /**
     * Публикует failed-result, если данные сообщения содержит минимальные поля корреляции.
     *
     * Шаги:
     * 1. Извлекает `user_id`, `operation_id` и `operation` из данные сообщения.
     * 2. Пропускает публикацию, если по данные сообщения нельзя собрать валидный result event.
     * 3. Определяет record id сущности по типу Warehouse catalog entity.
     * 4. Публикует `WarehouseCatalogMutationResultDTO` со статусом `failed` и reason `contract_mismatch`.
     *
     * @param  array<string, mixed>  $данные  сообщения
     * @param  array<int, string>  $invalidKeys
     */
    public function report(WarehouseCatalogEntityEnum $entity, array $payload, array $invalidKeys): void
    {
        $userId = $this->integerOrNull($payload['user_id'] ?? null);
        $operationId = $this->stringOrNull($payload['operation_id'] ?? null);
        $operation = $this->operationOrNull($payload['operation'] ?? null);

        if ($userId === null || $operationId === null || $operation === null) {
            return;
        }

        $this->notifier->notify(new WarehouseCatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: WarehouseCatalogMutationStatusEnum::Failed,
            recordId: $this->recordId($entity, $payload),
            reason: WarehouseCatalogMutationRejectReasonEnum::ContractMismatch->value,
            errors: [
                'message' => self::MESSAGE,
                'invalid_keys' => $invalidKeys,
            ],
        ));
    }

    /**
     * Достает record id из вложенного данные сообщения конкретной сущности.
     *
     * Шаги:
     * 1. Выбирает ключ вложенной сущности по Warehouse catalog entity.
     * 2. Возвращает `null`, если данные сообщения сущности отсутствует или имеет неверный тип.
     * 3. Нормализует найденное значение `id` в `int|null`.
     *
     * @param  array<string, mixed>  $данные  сообщения
     */
    private function recordId(WarehouseCatalogEntityEnum $entity, array $payload): ?int
    {
        $entityKey = match ($entity) {
            WarehouseCatalogEntityEnum::Brand => 'brand',
            WarehouseCatalogEntityEnum::Nomenclature => 'nomenclature',
            WarehouseCatalogEntityEnum::PackDimension => 'pack_dimension',
            WarehouseCatalogEntityEnum::Kit => 'kit',
        };

        $entityPayload = $payload[$entityKey] ?? null;

        if (! is_array($entityPayload)) {
            return null;
        }

        return $this->integerOrNull($entityPayload['id'] ?? null);
    }

    /**
     * Нормализует operation из данные сообщения в enum мутации Warehouse Catalog.
     *
     * Шаги:
     * 1. Приводит входное значение к строке.
     * 2. Пытается собрать `WarehouseCatalogMutationOperationEnum`.
     * 3. Возвращает `null`, если значение не входит в wire-contract enum.
     */
    private function operationOrNull(mixed $operation): ?WarehouseCatalogMutationOperationEnum
    {
        try {
            return WarehouseCatalogMutationOperationEnum::from((string) $operation);
        } catch (ValueError) {
            return null;
        }
    }

    /**
     * Нормализует nullable scalar значение в integer.
     *
     * Шаги:
     * 1. Отбрасывает `null` и пустую строку как отсутствие значения.
     * 2. Приводит непустое значение к `int`.
     */
    private function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Нормализует nullable scalar значение в непустую строку.
     *
     * Шаги:
     * 1. Приводит значение к строке и убирает внешние пробелы.
     * 2. Возвращает `null`, если после нормализации строка пустая.
     */
    private function stringOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : (string) $value;

        return $value === '' ? null : $value;
    }
}
