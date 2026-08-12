<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Services\Kit;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Kit\ImportKitFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\Kit\KitImportRowDTO;
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
     * Шаги:
     * 1) Сохранить repository номенклатуры для резолва состава по артикулам.
     * 2) Сохранить repository наборов для поиска существующей записи.
     * 3) Сохранить KitProperties client для расчета packaging/type/importHash.
     * 4) Сохранить command port записи Kit и pivot-состава.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private KitRepositoryInterface $kits,
        private KitPropertiesClientInterface $kitProperties,
        private KitCommandInterface $command,
    ) {}

    /**
     * Валидирует строку и пишет набор.
     * Шаги:
     * 1) Прочитать optional id и список артикулов из Excel-строки.
     * 2) Запретить пустой список артикулов.
     * 3) Найти номенклатуры по артикулам в исходном порядке строки.
     * 4) Рассчитать свойства набора через KitProperties boundary.
     * 5) Запретить смешанный комплект, для которого не рассчиталась упаковка.
     * 6) Собрать KitData с гарантийным сроком, флагами продажи/активности и importHash.
     * 7) Преобразовать состав в список ids для pivot.
     * 8) Найти существующий kit по id или importHash.
     * 9) Создать новый kit или обновить найденный с тем же составом.
     *
     */
    public function importFromRow(KitImportRowDTO $row): KitData
    {
        $ordered = $this->resolveOrderedNomenclatures($row->partNumbers);
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
            isSaleSeparately: $row->isSaleSeparately,
            isActive: $row->isActive,
            id: $row->id,
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
     * Шаги:
     * 1) Если в Excel передан id, сначала искать kit по id.
     * 2) Вернуть найденный kit, даже если importHash изменился.
     * 3) Если id не дал результата и importHash отсутствует, вернуть null.
     * 4) Иначе искать существующий kit по importHash состава.
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
     * Шаги:
     * 1) Перенести все поля KitData без пересчета свойств.
     * 2) Заменить только id, найденный repository.
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
     * Резолвит номенклатуры по артикулам, сохраняя исходный порядок строки.
     * Шаги:
     * 1) Загрузить найденные номенклатуры одним repository-запросом, индексированным по part number.
     * 2) Пройти исходный список артикулов в порядке Excel-строки.
     * 3) Собрать ordered список найденных NomenclatureData.
     * 4) Накопить отсутствующие артикулы.
     * 5) Если есть пропуски, выбросить ImportRowValidationException с перечислением артикулов.
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
     * Шаги:
     * 1) Проверить, что NomenclatureData содержит id.
     * 2) Если id отсутствует, выбросить row validation exception с артикулом.
     * 3) Вернуть id для записи kit_nomenclature pivot.
     */
    private function nomenclatureId(NomenclatureData $nomenclature): int
    {
        if ($nomenclature->id === null) {
            throw ImportRowValidationException::withMessage("Номенклатура {$nomenclature->partNumber} не имеет id");
        }

        return $nomenclature->id;
    }
}
