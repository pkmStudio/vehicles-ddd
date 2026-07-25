<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Services\Nomenclature;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Nomenclature\ImportNomenclatureFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private NomenclatureCommandInterface $command,
        private TypeTemplateResolverInterface $templateResolver,
        private TemplatesClientInterface $templates,
    ) {}

    /**
     * Валидирует строку и пишет номенклатуру через явные create/update команды.
     *
     * @param  array<int, mixed>  $row
     * @param  Collection<int, TypeData>  $types
     * @param  Collection<int, BrandData>  $brands
     */
    public function importFromRow(array $row, Collection $types, Collection $brands): NomenclatureData
    {
        $typeNameKey = fn (TypeData $type): string => mb_strtoupper(trim($type->name));
        $brandNameKey = fn (BrandData $brand): string => mb_strtoupper(trim($brand->name));

        $typesByName = $types->keyBy($typeNameKey);
        $brandsByName = $brands->keyBy($brandNameKey);

        $id = isset($row[0]) && trim((string) $row[0]) !== '' ? (int) trim((string) $row[0]) : null;

        $typeName = mb_strtoupper(trim((string) ($row[1] ?? '')));
        $type = $typesByName->get($typeName);
        if ($type === null) {
            throw new InvalidArgumentException(
                "Тип товара «{$row[1]}» не найден. Проверьте колонку «Тип товара» (столбец B).",
            );
        }

        $brandName = mb_strtoupper(trim((string) ($row[2] ?? '')));
        $brand = $brandsByName->get($brandName);
        if ($brand === null) {
            throw new InvalidArgumentException(
                "Бренд «{$row[2]}» не найден. Проверьте колонку «Бренд» (столбец C).",
            );
        }

        $template = $this->templateResolver->resolve($type);
        if ($template === null) {
            throw new InvalidArgumentException(
                "Шаблон деталей не найден для типа «{$type->name}» (ID={$type->id}).",
            );
        }

        $details = $this->templates->buildNomenclatureDetails($template, $row, self::DETAILS_START_INDEX);
        $this->validateDetails($template, $details, (string) ($row[5] ?? ''));

        $weight = $this->parsePositiveInteger(
            value: $row[7] ?? null,
            columnName: 'Вес',
            columnLetter: 'H',
        );

        $data = new NomenclatureData(
            typeId: $type->id,
            brandId: $brand->id,
            name: (string) ($row[3] ?? ''),
            country: (string) ($row[4] ?? ''),
            partNumber: trim((string) ($row[5] ?? '')),
            color: (string) ($row[6] ?? ''),
            weight: $weight,
            material: $this->labelsToKeys((string) ($row[8] ?? ''), self::MATERIAL_KEYS_BY_LABEL),
            vehicleType: $this->labelsToKeys((string) ($row[9] ?? ''), self::VEHICLE_TYPE_KEYS_BY_LABEL),
            quantityPak: isset($row[10]) ? (int) $row[10] : 1,
            quantityInPak: isset($row[11]) ? (int) $row[11] : 1,
            details: $details,
            id: $id,
        );

        if ($id !== null) {
            return $this->command->updateById($data);
        }

        $existing = $this->nomenclatures->findByPartNumber($data->partNumber);
        if ($existing !== null) {
            return $this->command->updateById($this->withId($data, $existing->id));
        }

        return $this->command->create($data);
    }

    /**
     * Проверяет обязательные поля details, которые критичны для последующей сборки комплектов.
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

        throw new InvalidArgumentException(
            "У щетки {$partNumber} не заполнена категория. Проверьте колонку «Категория».",
        );
    }

    /**
     * Возвращает копию data с id найденной записи.
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
     * Возвращает положительное целое значение из Excel-ячейки.
     */
    private function parsePositiveInteger(mixed $value, string $columnName, string $columnLetter): int
    {
        $normalized = is_string($value) ? trim($value) : $value;
        $parsed = null;

        if (is_int($normalized)) {
            $parsed = $normalized;
        } elseif (is_float($normalized) && floor($normalized) === $normalized) {
            $parsed = (int) $normalized;
        } elseif (is_string($normalized) && preg_match('/^\d+$/', $normalized) === 1) {
            $parsed = (int) $normalized;
        }

        if ($parsed === null || $parsed <= 0) {
            throw new InvalidArgumentException(
                "{$columnName} должен быть положительным целым числом в граммах. Проверьте столбец {$columnLetter}.",
            );
        }

        return $parsed;
    }

    /**
     * Переводит `;`-джойн русских лейблов в массив внутренних ключей.
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
