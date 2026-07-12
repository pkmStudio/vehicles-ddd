<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Application\Services\Kit;

use App\Warehouse\Import\Domain\Contracts\Commands\KitCommandInterface;
use App\Warehouse\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\Import\Domain\Contracts\Services\Kit\UpsertKitFromRowServiceInterface;
use App\Warehouse\Import\Domain\ModelData\Kit\NomenclatureForKitData;
use App\Warehouse\Import\Domain\ModelData\KitData;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData as KitPropertiesNomenclatureData;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData as KitPropertiesTypeData;
use InvalidArgumentException;

/**
 * Резолвит состав Warehouse-набора по артикулам, считает его свойства через `KitProperties`
 * (межфичевый вызов внутри домена — через порт, с явным переводом Data-объектов на границе, как и
 * `KitProperties → Packaging`) и пишет запись через Command.
 */
final readonly class UpsertKitFromRowService implements UpsertKitFromRowServiceInterface
{
    private const int GUARANTEE_MONTHS = 12;

    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private KitPropertiesServiceInterface $kitProperties,
        private KitCommandInterface $command,
    ) {}

    /**
     * Этот метод резолвит артикулы, считает свойства набора и записывает его.
     *
     * Шаги:
     * 1) Распарсить id и `;`-список артикулов.
     * 2) Резолвить номенклатуры по артикулам, сохраняя исходный порядок — бросить исключение,
     *    если хоть один артикул не найден.
     * 3) Перевести номенклатуры в KitProperties-шные типы и посчитать свойства набора.
     * 4) Если упаковка не определилась (смешанный комплект) — бросить исключение.
     * 5) Собрать KitData и записать через Command (состав — в порядке артикулов строки).
     *
     * @param  array<int, mixed>  $row
     */
    public function upsertFromRow(array $row): KitData
    {
        $idCell = isset($row[0]) ? trim((string) $row[0]) : '';
        $id = $idCell !== '' ? (int) $idCell : null;

        $partNumbers = $this->parsePartNumbers((string) ($row[1] ?? ''));
        if ($partNumbers === []) {
            throw new InvalidArgumentException('Список артикулов набора пуст');
        }

        $ordered = $this->resolveOrderedNomenclatures($partNumbers);

        $properties = $this->kitProperties->build(array_map(
            fn (NomenclatureForKitData $n): KitPropertiesNomenclatureData => $this->toKitPropertiesNomenclature($n),
            $ordered,
        ));

        if ($properties->packDimensionId === null) {
            throw new InvalidArgumentException(
                'Невозможно автоматически рассчитать упаковку для смешанного комплекта',
            );
        }

        $data = new KitData(
            complectation: $properties->complectation,
            guarantee: self::GUARANTEE_MONTHS,
            quantityInPackage: $properties->quantityInPackage,
            quantityPackage: $properties->quantityPackage,
            complement: count($ordered) > 1,
            weight: (int) round($properties->weight),
            packDimensionId: $properties->packDimensionId,
            typeId: $properties->typeId,
            importHash: $properties->importHash,
            isSaleSeparately: ($row[2] ?? null) === 'Да',
            isActive: ($row[3] ?? null) !== 'Нет',
            id: $id,
        );

        $nomenclatureIds = array_map(fn (NomenclatureForKitData $n): int => $n->id, $ordered);

        return $this->command->upsert($data, $id, $nomenclatureIds);
    }

    /**
     * Этот метод разбирает `;`-список артикулов, обрезая пробелы и отбрасывая пустые куски.
     *
     * @return array<int, string>
     */
    private function parsePartNumbers(string $rawCell): array
    {
        return array_values(array_filter(array_map('trim', explode(';', $rawCell))));
    }

    /**
     * Этот метод резолвит номенклатуры по артикулам, сохраняя исходный порядок строки — он
     * становится `sort` в pivot-таблице.
     *
     * @param  array<int, string>  $partNumbers
     * @return array<int, NomenclatureForKitData>
     */
    private function resolveOrderedNomenclatures(array $partNumbers): array
    {
        $found = $this->nomenclatures->findByPartNumbers($partNumbers);

        $missing = [];
        $ordered = [];

        foreach ($partNumbers as $partNumber) {
            $nomenclature = $found->get($partNumber);
            if ($nomenclature === null) {
                $missing[] = $partNumber;

                continue;
            }
            $ordered[] = $nomenclature;
        }

        if ($missing !== []) {
            throw new InvalidArgumentException(
                'Номенклатура не найдена по артикулам: '.implode(', ', $missing),
            );
        }

        return $ordered;
    }

    private function toKitPropertiesNomenclature(NomenclatureForKitData $nomenclature): KitPropertiesNomenclatureData
    {
        return new KitPropertiesNomenclatureData(
            typeId: $nomenclature->typeId,
            partNumber: $nomenclature->partNumber,
            quantityInPak: $nomenclature->quantityInPak,
            quantityPak: $nomenclature->quantityPak,
            weight: $nomenclature->weight,
            material: $nomenclature->material,
            details: $nomenclature->details,
            id: $nomenclature->id,
            type: $nomenclature->type === null ? null : new KitPropertiesTypeData(
                name: $nomenclature->type->name,
                char: $nomenclature->type->char,
                id: $nomenclature->type->id,
            ),
        );
    }
}
