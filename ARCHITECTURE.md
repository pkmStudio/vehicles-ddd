# Архитектура `dan-vehicles`

Справочник: **что куда класть, что должно быть тонким, что к какому слою относится и почему.**
Домен живёт в `app/Vehicles/`. Внутри — 4 слоя: **Domain → Application → Infrastructure → Presentation**.

Правило зависимостей (Dependency Rule): стрелки внутрь.

```
Presentation ──▶ Application ──▶ Domain
       │              │            ▲
       └─────▶ Infrastructure ─────┘
```

- **Domain** — декларации домена: Eloquent-модели (только связи) + Contracts (порты) + ModelData + DTOs + Enums + Events + Templates + Services (чистые правила).
- **Application** знает только Domain. Оркестрация: UseCases, Factories, Services, тонкие Listeners/Jobs/Observers.
- **Infrastructure** реализует интерфейсы, знает Domain и Application, тащит фреймворк/внешний мир (БД, Excel, RabbitMQ, S3).
- **Presentation** — точки входа (console, http), максимально тонкие, дёргают Application.

Нарушение, за которым следим: Domain/Application **не импортируют** `Maatwebsite\Excel`, `PhpAmqpLib`, конкретные фасады записи и т.п. — только через порты-интерфейсы.

---

## 1. Domain — `app/Vehicles/Domain`

Бизнес-сердце. Без фреймворковой инфраструктуры.

| Папка | Что лежит | Толщина |
|---|---|---|
| `Models/` | Eloquent-модели | **АНЕМИЧНЫЕ**: только связи (`hasMany`/`belongsTo`/`morphTo`), `$casts`, `$timestamps`. Без бизнес-логики, без `$with`, без accessor/mutator. Наследуют `BaseModel` (`guarded = []`, unguarded) — `$fillable` не используем; mass-assignment безопасен, т.к. запись идёт через Command+ModelData (фиксированный набор полей), не из сырого ввода. Защитить колонку → переопределить `$guarded` в конкретной модели. |
| `Contracts/<Concern>/` | **Порты** (интерфейсы) для адаптеров Infrastructure | Группируются по инфра-концерну, зеркально `Infrastructure/`: `Contracts/Repositories/…`, `Contracts/Commands/…`, `Contracts/Imports/…` (поведенческие `import(path)`/`parse(path)`), `Contracts/Exports/…` (`download(fileName)`), `Contracts/Notifications/…`. В будущем — `Contracts/Messaging/`, `Contracts/Cache/`. Реализация — в Infrastructure. Без вложенной папки-сущности: на сущность ровно один Repository и один Command. **Почему в Domain:** порты — часть декларации домена («домен заявляет, как с ним работать»); Repository-порты возвращают Domain-модели, Command-порты принимают `ModelData` (тоже Domain) — всё ссылается внутрь. |
| `ModelData/<Entity>/` | **Отображения моделей** | `final readonly` + `toArray()`. **Без валидации в конструкторе.** Типизированный «снимок строки» для передачи в Command. Имя класса — `<Entity>Data`. Чистые данные → в Domain. |
| `DTOs/` | **Транспортные объекты сценариев** (вход/выход use-case, payload) | `final readonly`. Форма данных, которой обменивается приложение и которая **не повторяет модель** (в отличие от `ModelData`): результаты use-case, запросы/ответы сценариев. Пример: `AssignEngineGroupResult` (исход назначения группы — `found`/`reassigned`/`previousGroupId`). Имя — `<Smth>Result`/`<Smth>Input` и т.п. по смыслу. |
| `Enums/` | Backed-enum'ы (`type`, `template`, …) | Логика значений (label/маппинг) — здесь. `DetailTemplateEnum::templateClass()` резолвит FQCN класса шаблона. |
| `Events/` | Доменные события | Plain DTO-события (`final readonly`), **без поведения** (никаких `subscribe()`/`handle()` — это листенеры). `AbstractImportCompleted`, `EnginesAndModificationsReady` и т.д. Имя — **факт в прошедшем времени БЕЗ суффикса `Event`** (`VehicleImportCompleted`, а не `VehicleImportCompletedEvent`): это легитимное исключение из «суффикс по виду класса» — событие читается как факт домена, суффикс несёт потребитель (`…Listener`/`…Subscriber`). |
| `Templates/` | Описания шаблонов полей (field-template) | Декларативные классы. Общий DSL вынесен в пакет `dan/field-templates`. |
| `Services/` | **Доменные сервисы** | Чистые бизнес-правила над `ModelData`/`DTO`/Enum, без инфраструктуры (без Eloquent-query, без фасадов/IO). Пример: `WiperSpecificationService` (структура деталей дворника: detect/split/merge сторон). Query/IO — за порт. |

> **Domain = полная декларация того, что происходит в приложении**: модели + контракты (порты) + ModelData (снимки строк) + DTO (транспорт сценариев) + enums + события + шаблоны + доменные сервисы (чистые правила). Без оркестрации/IO. Application/Infrastructure/Presentation **оперируют** этими декларациями и реализуют поведение. Domain самодостаточен — переезжает папкой в отдельный сервис.

**Почему модели анемичные:** бизнес-логика разъезжается по слоям предсказуемо (Services/UseCases), модели остаются сериализуемыми и тестируемыми, нет «магии» автозагрузки.

**Enum через casts:** колонки в миграциях — `string` с `->comment('XxxEnum')`; преобразование — в `$casts` модели. В миграциях `enum()` не используем.

---

## 2. Application — `app/Vehicles/Application`

Оркестрация и поведение. Сценарии, фабрики, тонкие адаптеры событий. Порты и `ModelData` живут в Domain; Application их потребляет. **Excel-адаптеры импорта/экспорта — НЕ здесь, а в Infrastructure** (см. секцию 3): чтение/запись файлов — внешний мир.

Правило по слоям:
- `UseCase` — сценарный вход с точки входа внешнего мира (`execute(...)`): импорт, экспорт, пересчёт, отчёт.
- `Service` — оркестрация внутри Application: подготовка данных, правил, последовательностей шагов и вызов инфраструктурных портов.
- `Support` — только вспомогательные helper-классы (маппинг/форматирование/разделение данных), без бизнес-сценариев и без “оркестрации”.

### Группировка — по фиче (feature-first)
Внутри Application делим **по фиче (способности)**, не по техническому типу: `Application/<Feature>/...`. Сейчас фичей две: `Import/` и `Export/`. Внутри фичи — под-папки по типу там, где файлов несколько: `UseCases/`, `Factories/`, `Support/`, `Services/`, `Listeners/`.

```
Application/
  Import/
    UseCases/<Entity>/   UpsertEngineFromSheetUseCase, AssignEngineGroupUseCase, …
    UseCases/Reporting/  ReportImportResultUseCase (кросс-сущностный сценарий)
    Factories/<Entity>/  <Entity>DataFactory
    Support/             TemplateDataBuilder, DetailsBuilder, EngineMainSheetImportService
    Services/            EngineModificationReadinessGate (gate-логика готовности процесса)
    Listeners/           Start*ImportListener, EngineModificationReadinessSubscriber, ReportImportResultListener
  Export/
    Support/             ExportDetailsBuilder, VehicleExportRow, EngineExportRow, PartSpecificationRowExpander, WiperRowExpander
    Services/            VehicleExportService, EngineExportService
    ...
  Jobs/<Entity>/         InvalidateMpCards*Job        ⚠️ ещё не под фичей
  Observers/<Entity>/    EngineObserver, VehicleObserver ⚠️ ещё не под фичей
```

> ⚠️ **Расхождение (не доделано):** `Jobs/` и `Observers/` пока лежат плоско, не под фичей — целевая раскладка feature-first до них ещё не дошла. Привести к `Application/<Feature>/{Jobs,Observers}/` при переносе домена MpSale (на нём завязана инвалидация MP-карточек). Это TODO, а не утверждённое исключение.

> **Почему feature-first только в Application:** Domain — общее ядро (модели/порты/enum), делить его по фичам нельзя (один `EngineData` нужен и импорту, и экспорту). Infrastructure — адаптеры (`Imports/`, `Repositories/` — это инфра-сторона фич). Application — слой самих сценариев, поэтому его и режем по фичам. Фича «размазана» по слоям (use-case в Application, Excel-адаптер в Infra) — это нормально.

| Папка фичи | Что лежит | Толщина / правила |
|---|---|---|
| `<Feature>/UseCases/<Entity>/` | **Оркестраторы** одного сценария | Единый `execute()`. Координируют: `Factory->make()` (валидация+сборка `ModelData`) → дёргают `Command`/`Repository`/доменный Service. Без прямого Eloquent, без Excel. Пример: `Import/UseCases/Vehicle/UpsertVehicleFromSheetUseCase`. |
| `<Feature>/Factories/<Entity>/` | **Сборка `ModelData` из сырой строки** (`<Entity>DataFactory`) | `make(array $row): <Entity>Data` валидирует И собирает Data в одном месте. Один параметр-массив; вычисляемые вызывающим поля (`manufacturer_id`, `parent_id`) кладём тем же массивом (тем же ключом). Enum-поля валидируем **сырыми значениями** через `Rule::enum(...)`, без `tryFrom` (см. «Грабли»). |
| `<Feature>/Support/` | Helpers | Вспомогательные утилиты (маппинг, форматирование, разбиение/компоновка), без сценарной оркестрации и без бизнес-решений. |
| `<Feature>/Listeners/` | Слушатели доменных событий | **ТОНКИЕ.** Делегируют в UseCase/Service. **Порт в Domain НЕ нужен** (см. ниже). Нейминг и количество — см. ниже. |
| `<Feature>/Services/` | Бизнес-логика фичи | Прикладные правила/координация портов, которым тесно в одном use-case. Порт нужен, только если сервис «ходит наружу» (тогда это driven-порт в Domain). |
| `Jobs/<Entity>/` | Очередные задачи | **ТОНКИЕ.** `handle()` резолвит сервис/use-case и зовёт его. Состояние job'ы — сериализуемые скаляры/DTO. (Группируются по своей фиче, когда она появится.) |
| `Observers/<Entity>/` | Обсерверы моделей | **ТОНКИЕ.** Только реакция на события модели → делегат в Service/UseCase. |

**Слушателям/джобам/обсерверам/командам/контроллерам контракт (порт) в Domain НЕ нужен.** Порт существует ради инверсии зависимости — для того, что use-case **зовёт наружу** (Repository/Command/Import/Export/Notification = driven-адаптеры). Слушатель — наоборот, **точка входа**: его дёргает диспетчер, он сам зовёт use-case. От него никто внутри не зависит (ссылается только `EventServiceProvider` картой событие→класс — это проводка). «Контракт» события — само событие в `Domain/Events`.

**Слушатели/обсерверы остаются в Application, не уезжают в Infrastructure.** «Нет порта» ≠ «другой слой» (порт и слой — независимы; у контроллеров/команд порта тоже нет, а живут в Presentation). Критерий слоя — **насколько тяжело класс завязан на внешний мир**:
- **In-process реакция на `Domain/Events` / Eloquent-события, тонкая, дёргает use-case** → **Application** (`Start*ImportListener`, `EngineObserver`). Это оркестрация приложением собственного домена.
- **Адаптер на границе интеграции** (внешний транспорт/брокер/файлы: `maatwebsite/excel`, RabbitMQ-`InboxConsumer`) → **Infrastructure**.

**ModelData vs DTO (договорённость):**
- `Domain/ModelData/` = **отображение модели** (`VehicleData` ≈ строка таблицы `vehicles`). Это то, что мы передаём в Command на запись.
- `Domain/DTOs/` = **транспортные объекты сценариев**: вход/выход use-case (команды/запросы, результаты), payload событий. **Не обязаны** повторять модель. Лежат в **Domain** (а не Application): по нашей трактовке Domain декларирует все формы данных приложения; Application их потребляет/возвращает. Пример: `AssignEngineGroupResult`.

**Почему слушатели/джобы/обсерверы тонкие:** их трудно тестировать и переиспользовать. Вся логика — в UseCase/Service, адаптер только «принял сигнал → позвал сценарий».

**Слушатели — сколько на событие:**
- **Реакции независимы** (порядок не важен, нужна изоляция ретрая/очередей) → **несколько слушателей** на событие, по одному на реакцию. Каждый — свой queued-job, свой failure-домен, своя конфигурация очереди; реакции явно видны в `EventServiceProvider`. Пример: `ManufacturerCommandImported` → `StartVehicleImportListener` + `StartEngineImportListener`.
- **Реакции связны** (по порядку / B зависит от A / общая транзакция) → **один слушатель → один UseCase-оркестратор**, который и выстраивает шаги. Не размазываем процесс по слушателям и не пишем `A(); B(); C();` прямо в слушателе — оркестрацию в use-case (тестируется без событийного слоя).

**Слушатели — нейминг:**
- На событие **один** слушатель → имя **по событию**: `<Event>Listener`.
- На событие **несколько** слушателей → имя **по действию**: `<Action>Listener` (по событию назвать нельзя — коллизия).
- Класс, слушающий **несколько событий** через Laravel `subscribe()`, — это **`…Subscriber`** (`EngineModificationReadinessSubscriber`), тоже в `Application/Listeners/`. Сам он тонкий-координатор; событие-факт остаётся плоским DTO в `Domain/Events`.
- UseCase при этом всегда именуется по действию (`ReportImportResultUseCase`), слушатель/подписчик — по правилу выше.

---

## 3. Infrastructure — `app/Vehicles/Infrastructure`

Реализация портов: БД, брокер, файлы, нотификации, **и Excel-адаптеры импорта/экспорта** (`maatwebsite/excel`) — это внешний мир.

| Папка | Что лежит | Правила |
|---|---|---|
| `Imports/<Entity>/` | Адаптеры импорта (`maatwebsite/excel`) | **Механика чтения файла:** `Excel::import($this, $path)`, чанки, `onFailure`. На каждую строку зовёт построчный **UseCase** (Application), бизнес-логику в адаптере не держит. **Точка входа** реализует поведенческий порт `Domain/Contracts/Imports/<X>Interface` (`import(string $path): void`). Sub-sheet'ы — внутренние классы без порта. Зависимости — через конструктор; sub-sheet'ы создаём `app()->makeWith(...)`, не `new`. |
| `Exports/<Entity>/` | Адаптеры экспорта (`maatwebsite/excel`) | Источник данных — Repository; сборка строк — `Support/*ExportRow`. **Точка входа** реализует порт `Domain/Contracts/Exports/<X>Interface`. Sub-sheet'ы / `FailuresExport` — внутренние. |
| `Repositories/` | **Чтение** (CQRS-lite) | `<Entity>Repository` (реализует порт `Domain/Contracts/Repositories/<Entity>RepositoryInterface`). Только запросы, без записи. Без папки-сущности — один репозиторий на сущность. |
| `Commands/` | **Запись** (CQRS-lite) | `<Entity>Command` (реализует порт `Domain/Contracts/Commands/<Entity>CommandInterface`). Принимают **`ModelData`**, а не сырые массивы. Здесь `save`/`upsert`/`delete`. Без папки-сущности — одна команда на сущность. |
| `Support/` (нейтральный) | Технические helpers Infrastructure | Если есть общие инфраструктурные технические helpers, которые не относятся к конкретной фиче приложения, они могут жить здесь. Бизнес-правила и сборка доменных данных здесь не выполняются. |
| `Messaging/` | RabbitMQ | `Consumers/InboxConsumer`, `RabbitMQPublisher`, `Commands/` (setup-команды брокера), `Workers/CustomRabbitMQQueue`, `DTOs/RabbitMessageDTO`, `Enums/{Inbound,Outbound}EventsEnum`. Очереди: `vehicles` (jobs), `vehicles.inbox` (входящие события), exchange `application.events`. |
| `Notifications/` | Внешние уведомления | Реализации портов уведомлений (напр. `RabbitMqFileNotificationService` → `FileNotificationServiceInterface`). UI-нотификации идут событием в Filament-сервис; файлы ошибок → S3 + сообщение. |
| `Providers/` | DI и события | `VehiclesServiceProvider` (биндинги интерфейс→реализация по списку `ENTITIES`), `EventServiceProvider` (карта событие→слушатель). |

**Порт (интерфейс) живёт в `Domain/Contracts/<Concern>/`, адаптер (реализация) — в Infrastructure.** Так стрелка зависимости идёт внутрь: и Application, и Infrastructure зависят на порт в Domain. Расположение зеркальное: `Domain/Contracts/Repositories/VehicleRepositoryInterface` ↔ `Infrastructure/Repositories/VehicleRepository`. Биндинги порт→адаптер — в `VehiclesServiceProvider`.

> Порты в Domain согласованы: Repository-порты возвращают Domain-модели, Command-порты принимают `Domain/ModelData` — всё внутри Domain. Это пакетная связка: `Contracts` и `ModelData` лежат вместе в Domain (иначе Command-порт ссылался бы наружу).

---

## 4. Presentation — `app/Vehicles/Presentation`

Точки входа. **Максимально тонкие.**

| Папка | Что лежит | Правила |
|---|---|---|
| `Console/Commands/` | Artisan-команды домена | Парсят аргументы → зовут UseCase/Service. Без бизнес-логики. |
| `Http/Controllers/` | Контроллеры | Тонкие: валидация запроса → UseCase → ответ. |

Регистрация команд — в `bootstrap/app.php` через `->withCommands([...])` (папки `Messaging/Commands` и `Presentation/Console/Commands`).

---

## 5. Сквозные правила

### Application × модели (правило A)
- Application **может держать и читать** Domain-модели (это доменный тип данных; Repository их и возвращает) — передавать дальше, читать свойства, отдавать в Command.
- Application **не делает персистентность сам**: никакого инлайн `Model::query()`/`::where`/`firstOrCreate`/`updateOrCreate`/`->save()`. **Чтения → через Repository-порт, записи → через Command-порт** (вход — `ModelData`).
- Эталон — `UpsertVehicleFromSheetUseCase`: min-id/parent/резолв марки ушли в `VehicleRepository`/`ManufacturerRepository`/`ManufacturerCommand` за портами.
- «Всё на интерфейсах» = только про **поведение** (repo/command/import/export/notification). `Data`/`Model` — значения, их не интерфейсят.
- Почему A, а не строгий «Application без Eloquent»: один Eloquent/Postgres навсегда, нет тестов домена без БД, read-формы = таблицы → налог маппинга model↔Data не оправдан.

### DI — «вариант А»
- Реальная конструкторная инъекция: зависимости — параметры конструктора (`private Xxx $x`).
- Экземпляры получаем через контейнер: `app(...)`, `app()->makeWith(...)`. **Не `new`** для классов с зависимостями.
- Биндинги интерфейс→реализация — в `VehiclesServiceProvider` (цикл по `ENTITIES`).
- Сервисы — без состояния → корректно сериализуются для очередей.
- **`final readonly class` для всех stateless-классов с инъекцией** — Repositories, Commands, UseCases, Services, Support-сервисы, тонкие Listeners с зависимостями. Зависимости — только промотированные `private readonly`-параметры конструктора; своего мутируемого состояния нет. `readonly` гарантирует это на уровне языка (нельзя случайно завести изменяемое поле) и подчёркивает, что объект — чистый исполнитель. Исключение — классы с легитимным изменяемым состоянием (если появятся): тогда не `readonly`, и это повод задуматься, не тянет ли класс на что-то другое.

### Naming
- Сущности: `Vehicle`, `Engine`, `Modification`, `Manufacturer`, `PartSpecification`.
- Группировка по сущности — там, где на сущность несколько файлов: `Imports/<Entity>/`, `Exports/<Entity>/`, `ModelData/<Entity>/`, `UseCases/<Entity>/`, `Factories/<Entity>/`. Где файл один на сущность (`Repositories/`, `Commands/`) — плоско, без папки-сущности.
- Read = `…Repository`, Write = `…Command`, отображение = `…Data`.
- **UseCase = глагольная фраза + суффикс `UseCase`** (`UpsertVehicleFromSheetUseCase`, `ReportImportResultUseCase`): use-case — это действие системы, поэтому имя глагольное; суффикс несём по виду класса — единообразно со всеми остальными (`…Repository`, `…Command`, `…Validator`, `…Listener`, `…Data`), и зеркалит папку `UseCases`. Единая точка входа — публичный метод `execute()` (один use-case = одно действие = один публичный метод). Вызов: `$this->useCase->execute(...)`.
- Listener — см. раздел про слушателей (по событию / по действию).

### Трейты (политика)
- Лежат в `app/Vehicles/Traits/` (`EnumHelperTrait`, `CachesImportFailures`).
- Трейт допустим для **чистого, самодостаточного** поведения без скрытого контракта с хостом: `EnumHelperTrait` (логика enum'ов), `CachesImportFailures` (своё состояние + поведение).
- Поведение с «скрытым контрактом», крупная логика, переиспользуемые мапперы → **сервис/use-case** (инъекция), а не трейт.
  Уже переведено: `HasVehicleImportBaseData`→`UpsertVehicleFromSheet`, `BuildDetails`→`DetailsBuilder`, `BuildExportDetails`→`ExportDetailsBuilder`, `HasVehicleBaseData`/`HasEngineBaseData`→`VehicleExportRow`/`EngineExportRow`.

### Грабли
- **`tryFrom` молча даёт null.** Нельзя `Enum::tryFrom($row)?->value` до валидации — невалидное значение тихо станет `null`. Валидируем **сырое** значение через `Rule::enum(...)`, маппинг в enum — уже в casts модели.
- В DI-строках провайдера осторожно с двойными бэкслешами при sed-правках namespace.

### События
- Пока все события — в `Domain/Events`. Возможно позже разделение на доменные vs интеграционные (между сервисами). Решение отложено.

---

## 6. Куда класть новое — шпаргалка

| Хочу добавить… | Кладу в… |
|---|---|
| Новое бизнес-правило одной сущности | `Domain/Services/` (или метод-связь, если это связь) |
| Новый сценарий (импорт/пересчёт/…) | `Application/<Feature>/UseCases/<Entity>/` (напр. `Import/UseCases/Engine/`) |
| Новый запрос к БД | порт → `Domain/Contracts/Repositories/`, адаптер → `Infrastructure/Repositories/` |
| Новую запись в БД | порт → `Domain/Contracts/Commands/`, адаптер → `Infrastructure/Commands/` (вход — `ModelData`) |
| Новый порт (брокер/кеш/нотификация) | `Domain/Contracts/<Concern>/`, реализация → `Infrastructure/<Concern>/` |
| Типизированный снимок модели | `Domain/ModelData/<Entity>/` |
| Транспортный объект сценария (вход/выход use-case, payload) | `Domain/DTOs/` (отдельно от ModelData) |
| Чистое доменное правило | `Domain/Services/` |
| Валидацию + сборку `ModelData` | `Application/<Feature>/Factories/<Entity>/` (`make()`) |
| Реакцию на доменное событие | `Application/<Feature>/Listeners/` (тонко, **без порта в Domain**) |
| Фоновую задачу | `Application/Jobs/<Entity>/` (тонко) |
| Импорт из Excel | адаптер → `Infrastructure/Imports/<Entity>/` (механика) + построчный UseCase в `Application/Import/UseCases/<Entity>/`; порт точки входа → `Domain/Contracts/Imports/` |
| Экспорт в Excel | адаптер → `Infrastructure/Exports/<Entity>/`, порт точки входа → `Domain/Contracts/Exports/` |
| Подготовку данных/формата для экспорта | `Application/Export/Services/` |
| Вспомогательные helpers для импорта/экспорта | `Application/Import/Support/`, `Application/Export/Support/`; общий технический helper для обоих — в `Infrastructure/Support/` |
| Artisan-команду | `Presentation/Console/Commands/` (тонко) |
| HTTP-эндпоинт | `Presentation/Http/Controllers/` (тонко) |
