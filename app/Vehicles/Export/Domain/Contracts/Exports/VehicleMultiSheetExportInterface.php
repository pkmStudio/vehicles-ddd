<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Exports;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface VehicleMultiSheetExportInterface
{
    /**
     * Сформировать файл выгрузки на скачивание. Транспорт (Excel) — в реализации.
     */
    public function download(string $fileName): BinaryFileResponse;
}
