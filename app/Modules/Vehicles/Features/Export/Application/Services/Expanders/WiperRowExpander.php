<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services\Expanders;

use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders\WiperRowExpanderInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\WiperExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

/**
 * Разворачивает ТС с раздельными по сторонам спецификациями дворников в строки экспорта.
 * Дворники хранятся по одной записи на сторону (front/back) — для старого формата выгрузки
 * нужно собрать обратно строки `{frontSpec, backSpec}`: legacy-записи «обе стороны» дают свою
 * строку, односторонние — декартово произведение front × back (с null на отсутствующей стороне).
 */
final readonly class WiperRowExpander implements WiperRowExpanderInterface
{
    /**
     * Инициализирует client шаблонов для чтения сторон дворников.
     *
     * Шаги:
     * 1) Сохранить Templates client для извлечения данных front/back сторон.
     */
    public function __construct(
        private TemplatesClientInterface $templates,
    ) {}

    /**
     * Разворачивает автомобили со спецификациями дворников в export DTO.
     *
     * Шаги:
     * 1) Для каждого автомобиля проверить наличие wiper-спецификаций.
     * 2) Добавить пустую строку, если спецификаций нет.
     * 3) Отдельно найти front-only, back-only и legacy both-sides спецификации.
     * 4) Legacy both-sides спецификации вывести отдельными строками.
     * 5) Односторонние спецификации объединить декартовым произведением front x back.
     * 6) Если ни одна строка не добавлена — вернуть пустую строку автомобиля.
     *
     * @param  Collection<int, VehicleData>  $vehicles  ТС с загруженной связью partSpecifications (только wiper)
     * @return Collection<int, WiperExportRowDTO>
     */
    public function expand(Collection $vehicles): Collection
    {
        $rows = collect();

        foreach ($vehicles as $vehicle) {
            $specs = $vehicle->partSpecifications;
            $specsEmpty = $specs->isEmpty();

            if ($specsEmpty) {
                $rows->push($this->row(
                    vehicle: $vehicle,
                    frontSpec: null,
                    backSpec: null,
                ));

                continue;
            }

            $front = $this->singleSide($specs, WiperSideEnum::FRONT->value);
            $back = $this->singleSide($specs, WiperSideEnum::BACK->value);
            $both = $this->bothSides($specs);
            $added = 0;

            foreach ($both as $spec) {
                $rows->push($this->row(
                    vehicle: $vehicle,
                    frontSpec: $spec,
                    backSpec: $spec,
                ));
                $added++;
            }

            $frontNotEmpty = $front->isNotEmpty();
            $backNotEmpty = $back->isNotEmpty();

            if ($frontNotEmpty || $backNotEmpty) {
                $frontRows = $frontNotEmpty ? $front : collect([null]);
                $backRows = $backNotEmpty ? $back : collect([null]);

                foreach ($frontRows as $frontSpec) {
                    foreach ($backRows as $backSpec) {
                        $rows->push($this->row(
                            vehicle: $vehicle,
                            frontSpec: $frontSpec,
                            backSpec: $backSpec,
                        ));
                        $added++;
                    }
                }
            }

            if ($added === 0) {
                $rows->push($this->row(
                    vehicle: $vehicle,
                    frontSpec: null,
                    backSpec: null,
                ));
            }
        }

        return $rows;
    }

    /**
     * Собирает DTO строки экспорта дворников.
     *
     * Шаги:
     * 1) Передать автомобиль и найденные side-спецификации в export DTO.
     * 2) Вернуть immutable строку для последующего mapping-а в Excel.
     */
    private function row(
        VehicleData $vehicle,
        ?PartSpecificationData $frontSpec,
        ?PartSpecificationData $backSpec,
    ): WiperExportRowDTO {
        return new WiperExportRowDTO(
            vehicle: $vehicle,
            frontSpec: $frontSpec,
            backSpec: $backSpec,
        );
    }

    /**
     * Односторонние записи: есть данные нужной стороны и нет противоположной.
     *
     * Шаги:
     * 1) Определить противоположную сторону для переданного side.
     * 2) Для каждой спецификации извлечь details нужной и противоположной стороны.
     * 3) Оставить только записи, где нужная сторона заполнена, а противоположная пустая.
     * 4) Сбросить ключи коллекции перед возвратом.
     *
     * @param  Collection<int, PartSpecificationData>  $specifications
     * @return Collection<int, PartSpecificationData>
     */
    private function singleSide(Collection $specifications, string $side): Collection
    {
        $other = $side === WiperSideEnum::FRONT->value
            ? WiperSideEnum::BACK->value
            : WiperSideEnum::FRONT->value;

        $hasSingleSide = function ($spec) use ($side, $other): bool {
            $details = (array) $spec->details;
            $sideData = $this->templates->vehicleWiperSideData($details, $side);
            $otherSideData = $this->templates->vehicleWiperSideData($details, $other);

            return $sideData !== [] && $otherSideData === [];
        };

        return $specifications->filter($hasSingleSide)->values();
    }

    /**
     * Legacy-записи с обеими сторонами в одной спецификации.
     *
     * Шаги:
     * 1) Для каждой спецификации извлечь front/back details.
     * 2) Оставить только записи, где обе стороны заполнены.
     * 3) Сбросить ключи коллекции перед возвратом.
     *
     * @param  Collection<int, PartSpecificationData>  $specifications
     * @return Collection<int, PartSpecificationData>
     */
    private function bothSides(Collection $specifications): Collection
    {
        $hasBothSides = function ($spec): bool {
            $details = (array) $spec->details;
            $frontData = $this->templates->vehicleWiperSideData($details, WiperSideEnum::FRONT->value);
            $backData = $this->templates->vehicleWiperSideData($details, WiperSideEnum::BACK->value);

            return $frontData !== [] && $backData !== [];
        };

        return $specifications->filter($hasBothSides)->values();
    }
}
