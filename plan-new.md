# plan-new.md — что сделано и что осталось

> Заменяет предыдущий `plan-new.md` (удалён владельцем). Это не история, а текущий срез:
> что сделано, что отложено, что в работе.

---

## 1. В работе / на очереди

### Аудит: строгость типов (`?array`/`mixed`) — находка, чинить по желанию

Прошёлся по всему `app/` в поисках слабо типизированных сигнатур (`?array`, `mixed`, голый
`array` без формы). Результат: серьёзной проблемы нет.

- **`?array` — 0 вхождений во всём проекте.**
- **`mixed` — 1 вхождение**: `Import/Application/Services/Template/DetailsBuilder.php:75`
  (`getFieldValue(...): mixed`) — осмысленно: тип поля определяется шаблоном в рантайме.
- **94 сигнатуры `: array`** — почти все объяснимы:
  - ~35 — контракты `maatwebsite/excel` (`headings()`, `map()`, `sheets()`, `registerEvents()`),
    сигнатуру задаёт пакет, строже не сделать.
  - ~5 — `toArray()` у DTO, уже с точной формой в докблоке (`@return array{user_id: int, ...}`).
  - ~20 — template/details-билдеры (`WiperSpecificationService`, `TemplateDataBuilder`,
    `ExportDetailsBuilder`) — по задумке динамические, форма `details` зависит от активного
    field-template. Строже = отдельный крупный проект (типизированные value-объекты на каждый
    шаблон), не разовая правка.
  - **Дешёвые кандидаты на улучшение (по желанию, не горит)**:
    - `Shared/Domain/Traits/EnumHelperTrait.php` — `names()`/`values()`/`toArray()` без формы
      в докблоке вообще; добавить `@return list<string>`/`@return array<string,string>`.
    - `UpsertVehicleFromSheetService::resolveManufacturer()` /
      `UpsertSparkPlugSpecByModificationService::resolveMsId()` — уже задокументированы точно
      (`@return array{0: int, 1: int}`), но по духу проекта (DTO вместо анонимных структур,
      см. Export-рефакторинг) — кандидаты на маленький именованный DTO вместо позиционного tuple.
    - `PartSpecificationDeduplicationService::deduplicate()` и summary-массивы в Maintenance —
      ассоциативный `array<string,int>` с фиксированными ключами, тоже кандидат на DTO.

### ✅ Аудит: Repository/Command должны работать только через `<Entity>Data` — найдено 1 нарушение, исправлено

Прошёлся по всем Repository/Command интерфейсам Import и Export (6 Repository, 6 Command).

**Repositories** — все чистые: `?EntityData`/`Collection<EntityData>` либо легитимные скалярные
read-агрегаты (`minMsId(): int`, `minMfaId(): int`, `parentMsId(): ?int`). Обоснование, почему
скаляр — не нарушение: правило «Repository отдаёт Data» защищает от утечки *мутируемого*
Eloquent-объекта (можно случайно `->save()` в обход Command) — у скаляра мутировать нечего,
оборачивание `int` в однополевой DTO не даёт защиты типов, только лишний класс.

**Commands** — 5 из 6 чистые (`upsert*(Data): Data`, плюс легитимный `syncWithoutDetaching(Data):
void` для pivot-операции, где нечего возвращать). **Найдено нарушение**:
`EngineCommandInterface::setGroupId(EngineData $engine, int $groupId): void` — брал второй сырой
`int`-параметр помимо Data и возвращал `void` вместо `Data`, единственный такой во всех Command.

**Исправлено:**
- `setGroupId(EngineData $engine, int $groupId): void` → `setGroupId(EngineData $data): EngineData`
  (интерфейс + `EngineCommand`) — новое значение группы теперь в `$data->groupId`, идентичность
  через `$data->id`, метод возвращает обновлённый `EngineData` (перечитывает запись после update).
- `AssignEngineGroupService` — собирает `new EngineData(engId, id, groupId: $groupId)` вместо
  передачи `$groupId` вторым аргументом.
- `AssignEngineGroupServiceTest` — оба мока `setGroupId` переведены на проверку Data-аргумента
  (`Mockery::on`) вместо позиционных `(engine, 7)`.
- 65/65 тестов зелёные после правки.

---

### ✅ Аудит: `new X(...)` и тернарники inline в аргументах вызовов — исправлено

Правило от владельца: значение, которое строится «на лету» внутри вызова другого метода
(`foo(new Bar(...))`) или тернарник прямо в именованном аргументе конструктора
(`value: $cond ? 1 : 2`), должно выноситься в отдельную переменную перед вызовом.

**Осознанно НЕ тронуто** (идиомы, решение владельца):
- `event(new VehicleImportCompleted(...))` и подобные — стандартная Laravel-идиома диспатча.
- `$this->onFailure(new Failure(...))` (~15 мест по Excel-адаптерам импорта) — устоявшийся
  идиом проекта, у `Failure` нет условной сложности на поле, вынесение добавило бы строк без
  пользы для отладки.
- Вложенные `new XField(...)` в декларациях шаблонов (`WiperTemplate` и т.п.) — **на паузе**,
  параллельно идёт рефакторинг Templates-фичи (`Shared/Templates`, типизированные
  `*DetailsData`), трогать после того, как он завершится.

**Исправлено** (везде: конструктор → переменная → вызов):
- `new X(...)` внутри вызова: `StartExportUseCase` (×2), `PartSpecificationRowExpander` (×2),
  `ExportFileRequestedHandler`, `ImportFileRequestedHandler`, `RabbitMqExportNotificationService`,
  `RabbitMqFileNotificationService`, `UpsertEngineSparkPlugSpecService`,
  `UpsertSparkPlugSpecByModificationService`, `AssignEngineGroupService`,
  `VehicleWiperSpecificationImportService`, `ReportImportResultService` (×3).
- Тернарник inline в именованном аргументе: `EngineDataFactory`, `VehicleDataFactory`,
  `ModificationDataFactory` (каждое поле — своя переменная перед конструктором, ~30 мест),
  `ReportImportResultService` (`path: is_string($path) ? $path : null`).
- 75/75 тестов зелёные после правки.

### ✅ Аудит: позиционные аргументы вместо именованных — найдено и исправлено

Отдельно от предыдущего пункта: искал места, где конструктор/вызов с несколькими параметрами
передаёт их позиционно, без `paramName:` (как в примере из `AirFilterDetailsData`, где каждый
аргумент подписан). Проверил все ~68 `new X(...)` в проекте — почти везде уже именованные
аргументы (Mapper-классы, DTO/Data-конструкторы уже так делают).

**Нашёл 6 нарушений** — вся семья событий `*ImportCompleted` (`VehicleImportCompleted`/
`EngineImportCompleted`/`EngineCrossImportCompleted`, наследуют `AbstractImportCompleted(userId,
cacheKey, runId)`) вызывалась позиционно тремя однотипными аргументами (`int`/`string`/`string`)
— на месте вызова невозможно понять, что где, не заглянув в конструктор:
- `VehicleMultiSheetImport.php:59`, `EngineMultiSheetImport.php:59`, `EngineCrossImport.php:133`,
  `EngineSparkPlugSpecificationImport.php:116` — везде `event(new X($a, $b, $c))` →
  `event(new X(userId: ..., cacheKey: ..., runId: ...))`.
- `ReportImportResultListenerTest.php` (×2) — `new VehicleImportCompleted(42, $cacheKey, 'run-123')`
  (голые литералы вперемешку с переменной, ещё менее читаемо) → именованные аргументы.

**Не нарушение** (проверил и исключил): `Failure(...)` (вендорский класс, ~15 мест, уже решено
не трогать), `RuntimeException`/`InvalidArgumentException` — один строковый аргумент, называть
нечего.

75/75 тестов зелёные (одна нестабильность между тестами в процессе проверки — `SparkPlugDetailsDataTest`/
`EngineSparkPlugSpecificationImportTest` падали только все вместе в одном прогоне, по отдельности
и при повторном полном прогоне — зелёные; похоже на порядко-зависимую утечку состояния в
параллельном рефакторинге Templates, не связано с этой правкой).

---

## 2. Осознанно отложено (не трогать без явного запроса)

- **`larastan`/`phpstan` + CI** (`.github/workflows`) — дешёвая, полезная задача (ловит баги
  класса «необъявленное свойство», как было в старом `plan.md` §6.1), но пока отложена.
- **Схема БД**: `timestamps()`, индексы на FK, уникальный индекс `engine_modification` — не
  добавляем, пока не понятна реальная нагрузка.

---

## 3. Закрыто (для справки)

- Рефакторинг `Export` по образцу `Import` (см. предыдущую сессию) — полностью, включая
  реальный сквозной тест через RabbitMQ.
- `ChangeProviderManufacturersToTD` — удалена (разовая задача выполнена).
- Докстроки «Шаги:» — добавлены во все существующие тесты.
- Feature-тесты на Export — написаны (`VehicleMultiSheetExportTest`, `EngineMultiSheetExportTest`,
  `ExportFileRequestedHandlerTest`), 65/65 зелёные.
- Аудит репозиториев Export на методы-сироты — `find()`/`findOrFail()`/`VehicleRepository::all()`
  удалены как неиспользуемые.
- Стейтфул-оркестрация импорта — не нужна (разовый прогон, не регулярный).
- Domain vs Integration события — закрыто без физического разделения, зафиксировано в
  `ARCHITECTURE.md` (события не сериализуются напрямую, wire-контракт — explicit `*NotificationDTO`).
- `EngineCrossImport`/`AssignEngineGroupService`/`EnginesCodeImport` — помечены `@deprecated` с
  комментарием про бизнес-доработку (группировка двигателей по кросс-кодам).
