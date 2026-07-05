<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Application\Services\Expanders;

use App\Vehicles\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Expanders\WiperRowExpanderInterface;
use App\Vehicles\Shared\Domain\Enums\Vehicle\WiperSideEnum;
use Illuminate\Support\Collection;

/**
 * Разворачивает ТС с раздельными по сторонам спецификациями дворников в строки экспорта.
 * Дворники хранятся по одной записи на сторону (front/back) — для старого формата выгрузки
 * нужно собрать обратно строки `{frontSpec, backSpec}`: legacy-записи «обе стороны» дают свою
 * строку, односторонние — декартово произведение front × back (с null на отсутствующей стороне).
 */
final readonly class WiperRowExpander implements WiperRowExpanderInterface
{
    public function __construct(
        private WiperSpecificationServiceInterface $wiper,
    ) {}

    /**
     * @param  Collection<int, object>  $vehicles  ТС с загруженной связью partSpecifications (только wiper)
     * @return Collection<int, object{vehicle: object, frontSpec: object|null, backSpec: object|null}>
     */
    public function expand(Collection $vehicles): Collection
    {
        $rows = collect();

        foreach ($vehicles as $vehicle) {
            $specs = $vehicle->partSpecifications;

            if ($specs->isEmpty()) {
                $rows->push($this->row($vehicle, null, null));

                continue;
            }

            $front = $this->singleSide($specs, WiperSideEnum::FRONT->value);
            $back = $this->singleSide($specs, WiperSideEnum::BACK->value);
            $both = $this->bothSides($specs);
            $added = 0;

            foreach ($both as $spec) {
                $rows->push($this->row($vehicle, $spec, $spec));
                $added++;
            }

            if ($front->isNotEmpty() || $back->isNotEmpty()) {
                $frontRows = $front->isNotEmpty() ? $front : collect([null]);
                $backRows = $back->isNotEmpty() ? $back : collect([null]);

                foreach ($frontRows as $frontSpec) {
                    foreach ($backRows as $backSpec) {
                        $rows->push($this->row($vehicle, $frontSpec, $backSpec));
                        $added++;
                    }
                }
            }

            if ($added === 0) {
                $rows->push($this->row($vehicle, null, null));
            }
        }

        return $rows;
    }

    private function row(object $vehicle, ?object $frontSpec, ?object $backSpec): object
    {
        return (object) ['vehicle' => $vehicle, 'frontSpec' => $frontSpec, 'backSpec' => $backSpec];
    }

    /**
     * Односторонние записи: есть данные нужной стороны и нет противоположной.
     *
     * @param  Collection<int, object>  $specifications
     * @return Collection<int, object>
     */
    private function singleSide(Collection $specifications, string $side): Collection
    {
        $other = $side === WiperSideEnum::FRONT->value
            ? WiperSideEnum::BACK->value
            : WiperSideEnum::FRONT->value;

        return $specifications->filter(function ($spec) use ($side, $other) {
            $details = (array) $spec->details;

            return $this->wiper->sideData($details, $side) !== []
                && $this->wiper->sideData($details, $other) === [];
        })->values();
    }

    /**
     * Legacy-записи с обеими сторонами в одной спецификации.
     *
     * @param  Collection<int, object>  $specifications
     * @return Collection<int, object>
     */
    private function bothSides(Collection $specifications): Collection
    {
        return $specifications->filter(function ($spec) {
            $details = (array) $spec->details;

            return $this->wiper->sideData($details, WiperSideEnum::FRONT->value) !== []
                && $this->wiper->sideData($details, WiperSideEnum::BACK->value) !== [];
        })->values();
    }
}
