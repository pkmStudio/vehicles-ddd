<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Presentation\Console\Support;

use App\Modules\Warehouse\Features\Import\Application\UseCases\External\PublishLocalImportRequestUseCase;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\LocalImportRequestDTO;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Базовая команда публикации локального Warehouse import file в тот же RabbitMQ-flow, что использует CRM.
 */
abstract class RequestLocalImportCommand extends Command
{
    /**
     * Логическое имя inbound-события из config/rabbit-transport.php.
     *
     * Шаги:
     * 1) Вернуть имя RabbitMQ-события конкретного Warehouse-импорта.
     * 2) Передать его в LocalImportRequestDTO базовой команды.
     */
    abstract protected function eventName(): string;

    /**
     * Routing key, привязанный к inbound-событию в config/rabbit-transport.php:setup.bindings.
     *
     * Шаги:
     * 1) Вернуть routing key конкретного Warehouse-импорта.
     * 2) Передать его publisher'у через LocalImportRequestDTO.
     */
    abstract protected function routingKey(): string;

    /**
     * Значение data.import_type, по которому handler выберет Excel-адаптер.
     *
     * Шаги:
     * 1) Вернуть wire-значение ImportTypeEnum конкретного каталога.
     * 2) Передать его handler'у для выбора Excel adapter'а.
     */
    abstract protected function importType(): string;

    /**
     * Удалять ли исходный файл после успешного импорта (data.cleanup_after_import).
     *
     * Шаги:
     * 1) Определить режим очистки для конкретной команды.
     * 2) Вернуть false по умолчанию для операторских локальных запусков.
     */
    protected function cleanupAfterImport(): bool
    {
        return false;
    }

    /**
     * Собирает request DTO и делегирует публикацию Application use case.
     *
     * Шаги:
     * 1) Прочитать path, disk, userId и optional operationId из CLI ввода.
     * 2) Добавить eventName, routingKey, importType и cleanupAfterImport из конкретной команды.
     * 3) Передать LocalImportRequestDTO в use case публикации.
     * 4) Вывести ошибку и вернуть FAILURE при неуспешном result.
     * 5) Вывести сообщение успеха и вернуть SUCCESS.
     */
    public function handle(PublishLocalImportRequestUseCase $useCase): int
    {
        $request = new LocalImportRequestDTO(
            eventName: $this->eventName(),
            routingKey: $this->routingKey(),
            importType: $this->importType(),
            disk: trim((string) ($this->option('disk') ?? 'local')),
            path: trim((string) $this->argument('path')),
            userId: (int) $this->option('user-id'),
            operationId: trim((string) ($this->option('operation-id') ?: Str::uuid())),
            cleanupAfterImport: $this->cleanupAfterImport(),
        );

        $result = $useCase->execute($request);

        if (! $result->success) {
            $this->error($result->message);

            return self::FAILURE;
        }

        $this->info($result->message);

        return self::SUCCESS;
    }
}
