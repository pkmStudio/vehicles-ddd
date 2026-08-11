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
        $userId = $this->integerOrNull($payload['user_id'] ?? null);
        $operationId = $this->stringOrNull($payload['operation_id'] ?? null);
        $operation = $this->operationOrNull($payload['operation'] ?? null);

        if ($userId === null || $operationId === null || $operation === null) {
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

        return $this->integerOrNull($entityPayload[$idKey] ?? null);
    }

    /**
     * Нормализует operation из payload в enum мутации каталога.
     *
     * Шаги:
     * 1. Приводит входное значение к строке.
     * 2. Пытается собрать `CatalogMutationOperationEnum`.
     * 3. Возвращает `null`, если значение не входит в wire-contract enum.
     */
    private function operationOrNull(mixed $operation): ?CatalogMutationOperationEnum
    {
        try {
            return CatalogMutationOperationEnum::from((string) $operation);
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
