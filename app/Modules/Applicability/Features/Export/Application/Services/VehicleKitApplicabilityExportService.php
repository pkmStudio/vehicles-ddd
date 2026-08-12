<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Application\Services;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories\KitApplicabilityExportRepositoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityReferenceServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\VehicleKitApplicabilityRowDTO;
use Illuminate\Support\Collection;

final readonly class VehicleKitApplicabilityExportService implements VehicleKitApplicabilityExportServiceInterface
{
    /**
     * Подключает источники строк применяемости и справочников для XLSX export.
     *
     * Шаги:
     * 1. Сохраняет read repository строк применяемости комплектов к автомобилям.
     * 2. Сохраняет provider справочных строк, которые уходят на отдельный лист.
     */
    public function __construct(
        private KitApplicabilityExportRepositoryInterface $repository,
        private VehicleKitApplicabilityReferenceServiceInterface $references,
    ) {}

    /**
     * Возвращает доменные строки основного листа применяемости.
     *
     * Шаги:
     * 1. Делегирует чтение repository, чтобы service не зависел от SQL/Eloquent.
     * 2. Возвращает коллекцию `VehicleKitApplicabilityRowDTO` для Excel adapter-а.
     */
    public function getRows(): Collection
    {
        return $this->repository->vehicleRows();
    }

    /**
     * Преобразует DTO строки применяемости в порядок колонок основного Excel-листа.
     *
     * Шаги:
     * 1. Принимает строку от Laravel Excel и трактует ее как `VehicleKitApplicabilityRowDTO`.
     * 2. Возвращает значения комплекта, модели, годов и кузова в порядке headings.
     */
    public function mapRow(mixed $row): array
    {
        /** @var VehicleKitApplicabilityRowDTO $row */
        return [
            $row->kitId,
            $row->partNumbers,
            $row->excelTableId,
            $row->vehicleMsId,
            $row->vehicleName,
            $row->generation,
            $row->yearFrom,
            $row->yearTo,
            $row->typeCarcase,
        ];
    }

    /**
     * Возвращает заголовки основного листа применяемости комплектов.
     *
     * Шаги:
     * 1. Фиксирует человекочитаемые названия колонок для kit, vehicle и carcase data.
     * 2. Сохраняет порядок, которому соответствует `mapRow()`.
     */
    public function getHeadings(): array
    {
        return [
            'ID комплекта',
            'Состав комплекта',
            'ID гугл таблицы',
            'ID модели',
            'Модель',
            'Поколение',
            'Год от',
            'Год до',
            'Тип кузова',
        ];
    }

    /**
     * Возвращает строки справочного листа с допустимыми значениями кузова.
     *
     * Шаги:
     * 1. Делегирует сбор справочника reference provider-у.
     * 2. Возвращает строки в формате, готовом для `ReferenceSheetExport`.
     */
    public function getReferenceRows(): Collection
    {
        return $this->references->carcaseTypeRows();
    }

    /**
     * Возвращает заголовки справочного листа для типов кузова.
     *
     * Шаги:
     * 1. Фиксирует единственную колонку справочника.
     * 2. Держит heading рядом с данными export-сервиса, чтобы sheet adapter оставался тонким.
     */
    public function getReferenceHeadings(): array
    {
        return ['Тип кузова'];
    }
}
