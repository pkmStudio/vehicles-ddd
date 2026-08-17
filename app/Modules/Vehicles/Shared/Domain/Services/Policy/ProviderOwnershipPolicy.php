<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Services\Policy;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;
use Closure;

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
     * @param  array<string, string|int|float|null>  $incoming
     * @param  array<int, string>  $existingAllowChangeFields
     * @param  array<int, string>  $incomingAllowChangeFields
     * @param  Closure(string): (string|int|float|null)  $currentValue
     * @return array<string, string|int|float|array<int, string>|null>
     */
    public function payload(
        ProviderEnum $existingProvider,
        ProviderEnum $incomingProvider,
        array $incoming,
        array $existingAllowChangeFields,
        array $incomingAllowChangeFields,
        Closure $currentValue,
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
     * Проверяет отличие двух scalar значений.
     */
    private function changed(string|int|float|null $current, string|int|float|null $incoming): bool
    {
        return (string) $current !== (string) $incoming;
    }
}
