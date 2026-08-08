<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\VehicleImportWritePolicyInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleImportWriteContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\VehicleImportSourceEnum;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Psr\Log\LoggerInterface;

/**
 * Применяет provider-aware правила записи автомобиля для import workflows.
 */
final readonly class VehicleImportWritePolicy implements VehicleImportWritePolicyInterface
{
    /**
     * Инициализирует зависимости policy через контейнер.
     */
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Возвращает данные автомобиля, которые можно безопасно записать из import source.
     */
    public function apply(
        VehicleData $incoming,
        ?VehicleData $existing,
        VehicleImportWriteContextDTO $context,
    ): VehicleData {
        if ($existing === null) {
            return $this->forCreate($incoming, $context);
        }

        if ($context->source === VehicleImportSourceEnum::TecDocCommand) {
            return $this->forAuthoritativeTecDocUpdate(
                incoming: $incoming,
                existing: $existing,
                context: $context,
            );
        }

        if ($existing->provider !== $context->sourceProvider) {
            $this->logger->info('Vehicles import kept existing vehicle ownership on provider conflict', [
                'operation_id' => $context->operationId,
                'source' => $context->source->value,
                'source_provider' => $context->sourceProvider->value,
                'existing_provider' => $existing->provider->value,
                'ms_id' => $context->msId,
                'row_identifier' => $context->rowIdentifier,
            ]);
        }

        return $this->forUpdate(
            incoming: $incoming,
            existing: $existing,
            context: $context,
        );
    }

    /**
     * TecDoc command import является источником истины и полностью исправляет существующую запись.
     */
    private function forAuthoritativeTecDocUpdate(
        VehicleData $incoming,
        VehicleData $existing,
        VehicleImportWriteContextDTO $context,
    ): VehicleData {
        if ($existing->provider !== ProviderEnum::TD) {
            $this->logger->info('Vehicles TecDoc import corrected existing vehicle provider', [
                'operation_id' => $context->operationId,
                'source' => $context->source->value,
                'source_provider' => $context->sourceProvider->value,
                'existing_provider' => $existing->provider->value,
                'ms_id' => $context->msId,
                'row_identifier' => $context->rowIdentifier,
            ]);
        }

        return new VehicleData(
            msId: $incoming->msId,
            mfaId: $incoming->mfaId,
            manufacturerId: $incoming->manufacturerId,
            name: $incoming->name,
            type: $incoming->type,
            steeringType: $incoming->steeringType,
            typeCarcase: $incoming->typeCarcase,
            provider: ProviderEnum::TD,
            generation: $incoming->generation,
            generationYearFrom: $incoming->generationYearFrom,
            generationYearTo: $incoming->generationYearTo,
            parentId: $incoming->parentId,
            parentMsId: $incoming->parentMsId,
            excelTableId: $incoming->excelTableId,
            localizedName: $incoming->localizedName,
            generationShort: $incoming->generationShort,
            isAllow: $incoming->isAllow,
            id: $existing->id,
        );
    }

    /**
     * Создает новую запись с provider владельца import source.
     */
    private function forCreate(
        VehicleData $incoming,
        VehicleImportWriteContextDTO $context,
    ): VehicleData {
        if ($incoming->provider !== $context->sourceProvider) {
            $this->logMaskedField(
                context: $context,
                provider: $context->sourceProvider,
                field: 'provider',
                incoming: $incoming->provider->value,
                preserved: $context->sourceProvider->value,
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
            provider: $context->sourceProvider,
            generation: $incoming->generation,
            generationYearFrom: $incoming->generationYearFrom,
            generationYearTo: $incoming->generationYearTo,
            parentId: $incoming->parentId,
            parentMsId: $incoming->parentMsId,
            excelTableId: $incoming->excelTableId,
            localizedName: $incoming->localizedName,
            generationShort: $incoming->generationShort,
            isAllow: $incoming->isAllow,
            id: $incoming->id,
        );
    }

    /**
     * Обновляет только writable поля и сохраняет ownership существующей записи.
     */
    private function forUpdate(
        VehicleData $incoming,
        VehicleData $existing,
        VehicleImportWriteContextDTO $context,
    ): VehicleData {
        $preserveLockedFields = $existing->provider !== ProviderEnum::OD
            || $existing->provider !== $context->sourceProvider;

        $this->logIfChanged($context, $existing, 'ms_id', $incoming->msId, $existing->msId);
        $this->logIfChanged($context, $existing, 'provider', $incoming->provider->value, $existing->provider->value);

        if (! $preserveLockedFields) {
            return new VehicleData(
                msId: $existing->msId,
                mfaId: $incoming->mfaId,
                manufacturerId: $incoming->manufacturerId,
                name: $incoming->name,
                type: $incoming->type,
                steeringType: $incoming->steeringType,
                typeCarcase: $incoming->typeCarcase,
                provider: $existing->provider,
                generation: $incoming->generation,
                generationYearFrom: $incoming->generationYearFrom,
                generationYearTo: $incoming->generationYearTo,
                parentId: $incoming->parentId,
                parentMsId: $incoming->parentMsId,
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
            provider: $existing->provider,
            generation: $existing->generation,
            generationYearFrom: $incoming->generationYearFrom,
            generationYearTo: $incoming->generationYearTo,
            parentId: $existing->parentId,
            parentMsId: $existing->parentMsId,
            excelTableId: $incoming->excelTableId,
            localizedName: $incoming->localizedName,
            generationShort: $incoming->generationShort,
            isAllow: $incoming->isAllow,
            id: $existing->id,
        );
    }

    /**
     * Логирует сохранение locked поля, если входящее значение отличается от сохраняемого.
     */
    private function logIfChanged(
        VehicleImportWriteContextDTO $context,
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
        VehicleImportWriteContextDTO $context,
        ProviderEnum $provider,
        string $field,
        mixed $incoming,
        mixed $preserved,
    ): void {
        $this->logger->debug('Vehicles import kept locked vehicle field', [
            'operation_id' => $context->operationId,
            'source' => $context->source->value,
            'source_provider' => $context->sourceProvider->value,
            'ms_id' => $context->msId,
            'row_identifier' => $context->rowIdentifier,
            'provider' => $provider->value,
            'field' => $field,
            'incoming' => $incoming,
            'preserved' => $preserved,
        ]);
    }
}
