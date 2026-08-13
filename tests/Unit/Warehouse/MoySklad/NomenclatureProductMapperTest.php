<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\MoySklad;

use App\Modules\Warehouse\Features\MoySklad\Application\Services\NomenclatureProductMapper;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductFolderMetaDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductPayloadDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет маппинг Warehouse-номенклатуры в payload товара МойСклад.
 */
final class NomenclatureProductMapperTest extends TestCase
{
    /**
     * Проверяет базовые поля payload и привязку productFolder.
     */
    public function test_maps_nomenclature_to_product_payload(): void
    {
        $nomenclature = new NomenclatureData(
            id: 15,
            typeId: 1,
            brandId: 2,
            name: 'Brake Pad',
            country: 'RU',
            partNumber: 'BP-15',
            color: 'Black',
            weight: 250,
            material: [],
            vehicleType: [],
            quantityPak: 1,
            quantityInPak: 1,
            details: [],
            brand: new BrandData(id: 2, name: 'Bosch'),
        );

        $folderMeta = [
            'meta' => [
                'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/productfolder/folder-1',
            ],
        ];

        $payload = (new NomenclatureProductMapper)->map(
            $nomenclature,
            MoySkladProductFolderMetaDTO::fromArray($folderMeta),
        );
        $payloadArray = $payload->toArray();

        $this->assertInstanceOf(MoySkladProductPayloadDTO::class, $payload);
        $this->assertSame('Brake Pad', $payload->name);
        $this->assertSame('BP-15', $payload->code);
        $this->assertSame('BP-15', $payload->article);
        $this->assertSame('nomenclature:15', $payload->externalCode);
        $this->assertSame(250.0, $payload->weight);
        $this->assertSame($folderMeta, $payloadArray['productFolder']);
        $this->assertStringContainsString('Бренд: Bosch', $payload->description);
    }
}
