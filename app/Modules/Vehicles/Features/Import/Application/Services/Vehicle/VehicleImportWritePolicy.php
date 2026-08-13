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
     *
     * Шаги:
     * 1) Сохранить PSR logger для provider ownership conflicts и corrections.
     */
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Возвращает данные автомобиля, которые можно безопасно записать из import source.
     *
     * Шаги:
     * 1) Если существующей записи нет — применить правила create.
     * 2) Если source является авторитетным TecDoc command import — применить полный TecDoc update.
     * 3) При конфликте provider ownership залогировать warning с контекстом строки.
     * 4) Для остальных updates применить правила writable/locked fields.
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
            $this->logger->warning('Vehicles import kept existing vehicle ownership on provider conflict', [
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
     *
     * Шаги:
     * 1) Если текущий provider не TD — залогировать correction warning.
     * 2) Собрать `VehicleData` из incoming business fields.
     * 3) Принудительно сохранить provider TD и id существующей записи.
     */
    private function forAuthoritativeTecDocUpdate(
        VehicleData $incoming,
        VehicleData $existing,
        VehicleImportWriteContextDTO $context,
    ): VehicleData {
        if ($existing->provider !== ProviderEnum::TD) {
            $this->logger->warning('Vehicles TecDoc import corrected existing vehicle provider', [
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
     *
     * Шаги:
     * 1) Собрать `VehicleData` из incoming fields.
     * 2) Установить provider из import context.
     * 3) Сохранить incoming id для create-сценариев, где он уже известен.
     */
    private function forCreate(
        VehicleData $incoming,
        VehicleImportWriteContextDTO $context,
    ): VehicleData {
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
     *
     * Шаги:
     * 1) Определить, нужно ли сохранять locked fields существующей записи.
     * 2) Если locked fields можно менять — вернуть update data с incoming business fields.
     * 3) Если locked fields нужно сохранить — оставить owner/provider/id-sensitive fields из existing.
     * 4) Вернуть `VehicleData` с id существующей записи.
     */
    private function forUpdate(
        VehicleData $incoming,
        VehicleData $existing,
        VehicleImportWriteContextDTO $context,
    ): VehicleData {
        $preserveLockedFields = $existing->provider !== ProviderEnum::OD
            || $existing->provider !== $context->sourceProvider;

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
}
