<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Services;

use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductPayloadDTO;

final readonly class ProductPayloadHasher
{
    /**
     * @param  array<string, mixed>|MoySkladProductPayloadDTO  $payload
     */
    public function hash(array|MoySkladProductPayloadDTO $payload): string
    {
        $payload = $payload instanceof MoySkladProductPayloadDTO ? $payload->toArray() : $payload;

        return sha1((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
