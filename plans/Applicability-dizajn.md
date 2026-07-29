# Дизайн: сервисы Warehouse и Applicability + межсервисное общение

> Заметка на будущее — когда дойдём до переноса доменов **Warehouse** (наборы `Kit`) и **Applicability** (применяемость `kit ↔ vehicle/engine`).
> Тут зафиксированы решения из обсуждения, чтобы не передумывать заново.

## Контекст / вводные (со слов владельца)

- Warehouse и Applicability будут отдельными доменами-сервисами **в той же кодобазе и с ОДНОЙ БД** (как сейчас Vehicles).
- Applicability меняется **редко**: на старте — посчитать всё, далее — инкрементально при добавлении новой машины или нового набора.
- Использование: **магазин** спрашивает у Applicability «дай применяемость для такой-то машины» → получает **ID наборов** (а состав/детали наборов берёт из Warehouse).

## Главный принцип границ

> Сервис **не знает о моделях/таблицах другого**. Знание чужой модели или прямой запрос к чужой таблице = стирание границы (тогда это не три сервиса, а один с тремя папками).
> Друг о друге знают только **контракты**: query-порт (published API) и/или integration-событие. Контракт = DTO в согласованных терминах, не обязан повторять внутреннюю модель.

**Направление зависимостей:** Applicability — downstream, считает НАД данными двух других → стрелки `Applicability → Vehicles` и `Applicability → Warehouse`. **Vehicles и Warehouse про Applicability не знают вообще.** Порты-провайдеры объявляет и потребляет Applicability; источники их реализуют своим read-API (отдают DTO-вьюхи, не модели).

## Решение (одна БД → способ «query-порты», без событий/проекций)

События и локальные проекции НЕ нужны (это для разных БД). Берём синхронные query-порты.

### Write-side — расчёт `RecalculateApplicabilityUseCase`
```
VehicleSpecsProvider::streamForApplicability(): iterable<SpecView>   // реализует Vehicles
KitsProvider::streamForApplicability(): iterable<KitView>            // реализует Warehouse
```
- Источники отдают DTO-вьюхи курсором/чанками (не всё в память, не модели).
- Use-case считает и пишет в **свою таблицу `applicabilities` (applicabilitable_type/id, kit_id)** через свой Command.
- Хранить `kit_id` в `applicabilities` — легитимно (ссылка-результат, которым владеет Applicability). Хранить там состав набора — НЕТ (воровство данных Warehouse).

### Триггеры пересчёта
- Старт → artisan `applicability:recalculate` (полный батч).
- Новая машина/набор → in-process доменное событие (`VehicleImportCompleted` уже есть, будущий `KitAdded`) → тонкий листенер → пересчёт нужного среза по тем же провайдер-портам. Проекции не нужны.

### Read-side — магазин спрашивает применяемость
```
ApplicabilityProvider::kitIdsForVehicle(int $vehicleId): int[]   // порт Applicability
```
**Отдаём только `kitIds`, НЕ сами наборы.** Почему:
1. Это и есть ответственность Applicability («какие наборы подходят»). Состав/цена/остатки — правда Warehouse.
2. Если отдавать полные наборы — Applicability станет зависеть от формы данных Kit; любое изменение Kit потащит изменения сюда.

**Склейку делает спрашивающий** (магазин: `applicability.kitIdsForVehicle()` → `warehouse.kitsByIds(ids)`), либо тонкий оркестратор **на стороне потребителя/BFF** — но НЕ внутри Applicability (иначе он потащит зависимость от Warehouse-Kit).

### Картина
```
Applicability ─ VehicleSpecsProvider (реализ. Vehicles) ─→ SpecView (DTO)
              ─ KitsProvider          (реализ. Warehouse)─→ KitView  (DTO)
Applicability → applicabilities (своя таблица: applicabilitable + kit_id)

Магазин → Applicability.kitIdsForVehicle() → [ids]
Магазин → Warehouse.kitsByIds(ids)         → [kits]   (склейка у магазина)
```
Если когда-нибудь БД разнесут — меняется только реализация провайдер-портов на event-проекции; use-case пересчёта и read-порт не трогаются.

---

## ⚠️ Долг в текущем коде Vehicles — вычистить при выделении Applicability

Сейчас в Vehicles гостит чужая (Applicability/Warehouse) территория. Часть уже удалена (см. ниже «Уже удалено»), часть пограничная.

**При появлении Applicability сделать:**
1. **Связь `kits()`** в `Vehicle`/`PartSpecification` (`morphToMany(Kit, 'applicabilitable', 'kit_applicabilitables')`) — это прямой доступ Vehicles к таблице Applicability + модели Warehouse. → **уже удалена** из моделей (см. ниже). При переносе: «kit'ы для ТС» резолвить через `ApplicabilityProvider::kitIdsForVehicle()`, а НЕ Eloquent-связью.
2. **Экспорты применяемости** `EngineKitApplicabilityExport` / `VehicleKitApplicabilityExport` (+ порты `*KitApplicabilityExportInterface`) — это отчёты по применяемости (строки = пары `kit↔vehicle/engine`). Принадлежат **Applicability**. → **уже удалены** из Vehicles. При переносе — воссоздать в Applicability, обогащая поля ТС/набора через провайдер-порты (DTO-вьюхи), а не через `kits()`/`partable`-связи.
3. **MpCard-инвалидация** (`InvalidateMpCardsBy{Engine,Vehicle}Job` + обсерверы `Engine/VehicleObserver`) — домен **MpSale**. → **уже удалены**. При появлении MpSale — воссоздать там (реакция на изменение spec/vehicle инвалидирует карточки) по своим портам.

## Уже удалено из Vehicles (этот заход)
- `Application/Jobs/{Engine,Vehicle}/InvalidateMpCards*Job` + папка `Jobs/`.
- `Application/Observers/{Engine,Vehicle}/*Observer` + папка `Observers/` + атрибуты `#[ObservedBy]` в моделях.
- `Infrastructure/Exports/{Engine,Vehicle}/*KitApplicabilityExport` + порты `Domain/Contracts/Exports/*KitApplicabilityExportInterface` + биндинги.
- Связь `kits()` + `use App\Models\Warehouse\Kit` в `Vehicle`/`PartSpecification`.

## Открытый вопрос (решить отдельно)
- `EngineSparkPlugSpecificationImport` — суть своя (свечи для двигателей модификации → `PartSpecification`), но реализация завязана на `App\Events\Warehouse\KitImportCompleted` / `App\Imports\Warehouse\KitImport` (Warehouse, битый `afterImport`) и не имеет потребителя. Решить: удалить целиком ИЛИ оставить, отвязав от Warehouse (переписать `afterImport` на свой `EngineImportCompleted`-стиль).
