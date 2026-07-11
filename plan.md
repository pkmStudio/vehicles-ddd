# plan.md — Анализ проекта `dan-vehicles` и план улучшений
# claude --resume caf3253b-b8ea-4166-a289-d28e85f709ad
> Документ написан по итогам чтения кода (не только существующих `*.md`). Часть выводов
> пересекается с `ARCHITECTURE.md` / `Final-audit.md` / `RESEARCH.md` / `Applicability-dizajn.md` —
> там, где пересекается, я ссылаюсь на них, а не дублирую. Здесь — то, что не было зафиксировано
> в этих документах, плюс конкретные баги, найденные построчным чтением кода.

## 1. Реструктуризация папок: feature-first

> ✅ Статус: **выполнено** (файлы физически перенесены, namespace обновлены, 48/48 тестов
> зелёные). Одно осознанное отклонение от целевого дерева ниже — см. врезку сразу после него.

Цель — развернуть структуру `app/Vehicles/` с layer-first (`Domain/Application/Infrastructure/
Presentation`, фичи размазаны внутри каждого слоя) на **feature-first**: каждая фича — это
вертикальный срез `Domain → Application → Infrastructure → Presentation` целиком внутри себя.
Дополнительно — Eloquent-модели остаются в `Domain`, но **дублируются по фичам как
ReadModel/WriteModel-копии**, а не делятся одной общей моделью на все фичи.

> **Важное уточнение из §3:** после решения перенести Eloquent-модель в Infrastructure и завести
> единую `Data` (см. §3), пункт "модели дублируются как ReadModel" ниже относится не к Eloquent-
> модели, а к `Domain/ModelData/<Entity>Data`. Eloquent-класс переезжает в `Infrastructure/Models/`
> каждой фичи как деталь реализации Repository/Command — дублирование по фичам сохраняется, но
> предмет дублирования другой. Дерево ниже показывает уже целевое (не текущее) расположение.

### Что реально сделано, а что осознанно отложено до §3

> Обновление: раздел ниже описывает состояние **на момент завершения самого переезда по
> папкам** (до §3/п.9 в §11). С тех пор §3 выполнен целиком (Import и Export) — временный
> `VehiclesServiceProvider` и общие `Domain/Contracts/Infrastructure/Repositories`/
> `Infrastructure/Repositories` из описания ниже **удалены**, `bootstrap/providers.php` сейчас
> перечисляет 4 провайдера, а не 5. Раздел оставлен как есть — фиксирует историю решения,
> актуальное состояние смотри в §3 и §11 (п.9).

Переезд выполнен **без** дублирования `Domain/Models` и без `spatie/laravel-data` — эти две
вещи содержательно связаны (Repository имеет смысл дублировать только вместе с моделью,
которую он читает) и явно относятся к отдельному, более крупному пункту 9 из §11 (`spatie/
laravel-data`, §3). Смешивать "переезд файлов по папкам" с "смена контракта Repository/Command"
в одном заходе было бы рискованнее и хуже проверяемо, поэтому:

- `Domain/Models/*` (все 8 сущностей), `Infrastructure/Repositories/*`, `Infrastructure/
  Commands/*` и их порты (`Domain/Contracts/Infrastructure/{Repositories,Commands}`) **остались
  на месте**, на старых layer-first путях — общие для Import и Export, как и были.
- Появился временный `VehiclesServiceProvider` (сильно ужатый, только `REPOSITORY_BINDINGS`) —
  это явный "shared kernel" на переходный период, с докблоком, что он исчезнет, когда дойдём
  до §3 и Repository/Model реально разъедутся по фичам.
- Всё остальное — Contracts (Imports/Exports/Services/Factories/Notifications), `ModelData`,
  `DTOs`, `Events`, `Enums/InOut/Sheets`, весь `Application` (Services/Factories/Listeners),
  `Infrastructure/{Imports,Exports,Notifications,Traits}` и `Presentation` — переехало по
  фичам полностью, как в дереве ниже.
- Добавлен один провайдер сверх исходного чертежа: `Templates/Application/
  TemplatesServiceProvider.php` — в черновике дерева его не было, но раз Templates — фича
  со своими портами (`DetailTemplateResolverInterface`, `WiperSpecificationServiceInterface`),
  логично, что она сама их и биндит, а не корневой провайдер.
- `bootstrap/providers.php` теперь перечисляет 5 провайдеров вместо 2: `VehiclesServiceProvider`
  (временный), `TemplatesServiceProvider`, `ImportServiceProvider`, `ImportEventServiceProvider`,
  `ExportServiceProvider`.
- Per-feature `Rabbit/{Import,Export}RabbitConfig.php` из раздела "Messaging" ниже — тоже не
  созданы: `config/rabbit-transport.php` остаётся плоским (см. врезку в разделе Messaging,
  не изменилась с прошлого статуса).

### Принятые решения (развилки)

| Развилка | Решение |
|---|---|
| Common Application-сервисы, нужные 2+ фичам (`DetailTemplateResolver`, `WiperSpecificationService`) | Не дублируем вслепую и не складываем в безликий `Common`. Раз они меняются вместе и нужны одновременно Import и Export — выносим **отдельной фичей `Templates`**, обе фичи зависят на её Domain-контракты. |
| Repository (чтение), нужные и Import, и Export | **Дублируем по фичам.** Каждая фича получает свою копию Repository, читающую свою копию Eloquent-модели (теперь — в `Infrastructure/Models/`, см. §3). Write (`Command`) остаётся только в Import — Export никогда не пишет. |
| Консольные команды-«чинилки» (`UpdateVehicleYears`, `ChangeProviderManufacturersToTD`, `DeduplicatePartSpecificationsCommand`, …), не относящиеся ни к Import, ни к Export | Новая фича **`Maintenance`** — разовые фиксы каталога живут отдельно от обычного цикла импорта/экспорта. |
| Enums (`VehicleTypeEnum` и т.п.) и `BaseModel` | **НЕ дублируем.** Это не сервисы и не модели с данными, а словарь, на который завязаны `$casts` колонок: два разных класса `VehicleTypeEnum` в Import и Export — риск рассинхрона данных, а не просто лишний код. Остаются единой точкой истины в `Shared/` (узкий «словарь» без поведения). |
| Messaging (RabbitMQ) | **Не отдельный модуль внутри `app/Vehicles`, а vendor-пакет** `pkmstudio/rabbit-transport` (см. ниже). У каждой фичи — свой вклад в общий конфиг пакета, а не общий написанный руками модуль. |
| Природа Import/Export: общий Domain на каталог Vehicles, или свой Domain на каждую фичу? | Подтверждено (дважды): **свой Domain на фичу**, несмотря на то что `Vehicle`/`Engine`/... физически одна и та же таблица для обеих. Механика чтения/записи Excel остаётся Infrastructure, как и раньше — это не отменяет vertical-slice выбор для Domain. Дублирование — осознанная плата за независимость фич, а не недосмотр. |
| Где живёт Eloquent-модель после перехода на единую `Data` (§3)? | **В `Infrastructure/Models/`.** Раз Application её больше не видит и не держит (только `Data`), это деталь реализации Repository/Command, а не декларация Domain. Domain остаётся: `Contracts` + `ModelData` (теперь и чтение, и запись) + `DTOs` + `Enums` + `Events` + `Templates`. |

### Целевое дерево

```
app/Vehicles/
├── Shared/Domain/
│   ├── Enums/Vehicle/                        # VehicleTypeEnum, CarcaseTypeEnum, SteeringTypeEnum,
│   │                                         # BrakeSystemTypeEnum, GearTypeEnum, DriveTypeEnum, WiperSideEnum
│   ├── Enums/Engine/                         # EngineFuelTypeEnum, EngineTypeEnum
│   └── Traits/EnumHelperTrait.php
│   (BaseModel — теперь Infrastructure/Models/BaseModel.php, см. §3: словарь Enums делить, Model — нет)
│
├── Templates/                                 # фича: декларации полей деталей (общие для Import/Export)
│   ├── Domain/
│   │   ├── Contracts/                        # DetailTemplateResolverInterface, WiperSpecificationServiceInterface
│   │   ├── Enums/DetailTemplateEnum.php
│   │   └── Templates/{Engine,Vehicle}/Templates/   # AirFilterTemplate, OilFilterTemplate, SparkPlugTemplate, WiperTemplate
│   └── Application/{DetailTemplateResolver,WiperSpecificationService}.php
│
├── Import/                                    # фича: приём CSV → каталог
│   ├── Domain/
│   │   ├── Contracts/{Repositories,Commands,Imports,Services,Factories,Notifications}/
│   │   ├── ModelData/<Entity>/               # <Entity>Data (Spatie\LaravelData\Data) — вход Command И выход Repository, см. §3
│   │   ├── DTOs/                             # VehicleImportPlan, EngineImportPlan, AssignEngineGroupResult, ModificationSparkPlugResult
│   │   ├── Enums/InOut/Sheets/               # VehicleImportSheet, EngineImportSheet
│   │   └── Events/                           # *CommandImported, *ImportCompleted, EnginesAndModificationsReady
│   ├── Application/                          # только Service — см. §2 (без UseCases/ и Support/)
│   │   ├── Services/<Entity>/                # Upsert*FromRowService, AssignEngineGroupService, ...
│   │   ├── Services/Reporting/ReportImportResultService.php
│   │   ├── Services/Template/                # TemplateDataBuilder, DetailsBuilder (бывший Support/)
│   │   ├── Services/Engine/EngineEditableColumnsMapper.php   # (бывший Support/)
│   │   ├── Services/EngineModificationReadinessGate.php
│   │   ├── Factories/<Entity>/
│   │   └── Listeners/                        # Start*ImportListener, *ReadinessSubscriber, ReportImportResultListener
│   ├── Infrastructure/
│   │   ├── Models/                           # Eloquent: Vehicle, Engine, Modification, Manufacturer, EngineModification,
│   │   │                                     # PartSpecification, Feature, FeatureValue — деталь Repository/Command, см. §3
│   │   ├── Imports/<Entity>/                 # Excel-адаптеры (механика чтения)
│   │   ├── Repositories/                     # строят <Entity>Data::from($model) из своих Infrastructure/Models
│   │   ├── Commands/                         # принимают <Entity>Data, пишут в свои Infrastructure/Models
│   │   ├── Notifications/RabbitMqFileNotificationService.php   # implements Domain\Contracts\Notifications\FileNotificationServiceInterface,
│   │   │                                                       # внутри — напрямую PkmStudio\RabbitTransport\RabbitMQPublisher
│   │   ├── Exports/ImportFailureReporter.php + FailuresExport.php   # отчёт об ошибках — логически часть Import
│   │   ├── Rabbit/ImportRabbitConfig.php     # statics inbound()/outbound()/bindings() — вклад фичи в общий конфиг
│   │   ├── Traits/CachesImportFailures.php
│   │   └── Providers/{ImportServiceProvider,ImportEventServiceProvider}.php
│   └── Presentation/Console/Commands/TecDocImportCars.php
│
├── Export/                                    # фича: каталог → Excel
│   ├── Domain/
│   │   ├── Contracts/{Repositories,Exports,Services}/
│   │   ├── ModelData/<Entity>/               # <Entity>Data — выход Repository (Export никогда не пишет), см. §3
│   │   ├── DTOs/                             # VehicleExportPlan, EngineExportPlan
│   │   └── Enums/InOut/Sheets/               # VehicleExportSheet, EngineExportSheet
│   ├── Application/                          # только Service — см. §2 (без Support/)
│   │   ├── Services/                         # VehicleExportService, EngineExportService
│   │   ├── Services/Details/ExportDetailsBuilder.php          # бывший Support/
│   │   ├── Services/Rows/VehicleExportRow.php, EngineExportRow.php   # бывший Support/
│   │   └── Services/Expanders/PartSpecificationRowExpander.php, WiperRowExpander.php   # бывший Support/
│   ├── Infrastructure/
│   │   ├── Models/                           # свои Eloquent-копии того же набора сущностей — деталь Repository, см. §3
│   │   ├── Exports/<Entity>/                 # Excel multi-sheet адаптеры
│   │   ├── Repositories/                     # строят <Entity>Data::from($model) из своих Infrastructure/Models
│   │   ├── Rabbit/ExportRabbitConfig.php     # пока пусто — у Export нет своих rabbit-событий
│   │   └── Providers/ExportServiceProvider.php
│   └── Presentation/                          # пока пусто — у экспорта нет своей точки входа
│
└── Maintenance/                                # фича: разовые фиксы каталога
    ├── Domain/Contracts/                       # по мере надобности
    ├── Application/Services/                   # PartSpecificationDeduplicationService,
    │                                           # VehicleWiperPartSpecificationSplitService, ...
    ├── Infrastructure/Models/                  # своя копия: Vehicle, PartSpecification,
    │                                           # Manufacturer, Modification (только то, что реально читает)
    └── Presentation/Console/Commands/
        ├── ChangeProviderManufacturersToTD.php
        ├── UpdateVehicleYears.php
        ├── UpdateModificationYears.php
        ├── DeduplicatePartSpecificationsCommand.php
        └── SplitVehicleWiperPartSpecificationsCommand.php
        (GroupEnginesCommand — @deprecated, удалена при реструктуризации по фичам)
```

### Messaging — внешний пакет вместо своего модуля

> ✅ Статус: выполнено. `Infrastructure/Messaging/*` удалён, пакет установлен и настроен
> (см. §6.2). Одно отличие от первоначального плана: подключён **не path-, а VCS-repository**
> (`https://github.com/pkmStudio/laravel-rabbitmq-transport`, `dev-master`) — пакет живёт в
> своём отдельном репозитории, а не только локально рядом с `dan-vehicles`. Разбивка
> `ImportRabbitConfig`/`ExportRabbitConfig` по фичам (ниже) пока не сделана — `config/
> rabbit-transport.php` сейчас плоский (`inbound`/`outbound`/`setup` объявлены прямо в файле),
> потому что feature-first переезд (Import/Export как отдельные папки) из этого же §1 ещё не
> выполнен. Разбить на per-feature классы — когда дойдём до самого переезда.

Свой `Infrastructure/Messaging/*` (см. §6.2 ниже про найденные в нём баги) заменяется пакетом
`pkmstudio/rabbit-transport`. Пакет уже устроен так, как было нужно: publisher с confirms,
config-driven inbound-реестр, `max_attempts`/`poison_action`/DLQ — то есть он закрывает баг
§6.2 этого документа целиком, просто заменой собственного кода на проверенный пакет.

Пакет — с **одним коннекшеном на приложение** (`'connection'`, `'inbound'`, `'outbound'`,
`'setup'` в его конфиге — плоские структуры, не массив подключений). Поэтому «свой конфиг на
фичу» реализуется не отдельными очередями, а так: каждая фича выставляет статический класс
(`ImportRabbitConfig`, `ExportRabbitConfig`) с методами `inbound()`/`outbound()`/`bindings()`,
а корневой `config/rabbit-transport.php` их просто сливает:

```php
return [
    'connection' => env('RABBIT_TRANSPORT_CONNECTION', 'rabbitmq_inbox'),
    'consumer' => [/* max_attempts/poison_action — общие для сервиса */],
    'inbound' => array_merge(
        Import\Infrastructure\Rabbit\ImportRabbitConfig::inbound(),
        Export\Infrastructure\Rabbit\ExportRabbitConfig::inbound(),
    ),
    'outbound' => array_merge(
        Import\Infrastructure\Rabbit\ImportRabbitConfig::outbound(),
        Export\Infrastructure\Rabbit\ExportRabbitConfig::outbound(),
    ),
    'setup' => [
        'exchange' => 'application.events',
        'queue' => 'vehicles.inbox',
        'bindings' => array_merge(
            Import\Infrastructure\Rabbit\ImportRabbitConfig::bindings(),
            Export\Infrastructure\Rabbit\ExportRabbitConfig::bindings(),
        ),
    ],
];
```

Свой `RabbitMQPublisherInterface`-порт (предлагался в `Final-audit.md`, П.3) больше не нужен —
`PkmStudio\RabbitTransport\RabbitMQPublisher` уже сам по себе заменяемый сервис из контейнера
вендора; инжектить его напрямую в Infrastructure-адаптер — Infrastructure→Infrastructure,
не нарушает правило «Domain/Application не знают о конкретных пакетах».

Входящие rabbit-события (`inbound`/`setup.bindings`) у сервиса пока не реализованы вообще
(плейсхолдер-bind сохранён из старого `RabbitMqSetupCommand`) — заводим отдельным следующим
заходом, когда появятся первые интеграции. Исходящее событие одно и уже реальное —
`outbound.FILE_EXPORTED` (уведомление о готовом файле для сервиса с Filament), перенесено
из `RabbitMqFileNotificationService` без изменения поведения.

### Цена решения

7–8 сущностей × 2 фичи (Import/Export) = Eloquent-модели (в Infrastructure) и `Data`-формы
(в Domain) физически дублируются. Это осознанная плата за независимость фич — пока схема
(миграции) одна, расхождения колонок между копиями обнаружатся только в рантайме, не на
уровне БД.

---

## 2. Application-слой Import/Export: убрать `UseCases` и `Support`, всё — `Service`

> Статус: согласованная схема, продолжение реструктуризации из §1. Дерево в §1 уже обновлено
> с учётом этого решения.

Принцип: **оркестрирует процесс Presentation** (Console Command, в будущем — Listener/HTTP-
контроллер как точка входа); всё, что лежит в `Application`, — это `Service`, вызываемый этим
оркестратором (или другим `Service`, или Infrastructure-адаптером построчно). Отдельной
прослойки `UseCase` между Presentation и `Service` не вводим — ни в Import, ни в Export.

### Почему

- **Это уже факт в коде, а не новый стиль.** Сегодня в репозитории нет ни одного класса с
  суффиксом `UseCase` — везде `*Service` (`UpsertVehicleFromSheetService`,
  `UpsertManufacturerFromRowService`, ...). При этом `ARCHITECTURE.md` описывает конвенцию,
  которая по факту никогда не была реализована: "`UseCase` — сценарный вход с точки входа
  внешнего мира (`execute(...)`)" (раздел 2, строка 51) и "`UseCase` всегда именуется по
  действию... (`ReportImportResultUseCase`)" (строка 167) — а в коде этот же класс называется
  `ReportImportResultService`. Формализуя это решение, мы не меняем код, а приводим документ
  в соответствие с тем, что команда и так уже выбрала на практике (`ARCHITECTURE.md` потребует
  точечной правки этих формулировок — отдельным шагом, вместе с фактическим переездом, не сейчас).
- **DI уже не различает их.** `VehiclesServiceProvider::SERVICE_BINDINGS` — один плоский массив,
  где `DetailTemplateResolverInterface`, `ExportDetailsBuilderInterface`,
  `EngineEditableColumnsMapperInterface` (сегодняшний `Support/`) лежат вперемешку с
  `UpsertVehicleFromSheetServiceInterface`, `EngineImportServiceInterface` (сегодняшний
  `Services/`) — контейнер их и так не разделяет, разделение было только на уровне папок.
- **Граница "когда сервис вырастает из `Support` в полноценный `Service`" — произвольная** и
  требует решения на каждый новый класс. Без неё проще: один тип строительного блока
  (`Service`), а оркестратор (Presentation/Listener) решает порядок вызовов.

### Что меняется (дерево в §1 уже отражает это)

| Было | Стало |
|---|---|
| `Import/Application/Support/TemplateDataBuilder.php` | `Import/Application/Services/Template/TemplateDataBuilder.php` |
| `Import/Application/Support/DetailsBuilder.php` | `Import/Application/Services/Template/DetailsBuilder.php` |
| `Import/Application/Support/EngineEditableColumnsMapper.php` | `Import/Application/Services/Engine/EngineEditableColumnsMapper.php` |
| `Export/Application/Support/ExportDetailsBuilder.php` | `Export/Application/Services/Details/ExportDetailsBuilder.php` |
| `Export/Application/Support/{Vehicle,Engine}ExportRow.php` | `Export/Application/Services/Rows/{Vehicle,Engine}ExportRow.php` |
| `Export/Application/Support/{PartSpecificationRow,Wiper}Expander.php` | `Export/Application/Services/Expanders/{PartSpecificationRow,Wiper}Expander.php` |

Папки `UseCases/` физически в коде не было — она стояла только как альтернативный вариант
имени в черновике дерева §1 (`Services|UseCases/<Entity>/`). Теперь убрана и оттуда: код
переименовывать по этой части не нужно, только понятийно зафиксировано "UseCase не заводим".

### Чем это не является

Это не отменяет существующую оркестрацию пайплайна через доменные события (§7.1 — каскад
`Manufacturer→Vehicle/Engine→Modification→...` через тонкие Listeners). Listener остаётся
точкой входа, которую дёргает диспетчер событий, и сам зовёт `Service`. Меняется только то, что
между Listener'ом и `Service` нет промежуточного `UseCase`: было бы "Listener → UseCase →
Service", остаётся "Listener → Service" — ровно как уже устроено в коде сегодня.

---

## 3. Repository/Command: единая `Data` вместо Eloquent в Domain (`spatie/laravel-data`)

> Статус: ✅ выполнено для Import, Export и Maintenance (детали и находки — в §11, пункт 9).
> Временный общий `VehiclesServiceProvider`/старые `Domain\Contracts\Infrastructure\
> Repositories`/`Infrastructure\Repositories` удалены — у каждой фичи своя копия.
> `app/Vehicles/Domain` удалена целиком без исключений — Maintenance тоже получил свою
> копию моделей (`Maintenance/Infrastructure/Models`).

Мотивация — не производительность и не мода на пакеты, а **контроль записи**: сегодня
Eloquent-модель (с `->save()`/`->update()`/`->delete()`) свободно ходит через весь Application
(Repository её отдаёт — значит любой код, до которого она дошла, технически может её изменить
и сохранить в обход `Command`). Это невозможно надёжно поймать на code review — только
соглашением. Правильнее сделать это невозможным на уровне типов.

### Решение

- Пакет `spatie/laravel-data`. Одна `<Entity>Data extends Spatie\LaravelData\Data` на сущность,
  работающая **в обе стороны**:
  - `Command::upsert(EngineData $data)` — вход на запись (как и было, просто теперь `Data`
    строится через пакет, а не руками).
  - `Repository::find(...): ?EngineData` — выход на чтение (новое): Repository внутри делает
    `EngineData::from($model)` и отдаёт `Data`, а не Eloquent-модель.
  - Маппинг в обе стороны (`::from()`/`toArray()`) берёт на себя пакет — по сущности не нужно
    писать свой конвертер.
  - Для выборок — `EngineData::collect($models)` **сохраняет тип контейнера, который дали на
    вход**: `Collection` → `Collection<EngineData>`, `array` → `array<EngineData>`, paginator →
    `LengthAwarePaginator<EngineData>`. Пакетную `DataCollection`-обёртку (с lazy-свойствами,
    `wrap()` для API) не берём — она даёт преимущество в первую очередь при отдаче наружу через
    HTTP, а HTTP-поверхности у сервиса почти нет. Контракт Repository не меняет форму: как и
    было в `ARCHITECTURE.md` ("возвращают `?Entity`/`Entity`/`Collection`"), просто `Entity` →
    `EntityData`.
- **Eloquent-модель переезжает из `Domain/Models` в `Infrastructure/Models`.** Раз Application
  никогда её не получает и не держит — это больше не декларация Domain, а деталь реализации
  Repository/Command. Domain становится чище: `Contracts` (порты) + `ModelData` (теперь и
  чтение, и запись) + `DTOs` + `Enums` + `Events` + `Templates`. `Models`-папки в Domain не
  остаётся ни в Import, ни в Export (дерево в §1 уже обновлено).

### Что это меняет в уже написанном

- **`ARCHITECTURE.md`, "Application × модели, правило A"** — этот раздел явно разрешал
  Application "держать и читать Domain-модели" и объяснял, почему не идём на строгий вариант
  ("налог маппинга model↔Data не оправдан"). Это решение отменяется осознанно: пакет снимает
  сам "налог маппинга", который и был причиной не делать строгий вариант — довод устарел, а не
  проигнорирован.
- **Правило "Repository-порты возвращают Domain-модели"** (`ARCHITECTURE.md`, раздел 3) меняется
  на "Repository-порты возвращают `Domain/ModelData`" — правки в сам документ вносим при
  реальном переезде, не сейчас.

### Что не решает сам пакет — держать в уме

- **Связи.** У Eloquent-модели связь ленивая — взял `$engine->modifications` где угодно по
  коду. У `Data`-снимка все нужные связи должны быть заранее подгружены и включены в форму
  объекта заранее — значит, разным вызывающим (например, Export, который ходит по
  `Vehicle→Modification→PartSpecification`) может понадобиться не одна универсальная
  `EngineData`, а несколько форм под разные срезы использования, а не одна "загрузить всё на
  всякий случай".
- **Цена на объём.** Reflection-маппинг `::from()`/`toArray()` на каждую строку — не бесплатно.
  Чанки импорта/экспорта — сотни-тысячи строк за проход; стоит бенчмаркать на реальном объёме,
  прежде чем считать это нейтральным по производительности изменением.
- **Новая зависимость Domain от вендора.** `<Entity>Data extends Spatie\LaravelData\Data` — это
  прямая зависимость Domain-класса от стороннего пакета. Это та же категория компромисса, что
  уже был принят для Eloquent-моделей в Domain (прагматизм важнее теоретической чистоты) —
  просто компромисс сдвигается с Model (которая уезжает в Infrastructure) на `Data` (которая
  остаётся в Domain и получает эту зависимость взамен).

---

## 4. Что делает проект

Laravel 13 (PHP 8.3) бэкенд-сервис **`dan-vehicles`** — один из доменных сервисов более крупной
системы (рядом упоминаются `MpSale`, `Warehouse`, `Applicability`, "сервис с Filament"). У сервиса
нет своего UI и почти нет HTTP-поверхности (`routes/web.php` — это дефолтная заглушка Laravel).
Это headless ETL/каталог-сервис:

- **Источник данных** — CSV/Excel-файлы в `storage/vehicles/*.csv` (производители, ТС, двигатели,
  модификации, связи двигатель↔модификация) от внешнего поставщика данных (TecDoc/"OD"→"TD").
- **Импорт** — через `maatwebsite/excel`: каждая сущность читается чанками, на каждую строку
  вызывается построчный use-case (`Upsert*FromRowService` / `Upsert*FromSheetService`), который
  валидирует, собирает `ModelData` и пишет в БД через `Command`-порт.
- **Оркестрация цепочки импорта** — через доменные события (`Manufacturer→Vehicle/Engine→
  Modification→(Engine+Modification готовы)→EngineModification`), см. `app/Vehicles/EventAudit.md`.
- **Экспорт** — обратно в Excel (многолистовые экспорты ТС/двигателей с деталями/шаблонами полей).
- **Интеграция** — RabbitMQ: исходящие уведомления о готовности файла, входящий inbox для событий
  из общего `application.events` exchange (на сегодня — пустой каркас, см. §6.2).
- Архитектура — явный DDD/гексагональный слой `Presentation → Application → Domain ← Infrastructure`,
  подробно описанный в `ARCHITECTURE.md`. Это **необычно зрелая** для такого объёма документация
  соглашений и решений (включая открытые развилки) — редкость, заслуживает внимания как сильная
  сторона процесса, а не только код.

## 5. Метод анализа

Помимо чтения `*.md`, я прочитал: `composer.json`, `docker-compose.yml`, `.env.example`,
`config/{queue,horizon}.php`, все миграции, `VehiclesServiceProvider`, `EventServiceProvider`,
все Excel-импорт-адаптеры (`Infrastructure/Imports/**`), `Messaging/**` (RabbitMQ), несколько
`Command`/`Repository`, `CachesImportFailures`, `ImportFailureReporter`, флагованные в
`Final-audit.md` "толстые" Presentation-команды, и весь `tests/` каталог. Ниже — то, что нашлось.

---

## 6. Критические баги (P0) — пайплайн импорта сейчас не работает

Это не "архитектурная слабость" в абстрактном смысле — это конкретные runtime-ошибки,
которые остановят первую же строку первого же импорта. Похоже, что цепочка импорта
**ни разу не была прогнана end-to-end** (согласуется с пометкой в `RESEARCH.md`: "Миграции —
ещё не запускались (по договорённости)").

### 6.1. ✅ ИСПРАВЛЕНО — `$this->useCase` — необъявленное свойство, баг в 10 местах

> Статус: исправлено. Механическая правка `$this->useCase` → `$this->service` во всех 10
> местах + регрессионный тест `tests/Feature/Vehicles/ManufacturerImportTest.php` (гоняет
> реальный `Excel::import` на фикстурном CSV из `tests/Fixtures/manufacturers_sample.csv`
> через настоящий Command/Repository на sqlite). Тест подтверждённо падал на баге
> (`Undefined property: ...::$useCase`) и проходит после фикса; полный набор — 38/38 зелёных.

Конструкторы везде называют зависимость `$service`, но тело метода обращалось к
несуществующему `$this->useCase`. PHP бросал `Error` (typed property must not be accessed
before initialization) — это **не** перехватывается соседним `catch (ValidationException $e)`,
поэтому исключение вылетало из `collection()`, весь queued-чанк падал как failed job, и
(при `tries=1`, см. §7.1) ретраев не было бы — `AfterImport` никогда не срабатывал, событие
`ManufacturerCommandImported`/`VehicleCommandImported`/... не диспатчилось, и вся цепочка
импорта останавливалась на первом же чанке первой же сущности (Производители).

Было 10 мест (не 11, как в первой версии этого документа — уточнено при фактическом фиксе):

| Файл | Строка |
|---|---|
| `app/Vehicles/Infrastructure/Imports/Manufacturer/ManufacturerCommandImport.php` | 48 |
| `app/Vehicles/Infrastructure/Imports/Vehicle/VehicleCommandImport.php` | 49 |
| `app/Vehicles/Infrastructure/Imports/Modification/ModificationCommandImport.php` | 49 |
| `app/Vehicles/Infrastructure/Imports/Engine/EngineCommandImport.php` | 48 |
| `app/Vehicles/Infrastructure/Imports/EngineModification/EngineModificationImport.php` | 45 |
| `app/Vehicles/Infrastructure/Imports/Engine/EngineCrossImport.php` | 70 |
| `app/Vehicles/Infrastructure/Imports/Engine/EngineSparkPlugSpecificationImport.php` | 77 |
| `app/Vehicles/Infrastructure/Imports/Engine/Sheets/EngineMainSheetImport.php` | 42 |
| `app/Vehicles/Infrastructure/Imports/Engine/Sheets/EngineSparkPlugsSheetImport.php` | 64 |
| `app/Vehicles/Application/Import/Listeners/ReportImportResultListener.php` | 18 |

**Почему не было поймано тестами:** юнит-тесты (`tests/Unit/Vehicles/*`) тестируют только
`Application/.../*Service` классы напрямую, с моками портов — они вообще не инстанцировали
`Infrastructure/Imports/*`-адаптеры и не видели это свойство. Это системный разрыв в стратегии
тестирования, см. §8 — Feature-тесты теперь закрывают этот разрыв для всех 10 адаптеров
(`tests/Feature/Vehicles/*ImportTest.php` + `ReportImportResultListenerTest.php`).

Написание этих тестов заодно нашло **два независимых, ранее не выявленных бага** в реальном
пайплайне импорта (не связанных с `$this->useCase`) — см. §6.4 и §6.5.

### 6.2. ✅ ИСПРАВЛЕНО — `InboxConsumer` — пропущенные `return`, обращение к методу `null`, гонка с `delete()`

> Статус: исправлено заменой на пакет. `Infrastructure/Messaging/*` удалён целиком (7 файлов) —
> `pkmstudio/rabbit-transport` (path/VCS-зависимость в `composer.json`, конфиг —
> `config/rabbit-transport.php`). `config/queue.php`/`config/horizon.php` переключены на его
> `InboxConsumer`/`CustomRabbitMQQueue` (connection `rabbitmq_inbox`, было `vehicles_inbox`).
> `rabbit-transport:setup` прогнан против реального RabbitMQ (контейнер `dan-shared`): exchange
> `application.events`, очередь `vehicles.inbox` и bind созданы. DLQ включён сразу же, пока
> очередь ещё пустая (`RABBIT_TRANSPORT_DLX_ENABLED=true` → `vehicles.inbox.dlx`/`.dlq`,
> подтверждено через RabbitMQ Management API: `x-dead-letter-exchange` на очереди есть) —
> позже это стоило бы удаления/пересоздания очереди (аргумент нельзя добавить задним числом).
> `RabbitMqFileNotificationService` переведён на `PkmStudio\RabbitTransport\{RabbitMQPublisher,
> DTOs\RabbitMessageDTO}`; свой `RabbitMQPublisherInterface`-порт удалён (Infrastructure→
> Infrastructure, обёртка не добавляла абстракции, см. §1). 48/48 тестов зелёные после замены.

Ниже — описание бага, из-за которого потребовалась замена (исторический контекст).

`app/Vehicles/Infrastructure/Messaging/Consumers/InboxConsumer.php:26-61` (файл удалён):

```php
if (! $eventName) {
    Log::error(...);
    $this->delete();          // нет return — выполнение продолжается!
}

$event = InboundEventsEnum::tryFrom($eventName);
if (! $event) {
    Log::error(...);
    $this->delete();          // нет return — выполнение продолжается!
}

[$class, $method] = $event->getHandler();   // $event === null → Error
```

Дополнительно `InboundEventsEnum` (`Infrastructure/Messaging/Enums/InboundEventsEnum.php`)
сейчас объявлен **без единого case** и `getHandler()` безусловно возвращает `[]` — это
осознанный TODO-каркас (зафиксировано в `RESEARCH.md`), но из-за бага выше это означает, что
**любое** сообщение, которое сегодня придёт в `vehicles.inbox`, гарантированно пройдёт по ветке
"нет хендлера" → `delete()` → следом `Error` на `null->getHandler()` → ловится внешним
`catch (Throwable)` → `\Log::error(...)` → `$this->release(20)` **на уже подтверждённом
(ack'нутом) сообщении**, что с высокой вероятностью бросит `AMQPProtocolChannelException`
(канал закрывается протоколом AMQP после некорректного re-deliver) — он формально перехвачен,
но закрытие канала может уронить весь consumer-процесс/воркер до переподключения.

Отдельно: даже после исправления `return`, в коде нет **DLQ / счётчика попыток** —
`release(20)` будет повторяться для "ядовитого" сообщения (битый payload, неизвестное событие)
бесконечно, забивая очередь и логи.

**Исправлено заменой** на `pkmstudio/rabbit-transport` (см. статус выше) — у пакета уже есть
`return` на обеих ветках, настраиваемый `max_attempts`, `poison_action` с DLQ. Реальные
`inbound`/`outbound`-обработчики (`config/rabbit-transport.php`) всё ещё пусты (кроме
`outbound.FILE_EXPORTED`) — заводим отдельным следующим заходом, когда появятся первые
интеграции (см. §11, п.17).

### 6.3. ✅ ИСПРАВЛЕНО — `Auth::id()` в headless-пайплайне — отчёт об ошибках импорта недостижим

> **Важное уточнение к находке (сделано при реализации фикса):** изначально этот пункт был
> сформулирован как "единственная точка входа — `TecDocImportCars`, и она сломана". Это
> неточно. `Auth::id()` живёт не в TecDoc-каскаде (`ManufacturerCommandImport → Vehicle/
> EngineCommandImport → ModificationCommandImport → EngineModificationImport` — тот, что
> реально работает и покрыт тестами из §8.1, у него вообще нет концепции пользователя) — а в
> **отдельном, никем сегодня не вызываемом** пути "кто-то конкретный просит личный импорт"
> (`VehicleMultiSheetImport`, `EngineMultiSheetImport`, `EngineCrossImport`,
> `EngineSparkPlugSpecificationImport`) — ни один контроллер/команда их не вызывает; вызывающий
> код появится, когда будет реальный HTTP/Rabbit-триггер. Баг был реальным (заминированным на
> будущее), но не блокировал ничего в текущем рабочем пайплайне — TecDoc-каскад им не пользуется.

`VehicleMultiSheetImport`/`EngineMultiSheetImport`/`EngineCrossImport`/
`EngineSparkPlugSpecificationImport` брали `$this->importedByUserId = (int) Auth::id()` в
конструкторе и гейтили диспатч `*ImportCompleted` условием `> 0`. В любом контексте без
веб-сессии (а вызывающего кода с веб-сессией пока и не существует) `Auth::id()` — `null`,
условие никогда не выполняется, `ReportImportResultListener` не вызывается.

**Исправление:** новый `Domain/DTOs/ImportRunContext.php` — `userId: int` (обязателен, **не**
`?int` — эти 4 класса существуют ровно для сценария "конкретный пользователь просит личный
отчёт", у него всегда есть инициатор; для случаев без пользователя есть отдельный, никак не
пересекающийся с этим, консольный TecDoc-каскад) + `runId: string` (uuid, генерируется
вызывающим на старте прогона). Сигнатуры `import()` всех 4 классов и их интерфейсов
(`VehicleMultiSheetImportInterface` и т.д.) теперь принимают `ImportRunContext $context`
явным параметром вместо чтения `Auth::id()` из конструктора. Гейт `> 0` убран — `*ImportCompleted`
диспатчится всегда. Побочный фикс — `runId` (не `userId`) стал основой cache-ключа отчёта об
ошибках (`"vehicle_import_failures_{$context->runId}"`) и lock-ключа в четырёх суб-листах
(`VehicleMainSheetImport`, `VehicleWipersSheetImport`, `EngineMainSheetImport`,
`EngineSparkPlugsSheetImport` — их конструкторский параметр `$userId` переименован в `$runId`,
он и раньше использовался только для уникальности lock-ключа, не для идентификации личности) —
это заодно закрывает §7.3 (коллизия ключей при повторном прогоне одним и тем же пользователем)
для всех четырёх классов. `ReportImportResultService::report()`/`AbstractImportCompleted`
остались с обязательным `int $userId` (не менялись на nullable) — согласовано с тем, что у этой
ветки пайплайна пользователь есть всегда.

Тесты обновлены/добавлены (`EngineCrossImportTest`, `EngineSparkPlugSpecificationImportTest` —
теперь через реальный `Excel::import()` вместо прямого вызова `collection()`, плюс
`EngineMainSheetImportTest`/`EngineSparkPlugsSheetImportTest` под переименованный параметр)
— явно проверяют, что `*ImportCompleted` диспатчится с переданным `userId` без гейта. Пробел:
`VehicleMultiSheetImport`/`EngineMultiSheetImport` (реальный `WithMultipleSheets` на именованных
листах xlsx, не CSV-самоссылка) остались без Feature-теста — фикстуру-xlsx не строили, риск
низкий (правки чисто механические, симметричные уже протестированным классам), но не 0.

### 6.4. ✅ ИСПРАВЛЕНО — `vehicles.excel_table_id` отсутствовал в миграции

Найдено при написании Feature-теста на `VehicleCommandImport` (§8.1): вставка падала с
`SQLSTATE[HY000]: table vehicles has no column named excel_table_id`. При этом поле активно
используется — `Domain/ModelData/Vehicle/VehicleData` (`excelTableId`), обе фабрики/сервиса
апсерта ТС (`VehicleDataFactory`, `UpsertVehicleFromSheetService` — построчный импорт из
"ручного" листа, где это ID строки Google-таблицы) и экспорт (`VehicleExportRow::getBaseData()`
читает `$vehicle->excel_table_id` как первую колонку "ID Гугл таблицы"). Колонка есть на
`modifications` (та же семантика), но по ошибке отсутствовала на `vehicles` — значит, **любой
импорт ТС через `UpsertVehicleFromSheetService` (лист с ручными правками) падал бы в проде**,
а экспорт молча отдавал бы `null` в первой колонке.

**Исправление:** добавлена `$table->string('excel_table_id')->nullable()` в
`database/migrations/2026_06_17_100003_create_vehicles_table.php` (миграции ещё не запускались
в проде — правка самого файла миграции, а не новая миграция, безопасна).

### 6.5. ✅ ИСПРАВЛЕНО — `EngineDataFactory` отклонял реальные (числовые) значения `engine_capacity`

Найдено при написании Feature-теста на `EngineCommandImport` (§8.1): `engine_capacity`
валидировался правилом `['nullable', 'string']`, но реальные файлы (`storage/vehicles/
engines.csv`, колонка "Объем двигателя (л.)") содержат чистые числа (`3.6`, `4`, `1.8`) — Excel/
CSV-ридер `maatwebsite/excel` парсит такие ячейки как PHP `int`/`float`, а не `string`, и
`Rule::string()` отклоняет их с `ValidationException`. Поскольку адаптер ловит это исключение
через `SkipsOnFailure` (не роняет импорт, только логирует), **эффект в проде — не крэш, а
тихий импорт нуля двигателей**: каждая строка реального файла валилась бы в `onFailure`
незаметно, если специально не читать логи построчно.

**Исправление:** правило ослаблено до `['nullable']` (тип колонки в БД и так `string`, а
значение — не бизнес-инвариант, где важно строгое различение int/string), а
`EngineDataFactory::make()` явно приводит значение к `(string)` перед передачей в
строго типизированный (`strict_types=1`) конструктор `EngineData`.

---

## 7. Архитектурные слабые места (P1)

### 7.1. Цепочка импорта — синхронная по доменным событиям, без чекпоинтов и resume

Пайплайн (`EventAudit.md`) — это 5+ шагов, последовательно запускаемых **тонкими листенерами
на доменные события**, а не явным оркестратором с шагами и состоянием прогресса:
`Manufacturer → {Vehicle, Engine (параллельно)} → Modification → (оба готовы) → EngineModification`.

Проблемы такого решения:
- **Нет персистентного состояния прогресса.** `EngineModificationReadinessGate` хранит только
  два булевых флага в кэше (`forever`), остальной прогресс цепочки нигде не фиксируется. Если
  процесс упадёт между шагами (например, после Vehicle, но до Engine), нет способа понять,
  "на чём остановились" и продолжить — только полный перезапуск `tecDoc-import-cars` с начала
  (а `gate->reset()` явно сбрасывает даже частично готовое состояние).
- **`tries=1` везде** (см. `config/horizon.php` `supervisor-default`/`supervisor-inbox`,
  оба `'tries' => 1'`) — единственный сбойный чанк (даже не баг, а, например, временный
  deadlock в Postgres) убивает весь дальнейший каскад без повторной попытки.
- **Нет атомарности между сущностями.** Если импорт Engine упадёт после того, как Vehicle/
  Modification уже записаны, в БД останется частично согласованный каталог без явного
  индикатора "это не финальное состояние".

Это нормальный компромисс для MVP с разовым импортом, но если пайплайн рассчитан на регулярные
прогоны (а судя по `app:tecDoc-import-cars`, "сбрасываем флаги на случай прерванного предыдущего
запуска" — да, рассчитан), стоит явно спроектировать stateful orchestration (хотя бы таблица
`import_runs` со статусами шагов) вместо implicit-состояния в Cache + cascade событий.

### 7.2. Жёстко зашитые пути к файлам

`StartVehicleImportListener`, `StartEngineImportListener`, `TecDocImportCars` и другие держат
буквальные строки вида `storage_path('vehicles/vehicles.csv')` прямо в коде. Последствия:
- Нельзя запустить два независимых импорта параллельно (разные файлы/окружения) — путь один
  на весь сервис.
- Нельзя дать произвольный (загруженный) файл — пайплайн жёстко привязан к локальному диску
  и фиксированному имени, что противоречит решению из `RESEARCH.md` ("S3 для отчётов об
  ошибках") — входные файлы остаются на локальном FS, выходные уже планируются в S3,
  непоследовательно.
- Путь должен быть частью данных, которые несёт событие/контекст запуска — не константой
  внутри Listener'а. **Не относится к `ImportRunContext` из §6.3**: тот покрывает 4 отдельных
  класса "личного" импорта (нет своего файла по умолчанию — путь и так параметр `import()`),
  а хардкод путей живёт в TecDoc-каскаде (`Start*ImportListener`, `TecDocImportCars`), у
  которого своя, не пересекающаяся с `ImportRunContext`, зона ответственности. Остаётся
  открытым пунктом.

### 7.3. ✅ ИСПРАВЛЕНО (для 4 классов из §6.3) — магические cache-ключи вместо явного идентификатора прогона

`cacheKey = "..._{$this->importedByUserId}"` / `lockKey = "..._lock_{$userId}"` — зависели
только от `userId`. Если один и тот же пользователь запустит импорт повторно до завершения
предыдущего, оба прогона писали бы ошибки в **один и тот же** Cache-ключ — гонка и
перемешивание отчётов. Затронуто ровно то же множество классов, что и в §6.3 (`CachesImportFailures`
используется только там: `VehicleMultiSheetImport`+суб-листы, `EngineMultiSheetImport`+суб-листы,
`EngineCrossImport`, `EngineSparkPlugSpecificationImport`) — TecDoc-каскад этот трейт не
использует вообще (просто логирует построчно), так что для него проблемы никогда не было.

**Исправлено вместе с §6.3:** `ImportRunContext.runId` (не `userId`) — основа и cache-ключа,
и lock-ключа во всех перечисленных классах. TTL (фиксированные 5 минут в
`CachesImportFailures::onFailure()`) пока не тронут — отдельный, более мелкий риск (не
коллизия ключей, а протухание при долгом прогоне), можно занести в P2 отдельным пунктом,
если на практике окажется проблемой.

### 7.4. Схема БД: нет временных меток и недостающие индексы

Все ключевые таблицы каталога — `manufacturers`, `vehicles`, `engines`, `modifications`,
`engine_modification` — созданы **без `timestamps()`** (только `part_specifications` и
`features`/`feature_values` их имеют). Следствия:
- Невозможно понять, когда запись создана/последний раз обновлена импортом — нет аудита,
  нет возможности построить инкрементальный импорт ("что изменилось с прошлого раза") без
  полного сравнения всего файла.
- Затрудняет диагностику инцидентов ("эта запись из вчерашнего импорта или из позапрошлого").

Также: `engine_modification` (`database/migrations/..._create_engine_modification_table.php`)
не имеет составного уникального индекса на `(engine_id, modification_id)` — корректность
сейчас полностью зависит от прикладной логики `syncWithoutDetaching` в
`EngineModificationCommand`; при параллельной записи (например, два воркера на одинаковых
строках) возможна гонка/дубль на уровне БД, которую сейчас ничто не предотвращает на уровне
схемы.

Отдельно: `foreignId()->constrained()` в Postgres **не создаёт автоматически вторичный индекс**
на колонке внешнего ключа (в отличие от MySQL/InnoDB) — он только создаёт сам constraint.
Для таблиц вида `modifications.vehicle_id`, `engine_modification.{engine_id,modification_id}`,
`part_specifications.partable_*` это значит, что JOIN/WHERE по FK-колонкам без отдельного
`->index()` будут делать seq scan по мере роста каталога. Стоит явно пройтись по миграциям
и добавить `->index()` (или составные уникальные индексы там, где это и есть бизнес-инвариант,
как с `engine_modification`).

**Исправление:** добавить `timestamps()` во все таблицы каталога (или хотя бы `updated_at` —
обсудить с владельцем, не противоречит ли это решению "не было `created_at` намеренно"), добавить
уникальный составной индекс `engine_modification(engine_id, modification_id)`, пройтись по
FK-колонкам и добавить индексы там, где они используются в WHERE/JOIN (Repository-классы — самый
быстрый способ найти такие места).

### 7.5. "Толстые" Presentation-команды (уже зафиксировано в `Final-audit.md`, П.1)

Подтверждаю находку из `Final-audit.md`: `ChangeProviderManufacturersToTD`,
`UpdateVehicleYears`, `UpdateModificationYears` гоняют `Model::query()->get()` +
`foreach`+`update()` прямо в `handle()` — N+1 update'ов (по одному `UPDATE` на запись вместо
одного bulk `UPDATE ... WHERE`), плюс бизнес-правило ("исправляем `2025` на `null`",
"меняем `OD` на `TD`") живёт в Presentation-слое, а не в Application/Domain — нарушает
собственную же `ARCHITECTURE.md` ("Presentation максимально тонкие"). `GroupEnginesCommand`
помечен `@deprecated`, но всё ещё лежит в дереве команд и регистрируется наравне с
действующими — стоит либо удалить, либо явно исключить из автозагрузки команд, иначе он рискует
быть вызван по привычке.

**Исправление:** вынести правило в `Application/.../Services` (см. §2 — без отдельного
`UseCase`/`Support`, сразу `Service`) с bulk-`update()`-запросом через Repository/Command-порт
(например, `ManufacturerCommand::bulkSetProvider(...)`), команда — тонкий вызов.
`GroupEnginesCommand` — удалить физически, если деприкейт подтверждён владельцем (раз он не
интерфейсирован и не протестирован — он уже фактически outside the architecture). При переезде
на feature-first (§1) обе правки естественно совмещаются с переносом в фичу `Maintenance`.

---

## 8. Тестирование и качество кода

### 8.1. Тестовая пирамида перевёрнута: 100% покрытия там, где баги невозможны, 0% — там, где они есть

`tests/Unit/Vehicles/*` (12 файлов) добротно покрывают `Application/*Service` классы — чистые,
с моками портов, без побочных эффектов. Это хорошо и стоит продолжать. Но:
- **Ни одного теста** на `Infrastructure/Imports/*` (Excel-адаптеры) — именно там лежит баг
  §6.1, и именно туда заходит реальный файл/чанк/коллекция.
- **Ни одного теста** на `EventServiceProvider`-проводку (что нужное событие реально вызывает
  нужный листенер) — баг §6.3 (гейт `userId > 0`) живёт на стыке Listener↔Service, тоже
  непокрытом.
- **Ни одного теста** на `InboxConsumer`/`RabbitMQPublisher` — баг §6.2 живёт именно здесь.
- **Ни одного теста** на `Repository`/`Command` классы против реальной (sqlite in-memory) БД —
  значит, миграции (которые "ещё не запускались", по `RESEARCH.md`) тоже никогда не
  валидировались тестами. `tests/Feature/ExampleTest.php` и `tests/Unit/ExampleTest.php` —
  это нетронутые шаблонные тесты Laravel by default.

**Рекомендация:** добавить минимум один **Feature**-тест, который реально гоняет
`Excel::import` на крошечном фикстурном CSV (2-3 строки) через весь адаптер
(`ManufacturerCommandImport` → реальный `ManufacturerCommand` → sqlite `:memory:` с
прогнанными миграциями, `QUEUE_CONNECTION=sync` уже выставлен в `phpunit.xml`). Такой тест
поймал бы баг §6.1 за секунды. Это не "больше тестов ради покрытия" — это единственный слой,
который сейчас вообще не верифицируется, и именно там нашлись все runtime-баги.

### 8.2. Нет статического анализа

В `composer.json` нет `phpstan`/`larastan` (только `pint` для стиля). Баг §6.1
(`$this->useCase` — обращение к необъявленному typed-свойству) — ровно тот класс ошибок,
который PHPStan на уровне 5+ ловит мгновенно и без необходимости что-либо запускать.
Дешёвое усиление: добавить `larastan` в `require-dev` и прогонять в CI.

### 8.3. Нет CI

В репозитории нет `.github/workflows` ни одного. `composer.json` уже содержит готовый
`composer test` скрипт — его сейчас никто не запускает автоматически при пуше/PR. Значит,
баг §6.1 (и любой будущий регресс такого рода) тихо доедет до прод-деплоя.

---

## 9. Эксплуатационные риски (P2)

### 9.1. Несостыковка queue-коннекшенов между Excel-импортами и Horizon

`config/horizon.php` определяет супервизоры **только** для `vehicles` и `vehicles_inbox`
(оба — RabbitMQ). Все `ShouldQueue`-классы импорта (`VehicleCommandImport` и т.д.) не
переопределяют `$connection`/`$queue`, значит они уйдут на **дефолтный** `QUEUE_CONNECTION`
(в `.env.example` — `database`). В `docker-compose.yml` нет ни одного сервиса
`queue:work --queue=default` для `database`-коннекшена — поднимается только `horizon`. Если в
проде `.env` тоже не переопределяет `QUEUE_CONNECTION` явно на `vehicles`, queued-чанки
Excel-импорта будут складываться в таблицу `jobs` и **никогда не обработаются** ни одним
воркером. Стоит явно зафиксировать (тестом или конфигом), на каком коннекшене реально
работают Excel-импорты, а не полагаться на дефолт окружения.

### 9.2. ✅ СНЯТО — `CustomRabbitMQQueue` тихо переопределяла внутренний метод пакета

Свой `Infrastructure/Messaging/Workers/CustomRabbitMQQueue.php` (оверрайдил `protected function
event()` `vladimir-yuldashev/laravel-queue-rabbitmq`, "во избежание конфликтов с Horizon") удалён
вместе со всей `Infrastructure/Messaging/*` (§6.2). `config/queue.php` теперь использует
`PkmStudio\RabbitTransport\Workers\CustomRabbitMQQueue` — тот же приём, но сопровождается
отдельно от `dan-vehicles`, версию `vladimir-yuldashev/laravel-queue-rabbitmq` фиксировать
для этого больше не нужно.

### 9.3. Мелкая гигиена репозитория

- `.phpunit.result.cache` закоммичен в репозиторий (артефакт локального прогона тестов, не
  нужен в git).
- `database/database.sqlite` — гитигнорен паттерном `*.sqlite*`, но физически лежит в дереве,
  убедиться, что он не содержит "залежавшихся" локальных данных, случайно нужных кому-то ещё.

---

## 10. Сильные стороны (чтобы план не выглядел только критикой)

- Слоистая архитектура (`Domain/Application/Infrastructure/Presentation`) проведена
  последовательно и **документирована** — большая редкость для проекта такого размера;
  `ARCHITECTURE.md`/`RESEARCH.md` явно фиксируют не только решения, но и открытые развилки —
  это снижает риск архитектурного дрейфа лучше любого ревью.
- Чёткое разделение чтения/записи через `ModelData`/Repository-Command (CQRS-lite) — это
  сильно облегчает именно то исправление, которое требуется в §6.3/§7.3 (заменить `Auth::id()`
  на явный контекст не потребует трогать домен).
- Доменные перечисления (`Enums/*`) и явная защита от грабли `tryFrom` (зафиксирована в
  `ARCHITECTURE.md` — "Грабли") показывают, что команда уже наступала на похожие проблемы
  и фиксирует уроки в документации, а не только в памяти.
- Self-аудит (`Final-audit.md`) — владелец/команда уже целенаправленно ищут архитектурные
  нарушения сами, до внешнего ревью. Этот документ продолжает ту же практику, а не открывает
  её впервые.

---

## 11. План действий по приоритету

**P0 — пайплайн не работает, чинить до любого реального прогона:**
1. ✅ Исправить `$this->useCase` → `$this->service` в 10 местах (§6.1).
2. ✅ Заменить `Infrastructure/Messaging/*` на пакет `pkmstudio/rabbit-transport` (§1, §6.2).
   Заодно включён DLQ (`vehicles.inbox.dlx`/`.dlq`) — раньше было бы дороже (пересоздание
   очереди), пока она пустая — бесплатно. Побочный фикс: `pkmstudio/rabbit-transport`
   требовал `illuminate/*: ^12.0`, для Laravel 13 (`^13.0`) виджено и запушено в сам пакет.
3. ✅ Заменить `Auth::id()`-зависимость на явный `ImportRunContext` (`userId: int` обязателен —
   уточнено при реализации, эти 4 класса не про безличный запуск), убрать гейт `> 0` (§6.3).
   Заодно закрыло §7.3 для тех же 4 классов (cache/lock-ключ на `runId`, не на `userId`).
4. ✅ Добавить Feature-тесты, реально гоняющие импорт через адаптер на фикстурных данных —
   регрессионный тест для пункта 1 (§8.1). Сделано на все 10 адаптеров
   (`tests/Feature/Vehicles/{Manufacturer,Vehicle,Modification,Engine,EngineModification,
   EngineCross,EngineSparkPlugSpecification,EngineMainSheet,EngineSparkPlugsSheet}ImportTest.php`
   + `ReportImportResultListenerTest.php`).
5. ✅ Добавить недостающую колонку `vehicles.excel_table_id` — найдено при написании теста
   для пункта 4, не связано с `$this->useCase` (§6.4).
6. ✅ Ослабить валидацию `engine_capacity` в `EngineDataFactory` (реальные числовые значения
   не проходили правило `'string'`, реальный импорт двигателей давал бы 0 строк) — найдено
   при написании теста для пункта 4 (§6.5).

**P1 — следующим заходом:**
7. ✅ Реструктуризация папок на feature-first по схеме §1 (Shared/Templates/Import/Export/
   Maintenance) — без переезда `Domain/Models`/Repository/Command (см. §1, осознанно
   отложено вместе с пунктом 9). 48/48 тестов зелёные после переезда.
8. ✅ Перевести Application-слой Import/Export на единый `Service`, убрать `UseCases`/`Support`
   (§2) — подтверждено физически: `Support/`/`UseCases/`-папок в дереве нет ни у одной фичи.
9. ✅ Внедрить `spatie/laravel-data` (§3) — **готово для Import, Export и Maintenance**.
   У каждой фичи своя копия Eloquent-моделей (`Import/Infrastructure/Models`,
   `Export/Infrastructure/Models`, `Maintenance/Infrastructure/Models`) и своя копия
   Repository (кроме Maintenance — там раньше и не было слоя Repository, работает напрямую
   через Eloquent, как и было задумано для "разовых фиксов каталога"), отдающая
   `<Entity>Data`; Command (запись) остаётся исключительно у Import. Временный общий
   `VehiclesServiceProvider` вместе со старыми `Domain/Contracts/Infrastructure/Repositories`
   и `Infrastructure/Repositories` удалён — обещание из §1 ("исчезнет вместе с переездом на
   spatie/laravel-data") выполнено буквально. `app/Vehicles/Domain` удалена целиком без
   исключений — общей копии моделей больше нет ни у одной фичи.
   Побочные находки/решения по ходу Import:
   - **`partable_type` — буквальное имя PHP-класса как дискриминатор полиморфной связи.**
     Раз Vehicle/Engine дублируются по фичам, писать туда `::class` копии модели конкретной
     фичи нельзя — разные фичи получили бы разные строки для одной и той же сущности, а
     Maintenance (не участвует в переезде) и уже существующие строки в БД остались на
     `App\Vehicles\Domain\Models\{Vehicle,Engine}`. Решение: Import использует общий
     `Domain\Models\Vehicle::class`/`Engine::class` (через алиасированный `use ... as
     PartableVehicleType`) как стабильное значение именно этой колонки, при этом сам
     Vehicle/Engine для запросов и связей — свои, локальные для фичи. Тот же вопрос встанет
     для Export при его переезде — если тогда введём нормальный `Relation::morphMap()`, эту
     договорённость нужно будет пересмотреть заодно.
   - **`WiperSpecificationServiceInterface`** (Templates, общий для Import/Export/Maintenance)
     трёх методов принимал Eloquent `PartSpecification` (`detectSideByPartSpecification`,
     `normalizeVehicleAdapters`, `splitSpecification`) — сломалось бы для Import, у которого
     теперь своя копия модели. Поменяно на примитивы (`array $details`, `?int
     $partSpecificationId`) — сервис и так был "чистый, только массивы" везде, кроме этих
     трёх мест; `detectSideByPartSpecification` за ненадобностью убран целиком (вызывающий
     теперь сам передаёт `$candidate->details` в `detectSide()`). Задело Maintenance
     (`VehicleWiperPartSpecificationSplitService`) — два места, оба обновлены.
   - **`EngineData`/`VehicleData`/`ModificationData`**: поля, зеркалящие enum-cast колонки
     модели (`type`, `steeringType`, `typeCarcase`, `engineType`, `gearType`, `driveType`,
     `brakeSystemType`, `engFuelType`), переведены со `string` на реальный enum-тип. Без этого
     `EntityData::from($model)` падает: `getAttribute()` отдаёт уже закастованный enum-объект,
     а конструктор ждал строку. `spatie/laravel-data` кастует enum туда-обратно сам (из
     строки/массива в объект и обратно в `toArray()`), доп. кода не потребовалось — только
     обновить типы и 2 фабрики (`VehicleDataFactory`, `ModificationDataFactory`), которые
     раньше передавали сырую строку напрямую.
   - **`Command::update()`/`setGroupId()`/`delete()`** раньше принимали живой Eloquent-объект
     (`update(Manufacturer $model, ManufacturerData $data)`) — теперь принимают только
     `<Entity>Data` с обязательным для этих операций `id` (identity вместо живого объекта).
     Единственный реальный вызывающий (`VehicleWiperSpecificationImportService::execute()`)
     обновлён — компонует новый `PartSpecificationData` с `id: $existing->id`.
   - **`ModificationData::$engines`** — единственное место, где понадобилась "не универсальная"
     форма из caveat в §3: `Repository::firstByMsIdAndModIdWithEngines()` кладёт туда
     `EngineData[]` (eager-loaded `engines`), никто другой это поле не трогает и не пишет —
     `Command` явно исключает `id`/`engines` из payload на запись (`Arr::except`), это не
     колонки таблицы `modifications`.
   - **`EngineData::$groupId`** — то же самое по духу: раз Repository теперь отдаёт `group_id`
     (иначе `AssignEngineGroupService` не мог бы прочитать текущее значение), `Command` обязан
     исключать `group_id` из `create`/`update`/`upsertByEngId`, иначе обычный upsert из листа
     импорта тихо затирал бы группу, назначенную отдельным путём (`setGroupId`).

   Побочные находки/решения по ходу Export (в дополнение к Import, без повторов):
   - **Export дублирует только 5 сущностей, а не все 8**, в отличие от Import: `Vehicle`,
     `Manufacturer`, `Engine`, `PartSpecification`, `FeatureValue` — единственные, которые
     Export реально читает. `Modification`/`EngineModification`/`Feature` сознательно не
     скопированы — у Import они дублировались только потому, что Command (запись) физически
     касается всех 8; у Export такого повода нет. Relation-методы на скопированных моделях,
     ссылавшиеся на недублированные сущности (`Vehicle::modifications()`,
     `Engine::modifications()`, `FeatureValue::feature()`), убраны — иначе это была бы мина:
     `SomeClass::class` на несуществующий класс не падает сразу (это просто строка), падение
     случилось бы только при первом реальном вызове relation.
   - **`getMorphClass()` — более надёжная версия фикса `partable_type` из блока Import.**
     Там, где связь `partSpecifications()` (`MorphMany`) реально используется (Export — весь
     смысл существования, в отличие от Import, где она не вызывается нигде), точечных
     `.where('partable_type', ...)` в Repository недостаточно — сама связь на модели тоже
     резолвится через `get_class($this)` и молча возвращает 0 строк без глобального
     `Relation::morphMap()`. Решение — override `getMorphClass()` на `Vehicle`/`Engine` в обеих
     фичах (Import — про запас, Export — обязательно), возвращающий стабильную строку
     `App\Vehicles\Domain\Models\{Vehicle,Engine}::class`. Проверено вручную (см. ниже) —
     без этого override `forWiperSheet()`/`forSparkPlugSheet()` тихо отдавали бы пустые
     `partSpecifications` для всех строк.
   - **`ExportDetailsBuilder::getDetailsData(Model $model, ...)` → `getDetailsData(array
     $details, ...)`.** Метод использовал только `$model->details` — сужение сигнатуры убрало
     зависимость от Eloquent целиком и заодно убрало странность в `VehicleExportService::
     mapWiperRow()`, где ради вызова создавалась синтетическая `new PartSpecification` только
     чтобы обернуть массив.
   - **`VehicleData`/`EngineData`/`PartSpecificationData` получили вложенные связи**
     (`manufacturer`, `parent` — самоссылка на один уровень, `partSpecifications`,
     `featureValue`) — ровно то, что реально читает код через `$vehicle->manufacturer->name`,
     `$vehicle->parent?->msId`, `$spec->featureValue?->name`. Заполняются только когда
     Repository явно их eager-loads (`forMainSheet`/`forWiperSheet`/`forSparkPlugSheet`) —
     не автоматически: пакет поддерживает автозагрузку через `#[LoadRelation]`, но она явно не
     годится для потенциально двусторонних связей (сам spatie предупреждает про
     бесконечный цикл при `ArtistData` ↔ `SongData`).
   - **Тестов на Export как не было, так и нет** (осознанное решение — рефактор без новых
     тестов). Вместо автотестов правильность проверена вручную одноразовым сценарием
     (создан и удалён, не часть репозитория): main-лист (marka+parent), wiper-лист (merge
     front/back через реальный `WiperRowExpander`, включая `getMorphClass()` — данные
     специально писались через **старую** модель, а читались через **новую**, чтобы
     доказать совместимость), спецификация свечей зажигания двигателя. Все три сценария
     дали ожидаемые данные без ошибок.

   Финальный шаг п.9 — довели до конца и Maintenance, `Domain/Models` удалена насовсем:
   - **Maintenance получил свою копию моделей** (`Maintenance/Infrastructure/Models`) — но
     только 4 сущности, которые реально использует (`Vehicle`, `PartSpecification`,
     `Manufacturer`, `Modification`), а не все 8 как Import. Relation-методы на
     недублированные сущности (`Vehicle::modifications()`, `Modification::engines()`,
     `PartSpecification::featureValue()`) убраны — та же мина с несуществующим классом,
     что и у Export.
   - **`app/Vehicles/Domain` удалена целиком** — общей копии моделей больше нет ни у одной
     фичи, каждая фича независима до конца, без исключений.
   - **`app/Vehicles/Presentation/Http/Controllers/Controller.php` удалён** — пустой
     Laravel-скаффолдинг с самого начала проекта, ничем не используется (нет маршрутов, нет
     `extends`). Presentation — только внутри своей фичи, общей папки для него нет и не
     должно быть.
   - **`PartableTypeEnum` (Shared) — стабильный дискриминатор `partable_type` вместо
     алиасов на конкретный класс.** Пока `Domain/Models` существовала, дискриминатор был
     "буквальный путь к общей модели" (`App\Vehicles\Domain\Models\Vehicle::class` через
     `use ... as PartableVehicleType`). После удаления `Domain/Models` эта строка стала
     ссылкой на несуществующий класс — по существу уже не "путь", а условное имя, которое
     только выглядело как путь. Заменено на `PartableTypeEnum::VEHICLE = 'vehicle'` /
     `::ENGINE = 'engine'` — короткое стабильное имя, не привязанное ни к чьему классу.
     Переименование **без миграции данных** — подтверждено, что реальных строк со старым
     значением в БД ещё нет. `DeduplicatePartSpecificationsCommand` заодно избавлена от
     `class_exists($partableType)` (эта проверка стала бы бессмысленной без реального класса)
     в пользу `PartableTypeEnum::tryFrom($partableType)`.
   - **Побочно найден и исправлен баг** (не связан с переездом, существовал и раньше):
     `PartSpecificationDeduplicationService::processGroup()` падал на
     `(string) $group->template` — `template` кастуется в `DetailTemplateEnum`, а backed enum
     не приводится к строке через `(string)`. Из-за этого `--dry-run`/применение команды
     дедупликации всегда возвращали `errors=1`, маскируя реальный результат. Исправлено на
     `$group->template->value`. Найдено ручным smoke-тестом (создан и удалён).
   - **Побочно найдена, но НЕ исправлена находка отдельного масштаба**: тот же smoke-тест
     показал, что `whereRaw('details = CAST(? AS jsonb)', ...)` (используется в
     `PartSpecificationDeduplicationService`, `VehicleWiperPartSpecificationSplitService` и
     Import-овском `PartSpecificationRepository::firstByVehicleTemplateSideAndDetails`/
     `forVehicleTemplateAndSide`) **на SQLite (тестовое окружение) сравнение никогда не
     совпадает**: SQLite не знает тип `jsonb`, "jsonb" не попадает ни под один паттерн
     type affinity кроме NUMERIC, и `CAST('{"...json...}' AS jsonb)` в NUMERIC affinity
     приводит нечисловую строку к `0` — сравнение с `0` не совпадает никогда. Проверено
     напрямую (`SELECT CAST(? AS jsonb)` вернул `0`). Это объясняет, почему ни один тест в
     проекте никогда не гонял реальный wiper-matching через настоящую БД — на SQLite это
     гарантированно не сработало бы, и никто не написал такой тест. На реальном Postgres
     (нативный `jsonb`) это, вероятно, работает иначе — не проверено. Заведено отдельным
     пунктом ниже (18), чинить нужно отдельно и с оглядкой на тестовое окружение.
   - **`partable(): MorphTo` убран** со всех трёх копий `PartSpecification` (Import/Export/
     Maintenance) — раньше (при общей `Domain\Models`) он случайно работал (Eloquent без
     `morphMap` просто инстанцировал класс по буквенной строке), а после перехода на
     `PartableTypeEnum::VEHICLE = 'vehicle'` гарантированно упал бы ("Class 'vehicle' not
     found"), не будучи при этом нигде вызван. Обсуждали регистрацию `Relation::morphMap()`
     как замену — отклонено: `morphMap` глобальный и однозначный, а копий модели три,
     пришлось бы выбрать одну "канонической" и тем самым тихо восстановить межфичевую
     связанность (тот самый shared kernel) или держать мутабельное глобальное состояние,
     которое пришлось бы вручную переключать на границах фич. Вместо этого — типобезопасный
     резолвер прямо в Repository: `PartSpecificationRepository::partable(PartSpecificationData
     $data): VehicleData|EngineData|null` (Import и Export — у каждой фичи свой, через уже
     существующие `VehicleRepositoryInterface`/`EngineRepositoryInterface` этой же фичи,
     `match` по `PartableTypeEnum`). У Maintenance — без замены: там нет ни `Repository`-слоя,
     ни своей копии `Engine`, резолвить всё равно нечем и незачем. Проверено smoke-тестом
     (создан и удалён): резолв Vehicle-владельца, Engine-владельца, null при отсутствии.
10. Подключить `larastan`/`phpstan` + базовый CI (`.github/workflows`: `pint --test`, `phpstan`,
    `composer test`) (§8.2, §8.3).
11. Вынести "толстые" Presentation-команды (`ChangeProviderManufacturersToTD`,
    `UpdateVehicleYears`, `UpdateModificationYears`) в Application-слой с bulk-update (§7.5).
    `GroupEnginesCommand` (deprecated) уже удалён при реструктуризации по фичам (п.7).
12. Добавить `timestamps()` в таблицы каталога, уникальный индекс
    `engine_modification(engine_id, modification_id)`, пройтись по FK-индексам (§7.4).
13. Параметризовать пути к файлам импорта вместо хардкода в Listener'ах TecDoc-каскада (§7.2)
    — `runId`-based cache-ключи для 4 "личных" классов уже сделаны вместе с §6.3 (см. §7.3),
    сюда не относится.

**P2 — по мере роста нагрузки/команды:**
14. Спроектировать stateful-оркестрацию импорта (таблица `import_runs` со статусами шагов)
    вместо implicit-состояния в Cache + каскада событий (§7.1).
15. Зафиксировать/проверить реальный queue-коннекшен Excel-импортов в проде (§9.1).
16. Убрать `.phpunit.result.cache` из git (§9.3).
17. Завести реальные `inbound`/`outbound`-обработчики в `rabbit-transport`, когда появится
    первая интеграция (см. примечание в §1 про Messaging).
18. ~~Починить сравнение jsonb-деталей на SQLite~~ — **решено**: тесты переведены с SQLite
    `:memory:` на выделенную Postgres-БД `dan_vehicles_test` (тот же сервер `pgsql`, роль
    `dan_vehicles`), см. `phpunit.xml` (`DB_CONNECTION=pgsql`, `DB_DATABASE=dan_vehicles_test`).
    `whereRaw('details = CAST(? AS jsonb)', ...)` теперь и в тестах, и в проде выполняется на
    одном движке БД, поэтому расхождение SQLite/Postgres по типу колонки больше не
    воспроизводимо в принципе — вариант (в) из старой формулировки. Все 51 тест зелёные на
    Postgres. Отдельного покрытия `PartSpecificationDeduplicationService`/
    `VehicleWiperPartSpecificationSplitService` тестами по-прежнему нет — это не покрытие
    jsonb-сравнения, а отсутствие тестов на эти сервисы вообще, отдельная задача при желании.
