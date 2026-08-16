<?php

declare(strict_types=1);

namespace App\Support\Http\Contracts;

interface HttpArraySerializableInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
