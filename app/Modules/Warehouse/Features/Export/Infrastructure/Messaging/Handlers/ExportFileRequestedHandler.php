<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Warehouse\Features\Export\Infrastructure\Messaging\Validators\ExportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Принимает RabbitMQ payload запроса Warehouse-экспорта и передаёт его в UseCase.
 */
final readonly class ExportFileRequestedHandler
{
    /**
     * Получает сценарий запуска экспорта и validator входящего сообщения.
     * Шаги:
     * 1) Сохранить external export use case port.
     * 2) Сохранить payload validator для входящего RabbitMQ сообщения.
     */
    public function __construct(
        private StartExportUseCaseInterface $useCase,
        private ExportFileRequestedPayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает внешний сценарий Warehouse-экспорта.
     *
     * Шаги:
     * 1) Проверить payload через Laravel validator.
     * 2) На бизнес-невалидном сообщении записать ошибку и дропнуть сообщение.
     * 3) Собрать ExportFileRequestDTO с output-disk из конфига сервиса.
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
                message: 'RabbitMQ: Warehouse export file request payload validation failed',
                context: [
                    'invalid_keys' => array_keys($validator->errors()->toArray()),
                ],
            );

            return;
        }

        $data = $validator->validated();
        $outputDisk = (string) config(
            key: 'warehouse.export.output.disk',
            default: 'local',
        );

        $exportType = ExportTypeEnum::from((string) $data['export_type']);
        $kitFilters = $exportType === ExportTypeEnum::Kit ? $this->kitFilters($data) : null;
        $kitSort = $exportType === ExportTypeEnum::Kit ? $this->kitSort($data) : null;

        $request = new ExportFileRequestDTO(
            userId: (int) $data['user_id'],
            operationId: (string) $data['operation_id'],
            exportType: $exportType,
            disk: $outputDisk,
            typeId: isset($data['type_id']) ? (int) $data['type_id'] : null,
            kitFilters: $kitFilters,
            kitSort: $kitSort,
        );

        $this->useCase->execute($request);
    }

    /**
     * Собирает нормализованные фильтры Kit Export из валидированного payload.
     * Шаги:
     * 1) Безопасно получить вложенный filters array.
     * 2) Нормализовать search в trimmed string|null.
     * 3) Привести ids/type_ids к list<int>.
     * 4) Привести boolean filters к доменным значениям.
     * 5) Привести part numbers к непустому list<string>.
     *
     * @param  array<string, mixed>  $data
     */
    private function kitFilters(array $data): KitExportFiltersDTO
    {
        $filters = $data['filters'] ?? [];
        $search = isset($filters['search']) ? trim((string) $filters['search']) : null;

        return new KitExportFiltersDTO(
            ids: array_values(array_map('intval', $filters['ids'] ?? [])),
            typeIds: array_values(array_map('intval', $filters['type_ids'] ?? [])),
            isActive: $filters['is_active'] ?? true,
            isSaleSeparately: $filters['is_sale_separately'] ?? false,
            nomenclaturePartNumbers: $this->filledStringList($filters['nomenclature_part_numbers'] ?? []),
            search: $search === '' ? null : $search,
        );
    }

    /**
     * Собирает сортировку Kit Export из валидированного payload.
     * Шаги:
     * 1) Безопасно получить вложенный sort array.
     * 2) Взять field из payload или fallback-нуться на id.
     * 3) Взять direction из payload или fallback-нуться на asc.
     *
     * @param  array<string, mixed>  $data
     */
    private function kitSort(array $data): KitExportSortDTO
    {
        $sort = $data['sort'] ?? [];

        return new KitExportSortDTO(
            field: isset($sort['field']) ? (string) $sort['field'] : 'id',
            direction: isset($sort['direction']) ? (string) $sort['direction'] : 'asc',
        );
    }

    /**
     * Приводит список строковых значений payload к непустому list<string>.
     * Шаги:
     * 1) Привести каждое валидированное строковое значение к trimmed string.
     * 2) Отфильтровать пустые строки.
     * 3) Переиндексировать результат как list<string>.
     *
     * @param  list<string>  $values
     * @return list<string>
     */
    private function filledStringList(array $values): array
    {
        $trimValue = fn (string $value): string => trim($value);
        $isFilledValue = fn (string $value): bool => $value !== '';

        $values = array_map(
            $trimValue,
            $values,
        );

        return array_values(array_filter(
            array: $values,
            callback: $isFilledValue,
        ));
    }
}
