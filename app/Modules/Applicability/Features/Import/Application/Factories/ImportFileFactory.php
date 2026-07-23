<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Application\Factories;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\FileImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\KitApplicabilityImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;

final readonly class ImportFileFactory implements ImportFileFactoryInterface
{
    public function make(ImportTypeEnum $type): FileImportInterface
    {
        return match ($type) {
            ImportTypeEnum::KitApplicability => app(KitApplicabilityImportInterface::class),
        };
    }
}
