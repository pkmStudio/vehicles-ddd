<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\CalculationCompletionNotificationDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\CalculationCompletionStatusEnum;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportCompletionStatusEnum;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ImportCompletionStatusEnum;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use PHPUnit\Framework\TestCase;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Calculation\DTO\CalculationCompleted as WireCalculationCompleted;
use PkmStudio\DanWireContracts\Vehicles\Shared\Results\DTO\FileExported as WireFileExported;
use PkmStudio\DanWireContracts\Vehicles\Shared\Results\DTO\ImportCompleted as WireImportCompleted;

final class ResultPayloadContractTest extends TestCase
{
    public function test_vehicles_import_result_payload_matches_wire_contract(): void
    {
        $payload = new ImportCompletionNotificationDTO(
            userId: 42,
            status: ImportCompletionStatusEnum::CompletedWithErrors,
            importType: ExternalImportTypeEnum::VehicleMultiSheet,
            operationId: 'vehicles-import-run',
            disk: 's3',
            errorsCount: 2,
            path: 'dan-vehicles/import/failures.xlsx',
        );

        $this->assertWirePayloadMatches($payload->toArray(), WireImportCompleted::fromArray(...));
    }

    public function test_vehicles_export_result_payload_matches_wire_contract(): void
    {
        $payload = new ExportCompletionNotificationDTO(
            userId: 42,
            status: ExportCompletionStatusEnum::Completed,
            exportType: ExportTypeEnum::Vehicle,
            operationId: 'vehicles-export-run',
            disk: 's3',
            path: 'exports/vehicles.xlsx',
        );

        $this->assertWirePayloadMatches($payload->toArray(), WireFileExported::fromArray(...));
    }

    public function test_warehouse_import_result_payload_matches_wire_contract(): void
    {
        $payload = new \App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO(
            status: \App\Modules\Warehouse\Features\Import\Domain\Enums\ImportCompletionStatusEnum::Failed,
            importType: ImportTypeEnum::Nomenclature,
            userId: 42,
            operationId: 'warehouse-import-run',
            failuresReportPath: 'dan-vehicles/import/warehouse-failures.xlsx',
            failuresReportDisk: 's3',
        );

        $this->assertWirePayloadMatches($payload->toArray(), WireImportCompleted::fromArray(...));
    }

    public function test_warehouse_export_result_payload_matches_wire_contract(): void
    {
        $payload = new \App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO(
            userId: 42,
            status: \App\Modules\Warehouse\Features\Export\Domain\Enums\ExportCompletionStatusEnum::Completed,
            exportType: \App\Modules\Warehouse\Features\Export\Domain\Enums\ExportTypeEnum::Kit,
            operationId: 'warehouse-export-run',
            disk: 's3',
            path: 'exports/warehouse-kits.xlsx',
            typeId: 7,
        );

        $this->assertWirePayloadMatches($payload->toArray(), WireFileExported::fromArray(...));
    }

    public function test_applicability_import_result_payload_matches_wire_contract(): void
    {
        $payload = new \App\Modules\Applicability\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO(
            status: \App\Modules\Applicability\Features\Import\Domain\Enums\ImportCompletionStatusEnum::Failed,
            importType: \App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum::KitApplicability,
            userId: 42,
            operationId: 'applicability-import-run',
            failuresReportPath: 'dan-vehicles/import/applicability-failures.xlsx',
            failuresReportDisk: 's3',
        );

        $this->assertWirePayloadMatches($payload->toArray(), WireImportCompleted::fromArray(...));
    }

    public function test_applicability_export_result_payload_matches_wire_contract(): void
    {
        $payload = new \App\Modules\Applicability\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO(
            userId: 42,
            status: \App\Modules\Applicability\Features\Export\Domain\Enums\ExportCompletionStatusEnum::Completed,
            exportType: \App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum::VehicleKitApplicability,
            operationId: 'applicability-export-run',
            disk: 's3',
            path: 'exports/applicability.xlsx',
        );

        $this->assertWirePayloadMatches($payload->toArray(), WireFileExported::fromArray(...));
    }

    public function test_applicability_calculation_result_payload_matches_wire_contract(): void
    {
        $payload = new CalculationCompletionNotificationDTO(
            status: CalculationCompletionStatusEnum::COMPLETED_WITH_FAILURES,
            operationId: 'applicability-calculation-run',
            processedKits: 12,
            calculatedKits: 9,
            skippedKits: 1,
            failedKits: 2,
            failuresReportPath: 'dan-vehicles/calculation/failures.xlsx',
            failuresReportDisk: 's3',
            userId: 42,
        );

        $this->assertWirePayloadMatches($payload->toArray(), WireCalculationCompleted::fromArray(...));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertWirePayloadMatches(array $payload, callable $fromArray): void
    {
        $expected = array_filter($payload, static fn (mixed $value): bool => $value !== null);
        $actual = $fromArray($payload)->toArray();

        foreach ($expected as $key => $value) {
            self::assertArrayHasKey($key, $actual);
            self::assertSame($value, $actual[$key]);
        }
    }
}
