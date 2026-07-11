# Архитектура `dan-vehicles`

Справочник: **что куда класть, что должно быть тонким, что к какому слою/фиче относится и почему.**

Раскладка — **feature-first**. Домен живёт в `app/Vehicles/`, разбит на фичи; внутри каждой
фичи — 4 слоя: **Domain → Application → Infrastructure → Presentation**.

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

---

## 0. Карта фич — `app/Vehicles/`

| Папка | Что это | Полнота слоёв |
|---|---|---|
| `Shared/` | Общий «словарь» без поведения: enum'ы, на которые завязаны `$casts` (`Vehicle/*`, `Engine/*`, `PartableTypeEnum`, `ProviderEnum`) + `EnumHelperTrait`. **НЕ дублируется по фичам** (два разных `VehicleTypeEnum` = риск рассинхрона данных, а не просто лишний код). | только `Domain/` |
| `Templates/` | Декларации полей деталей (field-template) + резолвер шаблонов + `WiperSpecificationService`. Общая для Import/Export/Maintenance фича; они зависят на её **Domain-контракты**. | `Domain/` + `Application/` |
| `Import/` | Приём CSV/Excel → каталог. Единственная фича с записью (`Command`). | полный вертикальный срез |
| `Export/` | Каталог → Excel. **Только чтение** — `Repository` есть, `Command` нет. Дублирует лишь 5 сущностей, которые реально читает. | `Domain/Application/Infrastructure` |
| `Maintenance/` | Разовые фиксы каталога (артизан-команды). Без слоя `Repository` — читает/пишет напрямую через Eloquent, это осознанно для «разовых фиксов». | `Application/Infrastructure/Presentation` |

**Почему feature-first, а Enums — в `Shared`:** фичи режем по способностям (Import/Export/…),
каждая независима и переезжаемая. Но enum'ы — это словарь значений колонок (`$casts`), а не
сервис и не модель с данными: дублировать их = риск рассинхрона схемы. Поэтому единая точка
истины в `Shared/`. Eloquent-модели, наоборот, **дублируются по фичам** (каждая фича — своя
копия в `Infrastructure/Models`), потому что это деталь реализации Repository/Command, и
независимость фич важнее отсутствия дублирования (осознанная плата — `plan.md §3`).

> **Цена:** пока схема (миграции) одна на все копии, расхождения колонок между копиями
> обнаружатся только в рантайме, не на уровне БД. Это принятый компромисс.

---

## 1. Domain фичи — `<Feature>/Domain`

Бизнес-сердце фичи. Без фреймворковой инфраструктуры (Eloquent-модель сюда **не** входит —
она в `Infrastructure/Models`, см. §3).

| Папка | Что лежит | Толщина / правила |
|---|---|---|
| `Contracts/<Concern>/` | **Порты** (интерфейсы) для всех инъектируемых классов фичи | Плоско по концерну: `Commands/`, `Repositories/`, `Imports/`, `Exports/`, `Factories/`, `Notifications/`, `Services/<Entity>/`, `UseCases/`. Порт всегда в Domain (стрелка внутрь), реализация — в своём слое. |
| `ModelData/<Entity>/` | **`<Entity>Data extends Spatie\LaravelData\Data`** | Работает в обе стороны: вход `Command` (запись) и выход `Repository` (чтение). Enum-поля — реального enum-типа (пакет кастует туда-обратно). Вложенные связи — только те, что реально читаются, и только когда Repository их eager-load'ит (не через `#[LoadRelation]` — риск бесконечного цикла на двусторонних связях). |
| `DTOs/` | **Транспортные объекты сценариев** (вход/выход, payload, контекст, план потока) | `final readonly`. Не повторяют модель. Примеры: `ImportRunContext` (кто/какой прогон), `VehicleImportPlan`/`EngineImportPlan` (политика потока), `AssignEngineGroupResult`, `ExternalFileImportFileRequestDTO`. |
| `Enums/` | Фиче-специфичные enum'ы потоков | Напр. схемы листов `InOut/Sheets/*`. Общий словарь значений — в `Shared`, не здесь. |
| `Events/` | Доменные события фичи | Plain DTO-события (`final readonly`), **без поведения**. Имя — **факт в прошедшем времени БЕЗ суффикса `Event`** (`VehicleImportCompleted`). Все события пока в одной папке `Domain/Events` (деление на доменные/интеграционные — открытый вопрос, `plan.md §12`). |

**`Templates/Domain`** дополнительно держит `Templates/<Entity>/Templates/*` (декларативные
описания полей: `AirFilterTemplate`, `WiperTemplate`, …) и `Enums/DetailTemplateEnum`
(`templateClass()` резолвит FQCN шаблона). Общий DSL — в пакете `dan/field-templates`.
Templates остаётся фичей-декларацией именно потому, что `DetailTemplateEnum` ссылается на классы
Template: уедь Template в Application — доменный enum смотрел бы наружу.

> **Domain = полная декларация того, что делает фича**: порты + `Data` + DTO + enum'ы + события
> (+ шаблоны у Templates). Без оркестрации/IO. Application/Infrastructure реализуют поведение.

---

## 2. Application фичи — `<Feature>/Application`

Оркестрация и поведение. **Каждый инъектируемый класс — за интерфейсом** из `Domain/Contracts`
(см. §5 «Интерфейс у каждого класса»).

| Папка | Что лежит | Толщина / правила |
|---|---|---|
| `Services/<Entity>/` и `Services/<Group>/` | **Основной строительный блок.** Прикладные правила и координация портов | `Upsert*FromRowService`, `AssignEngineGroupService`, `ReportImportResultService`, `EngineModificationReadinessGate` (gate-логика), `Template/{TemplateDataBuilder,DetailsBuilder}`, `Engine/EngineEditableColumnsMapper` и т.п. Порт обязателен (`Contracts/Services/<Entity>/`). |
| `UseCases/<Group>/` | **Точка входа сценария**, вызываемая внешним триггером | `execute(...)`. Оркестратор, который дёргают Presentation/Listener/consumer (напр. `External/StartExternalFileImportUseCase` — старт импорта по внешнему запросу). Тоже за портом (`Contracts/UseCases/`). Заводим, когда есть внешний триггер сценария; для внутренних правил хватает `Service`. |
| `Factories/<Entity>/` | **Валидация + сборка `<Entity>Data` из сырой строки** | `make(array $row): <Entity>Data`. Enum-поля валидируем **сырыми значениями** через `Rule::enum(...)`, без `tryFrom` (см. «Грабли»). Приведение типов (напр. `(string)`) — до передачи в `strict_types` конструктор `Data`. За портом (`Contracts/Factories/`). |
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
- Слушатель **нескольких** событий через `subscribe()` → `…Subscriber`
  (`EngineModificationReadinessSubscriber`), тоже в `Application/Listeners/`.

---

## 3. Infrastructure фичи — `<Feature>/Infrastructure`

Реализация портов: Eloquent-модели, БД, файлы, брокер, Excel-адаптеры (`maatwebsite/excel`).

| Папка | Что лежит | Правила |
|---|---|---|
| `Models/` | **Eloquent-модели фичи** (своя копия набора сущностей) | **АНЕМИЧНЫЕ**: связи, `$casts`, `$timestamps`. Без бизнес-логики. Наследуют `BaseModel` (`guarded = []`; запись идёт через Command+`Data`, фиксированный набор полей → mass-assignment безопасен). Deдуп по фичам: Import — все 8 сущностей (Command пишет во все), Export — только 5 читаемых, Maintenance — только 4. Relation-методы на **недублированные** сущности убираем (иначе мина: `Class::class` на несуществующий класс падает при первом вызове связи). |
| `Repositories/` | **Чтение** (CQRS-lite) | `<Entity>Repository` реализует `Contracts/Repositories/<Entity>RepositoryInterface`. Внутри `<Entity>Data::from($model)` — отдаёт **`Data`**, не модель. Скалярные read-агрегаты (`minMsId(): int`) легитимны (`plan.md §12`). Только запросы, без записи. (У Export есть, у Maintenance нет.) |
| `Commands/` | **Запись** (CQRS-lite) — **только Import** | `<Entity>Command` реализует `Contracts/Commands/<Entity>CommandInterface`. Принимают **`<Entity>Data`**. `save`/`upsert`/`delete`. `update`/`delete` принимают `Data` с обязательным `id` (identity вместо живого объекта). Из payload на запись исключают поля, которые не колонки (`Arr::except` для `engines`/`groupId` и т.п.). |
| `Imports/<Entity>/` | Адаптеры импорта (`maatwebsite/excel`) — **только Import** | Механика чтения: `Excel::import`, чанки, `onFailure`. На каждую строку зовёт построчный **Service** (Application). Точка входа реализует порт `Contracts/Imports/<X>Interface`. Sub-sheet'ы — внутренние, создаём `app()->makeWith(...)`, не `new`. |
| `Exports/<Entity>/` | Адаптеры экспорта — Export + `ImportFailureReporter`/`FailuresExport` в Import (отчёт об ошибках) | Источник — Repository; сборка строк — `Application/Services/Rows|Expanders`. Точка входа реализует порт `Contracts/Exports/<X>Interface`. |
| `Notifications/` | Внешние уведомления | Напр. `RabbitMqFileNotificationService` → `FileNotificationServiceInterface`; внутри — напрямую `PkmStudio\RabbitTransport\RabbitMQPublisher` (Infra→Infra, отдельный порт-обёртка не нужен). |
| `Providers/` | DI и события фичи | `<Feature>ServiceProvider` (биндинги `Interface::class => Impl::class`), `ImportEventServiceProvider` (карта событие→слушатель). |

**Порт — в `Domain/Contracts/<Concern>/`, реализация — в Infrastructure.** Расположение
зеркальное: `Contracts/Repositories/VehicleRepositoryInterface` ↔ `Repositories/VehicleRepository`.

**RabbitMQ** — вынесен в пакет `pkmstudio/rabbit-transport` (не свой модуль). Конфиг —
`config/rabbit-transport.php` (exchange `application.events`, очередь `vehicles.inbox`, DLQ).
Реальные inbound/outbound-обработчики пока почти пусты — заводим по мере интеграций.

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
| `Console/Commands/` | Artisan-команды фичи | Парсят аргументы → зовут UseCase/Service. Без бизнес-логики. Import: `TecDocImportCars`. Maintenance: `ChangeProviderManufacturersToTD`, `UpdateVehicleYears`, `DeduplicatePartSpecificationsCommand`, … |
| `Http/Controllers/` | Контроллеры | Тонкие: валидация → UseCase → ответ. (Общей Presentation-папки на весь домен нет — только внутри фичи.) |

Регистрация команд — через `bootstrap/app.php` `->withCommands([...])`.

---

## 5. Сквозные правила

### Интерфейс у каждого инъектируемого класса
В коде принята сильная версия: **порт есть у каждого класса, который резолвится из
контейнера** — Repository, Command, Import, Export, Factory, **Service, UseCase**, Notification.
Все биндятся в `<Feature>ServiceProvider` картами `Interface::class => Impl::class`
(`COMMAND_BINDINGS`, `SERVICE_BINDINGS`, `USE_CASE_BINDINGS`, …).

**Единственное исключение — Listeners/Subscriber**: они точки входа (их зовёт диспетчер, сами
ни от кого внутри не зависят), контракт = само событие. `Data`/`Model`/`DTO`/`Enum`/`Event` —
это **значения**, их не интерфейсим.

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
  `ModelData/<Entity>/`, `Services/<Entity>/`, `Factories/<Entity>/`). Где файл один
  (`Repositories/`, `Commands/`) — плоско.
- Read = `…Repository`, Write = `…Command`, снимок = `…Data`, порт = `…Interface`.
- **Service** — глагольная фраза + суффикс: `UpsertVehicleFromSheetService`. Единая точка входа —
  публичный `execute()`.
- **UseCase** — глагольная фраза + суффикс `UseCase`, публичный `execute()`
  (`StartExternalFileImportUseCase`). Заводим для сценариев с внешним триггером.
- Listener/Subscriber — см. §2.

### Enum через casts
Колонки в миграциях — `string` с `->comment('XxxEnum')`; преобразование — в `$casts` модели.
`enum()` в миграциях не используем. `Data`-поля, зеркалящие enum-cast колонки, — реального
enum-типа (`spatie/laravel-data` кастует туда-обратно сам).

### Трейты (политика)
- `Shared/Domain/Traits/EnumHelperTrait` (логика enum'ов), `Import/Infrastructure/Traits/
  CachesImportFailures` (своё состояние `$cacheKey`/`$lockKey` + поведение).
- Трейт допустим для **чистого самодостаточного** поведения без скрытого контракта с хостом.
  Крупная логика / переиспользуемые мапперы / «скрытый контракт» → сервис за портом, не трейт.

### Грабли
- **`tryFrom` молча даёт `null`.** Нельзя `Enum::tryFrom($row)?->value` до валидации — невалидное
  значение тихо станет `null`. Валидируем **сырое** значение через `Rule::enum(...)`, маппинг в
  enum — в casts модели.
- **Relation-методы на недублированные в фиче сущности** — мина: строка-класс не падает при
  объявлении, только при первом вызове связи. Убирать.
- **Backed enum не приводится к строке через `(string)`** — использовать `->value`.

---

## 6. Куда класть новое — шпаргалка

Сначала выбери **фичу** (`Import`/`Export`/`Maintenance`/`Templates`), потом слой.

| Хочу добавить… | Кладу в… |
|---|---|
| Общий enum-словарь значений (cast колонки) | `Shared/Domain/Enums/` (не дублировать по фичам) |
| Описание полей детали (field-template) | `Templates/Domain/Templates/<Entity>/Templates/` |
| Новый сценарий с внешним триггером (старт импорта по запросу) | `<Feature>/Application/UseCases/<Group>/` + порт `Domain/Contracts/UseCases/` |
| Новое прикладное правило/координацию | `<Feature>/Application/Services/<Entity>/` + порт `Domain/Contracts/Services/<Entity>/` |
| Валидацию + сборку `<Entity>Data` | `<Feature>/Application/Factories/<Entity>/` (`make()`) + порт `Contracts/Factories/` |
| Реакцию на доменное событие | `<Feature>/Application/Listeners/` (тонко, **без порта**) |
| Новый запрос к БД | порт → `Domain/Contracts/Repositories/`, адаптер → `Infrastructure/Repositories/` (отдаёт `Data`) |
| Новую запись в БД (только Import) | порт → `Domain/Contracts/Commands/`, адаптер → `Infrastructure/Commands/` (вход `Data`) |
| Снимок строки / транспорт | `Domain/ModelData/<Entity>/` (`extends Data`) или `Domain/DTOs/` (транспорт сценария) |
| Доменное событие | `Domain/Events/` (плоский `final readonly`, факт в прошедшем времени) |
| Импорт из Excel | адаптер → `Import/Infrastructure/Imports/<Entity>/` + построчный Service; порт → `Contracts/Imports/` |
| Экспорт в Excel | адаптер → `Export/Infrastructure/Exports/<Entity>/`; порт → `Contracts/Exports/`; сборка строк → `Export/Application/Services/Rows|Expanders/` |
| Внешнее уведомление/интеграцию | порт → `Domain/Contracts/Notifications/`, реализация → `Infrastructure/Notifications/` (внутри — пакет rabbit-transport) |
| Разовый фикс каталога | `Maintenance/` (команда в `Presentation/Console/Commands/` + `Application/Services/`; Eloquent напрямую, без Repository) |
| Artisan-команду / HTTP-эндпоинт | `<Feature>/Presentation/Console/Commands/` или `Http/Controllers/` (тонко) |
| Новую копию Eloquent-модели для фичи | `<Feature>/Infrastructure/Models/` (только сущности, которые фича реально трогает) |