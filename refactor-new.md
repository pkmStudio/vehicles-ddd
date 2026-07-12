# Рекомендации по рефакторингу — `app/Vehicles/`

Документ собран по итогам сквозного аудита кода домена `app/Vehicles/` (фичи `Export`, `Import`,
`Maintenance`, `Templates`, `Shared`) против конвенций, описанных в `ARCHITECTURE.md`. Каждая
находка проверена по факту использования в коде (`grep` по всему `app/`/`tests/`), а не только
по внешнему виду — то, что выглядело дублированием, но было осознанным архитектурным решением
(например, дублирование Eloquent-моделей по фичам), в список не включено.

Находки сгруппированы по приоритету: **P1** — стоит сделать в первую очередь (реальное
дублирование логики или явный мёртвый код с риском рассинхрона), **P2** — стоит сделать, но не
горит, **P3** — низкий приоритет / на усмотрение.

---

## P1 — сделать в первую очередь

### 1. Общий DSL-обходчик шаблона деталей вместо двух копий (Export ↔ Import)

`ExportDetailsBuilder::getFieldValue()` (`app/Vehicles/Export/Application/Services/Details/ExportDetailsBuilder.php:96-142`)
и `DetailsBuilder::getFieldValue()` (`app/Vehicles/Import/Application/Services/Template/DetailsBuilder.php:75-132`) —
обе обходят один и тот же template-DSL (`children`/`select`/`conditional_select`/`array`), одна
читает `details → row`, другая `row → details`. Блок слияния `options_source` для
`conditional_select` совпадает почти побайтово (включая комментарий на русском), а идиома
`isset($fieldConfig['multiple']) && $fieldConfig['multiple'] === true` продублирована дважды
внутри каждого файла.

Расхождение уже вызвало реальный баг класса «забыли поправить в обеих ветках»: у Export
`getVarValue` не бросает исключение при отсутствии ключа в `variables`, а у Import
`getVarKey`/`getVarKeys` — бросает `\Exception` (`DetailsBuilder.php:196-199`). Одинаковый DSL,
разное поведение на крайний случай.

**Рекомендация:** вынести общий обходчик (структура `children`/`select`/`conditional_select`/
`array` + слияние `options_source` + `isMultiple()`) в фичу `Templates` (там уже живёт
`DetailTemplateResolver`, декларации шаблонов) как переиспользуемый сервис с внедряемым
«кодеком» чтения/записи значения leaf-поля. Export и Import оставляют только тонкие адаптеры,
реализующие сам кодек.

Файлы: `Export/Application/Services/Details/ExportDetailsBuilder.php`,
`Import/Application/Services/Template/DetailsBuilder.php`.

---

### 2. Общий базовый класс для пяти Command-Import адаптеров

`EngineCommandImport.php:30-88`, `ManufacturerCommandImport.php:30-98`,
`ModificationCommandImport.php:30-97`, `VehicleCommandImport.php:30-97` (все в
`Import/Infrastructure/Imports/<Entity>/`) и `EngineModificationImport.php:27-76`
(`Import/Infrastructure/Imports/EngineModification/`) — побитово одинаковый скелет:
конструктор `(Service, Mapper)` → `import(): Excel::import($this, $path)` → `chunkSize()` →
`collection()` с циклом `try { map → service } catch (ValidationException|InvalidArgumentException)
→ onFailure(new Failure(...))` → идентичный `Log::error(...)` по 4 полям → `startRow(): 2`.
Различается только entity-label, `chunkSize`, событие в `registerEvents()` и наличие
пост-проверки «сущность не найдена».

**Рекомендация:** вынести общий шаблон в абстрактный класс
`Infrastructure/Imports/AbstractRowCommandImport` (entity-label, chunkSize, событие/пост-проверка
— параметры/шаблонный метод). Уберёт ~300 строк дублирования, оставив только различия по существу
(маппер + сервис на сущность).

---

### 3. Общий `export()` и единый источник имени файла (Engine ↔ Vehicle multi-sheet export)

`EngineMultiSheetExport::export()` (`Export/Infrastructure/Exports/Engine/EngineMultiSheetExport.php:17-26`)
и `VehicleMultiSheetExport::export()` (`Export/Infrastructure/Exports/Vehicle/VehicleMultiSheetExport.php:21-30`)
дословно совпадают (resolve disk/directory → `sprintf('%s/<prefix>-catalog-%s.xlsx', ...)` →
`ExcelFacade::store`), отличаясь только литералом `engine`/`vehicle`. Тот же формат имени файла
независимо повторяется в третьем месте — `CleanupStaleExportFilesService::cleanup()`
(`Export/Application/Services/External/CleanupStaleExportFilesService.php:20-29`), причём
комментарий в коде сам признаёт связность («см. sprintf в соответствующих export()»). Три места
обязаны синхронно знать один и тот же формат имени, и ничего кроме комментария это не защищает.

**Рекомендация:** завести `ExportTypeEnum::filePrefix(): string` (по аналогии с
`DetailTemplateEnum::templateClass()`) и общий `AbstractMultiSheetExport`/path-resolver сервис,
которым пользуются оба `*MultiSheetExport` и `CleanupStaleExportFilesService` — один источник
истины для формата имени файла.

---

### 4. Мёртвые классы/интерфейсы — удалить или подключить

- **`Import/Infrastructure/Models/EngineModification.php`** — Eloquent-модель нигде не
  используется. `Engine::modifications()` (`Import/Infrastructure/Models/Engine.php:29-33`)
  объявлена как `belongsToMany(Modification::class)->withPivot(...)` **без** `->using(EngineModification::class)`,
  поэтому `$casts = ['type' => VehicleTypeEnum::class]` в модели никогда не применяется. Либо
  удалить модель, либо подключить через `->using()`.
- **`EnginesCodeImport`** (`Import/Infrastructure/Imports/Engine/EnginesCodeImport.php:12-14`) —
  помечен `@deprecated`, но всё ещё забинжен в `ImportServiceProvider.php:148`; метод `parse()`
  нигде не вызывается. Вместе с ним мёртв и порт
  `Domain/Contracts/Imports/Command/EnginesCodeImportInterface.php`. Удалить класс, интерфейс и
  биндинг.
- **`Import/Domain/ModelData/FeatureData.php`** — класс полностью не используется нигде (нет ни
  `FeatureRepositoryInterface`, ни `FeatureCommandInterface`, ни одной фабрики/сервиса).
  Сиротский класс — удалить либо явно пометить как задел на будущее.
- **`Import/Domain/Contracts/Reporting/FailuresExportInterface.php`** — пустой маркерный
  интерфейс без единого метода. Не обязательно удалять (нарушит единообразие DI-биндингов), но
  стоит явно прокомментировать, что это осознанный «пустой DI-маркер».
- **`Export/Domain/Contracts/Repositories/VehicleRepositoryInterface::find()/findOrFail()`**
  (`Export/Infrastructure/Repositories/VehicleRepository.php:15-23`) и аналогичные
  `EngineRepositoryInterface::find()/findOrFail()` — нигде не вызываются в фиче Export. Удалить.
  `VehicleRepository::all()` тоже не используется — удалить либо явно оставить с обоснованием.
- **`Export/Infrastructure/Models/Manufacturer::vehicles(): HasMany`** — неиспользуемая обратная
  связь (Repository грузит только `Vehicle → manufacturer`).
- **`Export/Infrastructure/Models/PartSpecification::vehicle(): BelongsTo`** — не вызывается
  нигде, к тому же жёстко привязана только к `Vehicle` (`partable_type = VEHICLE`), хотя
  `partable_type` полиморфно указывает и на `Engine` — если её когда-то начнут использовать для
  двигателя, она молча вернёт `null`. Именно то, от чего предостерегает собственный комментарий
  над моделью. Удалить.
- **`Shared/Domain/Traits/EnumHelperTrait`** (`names()/values()/toArray()`,
  `Shared/Domain/Traits/EnumHelperTrait.php:12-31`) — подключён в 9 enum'ах
  (`ProviderEnum`, `Engine/*`, `Vehicle/*`), но ни один из методов трейта нигде не вызывается
  (все реальные `::toArray()` в проекте относятся к enum'ам из пакета `dan/field-templates`, не
  к этому трейту). Убрать `use EnumHelperTrait` из всех 9 enum'ов (и сам трейт, если не
  планируется UI-справочник) либо явно задокументировать назначение.
- **`Templates/Application/WiperSpecificationService::getVehicleAdapterCount()` и
  `::sanitizeDetailsForSide()`** (`WiperSpecificationService.php:141-151`, `:160-167`) — не
  вызываются нигде, в отличие от остальных методов интерфейса. Удалить из порта и реализации.
- **`Templates/Application/DetailTemplateResolver::resolveBySlug()`**
  (`DetailTemplateResolver.php:28-31`) — нет ни одного вызывающего (все точки входа уже имеют
  типизированный `DetailTemplateEnum` и вызывают `resolve()` напрямую). Удалить, пока не
  появится реальный потребитель (например, HTTP-контроллер по слагу).

---

## P2 — стоит сделать

### 5. Дублирование бизнес-правила «мотоцикл без типа кузова» (Import)

`UpsertVehicleFromSheetService.php:49-57` и `UpsertVehicleFromTdRowService.php:48-55` — буквально
одинаковый блок («TecDoc не даёт тип кузова для мотоциклов → подставить
`CarcaseTypeEnum::MOTORCYCLE`»), в одном ещё висит `TODO: удалить после прогонки импорта и
экспорта`. Перенести дефолтинг в `VehicleDataFactory::make()` (единственное место, которое и так
проверяет валидность `type_carcase`), либо в общий приватный хелпер, вызываемый из обоих
сервисов.

### 6. Дублирование сборки `PartSpecificationData` для свечей зажигания (Import)

`UpsertEngineSparkPlugSpecService.php:37-42` и `UpsertSparkPlugSpecByModificationService.php:58-63`
— идентичная конструкция `new PartSpecificationData(partableType: ENGINE, template: SPARK_PLUGS,
...)`. Вынести в именованный статический конструктор `PartSpecificationData::forEngineSparkPlugs(
int $engineId, array $details): self` (это сборка значения, не бизнес-логика — можно прямо в
Data-классе).

### 7. Дублирование cache-key логики отчёта об ошибках импорта (4 места)

`EngineCrossImport.php:43-50`, `EngineSparkPlugSpecificationImport.php:50-57`,
`EngineMultiSheetImport.php:67-81`, `VehicleMultiSheetImport.php:67-81` — каждый независимо
повторяет `sprintf((string) config('vehicles-import.failures.cache.keys.X'), $runId)`. Вынести в
общий хелпер (метод на `CachesImportFailures` или отдельный
`ImportFailureCacheKeys::forEntity(string $prefix, string $runId): array{cacheKey, lockKey}`).

### 8. Ручные транзакции вместо `DB::transaction()` (Import)

`EngineMainSheetImport.php:39-56`, `EngineSparkPlugsSheetImport.php:59-88`,
`VehicleMainSheetImport.php:38-56` вручную повторяют `DB::beginTransaction()/commit()/rollBack()`
в `try/catch`, тогда как соседний `VehicleWipersSheetImport.php:50` уже делает то же самое
идиоматично через `DB::transaction(fn () => ...)`. Привести первые три к тому же стилю — короче
и без риска забыть `rollBack()`.

### 9. Избыточная проверка, которая никогда не сработает (Import)

`VehicleWipersSheetImport.php:21,41` — класс реализует `SkipsEmptyRows` (пакет `maatwebsite/excel`
сам фильтрует полностью пустые строки до вызова `collection()`), но внутри `collection()` всё
ещё вручную проверяет `if ($row->filter()->isEmpty()) { continue; }` — до этой строки пустая
строка в принципе дойти не может. Удалить (либо, если имелась в виду «пустая после trim» —
прокомментировать явно, иначе выглядит как случайное дублирование).

### 10. Частично избыточная повторная валидация типов (Import)

Формат-конвертер в Infrastructure (`ImportRowValueFormatter::nullableInt/nullableFloat`) уже
бросает исключение и отдаёт типизированный DTO (`?int`/`?float`), но Application-фабрики
(`EngineDataFactory::make()` и аналогичные для Vehicle/Manufacturer/Modification/
EngineModification) повторно валидируют те же поля через `'integer'/'numeric'` правила Laravel
`Validator`, хотя тип уже гарантирован. Оставить в фабриках только то, что реально добавляет
ценность (`required`, `Rule::enum`, бизнес-правила), либо явно задокументировать как осознанный
defense-in-depth.

### 11. Разнобой в доступе к Cache/Storage — фасады vs DI (Import)

`ExternalImportCacheService`, `CleanupExternalImportFileService`, `ReportImportResultService`
используют статические фасады `Cache::`/`Storage::` напрямую, тогда как соседний
`EngineModificationReadinessGate` (та же категория задач — cache-состояние импорта) корректно
инъецирует `Illuminate\Contracts\Cache\Repository` через конструктор. По правилу «DI — вариант
А» стоит унифицировать — перейти на конструкторную инъекцию контрактов вместо фасадов, для
единообразия и тестируемости через моки конструктора.

### 12. `EngineModificationData::$type` — не enum-тип, вопреки собственному правилу (Import)

`Domain\ModelData\EngineModificationData.php:21` — поле `public readonly string $type`, хотя
колонка `engine_modification.type` кастится как `VehicleTypeEnum` и ARCHITECTURE.md §1 явно
требует enum-тип для таких полей (сравните с корректными `ModificationData::$type`,
`VehicleData::$type`). Соответствующая фабрика `EngineModificationDataFactory.php:33` тоже
единственная, которая после `Rule::enum(...)`-валидации не конвертирует в `VehicleTypeEnum::from(...)`,
а оставляет `(string)`. Привести к enum-типу, как у всех сиблингов.

### 13. Хардкод путей CSV вместо конфига (Import)

Пять мест жёстко зашивают `storage_path('vehicles/*.csv')`: `TecDocImportCars.php:39` и четыре
`Start*CommandImportListener.php:18` (Vehicle/Engine/Modification/EngineModification). Проект уже
установил прецедент «cache-ключи и TTL — не литералы, а шаблоны в `config/vehicles-import.php`»
— применить тот же принцип и здесь: `config/vehicles-import.php` →
`console.paths.{manufacturers,vehicles,engines,modifications,engine_modification}`.

### 14. Разнобой в именовании констант «поля не для записи» (Import Commands)

`EngineCommand::NON_WRITABLE_FIELDS` vs `ModificationCommand::NON_COLUMN_FIELDS` — одна и та же
концепция под разными именами, остальные Command-классы вообще инлайнят `['id']` без константы.
Унифицировать нейминг/подход во всех Command-классах.

### 15. `readonly` пропущен у пяти Command-Import адаптеров (Import)

`EngineCommandImport`, `ManufacturerCommandImport`, `ModificationCommandImport`,
`VehicleCommandImport`, `EngineModificationImport` объявлены как `final class` без `readonly`,
хотя собственного мутируемого состояния не имеют (в отличие от `EngineCrossImport`/
`EngineMultiSheetImport`, где `readonly` действительно невозможен из-за DTO-контекста,
присваиваемого после конструктора). Добавить `readonly`.

### 16. Избыточная развилка вместо прямого return (Import)

`ExternalImportCacheService::accept()` (`Application/Services/External/ExternalImportCacheService.php:17-24`):
```php
if (Cache::add(...)) { return true; }
return false;
```
эквивалентно `return Cache::add(...);`. Мелкая, но безопасная правка.

### 17. Дублирование в Maintenance: две почти идентичные Artisan-команды

`UpdateModificationYears.php:29-40` и `UpdateVehicleYears.php:29-40` — отличаются только моделью
(`Modification`/`Vehicle`) и колонкой (`year_to`/`generation_year_to`), логика (`get()` + `foreach`
+ `update()` + счётчик) идентична. Вынести в один параметризуемый
`Application/Services/FixYearToService` (модель+колонка как параметры) — заодно исправляет
несоответствие архитектуре (см. п.20).

Заодно там же — N+1: цикл `get()`+`foreach`+`update()` стоит заменить на один массовый
`Model::query()->where(...)->update([...])`, который сам вернёт число затронутых строк.

### 18. Дублирование JSONB-сравнения `details` в Maintenance

`PartSpecificationDeduplicationService.php:110-118` и
`VehicleWiperPartSpecificationSplitService.php:121-130` — оба содержат один и тот же фрагмент
`whereRaw('details = CAST(? AS jsonb)', [json_encode(...)])`. Вынести в общий трейт
(`MatchesPartSpecificationDetails` с методом `whereDetailsEqual($query, array $details)`) — по
собственной политике трейтов проекта («чистое самодостаточное поведение»).

### 19. Двойной пересчёт `splitSpecification()` в Maintenance

`VehicleWiperPartSpecificationSplitService::cleanupEmptySides()` (строка 212) вычисляет
`splitSpecification()` только ради проверки `shouldSplit()` и отбрасывает результат; если проверка
говорит «нужно разбивать», `processSpecification` (строка 68) пересчитывает то же самое заново на
тех же данных. Посчитать один раз и переиспользовать результат.

### 20. Несоответствие архитектуре: Maintenance-команды пишут Eloquent прямо в `handle()`

`UpdateModificationYears`/`UpdateVehicleYears` нарушают собственный паттерн фичи (шпаргалка §6:
«разовый фикс → команда в Presentation + Application/Services»). Соседние
`DeduplicatePartSpecificationsCommand`/`SplitVehicleWiperPartSpecificationsCommand` уже сделаны
правильно (тонкие, делегируют в Service). Исправляется тем же рефакторингом, что и п.17.

---

## P3 — низкий приоритет / на усмотрение

- **Тестовый helper `readSheets()` дублирован дословно** между
  `tests/Feature/Vehicles/EngineMultiSheetExportTest.php:30-37` и
  `VehicleMultiSheetExportTest.php:44-55`. Вынести в общий trait `Tests\Concerns\ReadsExcelSheets`.
- **`ExportRunCacheService` (Export) и `ExternalImportCacheService` (Import)** реализуют
  одинаковый паттерн «идемпотентность по runId через cache». Не настаиваем на немедленном
  слиянии (в духе принятого в проекте компромисса «дублирование ради независимости фич»), но при
  следующем похожем сценарии — кандидат на `Shared/Infrastructure/Services/CacheIdempotencyService`.
- **Тройное резервное значение конфига** (`config('vehicles-export.output.disk', 'local')` и
  аналогичные) в `EngineMultiSheetExport`, `VehicleMultiSheetExport`,
  `CleanupStaleExportFilesService` — вторые аргументы `config(...)` не сработают в штатной
  работе (конфиг-файл и так задаёт дефолты), но создают риск рассинхрона при правке дефолта.
  Убрать вторые аргументы либо резолвить disk/directory/retention в одном месте.
- **`PartSpecificationRowExpander` (Export)** называется обобщённо, но жёстко типизирован под
  `EngineData` (в отличие от `WiperRowExpander`, чьё имя честно отражает специфичность).
  Переименовать в `EnginePartSpecificationRowExpander` либо явно задокументировать
  Engine-specific природу.
- **Два похожих row-mapper'а с разными индексами колонок** — `EngineMainSheetRowMapper` и
  `EngineSheetRowMapper` (`Import/Infrastructure/Imports/Engine/Mappers/`). Разделение оправдано
  (два разных Excel-формата), но хрупко к сдвигу колонок — стоит зафиксировать соответствие
  колонка↔индекс в общей константе/таблице или покрыть построчными тестами с эталонным рядом.
- **Неиспользуемые relation-методы в Import-моделях**: `Feature::values()`, `FeatureValue::feature()`,
  `PartSpecification::vehicle()` (в `Import/Infrastructure/Models/`) — нигде не вызываются.
  Решить: либо реально нужны будущему сценарию (оставить с комментарием), либо удалить.
- **`Vehicle::partSpecifications(): MorphMany` в Maintenance** — не используется ни одним из двух
  Maintenance-сервисов (оба работают через `PartSpecification::query()` напрямую). Не мина (класс
  существует), но неиспользуемая поверхность API — либо убрать, либо оставить с комментарием, как
  уже сделано для убранных связей `Modification::engine()`/`PartSpecification::partable()`.
- **`getTemplateKey()` рассинхронизирован с `DetailTemplateEnum`** — `AirFilterTemplate`/
  `OilFilterTemplate` возвращают `'air_filter'`/`'oil_filter'` (snake_case), тогда как
  `DetailTemplateEnum` хранит те же шаблоны как `airFilter`/`oilFilter` (camelCase).
  `getTemplateKey()` пока нигде не вызывается, но при появлении потребителя это создаст путаницу.
  Привести к единому регистру.
- **Rendezvous-схема Import (`EngineModificationReadinessGate` + `Subscriber` + cache-флаги +
  `EnginesAndModificationsReady`)** — сама схема корректна и соответствует конвенциям проекта
  (не требует немедленной правки). На будущее: если Excel-адаптеры импорта станут явно
  диспетчериться как `Bus::batch(...)`, состояние «кто уже готов» можно будет держать в родной
  batch-бухгалтерии Laravel вместо самодельных cache-флагов — рассмотреть при следующей ревизии
  Infrastructure-слоя импорта.
- **Документационный пробел, не код**: в ARCHITECTURE.md §3 для `Exports/<Entity>/` не
  зафиксировано (в отличие от `Imports/<Entity>/`), что sheet-адаптеры внутри multi-sheet экспорта
  — «внутренние» и резолвятся через `app()/makeWith()` без отдельного порта. Код так и делает
  (`EngineMultiSheetExport::sheets()`, `VehicleMultiSheetExport::sheets()`) — стоит добавить
  зеркальную фразу в документ, чтобы не выглядело отклонением от правила «порт у каждого класса».
- **Расхождение §0 ARCHITECTURE.md с фактом**: документ утверждает «Import — все 8 сущностей
  (Command пишет во все)», но в `Infrastructure/Commands/` только 6 Command-классов — нет
  `FeatureCommand`/`FeatureValueCommand` (`Feature`/`FeatureValue` в Import только читаются).
  Поправить формулировку в документе или задокументировать источник данных для этих двух сущностей.
