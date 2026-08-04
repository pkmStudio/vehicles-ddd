<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\Vehicle\VehicleMutationWritePolicyInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationWriteContextDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Psr\Log\LoggerInterface;

/**
 * Применяет provider-aware правила записи автомобиля для catalog mutations.
 */
final readonly class VehicleMutationWritePolicy implements VehicleMutationWritePolicyInterface
{
    /**
     * Инициализирует зависимости policy через контейнер.
     */
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Принудительно создает catalog-owned автомобиль как OD запись.
     */
    public function applyForCreate(
        VehicleData $incoming,
        VehicleMutationWriteContextDTO $context,
    ): VehicleData {
        if ($incoming->provider !== ProviderEnum::OD) {
            $this->logMaskedField(
                context: $context,
                msId: $incoming->msId,
                provider: ProviderEnum::OD,
                field: 'provider',
                incoming: $incoming->provider->value,
                preserved: ProviderEnum::OD->value,
            );
        }

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
     */
    public function applyForUpdate(
        VehicleData $incoming,
        VehicleData $existing,
        VehicleMutationWriteContextDTO $context,
    ): VehicleData {
        $provider = $existing->provider;
        $this->logIfChanged($context, $existing, 'ms_id', $incoming->msId, $existing->msId);
        $this->logIfChanged($context, $existing, 'provider', $incoming->provider->value, $provider->value);

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

        $this->logIfChanged($context, $existing, 'mfa_id', $incoming->mfaId, $existing->mfaId);
        $this->logIfChanged($context, $existing, 'manufacturer_id', $incoming->manufacturerId, $existing->manufacturerId);
        $this->logIfChanged($context, $existing, 'parent_id', $incoming->parentId, $existing->parentId);
        $this->logIfChanged($context, $existing, 'generation', $incoming->generation, $existing->generation);
        $this->logIfChanged($context, $existing, 'type_carcase', $incoming->typeCarcase->value, $existing->typeCarcase->value);

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
     */
    public function allowsCatalogManagedFields(VehicleData $existing): bool
    {
        return $existing->provider === ProviderEnum::OD;
    }

    /**
     * Логирует сохранение locked поля, если входящее значение отличается от сохраняемого.
     */
    private function logIfChanged(
        VehicleMutationWriteContextDTO $context,
        VehicleData $existing,
        string $field,
        mixed $incoming,
        mixed $preserved,
    ): void {
        if ($incoming === $preserved) {
            return;
        }

        $this->logMaskedField(
            context: $context,
            msId: $existing->msId,
            provider: $existing->provider,
            field: $field,
            incoming: $incoming,
            preserved: $preserved,
        );
    }

    /**
     * Пишет debug-событие о field masking без Laravel facade.
     */
    private function logMaskedField(
        VehicleMutationWriteContextDTO $context,
        int $msId,
        ProviderEnum $provider,
        string $field,
        mixed $incoming,
        mixed $preserved,
    ): void {
        $this->logger->debug('Vehicles catalog mutation kept locked vehicle field', [
            'operation_id' => $context->operationId,
            'owner_external_id' => $context->ownerExternalId,
            'ms_id' => $msId,
            'provider' => $provider->value,
            'field' => $field,
            'incoming' => $incoming,
            'preserved' => $preserved,
        ]);
    }
}
