<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\WiperExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

/**
 * Порт сборки данных Excel-экспорта автомобилей.
 */
interface VehicleExportServiceInterface
{
    /**
     * Возвращает строки основного листа автомобилей.
     *
     * Шаги:
     * 1) Получить vehicle snapshots для основного листа с учетом allow-фильтра.
     * 2) Вернуть collection в порядке строк Excel.
     *
     * @return Collection<int, VehicleData>
     */
    public function getMainRows(bool $isAllow): Collection;

    /**
     * Возвращает заголовки основного листа автомобилей.
     *
     * Шаги:
     * 1) Собрать базовые vehicle headings.
     * 2) Вернуть заголовки в порядке колонок основного листа.
     *
     * @return array<int, string>
     */
    public function getMainHeadings(): array;

    /**
     * Преобразует vehicle snapshot в строку основного листа.
     *
     * Шаги:
     * 1) Прочитать базовые поля vehicle snapshot.
     * 2) Вернуть значения в порядке заголовков основного листа.
     *
     * @return array<int, mixed>
     */
    public function mapMainRow(VehicleData $row): array;

    /**
     * Возвращает строки листа спецификаций дворников.
     *
     * Шаги:
     * 1) Получить vehicles с загруженными wiper specifications.
     * 2) Развернуть front/back specifications в export row DTO.
     *
     * @return Collection<int, WiperExportRowDTO>
     */
    public function getWiperRows(bool $isAllow): Collection;

    /**
     * Возвращает заголовки листа спецификаций дворников.
     *
     * Шаги:
     * 1) Собрать базовые vehicle headings.
     * 2) Добавить headings details-шаблона дворников.
     *
     * @return array<int, string>
     */
    public function getWiperHeadings(): array;

    /**
     * Преобразует wiper export DTO в строку листа дворников.
     *
     * Шаги:
     * 1) Собрать базовые vehicle cells.
     * 2) Добавить объединенные front/back wiper details cells.
     *
     * @return array<int, mixed>
     */
    public function mapWiperRow(WiperExportRowDTO $row): array;

    /**
     * Возвращает строки справочного листа автомобилей.
     *
     * Шаги:
     * 1) Собрать reference columns для vehicle export.
     * 2) Нормализовать колонки в строки справочного листа.
     *
     * @return Collection<int, array<int, mixed>>
     */
    public function getReferenceRows(): Collection;

    /**
     * Возвращает заголовки справочного листа автомобилей.
     *
     * Шаги:
     * 1) Собрать reference columns для vehicle export.
     * 2) Вернуть названия колонок справочного листа.
     *
     * @return array<int, string>
     */
    public function getReferenceHeadings(): array;
}
