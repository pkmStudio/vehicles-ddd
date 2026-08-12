<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\UseCases\External;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Files\LocalImportFileStorageInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Publishers\LocalImportRequestPublisherInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\External\PublishLocalImportRequestUseCaseInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\LocalImportRequestDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\LocalImportRequestResultDTO;
use Psr\Log\LoggerInterface;

/**
 * Проверяет локальный Vehicles import file и публикует import request через порт транспорта.
 */
final readonly class PublishLocalImportRequestUseCase implements PublishLocalImportRequestUseCaseInterface
{
    /**
     * Получает порты Storage/RabbitMQ и PSR logger для workflow-событий.
     *
     * Шаги:
     * 1) Сохранить storage port для проверки локального import file.
     * 2) Сохранить publisher port для отправки RabbitMQ request.
     * 3) Сохранить PSR logger для actionable workflow anomalies.
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
     * 1) Проверить положительный `userId`.
     * 2) Проверить, что path является безопасным относительным путем.
     * 3) Проверить наличие настроенного storage disk.
     * 4) Проверить наличие файла и залогировать warning, если его нет.
     * 5) Опубликовать RabbitMQ request через publisher port.
     * 6) Вернуть result DTO с сообщением успеха или ошибкой публикации.
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
            $this->logger->warning('Локальный файл импорта Vehicles не найден.', [
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
     *
     * Шаги:
     * 1) Отклонить пустой путь.
     * 2) Отклонить absolute path.
     * 3) Отклонить path traversal через `..`.
     */
    private function isValidRelativePath(string $path): bool
    {
        return $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '..');
    }
}
