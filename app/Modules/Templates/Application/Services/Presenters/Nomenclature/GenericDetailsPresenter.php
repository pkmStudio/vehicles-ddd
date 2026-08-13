<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\GenericDetailsData;

/** Рендерит форму `generic` (тип V_BELT) — заглушка без полей, нет ни заголовков, ни ячеек. */
final readonly class GenericDetailsPresenter extends AbstractDetailsPresenter
{
    /**
     * Этот метод возвращает заголовки generic-шаблона.
     * Шаги:
     * 1) Возвращает пустой список, потому что у шаблона нет details-колонок.
     */
    public function headings(): array
    {
        return [];
    }

    /**
     * Этот метод указывает Data-класс generic presenter-а.
     * Шаги:
     * 1) Возвращает class-string `GenericDetailsData`.
     *
     * @return class-string<GenericDetailsData>
     */
    protected function dataClass(): string
    {
        return GenericDetailsData::class;
    }

    /**
     * Этот метод рендерит generic details.
     * Шаги:
     * 1) Проверяет тип `GenericDetailsData`.
     * 2) Возвращает пустой список ячеек, потому что шаблон не имеет полей.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, GenericDetailsData::class);

        return [];
    }
}
