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
     * Получает сервис нормализации дворников и logger для отказов по details contract.
     *
     * Шаги:
     * 1) Принять shared-kernel сервис, который определяет сторону и split-ит wiper details.
     * 2) Принять logger для warning о payload, отклоненном до записи.
     */
    public function __construct(
        private WiperSpecificationServiceInterface $wipers,
        private LoggerInterface $logger,
    ) {}

    /**
     * Проверяет и нормализует details перед записью.
     *
     * Шаги:
     * 1) Пропустить без изменений все не-wiper шаблоны и specs, владельцем которых не является vehicle.
     * 2) Удалить UI-only поля и пустые значения из details.
     * 3) Отклонить пустой payload или payload с несколькими сторонами дворников.
     * 4) Нормализовать details под найденную сторону и запретить несколько adapter values в одной мутации.
     * 5) Split-ить details через shared-kernel сервис и принять только единственный непустой вариант.
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
     * Шаги:
     * 1) Убрать поле position, используемое формой, но не jsonb-contract спецификации.
     * 2) Вернуть details без изменения остальных ключей.
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
     * Шаги:
     * 1) Пройти по каждому ключу details.
     * 2) Для вложенных массивов применить такую же очистку рекурсивно.
     * 3) Пропустить null, пустые строки и массивы, которые стали пустыми после очистки.
     * 4) Вернуть компактный массив только со значимыми значениями.
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
     *
     * Шаги:
     * 1) Собрать error entry с field/rule/message для result DTO.
     * 2) Записать warning с operation id, spec id, template, owner type и нарушенным правилом.
     * 3) Вернуть invalid result без details и с единственной ошибкой.
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
