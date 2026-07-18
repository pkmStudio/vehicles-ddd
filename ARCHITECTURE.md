# Архитектура `dan-vehicles`

Справочник: **что куда класть, что должно быть тонким, что к какому слою/фиче относится и почему.**

> Актуализация 2026-07-18: бизнес-модули перенесены в `app/Modules/*`. `Templates` живёт как
> отдельный shared-kernel модуль, а `Warehouse` и `Vehicles` имеют module-level `Shared/` с
> публичными событиями и общей инфраструктурой
> (`Shared/Infrastructure/Database/Migrations`, `Shared/Infrastructure/Providers`). Межфичевые
> синхронные вызовы идут через локальные `Domain/Contracts/Clients/*ClientInterface` фичи-потребителя
> и adapter в её `Infrastructure/Clients`; чужие service/factory/presenter-контракты в
> `Application` не импортируем. Детальный план и список выполненных переносов см. `refactor-ms.md`.

Раскладка — **module-first + feature-first**. Бизнес-модули живут в `app/Modules/*`.
У доменных модулей (`Vehicles`, `Warehouse`) сначала выбираем фичу в `Features/*`
(`Catalog`, `Import`, `Export`, `Maintenance`, `Packaging`, `KitProperties`, ...), внутри фичи —
слой: **Domain → Application → Infrastructure → Presentation**. Общий модуль `Templates` пока
не дробится на `Features/`, потому что внутри него нет нескольких самостоятельных фич.

> История перехода layer-first → feature-first, переход на `spatie/laravel-data`, удаление
> общей `Domain/Models` и т.п. зафиксированы в `plan.md` (§1–§3, §11). Здесь — только **целевое
> состояние конвенций**, а не путь к нему.

Правило зависимостей (Dependency Rule) действует **внутри каждой фичи**: стрелки внутрь.

```
Presentation ──▶ Application ──▶ Domain
       │              │            ▲
       └─────▶ Infrastructure ─────┘
```

- **Domain** — декларации фичи: Contracts (порты) + ModelData (`spatie/laravel-data`) + DTOs +
  Enums (фиче-специфичные) + Events + Templates-декларации. Без фреймворковой инфраструктуры.
- **Application** знает только Domain своей фичи (+ Domain-контракты фичи `Templates`/`Shared`).
  Оркестрация и правила: Services, UseCases (точки входа), Factories, тонкие Listeners.
- **Infrastructure** реализует порты фичи: Eloquent-**Models**, Repositories, Commands,
  Excel-Imports/Exports, Notifications, Providers. Тащит фреймворк/внешний мир.
- **Presentation** — точки входа фичи (console, http), максимально тонкие, дёргают Application.

Нарушение, за которым следим: Domain/Application фичи **не импортируют** `Maatwebsite\Excel`,
конкретные пакеты брокера, фасады записи и т.п. — только через порты в `Domain/Contracts`.

### Module `Shared` и межфичевые границы

`Shared/` внутри `Warehouse` или `Vehicles` — публичная часть модуля, а не папка для удобного
складывания общего кода.

В `Shared` можно класть:

- `Domain/Events` — факты, которые должны слушать другие фичи этого же модуля;
- `Domain/Enums` — только enum'ы, которые являются wire/db-контрактом между фичами;
- `Infrastructure/Database/Migrations` и `Infrastructure/Providers/<Module>ServiceProvider.php` —
  module-level инфраструктуру, общую для всех фич модуля.

В `Shared` не кладём:

- `ModelData`;
- Eloquent-модели;
- repositories, commands, use cases, services;
- внутренние enum'ы конкретного workflow;
- события, которые используются только внутри одной фичи.

**ModelData остаётся локальным для фичи.** Даже если два `Data`-класса имеют одинаковые поля, это
совпадение контракта на границе, а не повод делать `Shared/Domain/ModelData`. При межфичевом
вызове adapter явно переводит DTO/Data фичи-потребителя в публичный контракт фичи-владельца.

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
- request/result workflow делается двумя событиями: request/fact event и отдельный result-event с
  `runId`/`correlationId`;
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
`Domain/Contracts/Clients/*ClientInterface`. Adapter в `Infrastructure/Clients` уже переводит этот
локальный язык в публичный API владельца (`Templates`, `KitProperties`, `Packaging`, ...).

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

**Почему feature-first, а Enums — в `Shared`:** фичи режем по способностям (Import/Export/…),
каждая независима и переезжаемая. Но enum'ы — это словарь значений колонок (`$casts`), а не
сервис и не модель с данными: дублировать их = риск рассинхрона схемы. Поэтому единая точка
истины в `Shared/`. Eloquent-модели, наоборот, **дублируются по фичам** (каждая фича — своя
копия в `Infrastructure/Models`), потому что это деталь реализации Repository/Command, и
независимость фич важнее отсутствия дублирования (осознанная плата — `plan.md §3`).

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

---

## 1. Domain фичи — `<Feature>/Domain`

Бизнес-сердце фичи. Без фреймворковой инфраструктуры (Eloquent-модель сюда **не** входит —
она в `Infrastructure/Models`, см. §3).

| Папка | Что лежит | Толщина / правила |
|---|---|---|
| `Contracts/<Concern>/` | **Порты** (интерфейсы) для всех инъектируемых классов фичи | Плоско по концерну: `Commands/`, `Repositories/`, `Exports/`, `Factories/`, `Notifications/`, `Services/<Entity>/`, `UseCases/<Group>/`. `Imports/` — единственный концерн, который дробится **по триггеру**, не по сущности: `Imports/Command/` (запускает консольный TecDoc-каскад, сигнатура `import(string $path): void`, без контекста вызова) и `Imports/External/` (запускает импорт по внешнему RabbitMQ-запросу на конкретный файл; общий предок `Imports/External/FileImportInterface::import(string $path, ImportRunContextDTO $context, ?string $disk = null): void` — контекст и disk явные, `Excel` сам читает файл с указанного disk). Порт всегда в Domain (стрелка внутрь), реализация — в своём слое. |
| `ModelData/` | **`<Entity>Data extends Spatie\LaravelData\Data`** | Плоская папка. Работает в обе стороны: вход `Command` (запись) и выход `Repository` (чтение). Enum-поля — реального enum-типа (пакет кастует туда-обратно). Вложенные связи — только те, что реально читаются, и только когда Repository их eager-load'ит (не через `#[LoadRelation]` — риск бесконечного цикла на двусторонних связях). |
| `DTOs/` | **Транспортные объекты сценариев** (вход/выход, payload, контекст) | `final readonly`. Не повторяют модель. Плоско — сквозные DTO сценария (`ImportRunContextDTO`, `ExternalImportFileRequestDTO`, `ExternalImportFileCleanupDTO`, `ImportCompletionNotificationDTO`, `AssignEngineGroupResultDTO`); по сущности — `DTOs/<Entity>/` там, где на сущность несколько построчных DTO (`Engine/EngineSheetRowDTO`, `Vehicle/VehicleTdRowDTO`, `Manufacturer/ManufacturerCommandRowDTO`, …) — тот же принцип группировки, что у `Services/`/`Factories/`. **`ImportRunContextDTO`** (`userId` + `runId`) — явный контекст запуска для `Imports/External/*` (не для консольного TecDoc-каскада — у него нет внешнего инициатора вообще): `userId` заменяет неявный `Auth::id()` (источник вызова — HTTP/Rabbit — всегда знает, кто просит), `runId` — а не `userId` — основа cache-ключа отчёта об ошибках и идемпотентности, чтобы конкурентные прогоны одного инициатора не затирали друг друга. |
| `Enums/` | Фиче-специфичные enum'ы потоков | Напр. схемы листов `InOut/Sheets/*`. Общий словарь значений — в `Shared`, не здесь. |
| `Events/` | Доменные события фичи | Plain DTO-события (`final readonly`), **без поведения**. Имя — **факт в прошедшем времени БЕЗ суффикса `Event`** (`VehicleImportCompleted`). По умолчанию события лежат плоско в `Domain/Events`; если в CRUD-фиче на каждую сущность есть набор однотипных фактов (`Created`/`Updated`/`Deleted`) и это не дробление на под-фичи, допустима группировка `Domain/Events/<Entity>/`. События не сериализуются напрямую наружу, wire-контракт — explicit `*NotificationDTO` (Listener вручную собирает DTO из полей события перед публикацией, см. `ReportImportResultListener`). |

**`Templates/Domain`** дополнительно держит декларацию формы `details` — `ModelData/`
(`AbstractDetailsData` + 4 конкретные формы), `Enums/` (`DetailTemplateEnum` + словарные enum'ы
полей) и `Traits/EnumHelperTrait` — см. §0. Сборка/рендер этой формы (`DetailsDataFactory`/
`DetailsDataPresenter`) и доменное правило хранения дворников (`WiperSpecificationService`) —
поведение, оно в `Templates/Application`, не здесь.

> **Domain = полная декларация того, что делает фича**: порты + `Data` + DTO + enum'ы + события
> (+ шаблоны у Templates). Без оркестрации/IO. Application/Infrastructure реализуют поведение.

---

## 2. Application фичи — `<Feature>/Application`

Оркестрация и поведение. **Каждый инъектируемый класс — за интерфейсом** из `Domain/Contracts`
(см. §5 «Интерфейс у каждого класса»).

| Папка | Что лежит | Толщина / правила |
|---|---|---|
| `Services/<Entity>/` и `Services/<Group>/` | **Основной строительный блок.** Прикладные правила и координация портов | `Upsert*FromRowService`, `AssignEngineGroupService`, `ReportImportResultService`, `EngineModificationReadinessGate` (gate-логика) и т.п. Порт обязателен (`Contracts/Services/<Entity>/`). |
| `UseCases/<Group>/` | **Точка входа сценария**, вызываемая внешним триггером | `execute(...)`. Оркестратор, который дёргают Presentation/Listener/consumer (напр. `External/StartExternalFileImportUseCase` — старт импорта по внешнему запросу). Тоже за портом (`Contracts/UseCases/`). Заводим, когда есть внешний триггер сценария; для внутренних правил хватает `Service`. |
| `Factories/` (плоско) | **Три вида фабрик**: (1) валидация + сборка `<Entity>Data` из сырой строки; (2) выбор существующей реализации по enum/типу входящего запроса; (3) сборка/рендер типизированной формы по enum (пара Factory+Presenter) | (1) `make(array $row): <Entity>Data`. Enum-поля валидируем **сырыми значениями** через `Rule::enum(...)`, без `tryFrom` (см. «Грабли»). Приведение типов (напр. `(string)`) — до передачи в `strict_types` конструктор `Data`. (2) **Selector-фабрика**: `make(<TypeEnum> $type): <PortInterface>` — только `match` по enum на уже забинженные в контейнере зависимости, никакой валидации/сборки (`ExternalFileImportFactory::make(ExternalImportTypeEnum): FileImportInterface` — какой Excel-адаптер запускать по типу из RabbitMQ-сообщения). (3) **Factory+Presenter пара**: `DetailsDataFactory::buildFromRow(TypeEnum, array $row, int &$index): AbstractDetailsData` (сборка из строки, `match` по enum вызывает приватный сборщик на каждую ветку — не просто выбор готовой зависимости, а реальная построчная сборка) + симметричный `DetailsDataPresenter::toExportCells()/headingsFor()` (обратное направление, рендер в Excel-ячейки). Общий механический хелпер чтения строки (сдвиг индекса, перевод label↔name, `;`-джойн) — не в самих формах `Data`, а в отдельном стейтфул-классе (`DetailsRowCursor`), т.к. это поведение, не декларация (см. §0). Все три вида — за портом в `Contracts/Factories/` (тоже плоско, без `<Entity>/`). |
| `Listeners/` | Слушатели доменных событий | **ТОНКИЕ.** Делегируют в Service/UseCase. **Порт НЕ нужен** (см. ниже). |

**Слушателям порт в Domain НЕ нужен** — в отличие от всего остального в Application. Порт есть
ради инверсии зависимости для того, что код **зовёт наружу** (или что подменяют/мокают в DI).
Слушатель — **точка входа**: его дёргает диспетчер, он сам зовёт Service/UseCase; от него внутри
никто не зависит (ссылается только `*EventServiceProvider` картой событие→класс). «Контракт»
слушателя — само событие в `Domain/Events`.

**Слушатели остаются в Application, не уезжают в Infrastructure.** Критерий слоя — тяжесть
завязки на внешний мир: тонкая in-process реакция на `Domain/Events` → Application
(`StartVehicleImportListener`, `EngineModificationReadinessSubscriber`); адаптер на границе
интеграции (Excel, брокер) → Infrastructure.

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
  `Domain/Events`, напр. `AbstractImportCompleted` с `userId`/`cacheKey`/`runId`) с **одной и
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
| `Repositories/` | **Чтение** (CQRS-lite) | `<Entity>Repository` реализует `Contracts/Repositories/<Entity>RepositoryInterface`. Внутри `<Entity>Data::from($model)` — отдаёт **`Data`**, не модель. Скалярные read-агрегаты (`minMsId(): int`) легитимны (`plan.md §12`). Только запросы, без записи. (У Export есть, у Maintenance нет.) |
| `Commands/` | **Запись** (CQRS-lite) — только в фичах, где запись является частью сценария | `<Entity>Command` реализует `Contracts/Commands/<Entity>CommandInterface`. Принимают **`<Entity>Data`**. `save`/`upsert`/`delete`. `update`/`delete` принимают `Data` с обязательным `id` (identity вместо живого объекта). Из payload на запись исключают поля, которые не колонки (`Arr::except` для `engines`/`groupId` и т.п.). У read-only фич (`Export`) `Command` не заводим. |
| `Imports/<Entity>/` | Адаптеры импорта (`maatwebsite/excel`) — **только Import** | Механика чтения: `Excel::import`, чанки, `onFailure`. На каждую строку зовёт построчный **Service** (Application). Точка входа реализует порт `Contracts/Imports/<X>Interface`. Sub-sheet'ы — внутренние, создаём `app()->makeWith(...)`, не `new`. |
| `Exports/<Entity>/` | Адаптеры экспорта — Export + `ImportFailureReporter`/`FailuresExport` в Import (отчёт об ошибках) | Источник — Repository; сборка строк — `Application/Services/Rows|Expanders`. Точка входа реализует порт `Contracts/Exports/<X>Interface`. |
| `Notifications/` | Внешние уведомления (**исходящий** адаптер брокера) | Напр. `RabbitMqFileNotificationService` → `FileNotificationServiceInterface`; внутри — напрямую `PkmStudio\RabbitTransport\RabbitMQPublisher` (Infra→Infra, отдельный порт-обёртка не нужен). |
| `Messaging/Handlers/` + `Messaging/Validators/` | Адаптер **входящих** сообщений брокера — симметрично `Notifications/` | `<Event>Handler::handle(array $data)`: валидирует payload через `Messaging/Validators/<Event>PayloadValidator` (Laravel `Validator`, допустимые значения enum-поля payload — из `<Enum>::cases()`, не литералом), собирает `DTO`, зовёт `UseCase`. Ошибка валидации → `Log::error` + `return` (сообщение просто дропается, не исключение — брокер не должен ретраить по бизнес-невалидности). |
| `Providers/` | DI и события фичи | `<Feature>ServiceProvider` (биндинги `Interface::class => Impl::class`), `ImportEventServiceProvider` (карта событие→слушатель). |

**Порт — в `Domain/Contracts/<Concern>/`, реализация — в Infrastructure.** Расположение
зеркальное: `Contracts/Repositories/VehicleRepositoryInterface` ↔ `Repositories/VehicleRepository`.

**RabbitMQ** — вынесен в пакет `pkmstudio/rabbit-transport` (не свой модуль). Конфиг —
`config/rabbit-transport.php` (exchange `application.events`, очередь `vehicles.inbox`, DLQ).
Inbound — `Messaging/Handlers/ImportFileRequestedHandler` → `StartExternalFileImportUseCase`;
outbound — `Notifications/RabbitMqFileNotificationService` на завершение импорта.

### Идемпотентность и отложенная очистка внешнего импорта — через cache, не БД

Внешний запрос на импорт (`Imports/External/*`) не должен запускаться дважды на один `runId`
(повтор сообщения брокера) и должен подчистить исходный файл — но только **после** того как
импорт реально закончится, а до этого момента импорт мог уйти в отдельные queued-job'ы: живого
объекта-владельца, который бы просто подождал и удалил файл в `finally`, не существует. Оба
аспекта — через `ExternalImportCacheServiceInterface`, не через БД/состояние процесса:
- `accept(request): bool` — атомарный `Cache::add` по `runId`; `false` = дубликат, `UseCase`
  тихо выходит без повторного запуска импорта.
- `forgetAccepted(runId)` — снять отметку принятого `runId` при ошибке импорта, чтобы повтор
  сообщения из брокера мог попробовать снова (иначе один сбойный прогон навсегда блокирует этот
  `runId`).
- `rememberCleanup(request)` / `pullCleanup(runId)` — сохранить/забрать `disk`+`path` файла;
  забирает и удаляет файл `CleanupExternalImportFileListener` на `AbstractImportCompleted` —
  когда завершение уже наступило, вне зависимости от того, в каком job'е.

Cache-ключи и TTL — **не строковые литералы в коде**, а шаблоны в `config/vehicles/import.php`
(`external.cache.keys.{accepted,cleanup}`, принимают `runId` через `sprintf`); тот же принцип —
для ключей блокировки отчёта об ошибках (`failures.cache.keys.*`).

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

### Интерфейс у каждого инъектируемого класса
В коде принята сильная версия: **порт есть у каждого класса, который резолвится из
контейнера** — Repository, Command, Import, Export, Factory, **Service, UseCase**, Notification.
Все биндятся в `<Feature>ServiceProvider` картами `Interface::class => Impl::class`
(`COMMAND_BINDINGS`, `SERVICE_BINDINGS`, `USE_CASE_BINDINGS`, …).

**Исключение — Listeners/Subscriber и Messaging/Handlers**: все они точки входа, которых зовёт
внешний диспетчер (событийная шина / брокер-транспорт по имени класса из конфига пакета), а не
код фичи через порт; сами ни от кого внутри не зависят. Контракт слушателя — само событие в
`Domain/Events`; контракт `Handler` — форма сообщения, которую проверяет его `Validator`
(`Messaging/Validators/`, тоже без порта — вспомогательный класс одного `Handler`, не
подменяемая точка расширения). `Data`/`Model`/`DTO`/`Enum`/`Event` — это **значения**, их не
интерфейсим.

**Maintenance-исключение:** разовые artisan-команды Maintenance могут инжектить конкретный
Application-сервис напрямую, если этот сервис не является расширяемой точкой фичи и не вызывается
другим кодом через контейнерный порт. Это та же прагматика, что и прямой Eloquent в Maintenance:
одноразовый фикс держим простым, не превращая его в полноценный вертикальный срез.

> Это осознанно строже, чем «порт только driven-адаптерам»: цена — много интерфейсов, выгода —
> любой класс подменяем/мокаем единообразно, DI однороден.

### Application × модели
- Application **не видит Eloquent-модель вообще**: Repository отдаёт `<Entity>Data`, Command
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
- **Service** — глагольная фраза + суффикс: `UpsertVehicleFromSheetService`. Единая точка входа —
  публичный `execute()`.
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

### Запрет `new` прямо внутри вызова метода
Нельзя писать `$this->class->method(new SomeClass())` — создание объекта аргументом прямо в
вызове. **Исключение — `event(new SomeEvent(...))`.** Во всех остальных случаях `new SomeClass()`
выносится в отдельную переменную перед вызовом:

```php
// плохо
$this->service->handle(new SomeClass($a, $b));

// хорошо
$someClass = new SomeClass($a, $b);
$this->service->handle($someClass);

// исключение — event() допускает new inline
event(new VehicleImportCompleted($runId));
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

## 6. Куда класть новое — шпаргалка

Сначала выбери **фичу** (`Import`/`Export`/`Maintenance`/`Templates`), потом слой.

| Хочу добавить… | Кладу в… |
|---|---|
| Общий enum-словарь значений (cast колонки) | `Shared/Domain/Enums/` (не дублировать по фичам) |
| Новый шаблон/поле формы `details` (`PartSpecification`) | `Data`-класс (`extends AbstractDetailsData`, только поля) → `Templates/Domain/ModelData/<Entity>/<X>DetailsData.php` (+ enum-словарь поля в `Templates/Domain/Enums/`, если select); сборку из строки — в `DetailsDataFactory`, рендер в ячейки — в `DetailsDataPresenter` (`Templates/Application/Factories/`), не в самом `Data`-классе |
| Новый сценарий с внешним триггером (старт импорта по запросу) | `<Feature>/Application/UseCases/<Group>/` + порт `Domain/Contracts/UseCases/` |
| Новое прикладное правило/координацию | `<Feature>/Application/Services/<Entity>/` + порт `Domain/Contracts/Services/<Entity>/` |
| Валидацию + сборку `<Entity>Data` | `<Feature>/Application/Factories/` (плоско, `make(array $row)`) + порт `Contracts/Factories/` |
| Выбор реализации по enum/типу входящего запроса | `<Feature>/Application/Factories/` (selector-фабрика, `make(Enum): Interface`) + порт `Contracts/Factories/` |
| Реакцию на доменное событие | `<Feature>/Application/Listeners/` (тонко, **без порта**) |
| Новый запрос к БД | порт → `Domain/Contracts/Repositories/`, адаптер → `Infrastructure/Repositories/` (отдаёт `Data`) |
| Новую запись в БД | если запись является ответственностью фичи: порт → `Domain/Contracts/Commands/`, адаптер → `Infrastructure/Commands/` (вход `Data`); в read-only фичах `Command` не заводим |
| Снимок строки / транспорт | `Domain/ModelData/` (`extends Data`) или `Domain/DTOs/` (транспорт сценария) |
| Доменное событие | `Domain/Events/` (обычно плоский `final readonly`, факт в прошедшем времени); для CRUD-наборов допустимо `Domain/Events/<Entity>/` |
| Импорт из Excel (консольный, без внешнего инициатора) | адаптер → `Import/Infrastructure/Imports/<Entity>/` + построчный Service; порт → `Contracts/Imports/Command/` |
| Импорт из Excel по внешнему запросу (userId/runId/disk снаружи) | адаптер implements `Contracts/Imports/External/FileImportInterface`; DTO-контекст → `ImportRunContextDTO` |
| Входящую команду от брокера (RabbitMQ) | `Import/Infrastructure/Messaging/Handlers/` (+ `Messaging/Validators/`) → зовёт `UseCase` |
| Экспорт в Excel | адаптер → `Export/Infrastructure/Exports/<Entity>/`; порт → `Contracts/Exports/`; сборка строк → `Export/Application/Services/Rows|Expanders/` |
| Внешнее уведомление/интеграцию | порт → `Domain/Contracts/Notifications/`, реализация → `Infrastructure/Notifications/` (внутри — пакет rabbit-transport) |
| Разовый фикс каталога | `Maintenance/` (команда в `Presentation/Console/Commands/` + `Application/Services/`; Eloquent напрямую, без Repository) |
| Artisan-команду / HTTP-эндпоинт | `<Feature>/Presentation/Console/Commands/` или `Http/Controllers/` (тонко) |
| Новую копию Eloquent-модели для фичи | `<Feature>/Infrastructure/Models/` (только сущности, которые фича реально трогает) |
