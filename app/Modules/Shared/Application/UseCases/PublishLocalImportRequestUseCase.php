<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\UseCases;

use App\Modules\Shared\Domain\Contracts\Files\LocalImportFileStorageInterface;
use App\Modules\Shared\Domain\Contracts\Publishers\LocalImportRequestPublisherInterface;
use App\Modules\Shared\Domain\Contracts\UseCases\PublishLocalImportRequestUseCaseInterface;
use App\Modules\Shared\Domain\DTOs\LocalImportRequestDTO;
use App\Modules\Shared\Domain\DTOs\LocalImportRequestResultDTO;
use Psr\Log\LoggerInterface;

/**
 * Проверяет локальный файл и публикует import request через порт транспорта.
 */
final readonly class PublishLocalImportRequestUseCase implements PublishLocalImportRequestUseCaseInterface
{
    /**
     * Получает порты Storage/RabbitMQ и PSR logger для workflow-событий.
     */
    public function __construct(
        private LocalImportFileStorageInterface $storage,
        private LocalImportRequestPublisherInterface $publisher,
        private LoggerInterface $logger,
    ) {}

    /**
     * Проверяет входные параметры, существование файла и публикует RabbitMQ-request.
     *
     * Шаги:
     * 1. Проверить userId и относительный path.
     * 2. Проверить Storage disk и существование файла через порт.
     * 3. Опубликовать request через publisher port и вернуть console-friendly result.
     */
    public function execute(LocalImportRequestDTO $request): LocalImportRequestResultDTO
    {
        if ($request->userId < 1) {
            return LocalImportRequestResultDTO::failure('Опция --user-id должна быть положительным целым числом.');
        }

        if (! $this->isValidRelativePath($request->path)) {
            return LocalImportRequestResultDTO::failure('Путь должен быть относительным и не должен содержать "..".');
        }

        if (! $this->storage->diskExists($request->disk)) {
            return LocalImportRequestResultDTO::failure("Storage disk [{$request->disk}] не настроен.");
        }

        if (! $this->storage->fileExists($request->disk, $request->path)) {
            $this->logger->warning('Локальный файл импорта не найден.', [
                'event' => $request->eventName,
                'routing_key' => $request->routingKey,
                'operation_id' => $request->operationId,
                'disk' => $request->disk,
                'path' => $request->path,
            ]);

            return LocalImportRequestResultDTO::failure(
                "Файл [{$request->path}] не найден на Storage disk [{$request->disk}].",
            );
        }

        $published = $this->publisher->publish($request);

        if (! $published) {
            return LocalImportRequestResultDTO::failure('Не удалось опубликовать RabbitMQ-запрос импорта.');
        }

        return LocalImportRequestResultDTO::success(sprintf(
            'RabbitMQ-запрос импорта опубликован: event=%s routing_key=%s operationId=%s disk=%s path=%s',
            $request->eventName,
            $request->routingKey,
            $request->operationId,
            $request->disk,
            $request->path,
        ));
    }

    /**
     * Проверяет, что path безопасен для локального Storage disk.
     */
    private function isValidRelativePath(string $path): bool
    {
        return $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '..');
    }
}
