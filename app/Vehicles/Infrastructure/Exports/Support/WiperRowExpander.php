<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Support;

use App\Vehicles\Application\Common\Services\WiperSpecificationService;
use Illuminate\Support\Collection;

/**
 * Разворачивает ТС с раздельными по сторонам спецификациями дворников в строки экспорта.
 * Дворники хранятся по одной записи на сторону (front/back) — для старого формата выгрузки
 * нужно собрать обратно строки `{frontSpec, backSpec}`: legacy-записи «обе стороны» дают свою
 * строку, односторонние — декартово произведение front × back (с null на отсутствующей стороне).
 */
final readonly class WiperRowExpander
{
    public function __construct(
        private WiperSpecificationService $wiper,
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

            $front = $this->singleSide($specs, WiperSpecificationService::SIDE_FRONT);
            $back = $this->singleSide($specs, WiperSpecificationService::SIDE_BACK);
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
        $other = $side === WiperSpecificationService::SIDE_FRONT
            ? WiperSpecificationService::SIDE_BACK
            : WiperSpecificationService::SIDE_FRONT;

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

            return $this->wiper->sideData($details, WiperSpecificationService::SIDE_FRONT) !== []
                && $this->wiper->sideData($details, WiperSpecificationService::SIDE_BACK) !== [];
        })->values();
    }
}
