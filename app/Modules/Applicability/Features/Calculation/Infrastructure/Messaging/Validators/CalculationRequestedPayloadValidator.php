<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Messaging\Validators;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;

final readonly class CalculationRequestedPayloadValidator
{
    /**
     * Получает Laravel validator factory.
     *
     * Шаги:
     * 1. Сохраняет factory фреймворковой валидации.
     * 2. Оставляет сборку правил методу `make()`.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создает validator для request payload расчета применяемости.
     *
     * Шаги:
     * 1. Требует user id и operation id внешнего request-а.
     * 2. Разрешает optional kit id для точечного расчета.
     * 3. Разрешает optional positive chunk size для чтения Warehouse kits.
     *
     * @param  array<string, mixed>  $data
     */
    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'kit_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'chunk' => ['sometimes', 'integer', 'min:1'],
        ]);
    }
}
