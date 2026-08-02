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
     */
    abstract protected function eventName(): string;

    /**
     * Routing key, привязанный к inbound-событию в config/rabbit-transport.php:setup.bindings.
     */
    abstract protected function routingKey(): string;

    /**
     * Значение data.import_type, по которому handler выберет Excel-адаптер.
     */
    abstract protected function importType(): string;

    /**
     * Удалять ли исходный файл после успешного импорта (data.cleanup_after_import).
     */
    protected function cleanupAfterImport(): bool
    {
        return false;
    }

    /**
     * Собирает request DTO и делегирует публикацию Application use case.
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
