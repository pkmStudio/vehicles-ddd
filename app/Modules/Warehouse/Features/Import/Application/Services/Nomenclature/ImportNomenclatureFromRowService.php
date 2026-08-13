<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Services\Nomenclature;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Nomenclature\ImportNomenclatureFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\Nomenclature\NomenclatureImportRowDTO;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\TypeData;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated;
use Illuminate\Support\Collection;

/**
 * Резолвит type_id/brand_id и detail-шаблон по справочникам, собирает details и пишет номенклатуру.
 */
final readonly class ImportNomenclatureFromRowService implements ImportNomenclatureFromRowServiceInterface
{
    private const array MATERIAL_KEYS_BY_LABEL = [
        'НИКЕЛЬ' => 'NICKEL',
        'ПЛАТИНА' => 'PLATINUM',
        'ИРИДИЙ' => 'IRIDIUM',
        'ДВОЙНОЙ ИРИДИЙ' => 'DOUBLE_IRIDIUM',
        'ДВОЙНАЯ ПЛАТИНА' => 'DOUBLE_PLATINUM',
        'ДВОЙНОЙ НИКЕЛЬ' => 'DOUBLE_NICKEL',
        'СТАЛЬ' => 'STEEL',
        'МЕТАЛЛ' => 'METAL',
        'ABS ПЛАСТИК' => 'ABS_PLASTIC',
        'БУМАГА' => 'PAPER',
        'РЕЗИНА' => 'RUBBER',
        'ПЛАСТИК' => 'PLASTIC',
    ];

    private const array VEHICLE_TYPE_KEYS_BY_LABEL = [
        'ЛЕГКОВЫЕ АВТОМОБИЛИ' => 'CAR',
        'КОММЕРЧЕСКИЙ ТРАНСПОРТ' => 'TRUCK',
        'ВНЕДОРОЖНИКИ' => 'SUV',
        'ГРУЗОВЫЕ АВТОМОБИЛИ И АВТОБУСЫ' => 'BUS',
    ];

    private const int DETAILS_START_INDEX = 12;

    /**
     * Получает чтение номенклатуры, команду записи, resolver шаблона и фабрику details.
     * Шаги:
     * 1) Сохранить repository для поиска существующей номенклатуры по id/part number.
     * 2) Сохранить command port для create/update записи.
     * 3) Сохранить resolver detail template по Warehouse type.
     * 4) Сохранить Templates client для построения typed details из Excel-строки.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private NomenclatureCommandInterface $command,
        private TypeTemplateResolverInterface $templateResolver,
        private TemplatesClientInterface $templates,
    ) {}

    /**
     * Валидирует строку и пишет номенклатуру через явные create/update команды.
     * Шаги:
     * 1) Переиндексировать предзагруженные types и brands по upper-case имени.
     * 2) Прочитать optional id, type name и brand name из Excel-строки.
     * 3) Найти type/brand в предзагруженных справочниках и дать адресную ошибку по колонке.
     * 4) Определить detail template для type и построить details через Templates boundary.
     * 5) Проверить domain-critical details для template WIPER.
     * 6) Распарсить положительный вес и map label-списки material/vehicle_type во внутренние keys.
     * 7) Собрать NomenclatureData.
     * 8) Если id передан, обновить найденную запись или создать запись с заданным id.
     * 9) Если id не передан, искать существующую запись по part number.
     * 10) Создать или обновить запись и опубликовать created/updated event с import context.
     *
     * @param  Collection<int, TypeData>  $types
     * @param  Collection<int, BrandData>  $brands
     */
    public function importFromRow(
        NomenclatureImportRowDTO $row,
        Collection $types,
        Collection $brands,
        ?int $userId = null,
        ?string $operationId = null,
    ): NomenclatureData {
        $typeNameKey = fn (TypeData $type): string => mb_strtoupper(trim($type->name));
        $brandNameKey = fn (BrandData $brand): string => mb_strtoupper(trim($brand->name));

        $typesByName = $types->keyBy($typeNameKey);
        $brandsByName = $brands->keyBy($brandNameKey);

        $typeName = mb_strtoupper($row->typeName);
        $type = $typesByName->get($typeName);
        if ($type === null) {
            throw ImportRowValidationException::withMessage(
                "Тип товара «{$row->typeName}» не найден. Проверьте колонку «Тип товара» (столбец B).",
            );
        }

        $brandName = mb_strtoupper($row->brandName);
        $brand = $brandsByName->get($brandName);
        if ($brand === null) {
            throw ImportRowValidationException::withMessage(
                "Бренд «{$row->brandName}» не найден. Проверьте колонку «Бренд» (столбец C).",
            );
        }

        $template = $this->templateResolver->resolve($type);
        if ($template === null) {
            throw ImportRowValidationException::withMessage(
                "Шаблон деталей не найден для типа «{$type->name}» (ID={$type->id}).",
            );
        }

        $details = $this->templates->buildNomenclatureDetails($template, $row->sourceCells, self::DETAILS_START_INDEX);
        $this->validateDetails($template, $details, $row->partNumber);

        $data = new NomenclatureData(
            typeId: $type->id,
            brandId: $brand->id,
            name: $row->name,
            country: $row->country,
            partNumber: $row->partNumber,
            color: $row->color,
            weight: $row->weight,
            material: $this->labelsToKeys($row->materialLabels, self::MATERIAL_KEYS_BY_LABEL),
            vehicleType: $this->labelsToKeys($row->vehicleTypeLabels, self::VEHICLE_TYPE_KEYS_BY_LABEL),
            quantityPak: $row->quantityPak,
            quantityInPak: $row->quantityInPak,
            details: $details,
            id: $row->id,
        );

        if ($row->id !== null) {
            $existingById = $this->nomenclatures->findById($row->id);

            if ($existingById === null) {
                $created = $this->command->createWithId($data);
                $this->dispatchMutationEvent($created, wasExisting: false, userId: $userId, operationId: $operationId);

                return $created;
            }

            $updated = $this->command->updateById($data);
            $this->dispatchMutationEvent($updated, wasExisting: true, userId: $userId, operationId: $operationId);

            return $updated;
        }

        $existing = $this->nomenclatures->findByPartNumber($data->partNumber);
        if ($existing !== null) {
            $updated = $this->command->updateById($this->withId($data, $existing->id));
            $this->dispatchMutationEvent($updated, wasExisting: true, userId: $userId, operationId: $operationId);

            return $updated;
        }

        $created = $this->command->create($data);
        $this->dispatchMutationEvent($created, wasExisting: false, userId: $userId, operationId: $operationId);

        return $created;
    }

    /**
     * Публикует shared catalog mutation event после import-created/import-updated записи.
     * Шаги:
     * 1) Подставить fallback userId/operationId для локальных импортов без внешнего контекста.
     * 2) Для существующей записи опубликовать NomenclatureUpdated.
     * 3) Для новой записи опубликовать NomenclatureCreated.
     * 4) Передать snapshot номенклатуры как array payload публичного события.
     */
    private function dispatchMutationEvent(
        NomenclatureData $nomenclature,
        bool $wasExisting,
        ?int $userId,
        ?string $operationId,
    ): void {
        $eventUserId = $userId ?? 0;
        $eventOperationId = $operationId ?? 'warehouse-nomenclature-import';

        if ($wasExisting) {
            event(new NomenclatureUpdated(
                userId: $eventUserId,
                operationId: $eventOperationId,
                nomenclature: $nomenclature->toArray(),
            ));

            return;
        }

        event(new NomenclatureCreated(
            userId: $eventUserId,
            operationId: $eventOperationId,
            nomenclature: $nomenclature->toArray(),
        ));
    }

    /**
     * Проверяет обязательные поля details, которые критичны для последующей сборки комплектов.
     * Шаги:
     * 1) Для всех templates кроме WIPER не применять дополнительное правило.
     * 2) Для WIPER прочитать category из details.
     * 3) Разрешить непустую category.
     * 4) Если category пустая, выбросить ImportRowValidationException с артикулом и колонкой.
     *
     * @param  array<string, mixed>  $details
     */
    private function validateDetails(
        NomenclatureDetailTemplateEnum $template,
        array $details,
        string $partNumber,
    ): void {
        if ($template !== NomenclatureDetailTemplateEnum::WIPER) {
            return;
        }

        $category = $details['category'] ?? null;
        if ($category !== null && trim((string) $category) !== '') {
            return;
        }

        throw ImportRowValidationException::withMessage(
            "У щетки {$partNumber} не заполнена категория. Проверьте колонку «Категория».",
        );
    }

    /**
     * Возвращает копию data с id найденной записи.
     * Шаги:
     * 1) Перенести все поля NomenclatureData без повторного parsing.
     * 2) Заменить только id найденной записи.
     * 3) Сохранить type snapshot, если он уже был в исходном Data.
     */
    private function withId(NomenclatureData $data, ?int $id): NomenclatureData
    {
        return new NomenclatureData(
            typeId: $data->typeId,
            brandId: $data->brandId,
            name: $data->name,
            country: $data->country,
            partNumber: $data->partNumber,
            color: $data->color,
            weight: $data->weight,
            material: $data->material,
            vehicleType: $data->vehicleType,
            quantityPak: $data->quantityPak,
            quantityInPak: $data->quantityInPak,
            details: $data->details,
            id: $id,
            type: $data->type,
        );
    }

    /**
     * Переводит `;`-джойн русских лейблов в массив внутренних ключей.
     * Шаги:
     * 1) Для пустой ячейки вернуть пустой список.
     * 2) Разбить ячейку по ';'.
     * 3) Нормализовать каждый label в upper-case trimmed string.
     * 4) Добавить только labels, присутствующие в переданном словаре.
     *
     * @param  array<string, string>  $keysByLabel
     * @return array<int, string>
     */
    private function labelsToKeys(string $value, array $keysByLabel): array
    {
        if (trim($value) === '') {
            return [];
        }

        $result = [];
        foreach (explode(';', $value) as $label) {
            $label = mb_strtoupper(trim($label));
            if ($label === '' || ! isset($keysByLabel[$label])) {
                continue;
            }
            $result[] = $keysByLabel[$label];
        }

        return $result;
    }
}
