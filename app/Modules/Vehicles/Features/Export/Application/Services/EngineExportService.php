<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders\EngineSparkPlugSpecificationRowExpanderInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows\EngineExportRowInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\Enums\EngineExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use Illuminate\Support\Collection;

/**
 * Готовит строки и заголовки Excel-экспорта двигателей.
 */
final readonly class EngineExportService implements EngineExportServiceInterface
{
    private array $fieldHeadings;

    /**
     * Инициализирует зависимости и заголовки шаблона свечей зажигания.
     *
     * Шаги:
     * 1) Сохранить read repository, row mapper, expander и Templates client.
     * 2) Получить заголовки details-шаблона свечей для последующей сборки листа.
     */
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineExportRowInterface $engineRow,
        private EngineSparkPlugSpecificationRowExpanderInterface $expander,
        private TemplatesClientInterface $templates,
    ) {
        $this->fieldHeadings = $this->templates->vehicleDetailHeadings(DetailTemplateEnum::SPARK_PLUGS);
    }

    /**
     * Возвращает строки основного листа двигателей.
     *
     * Шаги:
     * 1) Запросить у repository данные для основного листа.
     * 2) Вернуть коллекцию typed `EngineData`.
     */
    public function getMainRows(): Collection
    {
        return $this->engines->forSheet(EngineExportSheetEnum::Main);
    }

    /**
     * Возвращает заголовки основного листа двигателей.
     *
     * Шаги:
     * 1) Делегировать формирование базовых заголовков row mapper-у.
     * 2) Вернуть список Excel-колонок без details-шаблона.
     */
    public function getMainHeadings(): array
    {
        return $this->engineRow->getBaseHeadings();
    }

    /**
     * Преобразует Data-снимок двигателя в строку основного листа.
     *
     * Шаги:
     * 1) Передать typed `EngineData` в базовый row mapper.
     * 2) Вернуть плоский массив Excel-ячеек.
     */
    public function mapMainRow(EngineData $row): array
    {
        return $this->engineRow->getBaseData($row);
    }

    /**
     * Возвращает развернутые строки листа свечей зажигания.
     *
     * Шаги:
     * 1) Получить двигатели со спецификациями свечей из repository.
     * 2) Развернуть каждый двигатель в одну или несколько строк export DTO.
     */
    public function getSparkPlugRows(): Collection
    {
        return $this->expander->expand($this->engines->forSheet(EngineExportSheetEnum::SparkPlug));
    }

    /**
     * Возвращает заголовки листа свечей зажигания.
     *
     * Шаги:
     * 1) Получить базовые заголовки двигателя.
     * 2) Добавить заголовки details-шаблона свечей.
     */
    public function getSparkPlugHeadings(): array
    {
        return array_merge($this->engineRow->getBaseHeadings(), $this->fieldHeadings);
    }

    /**
     * Преобразует строку спецификации свечей в плоский набор Excel-ячеек.
     *
     * Шаги:
     * 1) Сформировать базовые ячейки двигателя.
     * 2) Если спецификация есть — отрендерить ее details через Templates.
     * 3) Если спецификации нет — заполнить details-колонки пустыми значениями.
     * 4) Склеить базовые ячейки и details-ячейки в одну export-строку.
     */
    public function mapSparkPlugRow(PartSpecificationExportRowDTO $row): array
    {
        $baseData = $this->engineRow->getBaseData($row->entity);

        if ($row->specification) {
            $detailsData = $this->templates->renderVehicleDetails(DetailTemplateEnum::SPARK_PLUGS, $row->specification->details);
        } else {
            $detailsData = array_fill(0, count($this->fieldHeadings), null);
        }

        return array_merge($baseData, $detailsData);
    }

    /**
     * Возвращает строки справочного листа двигателя.
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
     * Возвращает заголовки справочного листа двигателя.
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
     * Возвращает справочные колонки для export-файла двигателей.
     *
     * Шаги:
     * 1) Добавить локальные engine enum-справочники.
     * 2) Добавить reference options details-шаблона свечей.
     *
     * @return array<string, list<string>>
     */
    private function referenceColumns(): array
    {
        return array_merge(
            [
                'Тип топлива' => $this->enumValues(EngineFuelTypeEnum::class),
            ],
            $this->templates->vehicleReferenceOptions(DetailTemplateEnum::SPARK_PLUGS),
        );
    }

    /**
     * Возвращает Excel-значения backed enum-справочника.
     *
     * Шаги:
     * 1) Взять все enum cases переданного backed enum.
     * 2) Преобразовать каждое value в строку для справочного листа.
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
