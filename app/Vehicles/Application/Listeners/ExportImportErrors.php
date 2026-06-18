<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\AbstractImportCompleted;
use App\Vehicles\Infrastructure\Exports\FailuresExport;
use App\Vehicles\Application\Contracts\Notifications\FileNotificationServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Throwable;

final readonly class ExportImportErrors
{
    public function __construct(
        private FileNotificationServiceInterface $notifier,
    ) {}

    public function handle(AbstractImportCompleted $event): void
    {
        $user = $event->user;
        $cacheKey = $event->cacheKey;

        try {
            $failures = Cache::get($cacheKey, []);

            if (! empty($failures)) {
                $fileName = 'import-failures'.now()->format('Y-m-d-His').'.csv';
                // TODO: диск 'exports' должен указывать на общее S3-хранилище (config/filesystems.php).
                ExcelFacade::store(new FailuresExport($failures), $fileName, 'exports', Excel::CSV);
                // Файл в S3 — публикуем сообщение «файл готов» (RabbitMqFileNotificationService).
                $this->notifier->send($user, "exports/{$fileName}");
            } else {
                // TODO: вместо Filament — опубликовать в RabbitMQ событие IMPORT_SUCCEEDED
                // сервису с Filament, чтобы он показал пользователю успех (см. OutboundEventsEnum).
                Log::info('Import completed without failures', ['user_id' => $user->id]);
            }
        } catch (Throwable $e) {
            Log::error('Import error export failed', ['exception' => $e]);
            // TODO: вместо Filament — опубликовать в RabbitMQ событие IMPORT_FAILED
            // сервису с Filament для уведомления пользователя об ошибке обработки результатов.
        } finally {
            Cache::forget($cacheKey);
        }
    }
}
