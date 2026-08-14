# Архитектура `dan-vehicles`

Справочник: **что куда класть, что должно быть тонким, что к какому слою/фиче относится и почему.**

> Актуализация 2026-08-11: бизнес-модули живут в `app/Modules/*`. `Templates` остаётся
> отдельный shared-kernel модуль, а `Warehouse` и `Vehicles` имеют module-level `Shared/` с
> публичными событиями и общей инфраструктурой
> (`Shared/Infrastructure/Database/Migrations`, `Shared/Infrastructure/Providers`). Межфичевые
> синхронные вызовы идут через локальные `Domain/Contracts/Clients/*ClientInterface` фичи-потребителя
> и adapter в её `Infrastructure/Clients`; чужие service/factory/presenter-контракты в
> `Application` не импортируем.

Раскладка — **module-first + feature-first**. Бизнес-модули живут в `app/Modules/*`.
У доменных модулей (`Vehicles`, `Warehouse`) сначала выбираем фичу в `Features/*`
(`Catalog`, `Import`, `Export`, `Maintenance`, `Packaging`, `KitProperties`, ...), внутри фичи —
слой: **Domain → Application → Infrastructure → Presentation**. Общий модуль `Templates` пока
не дробится на `Features/`, потому что внутри него нет нескольких самостоятельных фич.

> Здесь фиксируется **целевое состояние конвенций**, а не история перехода.

Правило зависимостей (Dependency Rule) действует **внутри каждой фичи**: стрелки внутрь.

```
Presentation ──▶ Application ──▶ Domain
       │              │            ▲
       └─────▶ Infrastructure ─────┘
```

- **Domain** — декларации фичи: Contracts (порты) + ModelData (`spatie/laravel-data`) + DTOs +
  Enums (фиче-специфичные) + Events + Templates-декларации. Без фреймворковой инфраструктуры.
- **Application** знает только Domain своей фичи (+ Domain-контракты `Templates` как shared-kernel
  и module-level `<Module>/Shared` через локальные adapters).
  Оркестрация и правила: Services, UseCases (точки входа), Factories, тонкие Listeners.
- **Infrastructure** реализует порты фичи: Eloquent-**Models**, Repositories, Commands,
  Excel-Imports/Exports, Notifications, Providers. Тащит фреймворк/внешний мир.
- **Presentation** — точки входа фичи (console, http), максимально тонкие, дёргают Application.

Нарушение, за которым следим: Domain/Application фичи **не импортируют** `Maatwebsite\Excel`,
конкретные пакеты брокера, фасады записи и т.п. — только через порты в `Domain/Contracts`.

Отдельное правило для queued Excel imports: если adapter реализует `ShouldQueue`, его свойства
должны оставаться сериализуемыми. Не храним в них service/repository/client/logger dependency
graph; зависимости резолвим в `collection()` или другом worker-time методе. `registerEvents()` для
таких imports не должен возвращать closures — используем сериализуемые callables вроде
`[self::class, 'afterImport']`.

Import/export adapter selection — ответственность `Infrastructure` или provider boundary.
Application/use case зависит от domain-порта фабрики/экспорта/импорта, но не выбирает конкретный
Excel adapter и не знает его runtime constructor-параметры. Laravel helpers `app()`,
`app()->makeWith(...)`, `event()` и `config()` допустимы, когда они остаются на boundary-слое и не
маскируют выбор конкретной инфраструктурной реализации внутри Application.

### Принятые архитектурные компромиссы

`dan-vehicles` — Laravel-сервис, а не framework-agnostic библиотека. Мы не планируем уходить от
Laravel, поэтому в проекте допустимы осознанные framework-связки, которые упрощают ежедневную
разработку и уже закреплены локальными правилами:

- `Illuminate\Support\Collection` может быть частью портов чтения и Application-сервисов, если это
  именно Support Collection, а не Eloquent Collection;
- Laravel Validator/`Rule::enum(...)` допустим в Application-фабриках, которые валидируют сырую
  строку импорта и собирают `Data`;
- Laravel cache/events contracts допустимы в Application-сервисах orchestration/gate-сценариев,
  если они выражают прикладное состояние workflow и не тянут Eloquent/Excel/RabbitMQ напрямую;
- Laravel `Log`/facades в обычном Application-коде не используем напрямую: логирование — через
  порт/PSR logger или Infrastructure-adapter. Исключения должны быть явно описаны рядом с фичей.
- Production `info`/`debug` logs не используем для нормального бизнес-потока. Успешные импорты,
  экспорты, публикации и расчёты подтверждаются result DTO, events, notifications и тестами.
  Логи уровня `warning`/`error` оставляем для actionable интеграционных аномалий и сбоев.
- Public sync clients между фичами/модулями только читают данные. Запись выполняется через use
  case/command или async event/result workflow, не через `*ClientInterface`.
- Public sync clients не ходят в БД напрямую: они делегируют owner use case/query service или
  repository port. SQL остаётся в `Infrastructure/Repositories`, а не в client adapter.

DDD в проекте применяется **стратегически**, а не как обязательная tactical DDD-модель с жирными
entities/aggregates. `ModelData` намеренно остаются тонкими снимками состояния, Eloquent-модели —
анемичными Infrastructure-деталями, а бизнес-сценарии и правила живут в Application
Services/UseCases. Если доменное правило становится самостоятельным и переиспользуемым, его можно
выделять в domain policy/specification/value object, но не превращать Eloquent/Data в "fat model" с
методами `import/export/create`.

### Module `Shared` и межфичевые границы

`Shared/` внутри `Warehouse`, `Vehicles` или `Applicability` — публичная часть конкретного модуля,
а не папка для удобного складывания общего кода. Верхнеуровневый `app/Modules/Shared` не
используется: технические workflow, console base classes, Excel helpers и adapters размещаются
внутри конкретной фичи. Небольшое дублирование между модулями допустимо ради изоляции bounded
contexts.

В `Shared` можно класть:

- `Domain/Contracts/Clients` — публичные client-контракты модуля для межфичевых/межмодульных
  sync-вызовов;
- `Domain/DTOs` — публичные DTO этих client-контрактов и module-level payload'ов;
- `Domain/Events` — факты, которые должны слушать другие фичи этого же модуля;
- `Domain/Enums` — только enum'ы, которые являются wire/db-контрактом между фичами;
- `Domain/Exceptions` — публичные исключения module-level контрактов;
- `Infrastructure/Clients` — adapter'ы, которые переводят локальный язык потребителя в публичный
  API владельца возможности;
- `Infrastructure/Logging` — технические module-level logging adapter'ы без бизнес-логики;
- `Infrastructure/Database/Migrations` и `Infrastructure/Providers/<Module>ServiceProvider.php` —
  module-level инфраструктуру, общую для всех фич модуля.

В `Shared` не кладём:

- `ModelData`;
- Eloquent-модели;
- repositories, commands, use cases, application services;
- внутренние enum'ы конкретного workflow;
- события, которые используются только внутри одной фичи.

**ModelData остаётся локальным для фичи.** Даже если два `Data`-класса имеют одинаковые поля, это
совпадение контракта на границе, а не повод делать `Shared/Domain/ModelData`. При межфичевом
вызове adapter явно переводит DTO/Data фичи-потребителя в публичный контракт фичи-владельца.

Public shared events не открывают наружу raw `array` payload'ы сущностей. Для created/updated/deleted
фактов используем scalar fields или typed event payload DTO/value objects. DTO может иметь простой
`toArray()`/`fromArray()`, если это механическая сериализация собственного состояния; сборка из
Eloquent, paginator, HTTP/RabbitMQ aliases/defaults, external API shape или нескольких объектов
остаётся в factory/presenter/Infrastructure adapter.

Для CRUD-фактов модуля используем entity-specific payload DTO
(`<Entity>EventPayloadDTO`), а не универсальный `CatalogEventPayloadDTO`/`array snapshot`.
Payload собирается явно по месту или через typed mapper конкретной сущности; не заводим
`fromData(object)`/`fromModel(object)` без точного входного типа. Вызов события не должен
прятать вложенный `new <Entity>EventPayloadDTO(...)` прямо внутри `new <Entity>Created(...)`:
сначала отдельная переменная `$payload`, потом `event(new ...)`, чтобы payload контракта читался
и ревьюился отдельно.

**Enum'ы по умолчанию локальные.** В `Shared/Domain/Enums` enum переносится только если расхождение
значений недопустимо: общий `$casts`/db-контракт, внешний payload для нескольких фич или единый
словарь значений, которым реально пользуются несколько фич. Workflow-статусы, reason'ы, operation
types и режимы импорта/экспорта остаются в своей фиче.

### События и sync clients

Событие — это факт "что-то произошло". У события нет return value. Если нужен ответ прямо сейчас,
это не event flow, а синхронный client/query contract.

Правила:

- cross-feature факт внутри модуля лежит в `<Module>/Shared/Domain/Events`;
- внутренний факт фичи лежит в `<Feature>/Domain/Events`;
- межсервисный request/result workflow коррелируется через `operation_id`;
- новые import/export/calculation workflows используют `operation_id` для cache/report state,
  идемпотентности и уведомлений; `runId` не является внешним correlation id и допустим только как
  legacy/internal runtime key там, где он реально еще остался;
- observer'ы для межфичевой синхронизации не используем, реакция идёт через listener/use case.

Синхронный вызов между фичами оформляется как public client владельца возможности и локальный
порт у потребителя:

```text
app/Modules/Templates/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Application/Clients/TemplatesClient.php

app/Modules/Vehicles/Features/Import/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Infrastructure/Clients/TemplatesClient.php
```

Application фичи-потребителя зависит только от своего локального
`Domain/Contracts/Clients/*ClientInterface`. Такой client — read-only sync boundary: он возвращает
данные прямо сейчас, но не создаёт/обновляет/удаляет состояние владельца. Adapter в
`Infrastructure/Clients` уже переводит этот локальный язык в публичный API владельца (`Templates`,
`KitProperties`, `Packaging`, ...).

Запрещённый вариант для Application-потребителя: напрямую импортировать чужие
`Domain\Contracts\Services`, `Domain\Contracts\Factories`, `Application\Services`,
`Application\Factories`, presenters или чужие `ModelData`. Эти зависимости допустимы только внутри
инфраструктурного adapter'а, который и является границей перевода.

---

## 0. Карта модулей и фич

| Папка | Что это | Полнота слоёв |
|---|---|---|
| `app/Modules/Templates/` | Shared-kernel модуль: типизированные `Data`-классы формы `details`, enum'ы шаблонов и словарей, сборка из строки, рендер в Excel-ячейки и доменное правило хранения дворников. | `Domain/` + `Application/` + `Infrastructure/` |
| `app/Modules/Vehicles/Shared/` | Общий «словарь» Vehicles без поведения: enum'ы, публичные события и module-level инфраструктура. | `Domain/` + `Infrastructure/` |
| `app/Modules/Vehicles/Features/Import/` | Приём CSV/Excel → каталог. Единственная Vehicles-фича с записью (`Command`). | полный вертикальный срез |
| `app/Modules/Vehicles/Features/Export/` | Каталог → Excel. **Только чтение** — `Repository` есть, `Command` нет. | `Domain/Application/Infrastructure/Presentation` |
| `app/Modules/Vehicles/Features/Catalog/` | Внешние catalog mutation-сценарии Vehicles. | полный вертикальный срез |
| `app/Modules/Vehicles/Features/Maintenance/` | Разовые фиксы каталога (артизан-команды). Без слоя `Repository` — читает/пишет напрямую через Eloquent, это осознанно для «разовых фиксов». | `Application/Infrastructure/Presentation` |
| `app/Modules/Warehouse/Shared/` | Общие события и module-level инфраструктура Warehouse, включая миграции. | `Domain/` + `Infrastructure/` |
| `app/Modules/Warehouse/Features/*/` | Warehouse-фичи: `Catalog`, `Import`, `Export`, `Packaging`, `KitProperties`, `WiperAdapterAudit`, `Maintenance`, `MoySklad`. | по фиче |

### Context map

Физические модули `app/Modules/*` считаются bounded contexts. Отношения между ними описываем явно,
чтобы не возникало скрытых зависимостей на чужие Application/Infrastructure классы.

| Context | Чем владеет | Публичная поверхность | Зависимости / потребители |
|---|---|---|---|
| `Vehicles` | Каталог автомобилей: `Vehicle`, `Engine`, `Manufacturer`, `Modification`, vehicle/engine `PartSpecification`. | Shared events created/updated/deleted, client contracts/DTOs для чтения данных Vehicles. | Потребители: `Applicability`, частично `Warehouse`/export-сценарии. Не отдаёт Eloquent-модели наружу. |
| `Warehouse` | Складской каталог: `Nomenclature`, `Kit`, `Type`, `PackDimension`, свойства наборов, аудит дворников, интеграция MoySklad. | Shared events, client contracts/DTOs для применяемости и export/audit-сценариев. | Потребители: `Applicability`, `Templates`, внешние интеграции. `MoySklad` остаётся под-контекстом Warehouse и не является общим API. |
| `Applicability` | Импорт, экспорт и расчёт применяемости комплектов к автомобилям. | Сценарии расчёта/экспорта, события завершения, notification DTOs. | Потребляет публичные contracts `Vehicles` и `Warehouse`; не владеет каталогами Vehicles/Warehouse. |
| `Templates` | Shared Kernel формы `details`: enum-словари, typed `Data`, сборка из строк и рендер в Excel. | Domain declarations + service/factory contracts, используемые другими context'ами. | Используется Vehicles/Warehouse/Applicability. Любое изменение формы `details` считается cross-context изменением. |

Правило context map: если context'у нужен ответ прямо сейчас, он использует sync client contract и
adapter. Если нужно сообщить факт без ответа — domain event. Прямой импорт чужих
`Application\Services`, `Application\Factories`, presenters, Eloquent-моделей и feature-local
`ModelData` запрещён.

**Почему feature-first, а Enums — в module-level `Shared`:** фичи режем по способностям (Import/Export/…),
каждая независима и переезжаемая. Но enum'ы — это словарь значений колонок (`$casts`), а не
сервис и не модель с данными: дублировать их = риск рассинхрона схемы. Поэтому единая точка
истины в `Shared/`. Eloquent-модели, наоборот, **дублируются по фичам** (каждая фича — своя
копия в `Infrastructure/Models`), потому что это деталь реализации Repository/Command, и
независимость фич важнее отсутствия дублирования.

> **Цена:** пока схема (миграции) одна на все копии, расхождения колонок между копиями
> обнаружатся только в рантайме, не на уровне БД. Это принятый компромисс.

**Форма `details` (`PartSpecification`) — декларация в Domain, сборка/рендер в Application.**
`part_specifications.details` (jsonb) — полиморфная по `template` (`DetailTemplateEnum`) форма,
которую пишет Import и читает Export. Раньше это обслуживал общий пакет `dan/field-templates`
(генерический DSL `AbstractTemplate`/`Fields/*`, обходимый рекурсивно двумя параллельными
интерпретаторами — `Import\DetailsBuilder` и `Export\ExportDetailsBuilder`) — исторически он
обслуживал ещё и рендер формы в Filament (`Rendering\FilamentTemplateRenderer`, `@deprecated`).
Сервис давно не имеет UI-слоя вообще (`Http/Controllers` в проекте нет), так что от DSL осталась
только одна реальная задача — типизированный снимок формы `details`.

- **`Domain/ModelData/`** — `AbstractDetailsData` (общий тип, `extends Spatie\LaravelData\Data`,
  без implementation) + 4 конкретные формы (`WiperDetailsData`, `SparkPlugDetailsData`,
  `OilFilterDetailsData`, `AirFilterDetailsData`) и их вложенные части (`WiperFrontDetailsData`,
  `SparkPlugThreadDetailsData`, …). Чистые объекты-значения: только конструктор с полями, **ни
  одного метода** — ни сборки, ни рендера. Порядок свойств конструктора = порядок колонок Excel.
- **`Application/Factories/`** — пара классов, симметричных друг другу:
  - `DetailsDataFactory::buildFromRow(DetailTemplateEnum, array $row, int &$index): AbstractDetailsData`
    строит форму из Excel-строки (замена `Import\DetailsBuilder`). Возвращает типизированный
    объект, не `array` — вызывающий сам решает, вызывать ли `->toArray()` (нужно перед записью в
    `PartSpecificationData::$details`).
  - `DetailsDataPresenter::headingsFor()`/`::toExportCells()` — обратное направление (замена
    `Export\ExportDetailsBuilder`): рендерит уже сохранённый `details`-массив в плоский набор
    Excel-ячеек/заголовков.
  - `DetailsRowCursor` — стейтфул-хелпер чтения строки (держит `row`+позицию), которым
    `DetailsDataFactory` пользуется для вложенной сборки без протаскивания `int &$index` через
    каждый приватный метод.
- Хранимый ключ select-полей — `case->name` соответствующего enum'а из `Domain/Enums/{Filter,
  SparkPlug,Wiper}/*` (не `->value` — это Excel-лейбл). Перевод в обе стороны даёт
  `EnumHelperTrait::fromLabel()/fromName()` (интерфейс-маркер `EnumHelperInterface` — не порт для
  DI, а подсказка статическому анализу для `$enumClass::fromLabel(...)`, где `$enumClass` —
  `class-string`).

### `Templates` как Shared Kernel

`Templates` — единственный полноценный shared-kernel проекта. Он общий не потому, что туда удобно
сложить переиспользуемый код, а потому что форма `details` является общим доменным контрактом между
импортом, экспортом, складом, транспортом и применяемостью.

Публичная поверхность `Templates`:

- `Domain/ModelData` — typed snapshots формы `details`;
- `Domain/Enums` — словари шаблонов и полей;
- `Domain/Contracts` — порты сервисов/фабрик/презентеров, которые реально вызываются другими
  context'ами;
- публичные Application-реализации, доступные только через соответствующие contracts.

Правила изменения:

- изменение структуры `details`, порядка export/import колонок или enum-значений считается
  cross-context изменением;
- перед таким изменением проверяем затронутые сценарии `Vehicles Import`, `Vehicles Export`,
  `Warehouse Import/Export`, `Applicability`;
- feature-specific правила не переносим в `Templates`, пока их реально не используют несколько
  context'ов;
- `Templates` не должен зависеть от `Vehicles`, `Warehouse` или `Applicability`.

### Глоссарий домена

Глоссарий фиксирует ubiquitous language проекта. Он пока минимальный и должен расширяться по мере
уточнения домена:

| Термин | Значение |
|---|---|
| `Vehicle` | Модель/поколение автомобиля в каталоге Vehicles. |
| `Engine` | Двигатель и связанные характеристики/группы двигателя. |
| `Modification` | Модификация автомобиля, связывающая модель, тип и двигатель. |
| `PartSpecification` | Спецификация детали для автомобиля/двигателя с полиморфной формой `details`. |
| `Nomenclature` | Складская товарная позиция. |
| `Kit` | Набор складских номенклатур. |
| `Type` | Тип складской номенклатуры/набора. |
| `Applicability` | Применяемость складского комплекта к автомобилю/модификации. |
| `Template` / `details` | Типизированная форма характеристик детали, общая для import/export. |
| `MoySklad` | Внешняя складская система; внутри проекта это под-контекст Warehouse-интеграции. |

---

## 1. Domain фичи — `<Feature>/Domain`

Бизнес-сердце фичи. Без фреймворковой инфраструктуры (Eloquent-модель сюда **не** входит —
она в `Infrastructure/Models`, см. §3).

| Папка | Что лежит | Толщина / правила |
|---|---|---|
| `Contracts/<Concern>/` | **Порты** (интерфейсы) для boundary-зависимостей фичи | Плоско по концерну: `Commands/`, `Repositories/`, `Exports/`, `Factories/`, `Notifications/`, `Services/<Entity>/`. `UseCases/` в `Domain/Contracts` не заводим: use case — concrete application entrypoint, а не порт. `Imports/` — единственный концерн, который дробится **по триггеру**, не по сущности: `Imports/Command/` (запускает консольный TecDoc-каскад, сигнатура `import(string $path): void`, без контекста вызова) и `Imports/External/` (запускает импорт по внешнему RabbitMQ-запросу на конкретный файл; общий предок `Imports/External/FileImportInterface::import(string $path, ImportRunContextDTO $context, ?string $disk = null): void` — контекст и disk явные, `Excel` сам читает файл с указанного disk). Порт всегда в Domain (стрелка внутрь), реализация — в своём слое. |
| `ModelData/` | **`<Entity>Data extends Spatie\LaravelData\Data`** | Плоская папка. Работает в обе стороны: вход `Command` (запись) и выход `Repository` (чтение). Enum-поля — реального enum-типа (пакет кастует туда-обратно). Вложенные связи — только те, что реально читаются, и только когда Repository их eager-load'ит (не через `#[LoadRelation]` — риск бесконечного цикла на двусторонних связях). |
| `DTOs/` | **Транспортные объекты сценариев** (вход/выход, payload, контекст) | `final readonly`. Не повторяют модель. Плоско — сквозные DTO сценария (`ImportRunContextDTO`, `ExternalImportFileRequestDTO`, `ExternalImportFileCleanupDTO`, `ImportCompletionNotificationDTO`, `AssignEngineGroupResultDTO`); по сущности — `DTOs/<Entity>/` там, где на сущность несколько построчных DTO (`Engine/EngineSheetRowDTO`, `Vehicle/VehicleTdRowDTO`, `Manufacturer/ManufacturerTdRowDTO`, …) — тот же принцип группировки, что у `Services/`/`Factories/`. **`ImportRunContextDTO`** (`userId` + `operationId`) — явный контекст запуска для `Imports/External/*` (не для консольного TecDoc-каскада — у него нет внешнего инициатора вообще): `userId` заменяет неявный `Auth::id()` (источник вызова — HTTP/Rabbit — всегда знает, кто просит), `operationId` — основа cache-ключа отчёта об ошибках, идемпотентности и межсервисной корреляции. `runId` не используем как внешний correlation id; он допустим только как внутренний runtime key старого workflow, если такой key реально остался в коде. |
| `Enums/` | Фиче-специфичные enum'ы потоков | Напр. схемы листов `InOut/Sheets/*`. Общий словарь значений — в `Shared`, не здесь. |
| `Events/` | Доменные события фичи | Plain DTO-события (`final readonly`), **без поведения**. Имя — **факт в прошедшем времени БЕЗ суффикса `Event`** (`VehicleImportCompleted`). По умолчанию события лежат плоско в `Domain/Events`; если в CRUD-фиче на каждую сущность есть набор однотипных фактов (`Created`/`Updated`/`Deleted`) и это не дробление на под-фичи, допустима группировка `Domain/Events/<Entity>/`. События не сериализуются напрямую наружу, wire-контракт — explicit `*NotificationDTO` (Listener вручную собирает DTO из полей события перед публикацией, см. `ReportImportResultListener`). |

DTO conversion rule: `toArray()`/`fromArray()` допустимы в DTO только для механической
сериализации собственного состояния: scalar casts, enum `->value`, прямые вложенные DTO arrays.
Validation, defaults, config lookup, HTTP/RabbitMQ aliases, Eloquent/paginator/external payload
mapping и сборка из нескольких объектов остаются в Presentation/Infrastructure factory,
presenter или adapter.

**`Templates/Domain`** дополнительно держит декларацию формы `details` — `ModelData/`
(`AbstractDetailsData` + 4 конкретные формы), `Enums/` (`DetailTemplateEnum` + словарные enum'ы
полей) и `Traits/EnumHelperTrait` — см. §0. Сборка/рендер этой формы (`DetailsDataFactory`/
`DetailsDataPresenter`) и доменное правило хранения дворников (`WiperSpecificationService`) —
поведение, оно в `Templates/Application`, не здесь.

> **Domain = полная декларация того, что делает фича**: порты + `Data` + DTO + enum'ы + события
> (+ шаблоны у Templates). Без оркестрации/IO. Application/Infrastructure реализуют поведение.

---

## 2. Application фичи — `<Feature>/Application`

Оркестрация и поведение. `UseCases` — concrete Application entrypoints без интерфейсов в
`Domain/Contracts`; Presentation, handlers и listeners могут инжектить конкретный use case класс.
Интерфейсы в `Domain/Contracts` оставляем для ports: repositories, commands, services, factories,
imports/exports, notifications, public clients и других boundary-зависимостей.

| Папка | Что лежит | Толщина / правила |
|---|---|---|
| `Services/<Entity>/` и `Services/<Group>/` | **Основной строительный блок.** Прикладные правила и координация портов | `Upsert*FromRowService`, `AssignEngineGroupService`, `ReportImportResultService`, `EngineModificationReadinessGate` (gate-логика) и т.п. Порт обязателен (`Contracts/Services/<Entity>/`). |
| `UseCases/<Group>/` | **Точка входа сценария**, вызываемая внешним триггером | `execute(...)`. Concrete orchestrator, который дёргают Presentation/Listener/consumer (напр. `External/StartExternalFileImportUseCase` — старт импорта по внешнему запросу). Интерфейс в `Domain/Contracts/UseCases` не заводим. Use case сам зависит от domain ports; для внутренних правил хватает `Service`. |
| `Factories/` (плоско) | **Application-фабрики без concrete adapter selection**: (1) валидация + сборка `<Entity>Data` из typed row DTO; (2) сборка/рендер типизированной формы по enum (пара Factory+Presenter) | (1) Публичных методов может быть несколько, если у сущности несколько разных typed row DTO/источников: например `makeFromTdRow(<Entity>TdRowDTO $row)` и `makeFromSheetRow(<Entity>SheetRowDTO $row)`. Каждый метод принимает конкретный DTO своего формата; сырой Excel/CSV row (`array<int, mixed>`) заканчивается в `Infrastructure/Imports/*/Mappers`: adapter/mapper читает колонки, нормализует scalar-значения, явно проставляет контекст источника (`provider`, `allow_change_fields`, `operation_id`, generated IDs, если они являются частью формата) и возвращает typed row DTO. UseCase/Application service не принимает сырой row array и не решает, какой provider поставить. Application factory валидирует значения из DTO через Laravel Validator/`Rule::enum(...)`, приводит типы до `strict_types` конструктора `Data` и возвращает `<Entity>Data`. Factory не выбирает adapter по строковому режиму/названию файла; различие сценариев выражается типом DTO и именем метода. (2) **Factory+Presenter пара**: `DetailsDataFactory::buildFromRow(TypeEnum, array $row, int &$index): AbstractDetailsData` (сборка из строки, `match` по enum вызывает приватный сборщик на каждую ветку — это реальная построчная сборка) + симметричный `DetailsDataPresenter::toExportCells()/headingsFor()` (обратное направление, рендер в Excel-ячейки). Общий механический хелпер чтения строки (сдвиг индекса, перевод label↔name, `;`-джойн) — не в самих формах `Data`, а в отдельном стейтфул-классе (`DetailsRowCursor`), т.к. это поведение, не декларация (см. §0). Выбор конкретных import/export adapters по enum/типу запроса реализуется в `Infrastructure/Factories` или provider closure за портом `Contracts/Factories/`. |
| `Listeners/` | Слушатели доменных событий | **ТОНКИЕ.** Делегируют в Service/UseCase. **Порт НЕ нужен** (см. ниже). |

Уточнение по selector-фабрикам: Application-фабрика может оставаться доменным портом, но выбор
конкретного Infrastructure adapter-а по enum/типу входящего запроса реализуется в
`Infrastructure/Factories` или provider closure. Application не должен содержать `match` по
конкретным Excel adapter-классам и не должен знать их runtime constructor-параметры.

**Слушателям порт в Domain НЕ нужен** — в отличие от всего остального в Application. Порт есть
ради инверсии зависимости для того, что код **зовёт наружу** (или что подменяют/мокают в DI).
Слушатель — **точка входа**: его дёргает диспетчер, он сам зовёт Service/UseCase; от него внутри
никто не зависит (ссылается только `*EventServiceProvider` картой событие→класс). «Контракт»
слушателя — само событие в `Domain/Events`.

**Слушатели остаются в Application, не уезжают в Infrastructure.** Критерий слоя — тяжесть
завязки на внешний мир: тонкая in-process реакция на `Domain/Events` → Application
(`StartVehicleImportListener`, `EngineModificationReadinessSubscriber`); адаптер на границе
интеграции (Excel, брокер) → Infrastructure.

In-process domain facts отправляем через Laravel helper `event(new DomainFact(...))`, это текущий
стиль проекта. `Illuminate\Contracts\Events\Dispatcher` инжектим только если нужен специфичный
контракт dispatcher-а, подписчик или тестовый boundary; для обычной публикации факта он лишний.

**Слушатели — сколько на событие и нейминг:**
- Реакции независимы → **несколько** слушателей, по одному на реакцию (свой queued-job/failure-
  домен). Имя — **по действию**: `<Action>Listener` (`StartVehicleImportListener`).
- Реакции связны (порядок / общая транзакция) → **один** слушатель → один UseCase/Service-
  оркестратор. Не размазываем `A(); B(); C();` по слушателю.
- Один слушатель на **одно** событие → имя по событию: `<Event>Listener`.
- Слушатель **нескольких структурно разных** событий (разные обработчики на каждое) через
  `subscribe()` → `…Subscriber` (`EngineModificationReadinessSubscriber`), тоже в
  `Application/Listeners/`.
- Несколько **родственных** событий (общая форма — базовый `abstract readonly` класс в
  `Domain/Events`, напр. `AbstractImportCompleted` с `userId`/`cacheKey`/`operationId`) с **одной и
  той же** реакцией → **не** `Subscriber` и не N копий: один action-named `Listener` с одним
  `handle()`, забинженный на каждое родственное событие своим вызовом `Event::listen(<Event>::
  class, <Listener>::class)` в `<Feature>EventServiceProvider` (`ReportImportResultListener`,
  `CleanupExternalImportFileListener` — оба висят на `VehicleImportCompleted` /
  `EngineImportCompleted` / `EngineCrossImportCompleted`). Критерий выбора: `subscribe()` — когда
  обработчик **разный** на каждое событие; повтор `Event::listen` — когда обработчик один и тот
  же и событиям достаточно общего родителя-DTO.

---

## 3. Infrastructure фичи — `<Feature>/Infrastructure`

Реализация портов: Eloquent-модели, БД, файлы, брокер, Excel-адаптеры (`maatwebsite/excel`).

| Папка | Что лежит | Правила |
|---|---|---|
| `Models/` | **Eloquent-модели фичи** (своя копия набора сущностей) | **АНЕМИЧНЫЕ**: связи, `$casts`, `$timestamps`. Без бизнес-логики. Наследуют `AbstractModel` (`guarded = []`; запись идёт через Command+`Data`, фиксированный набор полей → mass-assignment безопасен). Deдуп по фичам: Import — все 8 сущностей (Command пишет во все), Export — только 5 читаемых, Maintenance — только 4. Relation-методы на **недублированные** сущности убираем (иначе мина: `Class::class` на несуществующий класс падает при первом вызове связи). |
| `Repositories/` | **Чтение** (CQRS-lite) | `<Entity>Repository` реализует `Contracts/Repositories/<Entity>RepositoryInterface`. Внутри `<Entity>Data::from($model)` — отдаёт **`Data`**, не модель. Возврат из Repository: `?<Entity>Data`, `Illuminate\Support\Collection<int, <Entity>Data>` или, для больших потоковых выборок, `Generator<int, <Entity>Data>`. Под `Collection` в портах/сервисах всегда понимается **Support Collection**, не `Illuminate\Database\Eloquent\Collection`; результат Eloquent `get()` сразу конвертируется через `<Entity>Data::collect($items, Collection::class)`. Узкие scalar read methods вроде `exists`, `count`, `nextId` допустимы, если это атомарное чтение для сценария без бизнес-логики и записи; не выносим их в отдельный service только ради формы. Потоковые методы инкапсулируют чанковое чтение внутри репозитория (`lazyById`, ручной цикл `chunkById`/`where id > lastId`) и наружу делают `yield` по `Data`, чтобы не грузить всю таблицу и не отдавать Eloquent. Только запросы, без записи. (У Export есть, у Maintenance нет.) |
| `Commands/` | **Запись** (CQRS-lite) — только в фичах, где запись является частью сценария | `<Entity>Command` реализует `Contracts/Commands/<Entity>CommandInterface`. Принимают **`<Entity>Data`**. `save`/`upsert`/`delete`. `update`/`delete` принимают `Data` с обязательным `id` (identity вместо живого объекта). Из payload на запись исключают поля, которые не колонки (`Arr::except` для `engines`/`groupId` и т.п.). У read-only фич (`Export`) `Command` не заводим. |
| `Factories/` | Adapter selector/builder реализации | Здесь живёт выбор конкретного Excel import/export adapter-а по enum/типу запроса и runtime-параметрам. Application видит только domain-порт фабрики; `match` по конкретным adapter-классам и `makeWith(...)` остаются в Infrastructure/provider boundary. |
| `Imports/<Entity>/` | Адаптеры импорта (`maatwebsite/excel`) — **только Import** | Механика чтения: `Excel::import`, чанки, `onFailure`. На каждую строку зовёт построчный **Service** (Application). Точка входа реализует порт `Contracts/Imports/<X>Interface`. Sub-sheet'ы — внутренние, создаём `app()->makeWith(...)`, не `new`. |
| `Exports/<Entity>/` | Адаптеры экспорта — Export + `ImportFailureReporter`/`FailuresExport` в Import (отчёт об ошибках) | Источник — Repository; сборка строк — `Application/Services/Rows|Expanders`. Точка входа реализует порт `Contracts/Exports/<X>Interface`. |
| `Notifications/` | Внешние уведомления (**исходящий** адаптер брокера) | Напр. `RabbitMqFileNotificationService` → `FileNotificationServiceInterface`; внутри — напрямую `PkmStudio\RabbitTransport\RabbitMQPublisher` (Infra→Infra, отдельный порт-обёртка не нужен). |
| `Messaging/Handlers/` + `Messaging/Validators/` | Адаптер **входящих** сообщений брокера — симметрично `Notifications/` | `<Event>Handler::handle(array $data)`: валидирует payload через `Messaging/Validators/<Event>PayloadValidator` (Laravel `Validator`, допустимые значения enum-поля payload — из `<Enum>::cases()`, не литералом), собирает `DTO`, зовёт `UseCase`. Ошибка валидации → `Log::error` + `return` (сообщение просто дропается, не исключение — брокер не должен ретраить по бизнес-невалидности). |
| `Providers/` | DI и события фичи | `<Feature>ServiceProvider` (биндинги `Interface::class => Impl::class`), `ImportEventServiceProvider` (карта событие→слушатель). |

**Порт — в `Domain/Contracts/<Concern>/`, реализация — в Infrastructure.** Расположение
зеркальное: `Contracts/Repositories/VehicleRepositoryInterface` ↔ `Repositories/VehicleRepository`.

Command method naming: если ключ поиска/identity уже лежит в `<Entity>Data`, метод называется по
действию (`create(Data)`, `update(Data)`, `delete(Data)`), а не по ключу (`updateByMsId`,
`updateByEngId`, `updateById`). `updateByX/deleteByX` допустимы только когда `X` передается
отдельным scalar-аргументом и это реально другой контракт записи, а не detail текущего Data.

Queued Excel imports не получают services/repositories/mappers через constructor, если это не
scalar/DTO/value state самого import run. Зависимости резолвятся worker-time lazy getter-ами
(`service()`, `rowMapper()`, `templates()`), которые кешируют instance на время обработки. Если в
свойствах нет container-resolved dependency graph, `__serialize()`/`__unserialize()` не нужны
только ради сброса service/mapper; их оставляем лишь для реального сериализуемого состояния
(`context`, `cacheKey`, `lockKey`).

### Import row DTO → EntityData

Для всех импортов поток один:

```text
Infrastructure import adapter
  -> Infrastructure row mapper
  -> Domain DTOs/<Entity>/<Entity>*RowDTO
  -> Application factory makeFrom*Row(<Entity>*RowDTO)
  -> Domain ModelData/<Entity>Data
  -> Infrastructure Command
```

Правила:

- `Infrastructure/Imports/*/Mappers` — единственное место, где читается сырой Excel/CSV row array
  и конкретные индексы колонок.
- Индексы колонок в mapper/import parser не пишем голыми числами (`$row[0]`, `$row[1]`). Каждая
  читаемая колонка объявляется как `private const int <COLUMN_NAME> = <index>` и используется через
  `$row[self::<COLUMN_NAME>]`. Это фиксирует контракт файла рядом с adapter-ом и делает сдвиг
  колонок явным diff-ом.
- Mapper валидирует жесткие требования формата файла: обязательные колонки, обязательные source
  markers и enum-значения не превращаются в silent defaults. Если формат источника не содержит
  поле, но источник известен adapter-у (например TecDoc provider), adapter/mapper проставляет его
  явно.
- Row DTO может не быть чистой копией файла: он несет normalized row state и явный контекст
  источника, который не лежит в файле, но известен adapter'у. Например TecDoc engine import обязан
  проставить `provider=TD`, а manager/import-from-CRM row — `provider=OD`; если формат допускает
  отсутствие внешнего id, mapper/row DTO явно сообщает сервису, что можно сгенерировать
  отрицательный id.
- Application service/use case не получает `array $row`, не подставляет provider defaults и не
  выбирает validation-сценарий по названию import adapter-а. Он вызывает factory и command.
- Application factory может иметь несколько публичных методов по числу разных typed row DTO
  (`makeFromTdRow(<Entity>TdRowDTO $row)`, `makeFromSheetRow(<Entity>SheetRowDTO $row)` и т.п.).
  Каждый метод валидирует DTO своего явного формата/контекста и возвращает `<Entity>Data`; factory
  не принимает raw row array и не выбирает сценарий по строковому `mode`.
- `<Entity>Data` не подставляет ownership defaults (`provider`, владелец источника, разрешенные
  поля изменения). Если поле обязательно для записи, оно обязательно в constructor.
- DB schema не должна придумывать ownership/source defaults для сущностей, где источник данных
  является бизнес-фактом. Для `engines.provider` и `modifications.provider` колонка обязательна,
  но без database default; `TD`/`OD` задает import/catalog adapter явно.

Сервисы вида `Upsert<Entity>FromRowService` работают с уже нормализованной строкой импорта. Если
для конкретного источника есть отдельная бизнес-логика, допустим специализированный сервис
`Upsert<Entity>FromTdRowService`; если различие только в колонках/явном контексте (`provider`,
`operation_id`, generated id policy), оно остается в mapper/row DTO, а сценарий записи остается
одним.

**RabbitMQ** — вынесен в пакет `pkmstudio/rabbit-transport` (не свой модуль). Конфиг —
`config/rabbit-transport.php` (exchange `application.events`, очередь `vehicles.inbox`, DLQ).
Inbound — `Messaging/Handlers/ImportFileRequestedHandler` → `StartExternalFileImportUseCase`;
outbound — `Notifications/RabbitMqFileNotificationService` на завершение импорта.

Rabbit config заполняем через enum-контракты `dan-wire-contracts`
(`<Context>EventName::<Case>->value`, `<Context>RoutingKey::<Case>->value`), а не строковыми
литералами. Outbound result payload соответствует wire DTO из пакета; не отправляем наружу
произвольные local arrays. Человекочитаемый текст уведомления формирует consumer (`dan-center`),
а `dan-vehicles` публикует machine-readable status/counters/report path/error fields.

REST CRM read API — отдельный read-only boundary для `dan-center`, а не место записи каталога.
Routes группируем по сущностям и возможностям, без агрегированного "умного" контроллера на все:
`VehicleCrmController`, `ManufacturerCrmController`, `EngineCrmController`, ... Контроллеры тонкие
и ходят в concrete read use cases/query services; запись идет только через catalog mutation
Rabbit/write workflow. HTTP response shape (`snake_case`, envelope, pagination meta/options) живет
в presenters/response DTO на Presentation/Infrastructure boundary, а не в Domain `ModelData`.
Доступ к CRM read API защищает service-key middleware.

### Идемпотентность и отложенная очистка внешнего импорта — через cache, не БД

Внешний запрос на импорт (`Imports/External/*`) не должен запускаться дважды на один `operationId`
(повтор сообщения брокера) и должен подчистить исходный файл — но только **после** того как
импорт реально закончится, а до этого момента импорт мог уйти в отдельные queued-job'ы: живого
объекта-владельца, который бы просто подождал и удалил файл в `finally`, не существует. Оба
аспекта — через `ExternalImportCacheServiceInterface`, не через БД/состояние процесса:
- `accept(request): bool` — атомарный `Cache::add` по `operationId`; `false` = дубликат, `UseCase`
  тихо выходит без повторного запуска импорта.
- `forgetAccepted(operationId)` — снять отметку принятого `operationId` при ошибке импорта, чтобы повтор
  сообщения из брокера мог попробовать снова (иначе один сбойный прогон навсегда блокирует этот
  `operationId`).
- `rememberCleanup(request)` / `pullCleanup(operationId)` — сохранить/забрать `disk`+`path` файла;
  забирает и удаляет файл `CleanupExternalImportFileListener` на `AbstractImportCompleted` —
  когда завершение уже наступило, вне зависимости от того, в каком job'е.

Cache-ключи и TTL — **не строковые литералы в коде**, а шаблоны в `config/vehicles/import.php`
(`external.cache.keys.{accepted,cleanup}`, принимают `operationId` через `sprintf`); тот же принцип —
для ключей блокировки отчёта об ошибках (`failures.cache.keys.*`).

### Расчет применяемости — chunked queue flow поверх cache, не БД

Внешний запрос `APPLICABILITY_CALCULATION_REQUESTED` запускает расчет применяемости комплектов
как request/result workflow с `operation_id`, а не как синхронный долгий use case:

- `Infrastructure/Messaging/Handlers/CalculationRequestedHandler` валидирует payload и ставит
  `DispatchKitApplicabilityCalculationJob`;
- dispatcher читает активные Warehouse kits через существующий read boundary
  `WarehouseKitClientInterface::activeKits(?int $kitId, int $chunk)`, собирает ids и режет их на
  chunk jobs. Без `kit_id` рассчитываются все активные комплекты; с `kit_id` — один комплект
  (это extension point для точечного пересчета, но создание/обновление комплекта пока не запускает
  его автоматически);
- runtime-state запуска (`chunks`, counters, affected ids, errors, finalization marker,
  idempotency markers завершенных chunks) хранится через Laravel Cache/Redis в
  `ApplicabilityCalculationRunProgress`. Отдельные таблицы `applicability_calculation_runs` /
  `applicability_calculation_chunks` не заводим, потому state нужен только для координации jobs и
  очищается после финализации;
- `CalculateKitApplicabilityChunkJob` вызывает существующий `CalculateKitApplicabilityUseCase`
  для каждого kit из своего чанка без промежуточной публикации внешнего result event;
- последний завершившийся chunk атомарно резервирует finalization через cache marker и ставит
  `FinalizeKitApplicabilityCalculationJob`;
- finalizer собирает aggregate result, публикует один `KitApplicabilityRecalculated` /
  `APPLICABILITY_CALCULATION_COMPLETED`, пишет failure report при ошибках и очищает runtime
  cache-state. Ошибочные запуски не сохраняются в отдельных таблицах; детали ошибок остаются в
  report notification и `warning`/`error` логах.

`failed()` callbacks у queued jobs могут резолвить infrastructure service через container
(`app(ApplicabilityCalculationRunProgress::class)`), потому Laravel вызывает `failed(Throwable)`
сам и не поддерживает dependency injection в эту точку. Это допустимое Infrastructure-исключение,
не Application-паттерн.

### Логирование production-кода

В обычном production-коде не используем `Log::info`, `Log::debug`, `logger()->info`,
`logger()->debug`, `$this->logger->info` или `$this->logger->debug`. `$this->info()` в Artisan
commands — console output, не production log, и под этот запрет не попадает.

Actionable события:
- invalid external/broker payload, missing external file, failed import/export/sync/calculation →
  `warning`/`error` с устойчивыми context keys (`operation_id`, provider/source ids,
  file path/disk, entity id; `run_id` только для legacy flows, где он реально существует);
- provider ownership conflict — ошибка строки/сценария (`ImportRowValidationException` или
  профильное domain exception), без автоматической смены источника данных;
- обычный successful path, skipped duplicate, mask locked-field update, completed calculation/import
  — без логов, если результат уже выражен result DTO/event/notification.

### Rendezvous двух параллельных веток импорта — Gate поверх cache

Когда два независимых потока импорта (двигатели/модификации) должны сойтись перед следующим
шагом, а порядок их завершения не гарантирован — состояние-«кто уже готов» хранит **cache**, а
не сам `Listener`/`Subscriber` (оба остаются `final readonly` без своего состояния, см. «DI —
вариант А»): `<X>ReadinessGate` инкапсулирует флаги-cache (`markXImported()` →
`cache->forever(flag)` → если выставлены оба флага — `reset()` флагов + `dispatch` синтетического
события готовности), `<X>ReadinessSubscriber` — тонкая транслирующая прослойка входных событий в
вызовы `Gate` (`EngineModificationReadinessGate` + `EngineModificationReadinessSubscriber` →
`EnginesAndModificationsReady`). Порт — только у `Gate` (`Contracts/Services/`); `Subscriber` —
листенер, порт ему не нужен (см. §2).

Если cache-флаг/ключ является частью observable контракта gate-сервиса или нужен тестам/другим
слоям, константа живет на port/interface (`FLAG_ENGINES`, `FLAG_MODIFICATIONS`), а не private const
в реализации. Реализация отвечает за конкретный Laravel Cache/Redis доступ.

### `partable_type` без `Relation::morphMap()`

Полиморфный дискриминатор `part_specifications.partable_type` хранит **стабильное имя**
`PartableTypeEnum::VEHICLE = 'vehicle'` / `::ENGINE = 'engine'`, не FQCN (копий модели несколько,
глобальный `morphMap` пришлось бы делать «канонической» одну копию — тихое восстановление
межфичевой связанности). Резолв владельца — типобезопасный, в самом Repository:
`PartSpecificationRepository::partable(PartSpecificationData): VehicleData|EngineData|null`
(`match` по `PartableTypeEnum`). Связь-`MorphTo` `partable()` на модели **убрана** (без `morphMap`
она бы падала на `'vehicle'`). Где `partSpecifications()` (`MorphMany`) реально нужна (Export) —
`getMorphClass()` переопределён на стабильную строку, иначе связь молча вернула бы 0 строк.

---

## 4. Presentation фичи — `<Feature>/Presentation`

Точки входа. **Максимально тонкие.**

| Папка | Что лежит | Правила |
|---|---|---|
| `Console/Commands/` | Artisan-команды фичи | Парсят аргументы → зовут UseCase/Service. Без бизнес-логики. Import: `TecDocImportCars`. Maintenance: `UpdateVehicleYears`, `DeduplicatePartSpecificationsCommand`, … |
| `Http/Controllers/` | Контроллеры | Тонкие: валидация → UseCase → ответ. (Общей Presentation-папки на весь домен нет — только внутри фичи.) |

Регистрация команд — через `bootstrap/app.php` `->withCommands([...])`.

---

## 5. Сквозные правила

### Интерфейсы только для ports
Интерфейс заводим для boundary-зависимостей, которые Application вызывает через порт:
Repository, Command, Import, Export, Factory, Service, Notification, public Client и другие
заменяемые adapters. Они биндятся в `<Feature>ServiceProvider` картами
`Interface::class => Impl::class` (`COMMAND_BINDINGS`, `SERVICE_BINDINGS`,
`FACTORY_BINDINGS`, …).

`UseCases` — concrete Application entrypoints. Для них не создаем
`Domain/Contracts/UseCases/*Interface.php`, не добавляем `USE_CASE_BINDINGS` и не пишем
`implements *UseCaseInterface`. Presentation, Messaging/Handlers, Jobs и Application listeners
могут инжектить конкретный use case класс напрямую; сам use case зависит от domain ports.

**Listeners/Subscriber и Messaging/Handlers** тоже не интерфейсим: все они точки входа, которых
зовёт внешний диспетчер (событийная шина / брокер-транспорт по имени класса из конфига пакета), а
не код фичи через порт. Контракт слушателя — само событие в `Domain/Events`; контракт `Handler` —
форма сообщения, которую проверяет его `Validator` (`Messaging/Validators/`, тоже без порта —
вспомогательный класс одного `Handler`, не подменяемая точка расширения). `Data`/`Model`/`DTO`/
`Enum`/`Event` — это **значения**, их не интерфейсим.

**Maintenance-исключение:** разовые artisan-команды Maintenance могут инжектить конкретный
Application-сервис напрямую, если этот сервис не является расширяемой точкой фичи и не вызывается
другим кодом через контейнерный порт. Это та же прагматика, что и прямой Eloquent в Maintenance:
одноразовый фикс держим простым, не превращая его в полноценный вертикальный срез.

> Это осознанно строже, чем «порт только driven-адаптерам»: цена — много интерфейсов, выгода —
> любой класс подменяем/мокаем единообразно, DI однороден.

### Application × модели
- Application **не видит Eloquent-модель вообще**: Repository отдаёт `<Entity>Data`,
  `Illuminate\Support\Collection<int, <Entity>Data>` или `Generator<int, <Entity>Data>`, Command
  принимает `<Entity>Data`. Живой модели, которую можно случайно `->save()` в обход Command, в
  Application не существует — это гарантируется типами, а не соглашением (`spatie/laravel-data`).
- Никакого инлайн `Model::query()`/`where`/`updateOrCreate`/`->save()` в Application/Domain.
  Чтения → Repository-порт, записи → Command-порт (вход `Data`).
- Исключение — `Maintenance`: у неё нет слоя Repository, разовые фиксы ходят в Eloquent напрямую
  (осознанно, это не общий цикл импорта/экспорта).

### DI — «вариант А»
- Реальная конструкторная инъекция: зависимости — промотированные `private readonly`-параметры.
- Экземпляры — через контейнер: `app(...)`, `app()->makeWith(...)`. **Не `new`** для классов с
  зависимостями.
- `app()`, `makeWith()`, `event()` и `config()` сами по себе не являются долгом. Долг — когда за
  ними Application начинает владеть выбором конкретного Infrastructure adapter-а, external API
  shape или runtime constructor-параметрами.
- Биндинги интерфейс→реализация — в `<Feature>ServiceProvider`.
- **`final readonly class` для всех stateless-классов с инъекцией** (Repositories, Commands,
  Services, UseCases, Factories, тонкие Listeners). Своего мутируемого состояния нет →
  корректно сериализуются для очередей. Легитимное изменяемое состояние (если появится) — не
  `readonly`, и это повод задуматься, не тянет ли класс на другое.

### Naming
- Сущности: `Vehicle`, `Engine`, `Modification`, `Manufacturer`, `EngineModification`,
  `PartSpecification`, `Feature`, `FeatureValue`.
- Группировка по сущности — где на сущность несколько файлов (`Imports/<Entity>/`,
  `Services/<Entity>/`, `DTOs/<Entity>/`). `ModelData/` и `Application/Factories/` — плоские
  папки (по одному файлу-фабрике на сущность/сценарий, группировать нечего). Где файл один
  (`Repositories/`, `Commands/`) — тоже плоско. Единственное исключение по **триггеру**, не по
  сущности — `Domain/Contracts/Imports/{Command,External}/` (см. §1).
- Read = `…Repository`, Write = `…Command`, снимок = `…Data`, порт = `…Interface`.
- **Service** — глагольная фраза + суффикс: `UpsertVehicleFromRowService`. Публичный метод
  называется по конкретному действию сценария (`upsertFromRow`, `assignGroup`, `markEnginesImported`);
  `execute()` не обязателен для service.
- **UseCase** — глагольная фраза + суффикс `UseCase`, публичный `execute()`
  (`StartExternalFileImportUseCase`). Заводим для сценариев с внешним триггером.
- Listener/Subscriber — см. §2.
- **`Abstract<Noun>` — префикс только для абстрактных классов** (`AbstractImportCompleted`,
  `AbstractDetailsData`, `AbstractModel`), никогда для интерфейсов. Разные сигналы: `Abstract` —
  «здесь есть реальный наследуемый код, просто неполный»; суффикс `Interface` — «здесь кода нет
  вообще, чистый контракт». Навешивать оба маркера на один тип (`AbstractFooInterface`) —
  дублировать один и тот же смысл; сколько реализаций/наследников у типа — не критерий выбора
  (интерфейс с одним имплементором — всё ещё интерфейс, абстрактный класс с одним наследником —
  всё ещё абстрактный класс). Базовый класс Eloquent-моделей фичи — тоже по этому правилу:
  `AbstractModel` (`Infrastructure/Models/`), не `BaseModel` — единый стиль для всех абстрактных
  классов домена, без отдельной конвенции для моделей.

### Enum через casts
Колонки в миграциях — `string` с `->comment('XxxEnum')`; преобразование — в `$casts` модели.
`enum()` в миграциях не используем. `Data`-поля, зеркалящие enum-cast колонки, — реального
enum-типа (`spatie/laravel-data` кастует туда-обратно сам).

### Трейты (политика)
- `Templates/Domain/Traits/EnumHelperTrait` (`fromLabel`/`fromName` — статический поиск по
  `self::cases()`, без состояния; допустим в Domain, т.к. это не оркестрация, а расширение
  самого enum'а до того, чем он по сути является — двусторонний словарь значение↔лейбл, сродни
  `WiperSideEnum::adapterField()`), `Import/Infrastructure/Traits/CachesImportFailures` (своё
  состояние `$cacheKey`/`$lockKey` + поведение).
- Трейт допустим для **чистого самодостаточного** поведения без скрытого контракта с хостом.
  Крупная логика / переиспользуемые мапперы / «скрытый контракт» → сервис за портом, не трейт.
  Стейтфул-поведение уровня сценария (держит состояние поверх нескольких полей/вызовов, может
  бросать исключение при нарушении бизнес-правила) → отдельный класс в Application (пример —
  `DetailsRowCursor`), не трейт и не Domain.

### Грабли
- **`tryFrom` молча даёт `null`.** Нельзя `Enum::tryFrom($row)?->value` до валидации — невалидное
  значение тихо станет `null`. Валидируем **сырое** значение через `Rule::enum(...)`, маппинг в
  enum — в casts модели.
- **Relation-методы на недублированные в фиче сущности** — мина: строка-класс не падает при
  объявлении, только при первом вызове связи. Убирать.
- **Backed enum не приводится к строке через `(string)`** — использовать `->value`.

### Тестовая стратегия

- Feature-тесты — основной уровень для бизнес-сценариев: handlers/use cases/DB/Excel/files/cache,
  create/update/delete/reject/idempotency/cascade/export-row outcomes.
- Unit-тесты оставляем для чистых правил и дорогих в feature-сценарии edge cases: Templates
  details factories/presenters, kit composition, packaging strategies, wiper/applicability
  extraction, row validation и narrow architecture regressions.
- Не держим пустые Laravel examples, тесты статического дублирования config без контрактной
  ценности и brittle tests, которые проверяют только точный порядок `shouldReceive()` вызовов
  repositories/commands. Если ветка важна бизнесу, переносим её в feature-тест или выделяем
  маленький pure rule test.

### Документирование: докблок у класса
**Над каждым классом — обязательный `/** */`**, кратко описывающий, что это за класс и что он
делает (1–3 строки, по существу — не пересказ имени класса).

```php
/**
 * Строит типизированный снимок формы `details` из строки Excel по типу шаблона.
 */
final readonly class DetailsDataFactory implements DetailsDataFactoryInterface
```

### Документирование: докблок у метода, с шагами
**Над каждым методом — обязательный `/** */`**: что метод делает, а если внутри несколько
логических шагов — блок `Шаги:` с пронумерованным списком.

```php
/**
 * Собирает AbstractDetailsData из строки Excel по enum-типу шаблона.
 *
 * Шаги:
 * 1) Определить конкретный класс формы по DetailTemplateEnum.
 * 2) Прочитать значения полей построчно через DetailsRowCursor.
 * 3) Собрать и вернуть типизированный объект формы.
 */
public function buildFromRow(DetailTemplateEnum $template, array $row, int &$index): AbstractDetailsData
```

### Запрет вычислений прямо внутри вызова/условия
Нельзя писать `$this->class->method(new SomeClass())` — создание объекта аргументом прямо в
вызове. **Исключения — `event(new SomeEvent(...))` и `$this->onFailure(new Failure(...))`.**
Во всех остальных случаях `new SomeClass()` выносится в отдельную переменную перед вызовом:

```php
// плохо
$this->service->handle(new SomeClass($a, $b));

// хорошо
$someClass = new SomeClass($a, $b);
$this->service->handle($someClass);

// исключения
event(new VehicleImportCompleted($operationId));
$this->onFailure(new Failure($row, 'ТС', $errors, $values));
```

То же правило действует для вычисляемых условий и коротких callbacks: результат вызова метода,
сложный предикат, mapper/filter callback или стрелочный callback сначала получает имя в локальной
переменной, затем передаётся в `if`, `map`, `array_map`, `filter`, `first`, `Cache::remember` и т.п.
Блоковые closures, которые задают scope (`DB::transaction(function (...) { ... })`,
`where(function (...) { ... })`, provider `implementation: function (...) { ... }`), допустимы inline,
если внутри них несколько действий и отдельное имя не улучшает читаемость.

```php
// плохо
if ($validator->fails()) {
    return;
}

$names = array_map(fn (ItemData $item): string => $item->name, $items);

// хорошо
$validationFailed = $validator->fails();

if ($validationFailed) {
    return;
}

$toName = fn (ItemData $item): string => $item->name;
$names = array_map($toName, $items);
```

### Вызов с несколькими параметрами — всегда многострочно, именованными аргументами
Если метод/конструктор принимает несколько параметров, вызов пишется **многострочно**, каждый
аргумент на своей строке, **именованными аргументами**:

```php
$this->service->execute(
    value: $value,
    value2: $value2,
);
```

---

## 5.1. Известные архитектурные долги

Этот раздел фиксирует расхождения, которые уже обнаружены и должны быть закрыты отдельными
изменениями. Он не отменяет целевые правила выше.

К исправлению в ближайших итерациях:

- Докблоки классов/методов, inline `new DTO(...)` в вызовах и многострочные именованные аргументы
  нужно привести к правилам §5 постепенно, без больших шумных форматирующих PR.
- Для новых queued Excel imports нужны обязательные serialization regression tests:
  `serialize($import)` и сериализация каждого listener из `registerEvents()`.

---

## 6. Куда класть новое — шпаргалка

Сначала выбери **фичу** (`Import`/`Export`/`Maintenance`/`Templates`), потом слой.

| Хочу добавить… | Кладу в… |
|---|---|
| Общий enum-словарь значений (cast колонки) | `Shared/Domain/Enums/` (не дублировать по фичам) |
| Новый шаблон/поле формы `details` (`PartSpecification`) | `Data`-класс (`extends AbstractDetailsData`, только поля) → `Templates/Domain/ModelData/<Entity>/<X>DetailsData.php` (+ enum-словарь поля в `Templates/Domain/Enums/`, если select); сборку из строки — в `DetailsDataFactory`, рендер в ячейки — в `DetailsDataPresenter` (`Templates/Application/Factories/`), не в самом `Data`-классе |
| Новый сценарий с внешним триггером (старт импорта по запросу) | `<Feature>/Application/UseCases/<Group>/` concrete class; без `Domain/Contracts/UseCases` |
| Новое прикладное правило/координацию | `<Feature>/Application/Services/<Entity>/` + порт `Domain/Contracts/Services/<Entity>/` |
| Валидацию + сборку `<Entity>Data` | `<Feature>/Application/Factories/` (плоско, один публичный метод на конкретный typed row DTO: `makeFromTdRow(...)`, `makeFromSheetRow(...)` и т.п.) + порт `Contracts/Factories/`; raw row array остается в `Infrastructure/Imports/*/Mappers` |
| Выбор import/export adapter-а по enum/типу входящего запроса | порт `Contracts/Factories/` + реализация в `<Feature>/Infrastructure/Factories/` или provider closure |
| Реакцию на доменное событие | `<Feature>/Application/Listeners/` (тонко, **без порта**) |
| Новый запрос к БД | порт → `Domain/Contracts/Repositories/`, адаптер → `Infrastructure/Repositories/` (отдаёт `Data`, `Collection<Data>` или `Generator<Data>` для потокового чтения) |
| Новую запись в БД | если запись является ответственностью фичи: порт → `Domain/Contracts/Commands/`, адаптер → `Infrastructure/Commands/` (вход `Data`); в read-only фичах `Command` не заводим |
| Снимок строки / транспорт | `Domain/ModelData/` (`extends Data`) или `Domain/DTOs/` (транспорт сценария) |
| Доменное событие | `Domain/Events/` (обычно плоский `final readonly`, факт в прошедшем времени); для CRUD-наборов допустимо `Domain/Events/<Entity>/` |
| Импорт из Excel (консольный, без внешнего инициатора) | адаптер → `Import/Infrastructure/Imports/<Entity>/` + построчный Service; порт → `Contracts/Imports/Command/` |
| Импорт из Excel по внешнему запросу (`userId`/`operationId`/`disk` снаружи) | адаптер implements `Contracts/Imports/External/FileImportInterface`; DTO-контекст → `ImportRunContextDTO` |
| Входящую команду от брокера (RabbitMQ) | `Import/Infrastructure/Messaging/Handlers/` (+ `Messaging/Validators/`) → зовёт `UseCase` |
| REST CRM read API | `Catalog/Presentation/Http/Controllers/<Entity>CrmController` → read use case/query service → presenter/response DTO; read-only, под service-key middleware |
| Экспорт в Excel | адаптер → `Export/Infrastructure/Exports/<Entity>/`; порт → `Contracts/Exports/`; сборка строк → `Export/Application/Services/Rows|Expanders/` |
| Queued Excel import | adapter остаётся в `Infrastructure/Imports`; constructor/saved state только scalar/DTO/value state; services/repositories/clients/loggers резолвятся во время job; listeners без closures |
| Внешнее уведомление/интеграцию | порт → `Domain/Contracts/Notifications/`, реализация → `Infrastructure/Notifications/` (внутри — пакет rabbit-transport) |
| Разовый фикс каталога | `Maintenance/` (команда в `Presentation/Console/Commands/` + `Application/Services/`; Eloquent напрямую, без Repository) |
| Artisan-команду / HTTP-эндпоинт | `<Feature>/Presentation/Console/Commands/` или `Http/Controllers/` (тонко) |
| Новую копию Eloquent-модели для фичи | `<Feature>/Infrastructure/Models/` (только сущности, которые фича реально трогает) |
