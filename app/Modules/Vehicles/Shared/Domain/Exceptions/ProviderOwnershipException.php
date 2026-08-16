<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Exceptions;

use DomainException;

/**
 * Ошибка provider ownership правила при записи catalog/import данных.
 */
final class ProviderOwnershipException extends DomainException
{
    /**
     * @param  array<string, array<int, string>|string>  $errors
     */
    private function __construct(
        private readonly array $errors,
    ) {
        parent::__construct($this->messageFromErrors($errors));
    }

    /**
     * Создает ошибку попытки сменить provider существующей записи.
     */
    public static function providerConflict(
        string $entityLabel,
        int $externalId,
        string $existingProvider,
        string $incomingProvider,
    ): self {
        return new self([
            'provider' => [
                "{$entityLabel} {$externalId} уже принадлежит provider={$existingProvider}; provider={$incomingProvider} не может менять provider существующей записи.",
            ],
        ]);
    }

    /**
     * Создает ошибку закрытых для изменения полей.
     *
     * @param  array<string, array<int, string>|string>  $errors
     */
    public static function fromMessages(array $errors): self
    {
        return new self($errors);
    }

    /**
     * Возвращает normalized errors для adapter-specific обработки.
     *
     * @return array<string, array<int, string>|string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Собирает короткое сообщение exception из массива ошибок.
     *
     * @param  array<string, array<int, string>|string>  $errors
     */
    private function messageFromErrors(array $errors): string
    {
        $messages = [];

        array_walk_recursive($errors, static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        return implode(' ', $messages);
    }
}
