<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Services;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogCascadeDeleteServiceInterface;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

final readonly class CatalogCascadeDeleteService implements CatalogCascadeDeleteServiceInterface
{
    /**
     * Получает порты чтения и записи, через которые каскадное удаление остается вне Eloquent.
     *
     * Шаги:
     * 1) Принять read-порты для поиска дочерних автомобилей, модификаций, связей двигателей и спецификаций.
     * 2) Принять command-порты для удаления найденных записей в правильном порядке.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
        private EngineRepositoryInterface $engines,
        private PartSpecificationRepositoryInterface $partSpecifications,
        private VehicleCommandInterface $vehicleCommand,
        private ModificationCommandInterface $modificationCommand,
        private EngineModificationCommandInterface $engineModificationCommand,
        private PartSpecificationCommandInterface $partSpecificationCommand,
    ) {}

    /**
     * Удаляет все автомобили производителя вместе с зависимыми модификациями и спецификациями.
     *
     * Шаги:
     * 1) Найти идентификаторы автомобилей, принадлежащих производителю.
     * 2) Передать найденные корневые автомобили в общий сценарий удаления поддерева.
     */
    public function deleteVehiclesByManufacturerId(int $manufacturerId): void
    {
        $this->deleteVehiclesByIds($this->vehicles->findIdsByManufacturerId($manufacturerId)->all());
    }

    /**
     * Удаляет автомобили и все данные, которые должны исчезнуть вместе с их поддеревом каталога.
     *
     * Шаги:
     * 1) Раскрыть переданные автомобили до полного поддерева потомков.
     * 2) Если удалять нечего, завершить сценарий без команд записи.
     * 3) Найти модификации и part specifications, привязанные к автомобилям поддерева.
     * 4) Сначала удалить зависимости модификаций от двигателей.
     * 5) Удалить модификации, спецификации автомобилей и сами автомобили.
     *
     * @param  array<int, int>  $vehicleIds
     */
    public function deleteVehiclesByIds(array $vehicleIds): void
    {
        $ids = $this->vehicleSubtreeIds($vehicleIds);

        if ($ids === []) {
            return;
        }

        $modificationIds = $this->modifications->findIdsByVehicleIds($ids)->all();
        $partSpecificationIds = $this->partSpecifications
            ->findIdsByPartable(PartableTypeEnum::VEHICLE, $ids)
            ->all();

        $this->deleteModificationDependencies($modificationIds);
        $this->modificationCommand->deleteByIds($modificationIds);
        $this->partSpecificationCommand->deleteByIds($partSpecificationIds);
        $this->vehicleCommand->deleteByIds($ids);
    }

    /**
     * Удаляет связи engine_modification для списка модификаций перед удалением самих модификаций.
     *
     * Шаги:
     * 1) Если список модификаций пустой, не выполнять команду записи.
     * 2) Найти идентификаторы pivot-записей engine_modification для этих модификаций.
     * 3) Удалить найденные связи через write-порт engine modification.
     *
     * @param  array<int, int>  $modificationIds
     */
    public function deleteModificationDependencies(array $modificationIds): void
    {
        if ($modificationIds === []) {
            return;
        }

        $engineModificationIds = $this->modifications
            ->findEngineModificationIdsByModificationIds($modificationIds)
            ->all();

        $this->engineModificationCommand->deleteByIds($engineModificationIds);
    }

    /**
     * Удаляет зависимости двигателя, которые блокируют безопасное удаление engine-записи.
     *
     * Шаги:
     * 1) Найти pivot-связи двигателя с модификациями.
     * 2) Найти part specifications, где двигатель является partable-владельцем.
     * 3) Удалить pivot-связи и спецификации двигателя через command-порты.
     */
    public function deleteEngineDependencies(int $engineId): void
    {
        $engineModificationIds = $this->engines->findEngineModificationIdsByEngineId($engineId)->all();
        $partSpecificationIds = $this->partSpecifications
            ->findIdsByPartable(PartableTypeEnum::ENGINE, [$engineId])
            ->all();

        $this->engineModificationCommand->deleteByIds($engineModificationIds);
        $this->partSpecificationCommand->deleteByIds($partSpecificationIds);
    }

    /**
     * Раскрывает набор корневых автомобилей до полного списка их потомков.
     *
     * Шаги:
     * 1) Нормализовать входные id к уникальному списку integer.
     * 2) Пока есть текущий фронтир, искать прямых детей найденных автомобилей.
     * 3) Добавлять только новых детей, чтобы не зациклиться на уже обработанных id.
     * 4) Вернуть полный уникальный список id поддерева.
     *
     * @param  array<int, int>  $rootIds
     * @return array<int, int>
     */
    private function vehicleSubtreeIds(array $rootIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $rootIds)));
        $frontier = $ids;

        while ($frontier !== []) {
            $childIds = $this->vehicles->findChildIdsByParentIds($frontier)->all();
            $childIds = array_values(array_diff($childIds, $ids));

            if ($childIds === []) {
                break;
            }

            $ids = array_values(array_unique([...$ids, ...$childIds]));
            $frontier = $childIds;
        }

        return $ids;
    }
}
