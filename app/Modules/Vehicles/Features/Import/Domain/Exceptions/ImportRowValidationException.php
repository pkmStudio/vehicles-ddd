<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Exceptions;

use DomainException;

/**
 * Ошибка валидации одной строки импорта без зависимости Domain/Application от Laravel Validator.
 */
final class ImportRowValidationException extends DomainException
{
    /**
     * Создает exception из нормализованного списка ошибок строки.
     *
     * @param  array<int, string>  $errors
     */
    private function __construct(
        private readonly array $errors,
    ) {
        parent::__construct(implode(' ', $errors));
    }

    /**
     * Собирает exception из вложенного массива сообщений validator'а.
     *
     * @param  array<string, array<int, string>|string>  $messages
     */
    public static function fromMessages(array $messages): self
    {
        $errors = [];

        array_walk_recursive($messages, static function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        return new self($errors === [] ? ['Ошибка валидации строки импорта.'] : $errors);
    }

    /**
     * Создает exception из одного сообщения об ошибке строки.
     */
    public static function fromMessage(string $message): self
    {
        return new self([$message]);
    }

    /**
     * Возвращает нормализованный список сообщений для отчета failures.
     *
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
