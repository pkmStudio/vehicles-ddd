<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Factories;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;

interface ExportFileFactoryInterface
{
    public function make(ExportTypeEnum $type): FileExportInterface;
}
