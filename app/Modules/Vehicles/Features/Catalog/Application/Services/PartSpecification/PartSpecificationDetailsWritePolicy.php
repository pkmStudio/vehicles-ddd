<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Services\PartSpecification;

use App\Modules\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\PartSpecificationDetailsWritePolicyInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationDetailsWriteResultDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Psr\Log\LoggerInterface;

/**
 * Применяет доменные правила записи details для catalog part specifications.
 */
final readonly class PartSpecificationDetailsWritePolicy implements PartSpecificationDetailsWritePolicyInterface
{
    /**
     * Инициализирует зависимости policy через контейнер.
     */
    public function __construct(
        private WiperSpecificationServiceInterface $wipers,
        private LoggerInterface $logger,
    ) {}

    /**
     * Проверяет и нормализует details перед записью.
     */
    public function apply(
        array $details,
        DetailTemplateEnum $template,
        PartableTypeEnum $ownerType,
        ?int $partSpecificationId,
        string $operationId,
    ): PartSpecificationDetailsWriteResultDTO {
        if ($template !== DetailTemplateEnum::WIPER || $ownerType !== PartableTypeEnum::VEHICLE) {
            return new PartSpecificationDetailsWriteResultDTO(
                valid: true,
                details: $details,
            );
        }

        $details = $this->pruneEmptyValues($this->withoutUiOnlyFields($details));
        if ($details === []) {
            return $this->reject(
                operationId: $operationId,
                partSpecificationId: $partSpecificationId,
                template: $template,
                ownerType: $ownerType,
                field: 'details',
                rule: 'required',
                message: 'Wiper details must contain one non-empty side.',
            );
        }

        $side = $this->wipers->detectSide($details);
        if ($side === null) {
            return $this->reject(
                operationId: $operationId,
                partSpecificationId: $partSpecificationId,
                template: $template,
                ownerType: $ownerType,
                field: 'details',
                rule: 'single_side',
                message: 'Wiper details must contain exactly one side: front or back.',
            );
        }

        $normalizedDetails = $this->wipers->sanitizeDetailsForSide(
            details: $details,
            side: $side,
        );
        if ($this->wipers->getVehicleAdapterCount($normalizedDetails, $side) > 1) {
            return $this->reject(
                operationId: $operationId,
                partSpecificationId: $partSpecificationId,
                template: $template,
                ownerType: $ownerType,
                field: 'details.'.$side,
                rule: 'single_adapter',
                message: 'Wiper details may contain only one adapter value per catalog mutation.',
            );
        }

        $variants = $this->wipers->splitDetails($normalizedDetails);
        if (count($variants) !== 1) {
            return $this->reject(
                operationId: $operationId,
                partSpecificationId: $partSpecificationId,
                template: $template,
                ownerType: $ownerType,
                field: 'details.'.$side,
                rule: 'required',
                message: 'Wiper details side must not be empty.',
            );
        }

        return new PartSpecificationDetailsWriteResultDTO(
            valid: true,
            details: $variants[0]['details'],
        );
    }

    /**
     * Удаляет UI-only поля, которые не являются частью сохраняемого details contract.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private function withoutUiOnlyFields(array $details): array
    {
        unset($details['position']);

        return $details;
    }

    /**
     * Рекурсивно удаляет null, пустые строки и пустые массивы.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private function pruneEmptyValues(array $details): array
    {
        $result = [];

        foreach ($details as $key => $value) {
            if (is_array($value)) {
                $value = $this->pruneEmptyValues($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Возвращает rejected result и пишет warn-событие о нарушении details rules.
     */
    private function reject(
        string $operationId,
        ?int $partSpecificationId,
        DetailTemplateEnum $template,
        PartableTypeEnum $ownerType,
        string $field,
        string $rule,
        string $message,
    ): PartSpecificationDetailsWriteResultDTO {
        $error = [
            'field' => $field,
            'rule' => $rule,
            'message' => $message,
        ];

        $this->logger->warning('Vehicles catalog mutation rejected invalid part specification details', [
            'operation_id' => $operationId,
            'part_specification_id' => $partSpecificationId,
            'template' => $template->value,
            'owner_type' => $ownerType->value,
            'field' => $field,
            'rule' => $rule,
        ]);

        return new PartSpecificationDetailsWriteResultDTO(
            valid: false,
            details: [],
            errors: [$error],
        );
    }
}
