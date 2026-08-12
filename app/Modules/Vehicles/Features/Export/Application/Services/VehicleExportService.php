<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders\WiperRowExpanderInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows\VehicleExportRowInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\VehicleExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\WiperExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\Enums\VehicleExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Support\Collection;

/**
 * Готовит строки и заголовки Excel-экспорта автомобилей.
 */
final readonly class VehicleExportService implements VehicleExportServiceInterface
{
    private array $fieldHeadings;

    /**
     * Инициализирует зависимости и заголовки шаблона дворников.
     *
     * Шаги:
     * 1) Сохранить read repository, row mapper, expander и Templates client.
     * 2) Получить заголовки details-шаблона дворников для последующей сборки листа.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleExportRowInterface $vehicleRow,
        private WiperRowExpanderInterface $expander,
        private TemplatesClientInterface $templates,
    ) {
        $this->fieldHeadings = $this->templates->vehicleDetailHeadings(DetailTemplateEnum::WIPER);
    }

    /**
     * Возвращает строки основного листа автомобилей.
     *
     * Шаги:
     * 1) Передать флаг фильтрации разрешенных автомобилей в repository.
     * 2) Вернуть коллекцию typed `VehicleData`.
     */
    public function getMainRows(bool $isAllow): Collection
    {
        return $this->vehicles->forSheet(VehicleExportSheetEnum::Main, $isAllow);
    }

    /**
     * Возвращает заголовки основного листа автомобилей.
     *
     * Шаги:
     * 1) Делегировать формирование базовых заголовков row mapper-у.
     * 2) Вернуть список Excel-колонок без details-шаблона.
     */
    public function getMainHeadings(): array
    {
        return $this->vehicleRow->getBaseHeadings();
    }

    /**
     * Преобразует Data-снимок автомобиля в строку основного листа.
     *
     * Шаги:
     * 1) Передать typed `VehicleData` в базовый row mapper.
     * 2) Вернуть плоский массив Excel-ячеек.
     */
    public function mapMainRow(VehicleData $row): array
    {
        return $this->vehicleRow->getBaseData($row);
    }

    /**
     * Возвращает развернутые строки листа дворников.
     *
     * Шаги:
     * 1) Получить автомобили со спецификациями дворников из repository.
     * 2) Развернуть каждую машину в одну или несколько строк export DTO.
     */
    public function getWiperRows(bool $isAllow): Collection
    {
        return $this->expander->expand($this->vehicles->forSheet(VehicleExportSheetEnum::Wiper, $isAllow));
    }

    /**
     * Возвращает заголовки листа дворников.
     *
     * Шаги:
     * 1) Собрать базовые заголовки автомобиля.
     * 2) Добавить колонки metadata part specification.
     * 3) Добавить заголовки details-шаблона дворников.
     */
    public function getWiperHeadings(): array
    {
        $specHeadings = [
            'Значение характеристики',
            'Название шаблона',
            'Приписка к поколению',
            'Приписка к описанию',
        ];

        return array_merge($this->vehicleRow->getBaseHeadings(), $specHeadings, $this->fieldHeadings);
    }

    /**
     * Преобразует пару спецификаций дворников в плоский набор Excel-ячеек.
     *
     * Шаги:
     * 1) Сформировать базовые ячейки автомобиля.
     * 2) Если обе спецификации пустые — вернуть пустые metadata/details колонки.
     * 3) Прочитать front/back детали через Templates client.
     * 4) Собрать metadata спецификации из доступной стороны.
     * 5) Смержить стороны дворников и отрендерить details-ячейки.
     */
    public function mapWiperRow(WiperExportRowDTO $row): array
    {
        $baseData = $this->vehicleRow->getBaseData($row->vehicle);
        $frontSpec = $row->frontSpec;
        $backSpec = $row->backSpec;

        if ($frontSpec === null && $backSpec === null) {
            return array_merge(
                $baseData,
                array_fill(0, 4, null),
                array_fill(0, count($this->fieldHeadings), null),
            );
        }

        $frontData = $frontSpec ? $this->templates->vehicleWiperSideData($frontSpec->details, WiperSideEnum::FRONT->value) : [];
        $backData = $backSpec ? $this->templates->vehicleWiperSideData($backSpec->details, WiperSideEnum::BACK->value) : [];

        $specData = [
            $frontSpec?->featureValue?->name ?? $backSpec?->featureValue?->name,
            DetailTemplateEnum::WIPER->value,
            $frontSpec?->name ?? $backSpec?->name,
            $frontSpec?->text ?? $backSpec?->text,
        ];

        $detailsData = $this->templates->renderVehicleDetails(
            DetailTemplateEnum::WIPER,
            $this->templates->mergeVehicleWiperForExport($frontData, $backData),
        );

        return array_merge($baseData, $specData, $detailsData);
    }

    /**
     * Возвращает строки справочного листа автомобилей.
     *
     * Шаги:
     * 1) Собрать справочные колонки из локальных enum и Templates reference options.
     * 2) Найти максимальную высоту среди колонок.
     * 3) Развернуть колонки в построчные Excel-значения.
     */
    public function getReferenceRows(): Collection
    {
        $columns = array_values($this->referenceColumns());
        $max = max(array_map('count', $columns));
        $rows = [];

        for ($i = 0; $i < $max; $i++) {
            $rows[] = array_map(
                static fn (array $values): mixed => $values[$i] ?? null,
                $columns,
            );
        }

        return collect($rows);
    }

    /**
     * Возвращает заголовки справочного листа автомобилей.
     *
     * Шаги:
     * 1) Собрать справочные колонки.
     * 2) Вернуть их имена как заголовки Excel-листа.
     */
    public function getReferenceHeadings(): array
    {
        return array_keys($this->referenceColumns());
    }

    /**
     * Возвращает справочные колонки для export-файла автомобилей.
     *
     * Шаги:
     * 1) Добавить локальные vehicle enum-справочники.
     * 2) Добавить reference options details-шаблона дворников.
     *
     * @return array<string, list<string>>
     */
    private function referenceColumns(): array
    {
        return array_merge(
            [
                'Тип кузова' => $this->enumValues(CarcaseTypeEnum::class),
                'Тип транспорта' => $this->enumValues(VehicleTypeEnum::class),
                'Провайдер' => $this->enumValues(ProviderEnum::class),
                'Рулевое управление' => $this->enumValues(SteeringTypeEnum::class),
            ],
            $this->templates->vehicleReferenceOptions(DetailTemplateEnum::WIPER),
        );
    }

    /**
     * Возвращает Excel-значения backed enum-справочника.
     *
     * @param  class-string<\BackedEnum>  $enumClass
     * @return list<string>
     */
    private function enumValues(string $enumClass): array
    {
        return array_map(
            static fn (\BackedEnum $case): string => (string) $case->value,
            $enumClass::cases(),
        );
    }
}
