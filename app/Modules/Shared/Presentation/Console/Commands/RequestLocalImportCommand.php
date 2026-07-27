<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Базовая команда публикации локального файла импорта в тот же RabbitMQ-flow, что использует CRM.
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
     * Проверяет файл в локальном Storage disk и публикует RabbitMQ-запрос импорта.
     */
    public function handle(RabbitMQPublisher $publisher): int
    {
        $path = trim((string) $this->argument('path'));
        $disk = trim((string) ($this->option('disk') ?? 'local'));
        $runId = trim((string) ($this->option('run-id') ?: Str::uuid()));
        $userId = (int) $this->option('user-id');

        if ($userId < 1) {
            $this->error('Опция --user-id должна быть положительным целым числом.');

            return self::FAILURE;
        }

        if (! $this->isValidRelativePath($path)) {
            $this->error('Путь должен быть относительным и не должен содержать "..".');

            return self::FAILURE;
        }

        if (! array_key_exists($disk, (array) config('filesystems.disks', []))) {
            $this->error("Storage disk [{$disk}] не настроен.");

            return self::FAILURE;
        }

        if (! Storage::disk($disk)->exists($path)) {
            $this->error("Файл [{$path}] не найден на Storage disk [{$disk}].");

            return self::FAILURE;
        }

        $published = $publisher->publish(
            message: new RabbitMessageDTO(
                name: $this->eventName(),
                data: [
                    'user_id' => $userId,
                    'run_id' => $runId,
                    'import_type' => $this->importType(),
                    'disk' => $disk,
                    'path' => $path,
                    'cleanup_after_import' => false,
                ],
            ),
            routingKey: $this->routingKey(),
        );

        if (! $published) {
            $this->error('Не удалось опубликовать RabbitMQ-запрос импорта.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'RabbitMQ-запрос импорта опубликован: event=%s routing_key=%s runId=%s disk=%s path=%s',
            $this->eventName(),
            $this->routingKey(),
            $runId,
            $disk,
            $path,
        ));

        return self::SUCCESS;
    }

    private function isValidRelativePath(string $path): bool
    {
        return $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '..');
    }
}
