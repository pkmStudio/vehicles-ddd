<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;

final readonly class TemplatesClient implements TemplatesClientInterface
{
    /**
     * Получает public Templates client для сборки details.
     *
     * Шаги:
     * 1) Принять shared-kernel Templates client.
     * 2) Сохранить его внутри adapter'а Import-фичи.
     */
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    /**
     * Делегирует сборку details номенклатуры в Templates.
     *
     * Шаги:
     * 1) Принять локальный enum шаблона, строку Excel и стартовую колонку.
     * 2) Передать scalar template value в public Templates client.
     * 3) Вернуть собранный массив details для записи номенклатуры.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildNomenclatureDetails(
        NomenclatureDetailTemplateEnum $template,
        array $row,
        int $startIndex,
    ): array {
        return $this->templates->buildNomenclatureDetails($template->value, $row, $startIndex);
    }
}
