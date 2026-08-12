<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Contracts\Clients;

/**
 * Public API Templates для синхронных запросов других фич.
 */
interface TemplatesClientInterface
{
    /**
     * Этот метод должен вернуть заголовки vehicle details-шаблона по строковому ключу.
     * Шаги:
     * 1) Проверить template key.
     * 2) Вернуть headings выбранного vehicle presenter-а.
     *
     * @return array<int, string>
     */
    public function vehicleDetailHeadings(string $template): array;

    /**
     * Этот метод должен вернуть справочники select-полей vehicle details-шаблона.
     * Шаги:
     * 1) Проверить template key.
     * 2) Вернуть reference options выбранного vehicle presenter-а.
     *
     * @return array<string, list<string>>
     */
    public function vehicleReferenceOptions(string $template): array;

    /**
     * Этот метод должен отрендерить vehicle details в Excel-ячейки.
     * Шаги:
     * 1) Проверить template key.
     * 2) Передать details выбранному vehicle presenter-у.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function renderVehicleDetails(string $template, array $details): array;

    /**
     * Этот метод должен собрать vehicle details из Excel-строки.
     * Шаги:
     * 1) Проверить template key.
     * 2) Прочитать details-колонки, начиная со стартового индекса.
     * 3) Вернуть plain array typed Data-объекта.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildVehicleDetails(string $template, array $row, int $startIndex): array;

    /**
     * Этот метод должен разбить legacy vehicle wiper details на side-варианты.
     * Шаги:
     * 1) Найти непустые стороны.
     * 2) Развернуть стороны по адаптерам.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, array<string, mixed>>
     */
    public function splitVehicleWiperDetails(array $details): array;

    /**
     * Этот метод должен определить сторону vehicle wiper details.
     * Шаги:
     * 1) Проверить корневые ключи details.
     * 2) Вернуть `front`, `back` или null.
     *
     * @param  array<string, mixed>  $details
     */
    public function detectVehicleWiperSide(array $details): ?string;

    /**
     * Этот метод должен разбить сохранённую спецификацию дворников на side-варианты.
     * Шаги:
     * 1) Нормализовать side-details.
     * 2) Вернуть варианты с исходным id спецификации.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, array<string, mixed>>
     */
    public function splitVehicleWiperSpecification(array $details, ?int $partSpecificationId): array;

    /**
     * Этот метод должен вернуть данные одной стороны vehicle wiper details.
     * Шаги:
     * 1) Проверить сторону.
     * 2) Вернуть side payload или пустой массив.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function vehicleWiperSideData(array $details, string $side): array;

    /**
     * Этот метод должен склеить front/back дворников для Excel export.
     * Шаги:
     * 1) Добавить непустую переднюю сторону.
     * 2) Добавить непустую заднюю сторону.
     *
     * @param  array<string, mixed>  $front
     * @param  array<string, mixed>  $back
     * @return array<string, mixed>
     */
    public function mergeVehicleWiperForExport(array $front, array $back): array;

    /**
     * Этот метод должен вернуть заголовки nomenclature details-шаблона.
     * Шаги:
     * 1) Проверить template key.
     * 2) Вернуть headings выбранного nomenclature presenter-а.
     *
     * @return array<int, string>
     */
    public function nomenclatureDetailHeadings(string $template): array;

    /**
     * Этот метод должен вернуть справочники select-полей nomenclature details-шаблона.
     * Шаги:
     * 1) Проверить template key.
     * 2) Вернуть reference options выбранного nomenclature presenter-а.
     *
     * @return array<string, list<string>>
     */
    public function nomenclatureReferenceOptions(string $template): array;

    /**
     * Этот метод должен отрендерить nomenclature details в Excel-ячейки.
     * Шаги:
     * 1) Проверить template key.
     * 2) Передать details выбранному nomenclature presenter-у.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function renderNomenclatureDetails(string $template, array $details): array;

    /**
     * Этот метод должен собрать nomenclature details из Excel-строки.
     * Шаги:
     * 1) Проверить template key.
     * 2) Прочитать details-колонки, начиная со стартового индекса.
     * 3) Вернуть plain array typed Data-объекта.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildNomenclatureDetails(string $template, array $row, int $startIndex): array;
}
