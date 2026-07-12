# План: замена Field-Template DSL на типизированные `Shared/Templates`-классы

Контекст решения — обсуждение в чате (см. историю): фича `Templates` изначально обслуживала
две задачи — (1) рендер Filament-формы (`FilamentTemplateRenderer`, уже `@deprecated`,
`filament/filament` не установлен), и (2) двустороннюю (де)сериализацию `details` (jsonb-колонка
`part_specifications.details`) для Import/Export. Задача (1) мертва, сервис сейчас — чистый
RabbitMQ-консьюмер без HTTP/UI слоя вообще (проверено: `Http/Controllers` в проекте нет).
Остаётся только (2) — и под неё генерический рекурсивный DSL (`AbstractTemplate`/`Fields/*`)
избыточен: делаем типизированные `spatie/laravel-data`-классы вместо обхода дерева.

## Итоговая раскладка

```
Shared/Templates/Domain/Enums/DetailTemplateEnum.php           (переехал из Templates/Domain/Enums)
Shared/Templates/Domain/Enums/Filter/FormEnum.php
Shared/Templates/Domain/Enums/Filter/PerformanceEnum.php
Shared/Templates/Domain/Enums/Filter/OilFilterFatherEnum.php
Shared/Templates/Domain/Enums/Filter/OilFilterThreadEnum.php
Shared/Templates/Domain/Enums/SparkPlug/ElectrodeGapEnum.php
Shared/Templates/Domain/Enums/SparkPlug/ThreadLengthEnum.php
Shared/Templates/Domain/Enums/SparkPlug/ThreadPitchEnum.php
Shared/Templates/Domain/Enums/SparkPlug/ThreadSizeEnum.php
Shared/Templates/Domain/Enums/SparkPlug/WrenchJawWidthEnum.php
Shared/Templates/Domain/Enums/Wiper/FrontAdapterTypeEnum.php
Shared/Templates/Domain/Enums/Wiper/RearAdapterTypeEnum.php

Shared/Templates/Domain/ModelData/Engine/SparkPlugDetailsData.php
Shared/Templates/Domain/ModelData/Engine/SparkPlugThreadDetailsData.php
Shared/Templates/Domain/ModelData/Engine/SparkPlugElectrodeDetailsData.php
Shared/Templates/Domain/ModelData/Engine/OilFilterDetailsData.php
Shared/Templates/Domain/ModelData/Engine/AirFilterDetailsData.php
Shared/Templates/Domain/ModelData/Vehicle/WiperDetailsData.php
Shared/Templates/Domain/ModelData/Vehicle/WiperFrontDetailsData.php
Shared/Templates/Domain/ModelData/Vehicle/WiperBackDetailsData.php

Shared/Templates/Domain/Traits/DetailsRowIO.php   (общие статические хелперы чтения/записи ячеек)

Templates/Application/WiperSpecificationService.php   — остаётся (реальная бизнес-логика: split/merge
                                                          по сторонам, не форма данных)
Templates/Domain/Contracts/WiperSpecificationServiceInterface.php — остаётся
Templates/Application/TemplatesServiceProvider.php    — остаётся, но сильно худеет (биндинг только
                                                          WiperSpecificationServiceInterface)
```

**Удаляется целиком:**
- `Templates/Domain/Templates/**` (4 класса на `AbstractTemplate`)
- `Templates/Application/DetailTemplateResolver.php` + `Templates/Domain/Contracts/DetailTemplateResolverInterface.php`
  (не нужны: `Data`-классы резолвятся статическим `::from()`/`::headings()` по FQCN из
  `DetailTemplateEnum::dataClass()`, инстанцировать через контейнер нечего)
- `Export/Application/Services/Details/ExportDetailsBuilder.php` + `ExportDetailsBuilderInterface`
- `Import/Application/Services/Template/DetailsBuilder.php` + `DetailsBuilderInterface`
- `packages/field-templates/**` целиком (весь `dan/field-templates`: `AbstractTemplate`, `Fields/*`,
  `CommonFields`, `Attributes/*`, `FilamentTemplateRenderer`, `Support/EnumHelperTrait`) +
  `composer.json`: секция `repositories` (`packages/field-templates`) и `require."dan/field-templates"`.

**Побочная находка при разборе:** `PositionEnum`, `BooleanOptionEnum`, `Filter\TypeEnum` и методы
`CommonFields::position()/booleanField()/filterType()/range()/adapterTypeFront()/adapterTypeRear()`
(не-`ForVehicle` варианты) не используются ни одним из 4 реальных шаблонов — мёртвый код внутри
самого пакета. Раз пакет целиком удаляется, отдельно их не выпиливаем — они просто не переезжают.

**`AIR_FILTER`/`OIL_FILTER` сегодня не подключены ни к одному Import/Export сценарию** (проверено
`grep` по `app/` — только объявление в enum). Их `Data`-классы переносим один в один по декларации
шаблона для полноты (кто-то же их зачем-то объявил), но **без тестового покрытия** — ниже
приоритет, ниже уверенность, при реальном подключении сценария потребуется отдельная проверка.

## Ключевые решения по типизации

1. **Хранимый ключ select-полей — это `CASE->name` enum'а, а не `CASE->value`.**
   Сегодняшний `SelectField::options` — это `EnumHelperTrait::toArray()` = `[name => value]`, где
   `value` — русский лейбл для Excel, `name` — то, что реально пишется в `details` JSON. Значит:
   - в `Shared/Domain/Traits/EnumHelperTrait` (сейчас мёртвый — см. `refactor-new.md`, находка
     про 9 enum'ов) вместо удаления **добавляем реальных потребителей**: заменяем
     `names()/values()/toArray()` (нигде не вызывались) на `fromLabel(string $label): ?static`
     (найти case по `->value` регистронезависимо — воспроизводит `getVarKey`) и
     `fromName(string $name): ?static` (найти case по `->name` — обратный резолв для экспорта).
     Enum'ы, которые *не* про select-справочники деталей (`ProviderEnum`, `Vehicle/*`, `Engine/*`)
     трейт больше не используют — `use EnumHelperTrait` убираем из них (сам вывод «мёртвый код»
     из `refactor-new.md` остаётся верным для НИХ, просто трейт как код не удаляется, а
     переиспользуется для новой задачи).
   - 11 «живых» Attribute-enum'ов подключают тот же трейт.
   - Поля в `*DetailsData` остаются `string`/`array<string>` (не типизируем как сам backed enum,
     потому что hранимое значение — это `->name`, а не `->value`, и автокаст `spatie/laravel-data`
     штатно матчит только по `->value`). Это осознанное отступление от правила ARCHITECTURE.md §1
     «enum-поля реального enum-типа» — то правило писалось для колонок с `$casts`, а не для
     значений внутри jsonb-поля с иной конвенцией хранения. Валидация — `Rule::in(array_column(
     Enum::cases(), 'name'))` в `rules()` каждого `Data`-класса (тот же приём, что уже применяют
     Import-фабрики).

2. **Общий трейт `DetailsRowIO` вместо генерического обхода дерева.**
   Заменяет и `ExportDetailsBuilder::getFieldValue()`, и `Import\DetailsBuilder::getFieldValue()`
   одним набором маленьких статических хелперов, переиспользуемых всеми 8 `Data`-классами:
   - `pullCell(array $row, int &$index): mixed` — читает ячейку и двигает индекс (замена ручного
     `$row[$currentIndex] ?? null; $currentIndex++`).
   - `labelToName(class-string $enum, ?string $label): ?string` / `nameToLabel(...)` — обёртки
     над `fromLabel()->name` / `fromName()->value`.
   - `pullMultiLabelToNames(array $row, int &$index, class-string $enum): array` /
     `namesToLabelString(array $names, class-string $enum): string` — `;`-джойн для multi-select
     (воспроизводит `getVarKeys`/`getVarValue` c `;`-разделителем).
   - `pullFloatArray(array $row, int &$index): array<float>` — **важно**: старый
     `DetailsBuilder` кастует элементы `array`-полей к `(float)` даже когда `itemType` в DSL был
     `'integer'` (см. `CommonFields::length()`/`rangeInteger()` — `itemType: 'integer'`, но
     обработчик в `getFieldValue()` всегда делает `(float) trim($val)`). Сохраняем это поведение
     1-в-1 ради обратной совместимости с уже импортированными данными — **не чиним** несоответствие
     типа сейчас, это отдельный вопрос вне рамок этого рефакторинга.
   - `pullRangeInt(array $row, int &$index): array{min: ?int, max: ?int}` — для `{min,max}`-объектов
     (`rangeInteger`, дважды во `front`, один раз в `back`).
   Каждый `Data`-класс использует эти хелперы в своих статических
   `fromImportRow(array $row, int &$index): static` и в собственных `toExportCells(): array` +
   `headings(): array` — сам класс диктует порядок полей (== порядок конструктора), обход дерева
   руками для каждого поля отдельно не пишем, но и generic recursion больше нет: раскрытие вложенных
   `Data` (thread/electrode, front/back) — обычные вложенные вызовы `NestedData::fromImportRow(...)`.

3. **Ответственность за резолв шаблона по enum — статический метод самого enum.**
   `DetailTemplateEnum::dataClass(): class-string` (переименованный `templateClass()`) — `match`,
   без побочных эффектов, без контейнера. Вызывающий код делает
   `($template->dataClass())::fromImportRow($row, $index)` (Import) или
   `($template->dataClass())::headings()` / `$data->toExportCells()` (Export) — резолвер-класс
   не нужен.

## Кто меняется (потребители)

**Import:**
- `VehicleWiperSheetRowMapper.php:29` — сейчас `$templateDataBuilder->buildBySlug($row, 20, $slug)`.
  Меняется на прямой вызов `WiperDetailsData::fromImportRow($row, $index)` (после
  `DetailTemplateEnum::from($slug)`, оставляем ровно один живой слаг — `wiper` — но код не завязан
  жёстко: остаётся общий `match` на `dataClass()`, просто у Vehicle-листа фактически ходит один
  шаблон).
- `EngineSparkPlugsSheetImport.php:62` и `EngineSparkPlugSpecificationImport.php:77` — сейчас
  `$templateDataBuilder->buildByTemplate($row, $index, DetailTemplateEnum::SPARK_PLUGS)`. Меняется
  на `SparkPlugDetailsData::fromImportRow($row->toArray(), $index)`.
- `TemplateDataBuilder`/`TemplateDataBuilderInterface` (`Import/Application/Services/Template/`,
  `Import/Domain/Contracts/Services/Template/`) — становятся не нужны, удаляются вместе с
  `DetailsBuilder`. Три вызывающих класса выше подключаются напрямую к `Shared/Templates`.
- `UpsertEngineSparkPlugSpecService`/`UpsertSparkPlugSpecByModificationService`/
  `VehicleWiperSpecificationImportService` — сигнатуры `details: array` не меняются (они и
  сегодня принимают собранный `array`, не типизированный объект — `Data`-класс собирается на
  уровне Infrastructure-адаптера/маппера, в `PartSpecificationData::$details` всё так же попадает
  `array`, через `$data->toArray()` — тип `Data`-класса используется только на входе-сборке, само
  хранение остаётся plain array, как обсуждали: `PartSpecificationData::$details` не становится
  полиморфным типизированным полем).

**Export:**
- `EngineExportService.php:31-32` — сейчас `$templates->resolve(SPARK_PLUGS)->getArrayTemplate()`
  + `$exportDetails->extractHeadingsFromTemplate(...)`. Меняется на
  `SparkPlugDetailsData::headings()` (конструктор, без резолвера).
  `mapSparkPlugRow()` (:60-71) — вместо `$exportDetails->getDetailsData($details, $templateConfig)`
  вызывает `SparkPlugDetailsData::fromExportDetails($row->specification->details)->toExportCells()`.
- `VehicleExportService.php:34-35,94-97` — аналогично на `WiperDetailsData`. Обратите внимание:
  `mapWiperRow()` сегодня сначала зовёт `$this->wiper->sideData(...)` +
  `$this->wiper->mergeForExport($frontData, $backData)` (`WiperSpecificationService`, остаётся!),
  и уже смерженный массив отдаёт в `ExportDetailsBuilder::getDetailsData()`. В новой версии —
  тот же `mergeForExport()` (не трогаем), а дальше `WiperDetailsData::fromExportDetails($merged)
  ->toExportCells()` вместо генерического обходчика.
- `ExportDetailsBuilder`/`ExportDetailsBuilderInterface` — удаляются.

**`Templates` (что остаётся от фичи):**
- `WiperSpecificationService` + интерфейс — без изменений в контракте, только `use`-импорт
  `DetailTemplateEnum` меняет неймспейс на `Shared\Templates\Domain\Enums`.
- `TemplatesServiceProvider` — убирает биндинг `DetailTemplateResolverInterface` (удалён),
  оставляет только `WiperSpecificationServiceInterface`.

## Порядок работы (с проверкой тестами на каждом шаге)

0. Зафиксировать зелёный baseline: прогнать полный набор тестов до правок.
1. `Shared/Domain/Traits/EnumHelperTrait` — переписать (`fromLabel`/`fromName` вместо
   `names/values/toArray`), убрать `use EnumHelperTrait` из 9 доменных enum'ов, где он был мёртв.
2. Перенести `DetailTemplateEnum` в `Shared/Templates/Domain/Enums/`, переименовать
   `templateClass()` → `dataClass()` (сигнатуры пока указывают на старые классы — промежуточный
   шаг, чтобы двигаться мелкими коммитами).
3. Перенести 11 живых Attribute-enum'ов в `Shared/Templates/Domain/Enums/{Filter,SparkPlug,Wiper}/`,
   подключить `EnumHelperTrait`.
4. Завести `Shared/Templates/Domain/Traits/DetailsRowIO`.
5. **Wiper** (самое тестами покрытое — `WiperSpecificationServiceTest`,
   `VehicleWiperSpecificationImportServiceTest`, `VehicleWipersSheetImportTest`,
   `VehicleMultiSheetExportTest`): `WiperFrontDetailsData`/`WiperBackDetailsData`/`WiperDetailsData`
   → переключить `VehicleWiperSheetRowMapper` и `VehicleExportService` → прогнать тесты.
6. **SparkPlug** (`UpsertEngineSparkPlugSpecServiceTest`,
   `UpsertSparkPlugSpecByModificationServiceTest`, `EngineSparkPlugsSheetImportTest`,
   `EngineMultiSheetExportTest`): `SparkPlugThreadDetailsData`/`SparkPlugElectrodeDetailsData`/
   `SparkPlugDetailsData` → переключить оба Excel-адаптера и `EngineExportService` → прогнать тесты.
7. **OilFilter/AirFilter** (без тестов сегодня) — портируем декларативно 1-в-1, без переключения
   потребителей (их и не было).
8. Обновить `DetailTemplateEnum::dataClass()` на новые FQCN, удалить `DetailTemplateResolver` +
   интерфейс, `ExportDetailsBuilder`/`DetailsBuilder` + интерфейсы, `TemplateDataBuilder` +
   интерфейс, старые `Templates/Domain/Templates/**`, обновить `TemplatesServiceProvider`,
   `ExportServiceProvider`, `ImportServiceProvider`.
9. Удалить `packages/field-templates/**`, вычистить `composer.json` (`repositories`, `require`),
   `composer update`.
10. Обновить `ARCHITECTURE.md`: описать новую подпапку `Shared/Templates` (первое исключение из
    «Shared только `Domain/`, без группировки по под-фичам»), сократившуюся фичу `Templates`
    (только `WiperSpecificationService`), убрать упоминания `DetailTemplateResolver`/
    `ExportDetailsBuilder`/`DetailsBuilder`/`TemplateDataBuilder` там, где они перечислены.
11. Финальный прогон полного набора тестов + `composer validate`.

## Риски / что нельзя молча сломать

- **Обратная совместимость хранения**: уже импортированные `PartSpecification.details` в БД
  хранят `case->name` (напр. `"H"`, `"WJ14"`). Новый код обязан читать/писать те же строки —
  никакой миграции данных в этом рефакторинге нет и не должно быть.
- **Порядок колонок Excel** — `headings()`/`fromImportRow()`/`toExportCells()` каждого класса
  обязаны сохранять тот же плоский DFS-порядок полей, что и сегодняшний `getArrayTemplate()`
  (порядок объявления в конструкторе `Data`-класса = порядок полей в старом `initializeFields()`).
- **`(float)`-каст на `array`-полях** (см. пункт 2 выше) — сознательно не чиним попутно.
- **OilFilter conditional `father`**: валидность значения зависит от `performance` (WIND_UP →
  `OilFilterThreadEnum`, DIRECT_FLOW → `OilFilterFatherEnum`, LONG_TERM → поле неприменимо).
  Переносим правило в кастомную валидацию `OilFilterDetailsData::rules()`/`withValidator()`, но
  раз сценарий не подключён и не покрыт тестами — низкий приоритет, отдельно перепроверить при
  реальном подключении.
