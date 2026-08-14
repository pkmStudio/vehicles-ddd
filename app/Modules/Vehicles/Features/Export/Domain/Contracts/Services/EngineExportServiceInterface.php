<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

/**
 * Порт сборки данных Excel-экспорта двигателей.
 */
interface EngineExportServiceInterface
{
    /**
     * Возвращает строки основного листа двигателей.
     *
     * Шаги:
     * 1) Получить engine snapshots для основного листа.
     * 2) Вернуть collection в порядке строк Excel.
     *
     * @return Collection<int, EngineData>
     */
    public function getMainRows(): Collection;

    /**
     * Возвращает заголовки основного листа двигателей.
     *
     * Шаги:
     * 1) Собрать базовые engine headings.
     * 2) Вернуть заголовки в порядке колонок основного листа.
     *
     * @return array<int, string>
     */
    public function getMainHeadings(): array;

    /**
     * Преобразует engine snapshot в строку основного листа.
     *
     * Шаги:
     * 1) Прочитать базовые поля engine snapshot.
     * 2) Вернуть значения в порядке заголовков основного листа.
     *
     * @return array<int, string|int|float|null>
     */
    public function mapMainRow(EngineData $row): array;

    /**
     * Возвращает строки листа спецификаций свечей зажигания.
     *
     * Шаги:
     * 1) Получить engines с загруженными spark plug specifications.
     * 2) Развернуть engines/specifications в export row DTO.
     *
     * @return Collection<int, PartSpecificationExportRowDTO>
     */
    public function getSparkPlugRows(): Collection;

    /**
     * Возвращает заголовки листа спецификаций свечей зажигания.
     *
     * Шаги:
     * 1) Собрать базовые engine headings.
     * 2) Добавить headings details-шаблона свечей.
     *
     * @return array<int, string>
     */
    public function getSparkPlugHeadings(): array;

    /**
     * Преобразует specification export DTO в строку листа свечей.
     *
     * Шаги:
     * 1) Собрать базовые engine cells.
     * 2) Добавить отрендеренные spark plug details cells.
     *
     * @return array<int, string|int|float|null>
     */
    public function mapSparkPlugRow(PartSpecificationExportRowDTO $row): array;

    /**
     * Возвращает строки справочного листа двигателя.
     *
     * Шаги:
     * 1) Собрать reference columns для engine export.
     * 2) Нормализовать колонки в строки справочного листа.
     *
     * @return Collection<int, array<int, string|null>>
     */
    public function getReferenceRows(): Collection;

    /**
     * Возвращает заголовки справочного листа двигателя.
     *
     * Шаги:
     * 1) Собрать reference columns для engine export.
     * 2) Вернуть названия колонок справочного листа.
     *
     * @return array<int, string>
     */
    public function getReferenceHeadings(): array;
}
