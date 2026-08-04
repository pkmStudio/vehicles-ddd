<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\DTOs;

final readonly class MoySkladProductPayloadDTO
{
    public function __construct(
        public string $name,
        public string $code,
        public string $article,
        public string $externalCode,
        public string $description,
        public float $weight,
        public MoySkladProductFolderMetaDTO $productFolderMeta,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $productFolder = $data['productFolder'] ?? [];

        return new self(
            name: (string) ($data['name'] ?? ''),
            code: (string) ($data['code'] ?? ''),
            article: (string) ($data['article'] ?? ''),
            externalCode: (string) ($data['externalCode'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            weight: (float) ($data['weight'] ?? 0),
            productFolderMeta: is_array($productFolder)
                ? MoySkladProductFolderMetaDTO::fromArray($productFolder)
                : MoySkladProductFolderMetaDTO::empty(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
            'code' => $this->code,
            'article' => $this->article,
            'externalCode' => $this->externalCode,
            'description' => $this->description,
            'weight' => $this->weight,
        ];

        if (! $this->productFolderMeta->isEmpty()) {
            $payload['productFolder'] = $this->productFolderMeta->toArray();
        }

        return $payload;
    }
}
