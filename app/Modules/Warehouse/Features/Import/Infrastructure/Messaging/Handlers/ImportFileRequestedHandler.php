<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Warehouse\Features\Import\Infrastructure\Messaging\Validators\ImportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Принимает RabbitMQ payload запроса Warehouse-импорта и передаёт его в UseCase. Один Handler на
 * оба типа (Nomenclature/PackDimension) — конкретный Excel-адаптер выбирается по data.import_type
 * (см. Application\Factories\ImportFileFactory).
 */
final readonly class ImportFileRequestedHandler
{
    /**
     * Получает use case запуска импорта и validator входящего payload.
     */
    public function __construct(
        private StartExternalFileImportUseCaseInterface $useCase,
        private ImportFileRequestedPayloadValidator $validator,
    ) {}

    /**
     * Этот метод валидирует payload, собирает DTO и запускает внешний сценарий Warehouse-импорта.
     *
     * Шаги:
     * 1) Проверить payload через Laravel validator.
     * 2) На бизнес-невалидном сообщении записать ошибку и дропнуть сообщение.
     * 3) Собрать ExternalImportFileRequestDTO с диском общих файлов из конфига.
     * 4) Передать DTO в UseCase.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error(
                message: 'RabbitMQ: Warehouse import file request payload validation failed',
                context: [
                    'invalid_keys' => array_keys($validator->errors()->toArray()),
                ],
            );

            return;
        }

        $data = $validator->validated();

        $request = new ExternalImportFileRequestDTO(
            userId: (int) $data['user_id'],
            runId: (string) $data['run_id'],
            importType: ImportTypeEnum::from((string) $data['import_type']),
            disk: (string) config(
                key: 'filesystems.files_disk',
                default: 's3',
            ),
            path: (string) $data['path'],
        );

        $this->useCase->execute($request);
    }
}
