<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use InvalidArgumentException;

/**
 * @template TData of AbstractDetailsData
 */
abstract readonly class AbstractDetailsPresenter
{
    /**
     * @return array<int, mixed>
     */
    abstract public function headings(): array;

    /**
     * @return class-string<TData>
     */
    abstract protected function dataClass(): string;

    /**
     * @param  TData  $data
     * @return array<int, mixed>
     */
    abstract public function cells(AbstractDetailsData $data): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function cellsFromDetails(array $details): array
    {
        return $this->cells($this->dataFrom($details));
    }

    /**
     * @param  array<string, mixed>  $details
     * @return TData
     */
    protected function dataFrom(array $details): AbstractDetailsData
    {
        $dataClass = $this->dataClass();

        return $dataClass::from($details);
    }

    /**
     * @template TExpected of AbstractDetailsData
     *
     * @param  class-string<TExpected>  $expectedClass
     * @return TExpected
     */
    protected function ensureData(AbstractDetailsData $data, string $expectedClass): AbstractDetailsData
    {
        if (! $data instanceof $expectedClass) {
            throw new InvalidArgumentException(sprintf(
                '%s expects %s, got %s.',
                static::class,
                $expectedClass,
                $data::class,
            ));
        }

        return $data;
    }
}
