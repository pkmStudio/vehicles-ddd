<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Application\Services\Nomenclature;

use App\Templates\Domain\Contracts\Factories\NomenclatureDetailsDataFactoryInterface;
use App\Warehouse\Import\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Warehouse\Import\Domain\Contracts\Services\Nomenclature\UpsertNomenclatureFromRowServiceInterface;
use App\Warehouse\Import\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Import\Domain\ModelData\BrandData;
use App\Warehouse\Import\Domain\ModelData\NomenclatureData;
use App\Warehouse\Import\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Резолвит type_id/brand_id и detail-шаблон по предзагруженным справочникам, собирает `details`
 * через shared kernel Templates и пишет Warehouse-номенклатуру через Command.
 */
final readonly class UpsertNomenclatureFromRowService implements UpsertNomenclatureFromRowServiceInterface
{
    /**
     * Русские Excel-лейблы материала → внутренний ключ. Обратная сторона констант
     * `Warehouse\Export\Application\Services\Rows\NomenclatureExportRow::MATERIAL_LABELS` —
     * значения должны совпадать 1:1, чтобы Import понимал то, что выгружает Export.
     */
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

    /**
     * Русские Excel-лейблы вида техники → внутренний ключ. Обратная сторона
     * `NomenclatureExportRow::VEHICLE_TYPE_LABELS`.
     */
    private const array VEHICLE_TYPE_KEYS_BY_LABEL = [
        'ЛЕГКОВЫЕ АВТОМОБИЛИ' => 'CAR',
        'КОММЕРЧЕСКИЙ ТРАНСПОРТ' => 'TRUCK',
        'ВНЕДОРОЖНИКИ' => 'SUV',
        'ГРУЗОВЫЕ АВТОМОБИЛИ И АВТОБУСЫ' => 'BUS',
    ];

    /**
     * Позиция первой detail-колонки в строке — совпадает с базовыми заголовками Export
     * (12 базовых колонок, индексы 0-11).
     */
    private const int DETAILS_START_INDEX = 12;

    /**
     * Получает команду записи, resolver шаблона и фабрику сборки details.
     */
    public function __construct(
        private NomenclatureCommandInterface $command,
        private TypeTemplateResolverInterface $templateResolver,
        private NomenclatureDetailsDataFactoryInterface $detailsFactory,
    ) {}

    /**
     * Этот метод резолвит type/brand/шаблон по строке, собирает details и пишет запись.
     *
     * Шаги:
     * 1) Проиндексировать справочники типов/брендов по верхнему регистру имени.
     * 2) Резолвить type_id и brand_id, бросить исключение, если не найдены.
     * 3) Резолвить detail-шаблон типа и собрать details через Templates.
     * 4) Собрать NomenclatureData и записать через Command (update по id либо upsert по артикулу).
     *
     * @param  array<int, mixed>  $row
     * @param  Collection<int, TypeData>  $types
     * @param  Collection<int, BrandData>  $brands
     */
    public function upsertFromRow(array $row, Collection $types, Collection $brands): NomenclatureData
    {
        $typesByName = $types->keyBy(fn (TypeData $type): string => mb_strtoupper(trim($type->name)));
        $brandsByName = $brands->keyBy(fn (BrandData $brand): string => mb_strtoupper(trim($brand->name)));

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

        $detailsIndex = self::DETAILS_START_INDEX;
        $details = $this->detailsFactory->buildFromRow($template, $row, $detailsIndex)->toArray();
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

        return $id !== null
            ? $this->command->updateById($data)
            : $this->command->upsertByPartNumber($data);
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
     * Этот метод переводит `;`-джойн русских лейблов в массив внутренних ключей.
     * Шаги:
     * 1) Пустая ячейка — вернуть пустой массив.
     * 2) Разбить по `;`, обрезать пробелы, привести к верхнему регистру.
     * 3) Лейблы без совпадения в словаре молча пропустить (то же поведение, что было у
     *    `BuildDetails::getVarKeys()` в dan-center — не бросать исключение на мультиселекте).
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
