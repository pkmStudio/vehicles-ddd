<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Presentation\Console\Support;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\External\PublishLocalImportRequestUseCaseInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\LocalImportRequestDTO;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Базовая команда публикации локального Vehicles import file в тот же RabbitMQ-flow, что использует CRM.
 */
abstract class RequestLocalImportCommand extends Command
{
    /**
     * Логическое имя inbound-события из config/rabbit-transport.php.
     *
     * Шаги:
     * 1. Выбрать событие, соответствующее конкретному типу локального импорта.
     * 2. Вернуть имя события для Rabbit envelope.
     */
    abstract protected function eventName(): string;

    /**
     * Routing key, привязанный к inbound-событию в config/rabbit-transport.php:setup.bindings.
     *
     * Шаги:
     * 1. Выбрать routing key для конкретного import request.
     * 2. Вернуть key для публикации в exchange.
     */
    abstract protected function routingKey(): string;

    /**
     * Значение data.import_type, по которому handler выберет Excel-адаптер.
     *
     * Шаги:
     * 1. Выбрать external import type, который поддерживает ImportFileFactory.
     * 2. Вернуть value enum для payload.
     */
    abstract protected function importType(): string;

    /**
     * Удалять ли исходный файл после успешного импорта (data.cleanup_after_import).
     *
     * Шаги:
     * 1. Зафиксировать policy конкретной local command.
     * 2. Вернуть false по умолчанию, чтобы локальный файл оставался доступен после запуска.
     */
    protected function cleanupAfterImport(): bool
    {
        return false;
    }

    /**
     * Собирает request DTO и делегирует публикацию Application use case.
     *
     * Шаги:
     * 1. Прочитать CLI arguments/options и заполнить operation_id по умолчанию.
     * 2. Собрать LocalImportRequestDTO с event/routing/import metadata.
     * 3. Передать DTO в Application use case публикации Rabbit-сообщения.
     * 4. Вывести результат и вернуть console exit code.
     */
    public function handle(PublishLocalImportRequestUseCaseInterface $useCase): int
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
