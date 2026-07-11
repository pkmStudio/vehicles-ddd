# План: конвенция именования DTO и вынос самоконструирования в фабрики

Разбор по замечанию: в `app/Vehicles/Import/Domain/DTOs` (и симметрично в
`app/Vehicles/Export/Domain/DTOs`) часть классов не имеет суффикса `DTO`, и часть классов
умеет строить себя сама через статические именованные конструкторы (`all()`, `mainOnly()`,
`notFound()`, `written()`) — это ответственность фабрики, а не пассивного DTO.

**Обновление**: в ходе разбора выяснилось, что у всех 4 "Plan"-классов (`EngineImportPlan`,
`VehicleImportPlan`, `EngineExportPlan`, `VehicleExportPlan`) вариативность (`mainOnly()`)
нигде реально не используется — ни один вызывающий код никогда не запрашивал частичный план,
а Export вообще не имеет Presentation-слоя, чтобы этим управлять. По решению пользователя
("Ну да, подобные классы, как будто вообще лишние... Сноси все. Всегда работаем с двумя
листами") — все 4 класса **удалены целиком**, а не переведены в фабрики (см. §3.1, статус
✅ выполнено). Остальные пункты плана (переименование оставшихся DTO, `ModificationSparkPlugResult`)
остаются актуальными и не реализованы.

## 1. Инвентаризация текущего состояния

| Класс | Папка | Суффикс `DTO`? | Самоконструирование? | Статус |
|---|---|---|---|---|
| `ExternalImportFileCleanupDTO` | Import/Domain/DTOs | ✅ есть | нет, только конструктор | без изменений |
| `ExternalImportFileRequestDTO` | Import/Domain/DTOs | ✅ есть | нет, только конструктор | без изменений |
| `ImportRunContext` | Import/Domain/DTOs | ❌ нет | нет, только конструктор | **осталось переименовать** |
| `AssignEngineGroupResult` | Import/Domain/DTOs | ❌ нет | нет, только конструктор | **осталось переименовать** |
| `ModificationSparkPlugResult` | Import/Domain/DTOs | ❌ нет | ✅ `notFound()`, `written()` | **осталось переименовать + решить судьбу фабрики** |
| ~~`EngineImportPlan`~~ | Import/Domain/DTOs | — | — | ✅ **удалён** |
| ~~`VehicleImportPlan`~~ | Import/Domain/DTOs | — | — | ✅ **удалён** |
| ~~`EngineExportPlan`~~ | Export/Domain/DTOs | — | — | ✅ **удалён** |
| ~~`VehicleExportPlan`~~ | Export/Domain/DTOs | — | — | ✅ **удалён** |

## 2. Что уже сделано (Plan DTO — удалены целиком)

Вместо выноса `all()`/`mainOnly()` в фабрики — весь механизм "план: какие листы включить"
признан лишней абстракцией (YAGNI: вариативность никогда не запрашивалась ни одним реальным
вызывающим) и убран:

- Удалены файлы: `Import/Domain/DTOs/{Engine,Vehicle}ImportPlan.php`,
  `Export/Domain/DTOs/{Engine,Vehicle}ExportPlan.php`.
- Удалены как полностью опустевшие после удаления `buildImportPlan()`:
  `Import/Application/Services/{Engine,Vehicle}ImportService.php` и их интерфейсы
  `Import/Domain/Contracts/Services/{Engine,Vehicle}ImportServiceInterface.php` (это были их
  единственные методы, и они нигде не вызывались).
- В `Export/Application/Services/{Engine,Vehicle}ExportService.php` и их интерфейсах убран
  только метод `buildExportPlan()` — остальные методы (`getMainRows`, `mapMainRow` и т.д.)
  остались, они реально используются.
- `EngineMultiSheetImport`/`VehicleMultiSheetImport` (Import, Infrastructure) и
  `EngineMultiSheetExport`/`VehicleMultiSheetExport` (Export, Infrastructure): убран параметр/
  свойство `?Plan $plan`, `sheets()` теперь безусловно возвращает оба листа.
- `VehicleMultiSheetExport`: единственное, что реально несла `VehicleExportPlan` кроме списка
  листов — флаг `isAllow`. Он не про "какие листы", а про фильтрацию данных, поэтому переехал
  в свой собственный конструкторский параметр `private bool $isAllow = false` — без всякого DTO.
- Удалены как ставшие полностью неиспользуемыми: `Export/Domain/Enums/InOut/Sheets/{Engine,Vehicle}ExportSheet.php`
  (использовались только в удалённом `hasSheet()`). Аналогичные Import-энамы
  (`EngineImportSheet`, `VehicleImportSheet`) — **оставлены**: они используются как ключи
  массива в `sheets()` (`$sheets[VehicleImportSheet::Main->value] = ...`), не только для
  выбора.
- Убраны биндинги `EngineImportServiceInterface`/`VehicleImportServiceInterface` из
  `ImportServiceProvider`. Биндинги `Engine/VehicleExportServiceInterface` в
  `ExportServiceProvider` остались — сами классы не удалены, только один метод.
- `php artisan test` — 54/54 зелёные после всех правок.

## 3. Осталось сделать

### 3.1 Переименование в `*DTO` (наименование, без изменения поведения)

Три класса всё ещё без суффикса:

1. `ImportRunContext` → `ImportRunContextDTO`
2. `AssignEngineGroupResult` → `AssignEngineGroupResultDTO`
3. `ModificationSparkPlugResult` → `ModificationSparkPlugResultDTO`

Механически безопасно (типы, не значения в БД/сериализации). Файлы, которые ссылаются на
старые имена:

**`ImportRunContext`**
- `app/Vehicles/Import/Domain/DTOs/ImportRunContext.php` → переименовать файл и класс
- `app/Vehicles/Import/Application/UseCases/External/StartExternalFileImportUseCase.php`
- `app/Vehicles/Import/Domain/Contracts/Imports/ExternalFileImportInterface.php`
- `app/Vehicles/Import/Domain/Contracts/Imports/VehicleMultiSheetImportInterface.php`
- `app/Vehicles/Import/Domain/Contracts/Imports/EngineMultiSheetImportInterface.php`
- `app/Vehicles/Import/Domain/Contracts/Imports/EngineSparkPlugSpecificationImportInterface.php`
- `app/Vehicles/Import/Domain/Contracts/Imports/EngineCrossImportInterface.php`
- `app/Vehicles/Import/Infrastructure/Imports/Vehicle/VehicleMultiSheetImport.php`
- `app/Vehicles/Import/Infrastructure/Imports/Engine/EngineMultiSheetImport.php`
- `app/Vehicles/Import/Infrastructure/Imports/Engine/EngineCrossImport.php`
- `app/Vehicles/Import/Infrastructure/Imports/Engine/EngineSparkPlugSpecificationImport.php`
- `tests/Feature/Vehicles/EngineCrossImportTest.php`
- `tests/Feature/Vehicles/EngineSparkPlugSpecificationImportTest.php`
- `tests/Feature/Vehicles/ImportFileRequestedHandlerTest.php`

**`AssignEngineGroupResult`**
- `app/Vehicles/Import/Domain/DTOs/AssignEngineGroupResult.php` → переименовать
- `app/Vehicles/Import/Application/Services/Engine/AssignEngineGroupService.php`
- `app/Vehicles/Import/Domain/Contracts/Services/Engine/AssignEngineGroupServiceInterface.php`

**`ModificationSparkPlugResult`**
- `app/Vehicles/Import/Domain/DTOs/ModificationSparkPlugResult.php` → переименовать
- `app/Vehicles/Import/Application/Services/Engine/UpsertSparkPlugSpecByModificationService.php`
- `app/Vehicles/Import/Domain/Contracts/Services/Engine/UpsertSparkPlugSpecByModificationServiceInterface.php`

### 3.2 `ModificationSparkPlugResult` — тоже самоконструируется (`notFound()`/`written()`)

В отличие от Plan-классов, у этого DTO единственный вызывающий —
`UpsertSparkPlugSpecByModificationService`, и реальный (не мёртвый) сценарий использования —
он используется, просто без вариативности листов. Открытый вопрос — что делать с
самоконструированием:

- **(а) Завести фабрику** — `ModificationSparkPlugResultFactory` +
  `ModificationSparkPlugResultFactoryInterface`, методы `notFound(string $reason)`/
  `written(int $count, array $skipped)`, внедрить в сервис через DI, зарегистрировать в
  `ImportServiceProvider`.
- **(б) Убрать статику, строить `new` прямо в сервисе** — `notFound()`/`written()` удаляются,
  оба места вызова в `UpsertSparkPlugSpecByModificationService` заменяются на прямой
  `new ModificationSparkPlugResultDTO(...)`. DTO остаётся чистым конструктором, как
  `AssignEngineGroupResultDTO`.

Рекомендация: (б) — по опыту с Plan-классами, лишняя фабрика ради единственного вызывающего
не оправдана; полноценная фабрика имеет смысл, когда конструирование переиспользуется в
нескольких местах (как было бы с Plan, если бы он не оказался лишним целиком). Но решение за
пользователем — задача маленькая и не блокирует остальное.

## 4. Порядок выполнения

1. Переименовать `ImportRunContext` → `ImportRunContextDTO`, `AssignEngineGroupResult` →
   `AssignEngineGroupResultDTO` — чисто механически, прогнать тесты.
2. Переименовать `ModificationSparkPlugResult` → `ModificationSparkPlugResultDTO`.
3. Решить (а)/(б) из §3.2 и применить.
4. Финальный `php artisan test` — должно остаться 54/54 (или больше, если что-то добавится
   параллельно) зелёных.
