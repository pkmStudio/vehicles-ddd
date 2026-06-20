# Целевая архитектура — чистая слоёная (Laravel-идиоматичная)

> Дополняет `Otchet.md` (диагноз). **Заменяет** прежний POPO-план: от Laravel не уходим, маппинг `Eloquent→Data` НЕ вводим.
> Цель — не «изолированное ядро», а **чистота ради удобства разработки**: понятно, что куда класть, зависимости направлены внутрь через порты на IO-стороне.

---

## Решение (зафиксировано)

| Слой | Что лежит | Может знать про… |
|---|---|---|
| **Domain** | **Данные + контракты.** Модели (Eloquent), `ModelData`, Enums, Events, Templates, `Contracts/` (порты). | Eloquent/Collection — **можно** (осознанно). Без фреймворк-IO (Excel/RabbitMQ). |
| **Application** | **Бизнес-логика и сценарии.** UseCases, Services, Factories, тонкие Listeners/Jobs/Observers. Потребляет доменные порты. | только Domain |
| **Infrastructure** | **Всё, что общается с внешним миром.** Repositories (read), Commands (write), **Excel Imports/Exports**, Messaging, Notifications, Support, Providers. Реализует driven-порты. | Domain + Application |
| **Presentation** | Точки входа (console/http), тонкие. | Application + Domain-порты |

**Правило зависимостей:** `Presentation → Application → Domain`, и `Infrastructure → (Application, Domain)`. Стрелки внутрь; наружу — только через порты Domain.

### Контракты идут в две стороны
1. **Driven-порты** (Repository, Command, Import, Export, Notification): объявлены в Domain, **реализованы в Infrastructure**. Application их **потребляет**, не реализует.
2. **Сценарные контракты** (интерфейс на use-case): опциональны, реализуются в Application. **Не плодим** — только если Presentation реально должен зависеть от абстракции (почти никогда).

### Принятый компромисс (честно)
Раз Domain знает Eloquent — домен **не юнит-тестируется без БД**, и порты «протекают» типами Eloquent (смена ORM была бы дорогой). Нам это не нужно. Взамен — простота и отсутствие налога на маппинг.

**Load-bearing правило (Правило A):** Application **никогда** не дёргает `Model::query()`/`::where`/`save` напрямую — только через Repository/Command-порты. Именно оно сохраняет тестируемость Application (порты мокаются) при Eloquent в Domain.

---

## Flow импорта (эталон)

```
Presentation   ImportController / artisan-команда
   │  → зависит от порта импорта (Domain)   [или от тонкого ImportXxxUseCase, если есть оркестрация]
   ▼
Application    ImportXxxUseCase             (опционально: сброс флагов, запуск, диспатч по завершении)
   │  → зависит от XxxImportInterface (порт, Domain)
   ▼  (контейнер подставляет реализацию)
Infrastructure XxxImport                    (Excel-адаптер: «МЕХАНИКА чтения файла»)
   │  Excel::import($this) → Excel сам зовёт collection()/onRow() по чанкам
   │  на каждую строку → зовёт построчный use-case (Application)
   ▼
Application    UpsertXxxFromRowUseCase      (БИЗНЕС-логика одной строки)
   │  → зависит от XxxRepositoryInterface / XxxCommandInterface (порты, Domain)
   ▼  (контейнер → Infra)
Infrastructure XxxRepository / XxxCommand   (БД)
```

**Почему это не цикл:** Application зависит только от **порта** (Domain), не от конкретного Infra-адаптера. Связь «Infra-адаптер → Application-use-case» — это `Infra → Application`, она легальна. Обратной статической ссылки `Application → Infra` нет (её даёт контейнер).

### Правило двух ответственностей в импорте
- **Infra-адаптер = механика:** знает Excel, режет файл на строки/чанки, ловит `onFailure`. Maatwebsite инвертирует управление (Excel зовёт колбэки адаптера), поэтому «вернуть строки в Application» без буферизации всего файла нельзя — отсюда паттерн «адаптер зовёт построчный обработчик» (инъекция политики, Strategy).
- **Application use-case = политика:** что делать с одной разобранной строкой (Factory→Command через порты). `final readonly`, без состояния → корректно сериализуется в очереди.

Эталон в коде: `VehicleMainSheetImport` (адаптер) → `UpsertVehicleFromSheetUseCase` (построчный сценарий).

---

## Delta от текущего кода (что реально меняем)

1. **Перенос Excel-адаптеров** `Application/Imports/*`, `Application/Exports/*` → `Infrastructure/Imports/*`, `Infrastructure/Exports/*` (неймспейсы + биндинги в `VehiclesServiceProvider` + внутренние `use`). Порты в `Domain/Contracts` остаются.
2. **Построчная логика → в use-case.** «Command-импорты» (`EngineCommandImport` и пр.) сейчас зовут Factory+Command прямо в адаптере — вынести в `UpsertXxxFromRowUseCase`, адаптер оставить «механикой». (Делаем после переноса, отдельным шагом.)
3. **Бизнес-логика → в Application-сервисы.** Гейт готовности из `EngineModificationReadinessSubscriber` → сервис; подписчик тонкий.
4. **Domain не трогаем** — модели и порты на месте.
5. **`ARCHITECTURE.md`** обновить: разрешить Eloquent/Collection в Domain, зафиксировать «две стороны контрактов» и расположение Imports/Exports в Infrastructure.

---

## План миграции — вертикальными срезами

**Срез Engine (эталон, в работе):**
1. ✅ Перенести `Application/Imports/Engine/*` → `Infrastructure/Imports/Engine/*` (поведение идентично).
2. Обновить 4 биндинга в `VehiclesServiceProvider` + внутренние `use` в `EngineMultiSheetImport`.
3. (след. шаг) Вынести построчную логику `EngineCommandImport` в `UpsertEngineFromRowUseCase`; адаптер — механика.
4. Покрыть построчный use-case unit-тестом (порты мокаются, без БД).

Далее тиражировать на Vehicle / Modification / Manufacturer и экспорты.

> Кандидаты на удаление при переносе (мёртвый код, из `Otchet.md`): `EngineImport` (дубль `EngineCommandImport`, нигде не инжектится), `EnginesCodeImport` (`@deprecated`).
