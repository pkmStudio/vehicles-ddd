<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

/**
 * Преобразует DTO публичного каталога ТС в HTTP-массивы.
 */
final readonly class VehicleCatalogPresenter
{
    /**
     * Получает общий преобразователь DTO, поддерживающих toArray().
     *
     * Шаги:
     * - Сохранить преобразователь массивов для вложенных сущностей детального контекста.
     */
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Возвращает HTTP-форму контекста модификации.
     *
     * Шаги:
     * - Преобразовать производителя, ТС и модификацию в отдельные массивы.
     * - Сохранить имена ключей, ожидаемые публичным catalog endpoint.
     *
     * @return array<string, array<string, float|int|string|null>>
     */
    public function modificationContext(CatalogModificationContextDTO $context): array
    {
        return [
            'manufacturer' => $this->arrays->item($context->manufacturer),
            'vehicle' => $this->arrays->item($context->vehicle),
            'modification' => $this->arrays->item($context->modification),
        ];
    }
}
