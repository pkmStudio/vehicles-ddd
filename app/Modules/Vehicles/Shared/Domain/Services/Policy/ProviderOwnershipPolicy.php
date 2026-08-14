<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Services\Policy;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;
use BackedEnum;

/**
 * Общее правило provider ownership для записей Vehicles catalog.
 */
final readonly class ProviderOwnershipPolicy
{
    /**
     * Проверяет, что сценарий не пытается сменить владельца записи без allow-change механики.
     *
     * Шаги:
     * 1) Сравнить provider существующей и входящей записи.
     * 2) Если provider отличается — выбросить domain exception.
     */
    public function assertSameProvider(
        ProviderEnum $existingProvider,
        ProviderEnum $incomingProvider,
        string $entityLabel,
        int $externalId,
    ): void {
        if ($existingProvider === $incomingProvider) {
            return;
        }

        throw ProviderOwnershipException::providerConflict(
            entityLabel: $entityLabel,
            externalId: $externalId,
            existingProvider: $existingProvider->value,
            incomingProvider: $incomingProvider->value,
        );
    }

    /**
     * Собирает payload для provider-aware обновления полей с allow_change_fields.
     *
     * Шаги:
     * 1) Если provider совпадает — разрешить полный update входящих бизнес-полей.
     * 2) Если provider отличается — сохранить provider существующей записи.
     * 3) Разрешить менять только пустые или явно открытые поля.
     * 4) Для закрытых измененных полей выбросить domain exception.
     *
     * @param  array<string, mixed>  $incoming
     * @param  array<int, string>  $existingAllowChangeFields
     * @param  array<int, string>  $incomingAllowChangeFields
     * @param  callable(string): mixed  $currentValue
     * @return array<string, mixed>
     */
    public function payload(
        ProviderEnum $existingProvider,
        ProviderEnum $incomingProvider,
        array $incoming,
        array $existingAllowChangeFields,
        array $incomingAllowChangeFields,
        callable $currentValue,
        string $entityLabel,
    ): array {
        if ($existingProvider === $incomingProvider) {
            return [
                ...$incoming,
                'allow_change_fields' => $incomingAllowChangeFields,
            ];
        }

        $allowedFields = $existingAllowChangeFields;
        $payload = [];
        $errors = [];

        foreach ($incoming as $field => $value) {
            $current = $currentValue($field);

            if ($current === null || in_array($field, $allowedFields, true)) {
                $payload[$field] = $value;

                if ($current === null && $value !== null && $value !== '') {
                    $allowedFields[] = $field;
                }

                continue;
            }

            if ($this->changed($current, $value)) {
                $errors[$field] = ["Поле {$field} закрыто для изменения у {$entityLabel}."];
            }
        }

        if ($errors !== []) {
            throw ProviderOwnershipException::fromMessages($errors);
        }

        $payload['allow_change_fields'] = array_values(array_unique($allowedFields));

        return $payload;
    }

    /**
     * Проверяет отличие двух scalar/enum значений.
     */
    private function changed(string|int|float|BackedEnum|null $current, string|int|float|BackedEnum|null $incoming): bool
    {
        $currentValue = $current instanceof BackedEnum ? (string) $current->value : (string) $current;
        $incomingValue = $incoming instanceof BackedEnum ? (string) $incoming->value : (string) $incoming;

        return $currentValue !== $incomingValue;
    }
}
