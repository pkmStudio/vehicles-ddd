# Refactor Templates

План по модулю `app/Modules/Templates` после ревью архитектуры. Код модуля пока не меняем.

## Контекст

`Templates` задуман как shared-kernel модуль:

- владеет формой `details` для Vehicles/Warehouse;
- хранит `Data`-классы, enum-справочники, сборку details из Excel-строки и рендер details обратно в Excel-ячейки;
- не ходит в БД и не содержит Eloquent/Repository/Command;
- не должен зависеть от модулей-потребителей (`Vehicles`, `Warehouse`).

По текущему состоянию DB/Eloquent/Repository/Command/Cache/Storage в `Templates` не найдены. Основные проблемы ниже.

## 1. Убрать зависимость Templates от Vehicles

Проблема:

- `app/Modules/Templates/Application/WiperSpecificationService.php` импортирует `App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\WiperSideEnum`;
- `Templates` начинает знать про одного из потребителей;
- направление зависимости становится неправильным для shared-kernel.

Почему это проблема:

- форма details для дворников принадлежит `Templates`, а не `Vehicles`;
- `front/back` и `adapterField()` являются частью схемы wiper details;
- если Warehouse или другой модуль начнет использовать тот же шаблон, `Templates` уже будет связан с Vehicles.

Решение:

1. Перенести `WiperSideEnum` в `App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum`.
2. Обновить импорты в `Templates`.
3. Обновить импорты в Vehicles:
   - `app/Modules/Vehicles/Features/Export/Application/Services/Expanders/WiperRowExpander.php`;
   - `app/Modules/Vehicles/Features/Export/Application/Services/VehicleExportService.php`;
   - остальные места по `rg "WiperSideEnum" app/Modules`.
4. Старый enum в `Vehicles/Shared` удалить, если после переноса не останется импортов.

## 2. Заменить Log facade на PSR logger

Проблема:

- `app/Modules/Templates/Application/WiperSpecificationService.php` использует `Illuminate\Support\Facades\Log`.

Почему это проблема:

- логирование является внешним IO;
- по договоренности такие вещи не должны быть прямыми фасадами внутри application-сервиса;
- в queued/serialized сценариях прямой logger может повторить проблему, которую уже разбирали в Warehouse.

Решение:

1. Внедрить `Psr\Log\LoggerInterface` в `WiperSpecificationService`.
2. Не использовать proxy из `Warehouse`, чтобы `Templates` не зависел от Warehouse.
3. Если нужен serializable logger, вынести proxy в нейтральное место, например `App\Shared\Infrastructure\Logging\LaravelLoggerProxy`, или сделать локальный `Templates\Infrastructure\Logging`.
4. Забиндить logger через `TemplatesServiceProvider`.

## 3. Убрать вычисления внутри вызовов

Проблема:

- `DetailsDataPresenter` передает `Data::from($details)` прямо в `cells(...)`;
- `NomenclatureDetailsDataPresenter` делает то же самое для всех nomenclature-шаблонов;
- `WiperDetailsPresenter` создает `new WiperFrontDetailsData` и `new WiperBackDetailsData` внутри аргументов методов.

Примеры:

- `app/Modules/Templates/Application/Services/DetailsDataPresenter.php`;
- `app/Modules/Templates/Application/Services/NomenclatureDetailsDataPresenter.php`;
- `app/Modules/Templates/Application/Services/Presenters/WiperDetailsPresenter.php`.

Почему это проблема:

- нарушает правило из `ARCHITECTURE.md`: вычисления, `new`, callbacks и сложные выражения выносим в именованные переменные;
- усложняет чтение больших `match`;
- сложнее отлаживать конкретный шаг преобразования.

Решение:

1. Вынести `Data::from($details)` в переменные.
2. В `WiperDetailsPresenter` вынести fallback data в переменные:
   - `$front = $data->front ?? new WiperFrontDetailsData;`
   - `$back = $data->back ?? new WiperBackDetailsData;`
3. Проверить `Templates` повторно через `rg "::from\\(|new .*\\)|array_map\\(" app/Modules/Templates`.

## 4. Сделать ошибки Templates явными

Проблема:

- `TemplatesClient` принимает `string $template` и вызывает `DetailTemplateEnum::from(...)` / `NomenclatureDetailTemplateEnum::from(...)`;
- невалидный template даст сырой `ValueError`;
- `DetailsRowCursor` приводит числа через `(int)` и `(float)`;
- `FormatsExportCells` молча теряет неизвестные enum names при экспорте.

Почему это проблема:

- ошибка внешнего payload или поврежденных details становится неявной;
- `abc` в числовой ячейке может превратиться в `0`;
- при export неизвестное enum-name может исчезнуть из файла без сигнала.

Решение:

1. Добавить кастомные ошибки Templates, например:
   - `UnknownTemplateException`;
   - `InvalidTemplateCellException`;
   - `UnknownTemplateEnumValueException`.
2. В `TemplatesClient` явно конвертировать string в enum через локальную переменную и ловить `ValueError`.
3. В `DetailsRowCursor` сделать строгие parse-методы для `int`, `float`, `array<int>`, `array<float>`.
4. В `FormatsExportCells` не терять неизвестные enum names молча. Минимум - бросать кастомную ошибку.

## 5. Решить, оставляем ли string API у TemplatesClient

Проблема:

- public API Templates сейчас принимает строки и возвращает массивы;
- внутри проекта потребители уже знают свои enum-ы и adapter-ы переводят локальный язык в публичный API Templates.

Варианты:

1. Оставить string API как wire-границу, но сделать явную конвертацию и кастомные ошибки.
2. Перевести public client на `DetailTemplateEnum` / `NomenclatureDetailTemplateEnum`, если все потребители являются PHP-модулями внутри проекта.

Предложение:

- на первом шаге оставить сигнатуры без изменения, чтобы не расширять diff;
- сделать safe enum conversion и явные ошибки;
- позже отдельно решить, нужен ли string API как стабильная внешняя граница.

## 6. Разгрузить Nomenclature selectors

Проблема:

- `NomenclatureDetailsDataFactory` держит большой constructor с default `new` и большой `match`;
- `NomenclatureDetailsDataPresenter` держит большой constructor, `headingsFor`, `referenceOptionsFor`, `toExportCells`;
- при добавлении нового шаблона нужно менять несколько больших мест.

Почему это проблема:

- высокая вероятность забыть один из списков;
- `referenceOptionsFor` централизует знание о полях всех шаблонов, хотя это ближе к конкретному presenter;
- default `new` в конструкторах выбивается из DI-подхода.

Решение:

1. Ввести registry/map `template => builder/presenter`.
2. Биндинг registry сделать в `TemplatesServiceProvider`.
3. Для presenter-ов ввести общий контракт, например:
   - `headings(): array`;
   - `cells(AbstractDetailsData $data): array`;
   - `referenceOptions(): array`.
4. Перенести `referenceOptions` в конкретные presenter-ы.
5. После этого добавление нового шаблона должно требовать:
   - enum case;
   - data class;
   - builder;
   - presenter;
   - одну регистрацию в map.

## 7. Привести provider к общему стилю

Проблема:

- `TemplatesServiceProvider` использует прямые повторяющиеся `$this->app->bind(...)`;
- в остальных модулях уже есть стиль с grouped binding maps.

Решение:

1. Добавить карты:
   - `SERVICE_BINDINGS`;
   - `FACTORY_BINDINGS`;
   - `CLIENT_BINDINGS`;
   - возможно `PRESENTER_BINDINGS`.
2. В `register()` пройтись по картам.
3. Там же подключить logger binding, если будет выбран proxy.

## 8. Добавить тесты для nomenclature-ветки Templates

Проблема:

- есть unit-тесты vehicle-части Templates в `tests/Unit/Vehicles/Templates/*`;
- отдельной unit-проверки большой nomenclature-ветки Templates нет;
- сейчас она в основном прикрыта Warehouse feature tests.

Решение:

1. Добавить `tests/Unit/Templates/NomenclatureDetailsDataFactoryTest.php`.
2. Добавить `tests/Unit/Templates/NomenclatureDetailsDataPresenterTest.php`.
3. Отдельно проверить:
   - сборку select/multi-select labels в enum names;
   - рендер enum names обратно в labels;
   - invalid numeric cells;
   - unknown enum names при export;
   - `referenceOptions`.

## Рекомендуемый порядок работ

1. Перенести `WiperSideEnum` в `Templates` и обновить импорты.
2. Заменить `Log` facade на `Psr\Log\LoggerInterface`.
3. Убрать inline `new` / `Data::from(...)` внутри вызовов.
4. Добавить кастомные ошибки и строгий parsing/export validation.
5. Добавить unit-тесты nomenclature-ветки.
6. Разгрузить selectors через registry/map.
7. Привести `TemplatesServiceProvider` к общему стилю binding maps.

