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
     *
     * @param  array<string, mixed>  $data
     */
    private function kitFilters(array $data): KitExportFiltersDTO
    {
        $filters = $this->arrayValue($data['filters'] ?? []);
        $search = isset($filters['search']) ? trim((string) $filters['search']) : null;

        return new KitExportFiltersDTO(
            ids: $this->intList($filters['ids'] ?? []),
            typeIds: $this->intList($filters['type_ids'] ?? []),
            isActive: $this->nullableBool($filters, 'is_active'),
            isSaleSeparately: $this->nullableBool($filters, 'is_sale_separately'),
            nomenclaturePartNumbers: $this->stringList($filters['nomenclature_part_numbers'] ?? []),
            search: $search === '' ? null : $search,
        );
    }

    /**
     * Собирает сортировку Kit Export из валидированного payload.
     *
     * @param  array<string, mixed>  $data
     */
    private function kitSort(array $data): KitExportSortDTO
    {
        $sort = $this->arrayValue($data['sort'] ?? []);

        return new KitExportSortDTO(
            field: isset($sort['field']) ? (string) $sort['field'] : 'id',
            direction: isset($sort['direction']) ? (string) $sort['direction'] : 'asc',
        );
    }

    /**
     * Гарантирует array-тип после валидации вложенных payload-полей.
     *
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * Приводит список числовых значений payload к list<int>.
     *
     * @return list<int>
     */
    private function intList(mixed $values): array
    {
        $values = is_array($values) ? $values : [];
        $toInteger = fn (mixed $value): int => (int) $value;

        return array_values(array_map(
            $toInteger,
            $values,
        ));
    }

    /**
     * Приводит список строковых значений payload к непустому list<string>.
     *
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        $values = is_array($values) ? $values : [];
        $trimValue = fn (mixed $value): string => trim((string) $value);
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

    /**
     * Возвращает nullable bool для необязательных boolean-фильтров.
     *
     * @param  array<string, mixed>  $filters
     */
    private function nullableBool(array $filters, string $key): ?bool
    {
        if (! array_key_exists($key, $filters)) {
            return null;
        }

        return filter_var($filters[$key], FILTER_VALIDATE_BOOL);
    }
}
