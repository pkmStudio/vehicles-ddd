# План: весь P1 из `refactor-new.md`

> Статус: план одобрен, реализация не начата (отложено). Начинать с раздела «Порядок работы»
> внизу — #4 (мёртвый код) → #3 (export/filePrefix) → #2 (AbstractRowCommandImport).

## Контекст

`refactor-new.md` — результат сквозного аудита `app/Vehicles/` осенью этой сессии. P1 — три
находки высокого приоритета (реальное дублирование логики или мёртвый код с риском рассинхрона).
Одна из трёх (`#1`, общий DSL-обходчик Export↔Import) уже сделана в отдельном рефакторинге
(field-template DSL → `Data`-классы, коммит `8b34be9`) и помечена ✅. Остаются:

- **#2** — 5 почти идентичных Command-Import адаптеров (Import).
- **#3** — дублирование `export()`/имени файла между `EngineMultiSheetExport`/
  `VehicleMultiSheetExport`/`CleanupStaleExportFilesService` (Export).
- **#4** — список мёртвого кода (модели/методы/классы без единого вызывающего).

Перепроверил находки по актуальному коду (`grep` по всему `app/`) — часть уже неактуальна:
- **`VehicleRepositoryInterface::find()/findOrFail()`, `EngineRepositoryInterface::find()/
  findOrFail()`, `VehicleRepository::all()`** — этих методов в коде уже нет (видимо, убраны в
  одном из предыдущих проходов). Из плана исключаю.
- **`EnginesCodeImport`** — с момента аудита класс получил явный `@deprecated`-докблок:
  «нигде не резолвится... оставлен как заготовка, не удалять до решения по фиче в целом».
  Решение уже принято другим человеком — **не трогаю**, исключаю из плана.
- **`FailuresExportInterface`** (пустой DI-маркер) — низкий приоритет, рекомендация была
  «не обязательно удалять». Оставляю как есть, не трогаю (экономлю время на реально мёртвом коде).

Итого в работе: #2, #3, и 5 конкретных мёртвых мест из #4.

---

## #2. Общий базовый класс для Command-Import адаптеров

**Файлы:** `EngineCommandImport.php`, `ManufacturerCommandImport.php`,
`ModificationCommandImport.php`, `VehicleCommandImport.php` (все в
`Import/Infrastructure/Imports/<Entity>/`), `EngineModificationImport.php`
(`Import/Infrastructure/Imports/EngineModification/`).

Перечитал все 5 — подтверждаю находку 1:1. Общий скелет: `import()` (`Excel::import($this,
$path)`), цикл в `collection()` (map → service, `catch(ValidationException|
InvalidArgumentException)` → `onFailure`), идентичный `onFailure()` (`Log::error` по 4 полям),
`startRow(): 2`. Различия: entity-label в сообщении, `chunkSize()` (100 или 500), у Vehicle/
Modification — доп. проверка «сущность не найдена» после вызова сервиса, `registerEvents()` есть
у всех кроме `EngineModificationImport`.

**Новый класс:** `Import/Infrastructure/Imports/AbstractRowCommandImport.php`
(`implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow`):
- `import()`, `startRow()`, `onFailure()` — переезжают как есть (без вариаций между классами).
- `collection()` — становится шаблонным методом: цикл + try/catch остаётся здесь, но вместо
  инлайн map+service — вызывает `abstract protected function processRow(array $rowValues, int
  $line): void`, а `catch`-ветки зовут защищённый `fail(int $line, string|array $errors, array
  $values)`, который строит `Failure` с `$this->entityLabel()`.
- `abstract protected function entityLabel(): string` (Russian label, как сейчас — 'Двигатель',
  'Производитель' и т.д., идёт в `Failure::attribute`).
- `chunkSize()` остаётся abstract (значения 100/500 не унифицируем, остаются как есть).
- `registerEvents()` **не** входит в базовый класс и не реализует `WithEvents` — эту пару
  (интерфейс + метод) 4 из 5 классов объявляют сами, как сейчас; `EngineModificationImport`
  просто их не объявляет (без изменений в его поведении).
- Конструкторы (Service+Mapper, разные типы) остаются в каждом конкретном классе — DI-инъекция
  типобезопасная, менять незачем (см. ARCHITECTURE.md «DI — вариант А»).

Каждый из 5 файлов после рефакторинга: `extends AbstractRowCommandImport`, конструктор,
`chunkSize()`, `entityLabel()`, `processRow()` (map + вызов сервиса +, где было, пост-проверка
«не найдено» через `$this->fail(...)`), `registerEvents()` (где было). Реализация сервиса/
маппера/эвента не меняется — только форма адаптера.

**Наблюдение, которое стоит явно принять:** сейчас `onFailure()` у каждого класса пишет разный
текст лога (`'Engine import failure'`, `'Manufacturer import failure'`, …). В общем классе он
станет одним и тем же для всех пяти (например, `Log::error('Import row failure', ['entity' =>
$this->entityLabel(), ...])`) — единственное наблюдаемое изменение поведения (только текст записи
в лог, не структура `Failure`/не бизнес-логика). Ни один тест текст лога не проверяет.

**Проверка:** `EngineImportTest`, `ManufacturerImportTest`, `ModificationImportTest`,
`VehicleImportTest`, `EngineModificationImportTest` — уже гоняют эти 5 адаптеров end-to-end,
достаточно прогнать их после рефакторинга.

---

## #3. Единый источник имени файла экспорта

**Файлы:** `Export/Infrastructure/Exports/Engine/EngineMultiSheetExport.php`,
`Export/Infrastructure/Exports/Vehicle/VehicleMultiSheetExport.php`,
`Export/Application/Services/External/CleanupStaleExportFilesService.php`,
`Export/Domain/Enums/ExportTypeEnum.php` (уже существует: `case Vehicle`/`case Engine`, сейчас
используется только `ExportFileFactory` для выбора адаптера по типу из RabbitMQ-сообщения).

1. Добавить `ExportTypeEnum::filePrefix(): string` (`match` → `'vehicle-catalog'`/
   `'engine-catalog'`).
2. Новый `Export/Infrastructure/Exports/AbstractMultiSheetExport.php`: абстрактный `export()`
   (resolve disk/directory из конфига → `sprintf('%s/%s-%s.xlsx', $directory,
   $this->exportType()->filePrefix(), $context->runId)` → `ExcelFacade::store` → `return $path`),
   опирающийся на `abstract protected function exportType(): ExportTypeEnum`.
3. `EngineMultiSheetExport`/`VehicleMultiSheetExport` → `extends AbstractMultiSheetExport`,
   оставляют только `exportType()` (1 строка) и `sheets()` (без изменений; у Vehicle — свой
   конструктор с `$isAllow`, тоже без изменений).
4. `CleanupStaleExportFilesService::FILE_PATTERNS` (сейчас захардкожен
   `['vehicle-catalog-*.xlsx', 'engine-catalog-*.xlsx']`) → заменить на генерацию из
   `ExportTypeEnum::cases()`: `array_map(fn (ExportTypeEnum $t) => $t->filePrefix() . '-*.xlsx',
   ExportTypeEnum::cases())`. Один источник истины вместо трёх мест, которые должны были вручную
   совпадать.

**Проверка:** `EngineMultiSheetExportTest`, `VehicleMultiSheetExportTest` (реальная генерация
xlsx + чтение обратно) — покрывают `export()`. Для `CleanupStaleExportFilesService` тестов нет
(до этой сессии не было и не планировалось) — проверю вручную, что паттерны совпадают со старыми
литералами (`vehicle-catalog-*.xlsx`, `engine-catalog-*.xlsx`), не буду заводить новый тестовый
файл ради этого рефакторинга (вне текущего скоупа).

---

## #4. Мёртвый код — 5 точечных правок

Каждая проверена `grep` по всему `app/` прямо перед этим планом — подтверждаю, что использующих
не осталось.

1. **Удалить `Import/Infrastructure/Models/EngineModification.php`.** Проверил: `->pivot` нигде
   не читается (запись pivot-строки идёт через `Engine::modifications()->syncWithoutDetaching()`
   с сырым массивом, класс модели в этом пути не участвует). `$casts['type']` мёртв полностью —
   удаляю модель, не подключаю через `->using()` (создавать типизацию ради несуществующего
   потребителя незачем).
2. **Удалить `Import/Domain/ModelData/FeatureData.php`.** Сиротский класс, ни одного упоминания
   нигде, кроме себя самого.
3. **Убрать `Manufacturer::vehicles(): HasMany`** из
   `Export/Infrastructure/Models/Manufacturer.php`. Неиспользуемая обратная связь.
4. **Убрать `PartSpecification::vehicle(): BelongsTo`** из
   `Export/Infrastructure/Models/PartSpecification.php`. Неиспользуемая и семантически неполная
   связь (жёстко привязана к `Vehicle`, хотя `partable_type` полиморфно указывает и на `Engine`).
5. **Убрать `getVehicleAdapterCount()`/`sanitizeDetailsForSide()`** из
   `Templates/Domain/Contracts/WiperSpecificationServiceInterface.php` и реализации
   `Templates/Application/WiperSpecificationService.php` (вместе с приватными хелперами,
   которые только они и используют, если такие останутся без вызывающих после удаления —
   перепроверю по факту).

**Проверка:** полный набор тестов (`php artisan test` в контейнере `app-vehicles`) — эти классы/
методы не имеют посвящённых тестов (они и были мёртвыми), так что маркер успеха — отсутствие
регресса во всём наборе (сейчас 76 зелёных).

---

## Порядок работы и верификация

1. #4 (мёртвый код) — самое безопасное, делаю первым, прогоняю полный набор тестов.
2. #3 (export/filePrefix) — прогоняю `EngineMultiSheetExportTest`/`VehicleMultiSheetExportTest`.
3. #2 (AbstractRowCommandImport) — самое объёмное, делаю последним, прогоняю все 5
   Import-feature-тестов + полный набор в конце.
4. Обновить `refactor-new.md`: пометить #2, #3 и 5 пунктов из #4 как ✅ (по образцу того, как
   уже помечен #1), с кратким «как сделано» под каждым, как в прошлый раз.
5. Не трогаю `ARCHITECTURE.md` в этом заходе, если новых конвенций не появится (оба новых
   абстрактных класса уже вписываются в существующее правило `Abstract<Noun>` — добавлю только
   если потребуется реально новое обобщение, а не просто новый файл по уже описанному паттерну).
