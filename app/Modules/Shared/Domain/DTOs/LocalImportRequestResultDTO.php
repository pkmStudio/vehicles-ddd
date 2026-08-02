<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\DTOs;

/**
 * Результат попытки публикации локального import request.
 */
final readonly class LocalImportRequestResultDTO
{
    /**
     * Получает статус и пользовательское сообщение для console entrypoint.
     */
    public function __construct(
        public bool $success,
        public string $message,
    ) {}

    /**
     * Создаёт успешный результат.
     */
    public static function success(string $message): self
    {
        return new self(
            success: true,
            message: $message,
        );
    }

    /**
     * Создаёт неуспешный результат.
     */
    public static function failure(string $message): self
    {
        return new self(
            success: false,
            message: $message,
        );
    }
}
