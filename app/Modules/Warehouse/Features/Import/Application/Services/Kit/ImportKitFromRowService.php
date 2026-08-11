<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Services\Kit;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Kit\ImportKitFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;

/**
 * Резолвит состав Warehouse-набора, считает свойства через KitProperties и пишет набор.
 */
final readonly class ImportKitFromRowService implements ImportKitFromRowServiceInterface
{
    private const int GUARANTEE_MONTHS = 12;

    /**
     * Получает чтение номенклатуры/наборов, расчёт свойств набора и команду записи Kit.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private KitRepositoryInterface $kits,
        private KitPropertiesClientInterface $kitProperties,
        private KitCommandInterface $command,
    ) {}

    /**
     * Валидирует строку и пишет набор.
     *
     * @param  array<int, mixed>  $row
     */
    public function importFromRow(array $row): KitData
    {
        $idCell = isset($row[0]) ? trim((string) $row[0]) : '';
        $id = $idCell !== '' ? (int) $idCell : null;

        $partNumbers = $this->parsePartNumbers((string) ($row[1] ?? ''));
        if ($partNumbers === []) {
            throw ImportRowValidationException::withMessage('Список артикулов набора пуст');
        }

        $ordered = $this->resolveOrderedNomenclatures($partNumbers);
        $properties = $this->kitProperties->build($ordered);

        if ($properties->packDimensionId === null) {
            throw ImportRowValidationException::withMessage(
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

        $toNomenclatureId = fn (NomenclatureData $nomenclature): int => $this->nomenclatureId($nomenclature);
        $nomenclatureIds = array_map(
            $toNomenclatureId,
            $ordered,
        );

        $existing = $this->findExistingKit($data);
        if ($existing === null) {
            return $this->command->create($data, $nomenclatureIds);
        }

        return $this->command->updateById($this->withId($data, $existing->id), $nomenclatureIds);
    }

    /**
     * Находит существующий набор по id или import hash.
     */
    private function findExistingKit(KitData $data): ?KitData
    {
        if ($data->id !== null) {
            $kit = $this->kits->findById($data->id);

            if ($kit !== null) {
                return $kit;
            }
        }

        if ($data->importHash === null) {
            return null;
        }

        return $this->kits->findByImportHash($data->importHash);
    }

    /**
     * Возвращает копию data с id найденной записи.
     */
    private function withId(KitData $data, ?int $id): KitData
    {
        return new KitData(
            complectation: $data->complectation,
            guarantee: $data->guarantee,
            quantityInPackage: $data->quantityInPackage,
            quantityPackage: $data->quantityPackage,
            complement: $data->complement,
            weight: $data->weight,
            packDimensionId: $data->packDimensionId,
            typeId: $data->typeId,
            importHash: $data->importHash,
            isSaleSeparately: $data->isSaleSeparately,
            isActive: $data->isActive,
            id: $id,
        );
    }

    /**
     * Разбирает `;`-список артикулов, обрезая пробелы и отбрасывая пустые куски.
     *
     * @return array<int, string>
     */
    private function parsePartNumbers(string $rawCell): array
    {
        return array_values(array_filter(array_map('trim', explode(';', $rawCell))));
    }

    /**
     * Резолвит номенклатуры по артикулам, сохраняя исходный порядок строки.
     *
     * @param  array<int, string>  $partNumbers
     * @return array<int, NomenclatureData>
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
            throw ImportRowValidationException::withMessage(
                'Номенклатура не найдена по артикулам: '.implode(', ', $missing),
            );
        }

        return $ordered;
    }

    /**
     * Возвращает id сохранённой номенклатуры для записи pivot-состава набора.
     */
    private function nomenclatureId(NomenclatureData $nomenclature): int
    {
        if ($nomenclature->id === null) {
            throw ImportRowValidationException::withMessage("Номенклатура {$nomenclature->partNumber} не имеет id");
        }

        return $nomenclature->id;
    }
}
