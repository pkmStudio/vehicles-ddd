<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications\KitResetNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitResetRequestDTO;
use Throwable;

/**
 * Выполняет bulk-сброс Warehouse-наборов и сообщает результат во внешний контур.
 */
final readonly class ResetKitsUseCase
{
    /**
     * Получает command-port записи наборов и notification-port результата.
     */
    public function __construct(
        private KitCommandInterface $kits,
        private KitResetNotificationServiceInterface $notifier,
    ) {}

    /**
     * Запускает сброс наборов.
     *
     * Шаги:
     * 1) Выполнить catalog command очистки kits.
     * 2) На успехе отправить completed-result.
     * 3) На ошибке отправить failed-result и пробросить исключение для retry/DLQ политики.
     *
     * @throws Throwable
     */
    public function execute(KitResetRequestDTO $request): void
    {
        try {
            $this->kits->reset();
            $this->notifier->completed($request->userId, $request->operationId);
        } catch (Throwable $e) {
            $this->notifier->failed($request->userId, $request->operationId);

            throw $e;
        }
    }
}
