# Архитектура `dan-vehicles`

Справочник: **что куда класть, что должно быть тонким, что к какому слою относится и почему.**
Домен живёт в `app/Vehicles/`. Внутри — 4 слоя: **Domain → Application → Infrastructure → Presentation**.

Правило зависимостей (Dependency Rule): стрелки внутрь.

```
Presentation ──▶ Application ──▶ Domain
       │              │            ▲
       └─────▶ Infrastructure ─────┘
```

- **Domain** не знает ни о ком. Чистый PHP + Eloquent-модели (только связи) + Enums + Events + Templates.
- **Application** знает только Domain (+ интерфейсы портов).
- **Infrastructure** реализует интерфейсы, знает Domain и Application, тащит фреймворк/внешний мир (БД, Excel, RabbitMQ, S3).
- **Presentation** — точки входа (console, http), максимально тонкие, дёргают Application.

Нарушение, за которым следим: Domain/Application **не импортируют** `Maatwebsite\Excel`, `PhpAmqpLib`, конкретные фасады записи и т.п. — только через порты-интерфейсы.

---

## 1. Domain — `app/Vehicles/Domain`

Бизнес-сердце. Без фреймворковой инфраструктуры.

| Папка | Что лежит | Толщина |
|---|---|---|
| `Models/` | Eloquent-модели | **АНЕМИЧНЫЕ**: только связи (`hasMany`/`belongsTo`/`morphTo`), `$fillable`, `$casts`. Без бизнес-логики, без `$with`, без accessor/mutator-методов модификации. |
| `Enums/` | Backed-enum'ы (`type`, `template`, …) | Логика значений (label/маппинг) — здесь. `DetailTemplateEnum::template()` резолвит класс шаблона. |
| `Events/` | Доменные события | Plain DTO-события (`final readonly`), без поведения. `AbstractImportCompleted`, `EnginesAndModificationsReady` и т.д. |
| `Templates/` | Описания шаблонов полей (field-template) | Декларативные классы. Общий DSL вынесен в пакет `dan/field-templates`. |
| `Services/` | **Доменные сервисы** (пока нет) | Бизнес-правила, которым тесно в одной модели и которые не зависят от инфраструктуры. Создаём по мере появления чистых правил. |

**Почему модели анемичные:** бизнес-логика разъезжается по слоям предсказуемо (Services/UseCases), модели остаются сериализуемыми и тестируемыми, нет «магии» автозагрузки.

**Enum через casts:** колонки в миграциях — `string` с `->comment('XxxEnum')`; преобразование — в `$casts` модели. В миграциях `enum()` не используем.

---

## 2. Application — `app/Vehicles/Application`

Оркестрация сценариев. Без знания о деталях БД/Excel/брокера — только порты (интерфейсы) и Domain.

| Папка | Что лежит | Толщина / правила |
|---|---|---|
| `Contracts/<Concern>/` | **Порты** (интерфейсы) для адаптеров Infrastructure | Группируются по инфра-концерну, зеркально `Infrastructure/`: `Contracts/Repositories/<Entity>RepositoryInterface`, `Contracts/Commands/<Entity>CommandInterface`, `Contracts/Notifications/…`. В будущем — `Contracts/Messaging/`, `Contracts/Cache/`. Порт принадлежит потребителю (Application), реализация — в Infrastructure. Без вложенной папки-сущности: на сущность ровно один Repository и один Command. |
| `UseCases/<Entity>/` | **Оркестраторы** одного сценария | Координируют: вызывают Validator → собирают `ModelData` → дёргают `Command`/`Repository`/доменный Service. Без прямого Eloquent-`save`, без Excel. Пример: `Vehicle/UpsertVehicleFromSheet`. |
| `ModelData/<Entity>/` | **Отображения моделей** (бывш. `DTOs`) | `final readonly` + `toArray()`. **Без валидации в конструкторе.** Это типизированный «снимок строки» для передачи в Command. Имя класса — `<Entity>Data`. |
| `Validators/<Entity>/` | Валидация входных данных | Вся валидация здесь (а не в DTO/Data). Enum-поля валидируем **сырыми значениями** через `Rule::enum(...)`, без `tryFrom` (см. «Грабли»). |
| `Listeners/` | Слушатели доменных событий | **ТОНКИЕ.** Делегируют в UseCase/Service. Без бизнес-логики внутри. Нейминг и количество — см. ниже. |
| `Jobs/<Entity>/` | Очередные задачи | **ТОНКИЕ.** `handle()` резолвит сервис/use-case и зовёт его. Состояние job'ы — сериализуемые скаляры/DTO, не зависимости. |
| `Observers/<Entity>/` | Обсерверы моделей | **ТОНКИЕ.** Только реакция на события модели → делегат в Service/UseCase. |

**ModelData vs DTO (договорённость):**
- `ModelData/` = **отображение модели** (`VehicleData` ≈ строка таблицы `vehicles`). Это то, что мы передаём в Command на запись.
- **DTO** (когда появятся) — это уже **отдельная** история: транспортные объекты для входа/выхода сценариев (команды/запросы, payload событий между сервисами). Положим их отдельно (напр. `Application/DTOs/` или рядом со сценарием), и они **не обязаны** повторять модель.

**Почему слушатели/джобы/обсерверы тонкие:** их трудно тестировать и переиспользовать. Вся логика — в UseCase/Service, адаптер только «принял сигнал → позвал сценарий».

**Слушатели — сколько на событие:**
- **Реакции независимы** (порядок не важен, нужна изоляция ретрая/очередей) → **несколько слушателей** на событие, по одному на реакцию. Каждый — свой queued-job, свой failure-домен, своя конфигурация очереди; реакции явно видны в `EventServiceProvider`. Пример: `ManufacturerCommandImported` → `StartVehicleImportListener` + `StartEngineImportListener`.
- **Реакции связны** (по порядку / B зависит от A / общая транзакция) → **один слушатель → один UseCase-оркестратор**, который и выстраивает шаги. Не размазываем процесс по слушателям и не пишем `A(); B(); C();` прямо в слушателе — оркестрацию в use-case (тестируется без событийного слоя).

**Слушатели — нейминг:**
- На событие **один** слушатель → имя **по событию**: `<Event>Listener`.
- На событие **несколько** слушателей → имя **по действию**: `<Action>Listener` (по событию назвать нельзя — коллизия).
- UseCase при этом всегда именуется по действию (`ReportImportResult`), слушатель — по правилу выше.

---

## 3. Infrastructure — `app/Vehicles/Infrastructure`

Реализация портов и всё «грязное»: БД, Excel, брокер, файлы, нотификации.

| Папка | Что лежит | Правила |
|---|---|---|
| `Repositories/` | **Чтение** (CQRS-lite) | `<Entity>Repository` (реализует порт `Application/Contracts/Repositories/<Entity>RepositoryInterface`). Только запросы, без записи. Без папки-сущности — один репозиторий на сущность. |
| `Commands/` | **Запись** (CQRS-lite) | `<Entity>Command` (реализует порт `Application/Contracts/Commands/<Entity>CommandInterface`). Принимают **`ModelData`**, а не сырые массивы. Здесь `save`/`upsert`/`delete`. Без папки-сущности — одна команда на сущность. |
| `Imports/<Entity>/` | Импорт из Excel | Адаптеры `maatwebsite/excel`. Пайплайн строки: **row → Validator → ModelData → Command**; чтение справочников — через Repository. Зависимости — через конструктор. |
| `Exports/<Entity>/` | Экспорт в Excel | Источник данных — Repository. Сборка строк/заголовков — сервисы `Support/*ExportRow`, `Support/ExportDetailsBuilder`. |
| `Support/` | Технические сервисы-помощники | `DetailsBuilder`, `ExportDetailsBuilder`, `VehicleExportRow`, `EngineExportRow`. Без статуса трейтов — обычные инъектируемые классы. |
| `Messaging/` | RabbitMQ | `Consumers/InboxConsumer`, publisher, `Commands/` (setup-команды брокера), `CustomRabbitMQQueue`. Очереди: `vehicles` (jobs), `vehicles.inbox` (входящие события), exchange `application.events`. |
| `Notifications/` | Внешние уведомления | Реализации портов уведомлений (напр. `RabbitMqFileNotificationService` → `FileNotificationServiceInterface`). UI-нотификации идут событием в Filament-сервис; файлы ошибок → S3 + сообщение. |
| `Providers/` | DI и события | `VehiclesServiceProvider` (биндинги интерфейс→реализация по списку `ENTITIES`), `EventServiceProvider` (карта событие→слушатель). |

**Порт (интерфейс) живёт в Application (`Application/Contracts/<Concern>/`), адаптер (реализация) — в Infrastructure.** Так стрелка зависимости идёт внутрь: Application зависит только от своего порта, Infrastructure реализует его. Расположение зеркальное: `Application/Contracts/Repositories/VehicleRepositoryInterface` ↔ `Infrastructure/Repositories/VehicleRepository`. Биндинги порт→адаптер — в `VehiclesServiceProvider`.

> Почему Command-порты обязаны быть в Application: они принимают `ModelData` (это `Application/ModelData`). Положи их в Domain — Domain начнёт зависеть от Application. Repository-порты держим там же для единообразия.

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

### DI — «вариант А»
- Реальная конструкторная инъекция: зависимости — параметры конструктора (`private Xxx $x`).
- Экземпляры получаем через контейнер: `app(...)`, `app()->makeWith(...)`. **Не `new`** для классов с зависимостями.
- Биндинги интерфейс→реализация — в `VehiclesServiceProvider` (цикл по `ENTITIES`).
- Сервисы — без состояния → корректно сериализуются для очередей.
- **`final readonly class` для всех stateless-классов с инъекцией** — Repositories, Commands, UseCases, Services, Support-сервисы, тонкие Listeners с зависимостями. Зависимости — только промотированные `private readonly`-параметры конструктора; своего мутируемого состояния нет. `readonly` гарантирует это на уровне языка (нельзя случайно завести изменяемое поле) и подчёркивает, что объект — чистый исполнитель. Исключение — классы с легитимным изменяемым состоянием (если появятся): тогда не `readonly`, и это повод задуматься, не тянет ли класс на что-то другое.

### Naming
- Сущности: `Vehicle`, `Engine`, `Modification`, `Manufacturer`, `PartSpecification`.
- Группировка по сущности — там, где на сущность несколько файлов: `Imports/<Entity>/`, `Exports/<Entity>/`, `ModelData/<Entity>/`, `Validators/<Entity>/`. Где файл один на сущность (`Repositories/`, `Commands/`) — плоско, без папки-сущности.
- Read = `…Repository`, Write = `…Command`, отображение = `…Data`.
- **UseCase = глагольная фраза без суффикса** (`UpsertVehicleFromSheet`, `ReportImportResult`): use-case — это действие системы, поэтому глагол; суффикс `UseCase`/`Action` не добавляем — он дублирует namespace `Application\UseCases`. Единая точка входа — метод `__invoke()`.
- Listener — см. раздел про слушателей (по событию / по действию).

### Трейты (политика)
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
| Новый сценарий (импорт/пересчёт/…) | `Application/UseCases/<Entity>/` |
| Новый запрос к БД | порт → `Application/Contracts/Repositories/`, адаптер → `Infrastructure/Repositories/` |
| Новую запись в БД | порт → `Application/Contracts/Commands/`, адаптер → `Infrastructure/Commands/` (вход — `ModelData`) |
| Новый порт (брокер/кеш/нотификация) | `Application/Contracts/<Concern>/`, реализация → `Infrastructure/<Concern>/` |
| Типизированный снимок модели | `Application/ModelData/<Entity>/` |
| Транспортный объект сценария/события | `Application/DTOs/` (отдельно от ModelData) |
| Проверку входных данных | `Application/Validators/<Entity>/` |
| Реакцию на доменное событие | `Application/Listeners/` (тонко) |
| Фоновую задачу | `Application/Jobs/<Entity>/` (тонко) |
| Парсер Excel | `Infrastructure/Imports/<Entity>/` |
| Технический помощник | `Infrastructure/Support/` |
| Artisan-команду | `Presentation/Console/Commands/` (тонко) |
| HTTP-эндпоинт | `Presentation/Http/Controllers/` (тонко) |
