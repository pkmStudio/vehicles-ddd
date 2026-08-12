<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\Vehicle\VehicleMutationWritePolicyInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationWriteContextDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Применяет provider-aware правила записи автомобиля для catalog mutations.
 */
final readonly class VehicleMutationWritePolicy implements VehicleMutationWritePolicyInterface
{
    /**
     * Принудительно создает catalog-owned автомобиль как OD запись.
     *
     * Шаги:
     * 1) Взять все нормализованные поля из входящего VehicleData.
     * 2) Принудительно установить provider=OD для записи, созданной catalog mutation.
     * 3) Вернуть новый immutable snapshot без изменения исходного объекта.
     */
    public function applyForCreate(
        VehicleData $incoming,
        VehicleMutationWriteContextDTO $context,
    ): VehicleData {
        return new VehicleData(
            msId: $incoming->msId,
            mfaId: $incoming->mfaId,
            manufacturerId: $incoming->manufacturerId,
            name: $incoming->name,
            type: $incoming->type,
            steeringType: $incoming->steeringType,
            typeCarcase: $incoming->typeCarcase,
            provider: ProviderEnum::OD,
            generation: $incoming->generation,
            generationYearFrom: $incoming->generationYearFrom,
            generationYearTo: $incoming->generationYearTo,
            parentId: $incoming->parentId,
            excelTableId: $incoming->excelTableId,
            localizedName: $incoming->localizedName,
            generationShort: $incoming->generationShort,
            isAllow: $incoming->isAllow,
            id: $incoming->id,
        );
    }

    /**
     * Сохраняет locked поля у non-OD автомобилей и не дает менять provider/ms_id.
     *
     * Шаги:
     * 1) Всегда сохранить текущие provider, ms_id и внутренний id существующей записи.
     * 2) Для OD-записи принять catalog-managed поля из входящего payload.
     * 3) Для non-OD-записи принять только безопасные редактируемые поля формы.
     * 4) Вернуть merged VehicleData для command update.
     */
    public function applyForUpdate(
        VehicleData $incoming,
        VehicleData $existing,
        VehicleMutationWriteContextDTO $context,
    ): VehicleData {
        $provider = $existing->provider;

        if ($this->allowsCatalogManagedFields($existing)) {
            return new VehicleData(
                msId: $existing->msId,
                mfaId: $incoming->mfaId,
                manufacturerId: $incoming->manufacturerId,
                name: $incoming->name,
                type: $incoming->type,
                steeringType: $incoming->steeringType,
                typeCarcase: $incoming->typeCarcase,
                provider: $provider,
                generation: $incoming->generation,
                generationYearFrom: $incoming->generationYearFrom,
                generationYearTo: $incoming->generationYearTo,
                parentId: $incoming->parentId,
                excelTableId: $incoming->excelTableId,
                localizedName: $incoming->localizedName,
                generationShort: $incoming->generationShort,
                isAllow: $incoming->isAllow,
                id: $existing->id,
            );
        }

        return new VehicleData(
            msId: $existing->msId,
            mfaId: $existing->mfaId,
            manufacturerId: $existing->manufacturerId,
            name: $incoming->name,
            type: $incoming->type,
            steeringType: $incoming->steeringType,
            typeCarcase: $existing->typeCarcase,
            provider: $provider,
            generation: $existing->generation,
            generationYearFrom: $incoming->generationYearFrom,
            generationYearTo: $incoming->generationYearTo,
            parentId: $existing->parentId,
            excelTableId: $incoming->excelTableId,
            localizedName: $incoming->localizedName,
            generationShort: $incoming->generationShort,
            isAllow: $incoming->isAllow,
            id: $existing->id,
        );
    }

    /**
     * OD-owned записи доступны для catalog-managed полей, остальные provider'ы защищены.
     *
     * Шаги:
     * 1) Сравнить provider существующего автомобиля с OD.
     * 2) Вернуть true только для записей, которыми владеет catalog mutation workflow.
     */
    public function allowsCatalogManagedFields(VehicleData $existing): bool
    {
        return $existing->provider === ProviderEnum::OD;
    }
}
