<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services\Details;

interface ExportDetailsBuilderInterface
{
    public function extractHeadingsFromTemplate(array $template): array;

    public function getDetailsData(array $details, array $template): array;
}
