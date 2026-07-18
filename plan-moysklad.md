# План: пакет `pkmstudio/moysklad-client` + фича `Warehouse/MoySklad`

> Статус: анализ dan-center завершён, архитектурные решения зафиксированы (сессия 2026-07-12).
> Пошаговой реализации ещё не было — это следующий шаг, когда решим приступать (см. «План работ»).

## Источник

Интеграция с МойСклад сейчас целиком живёт в `/home/user/projects/dan-center`, размазана по трём
местам без единой границы:

- `app/MoySklad/` (28 файлов, ~3100 строк) — HTTP-клиент, circuit breaker, эндпоинты, "Resource"-
  оркестраторы, мапперы, enum'ы, исключения, фасад + service provider.
- `app/Services/MoySklad/` (13 файлов, ~1768 строк) — бизнес-оркестрация: pull заказов, backfill
  номенклатуры, sync комплектов MpCard, аналитика.
- `app/Jobs/MoySklad/` (8 файлов, ~646 строк) — очередь поверх сервисов/SDK.

Это зрелая, промышленно закалённая интеграция: two-way sync (push номенклатуры/комплектов, pull
заказов маркетплейсов), свой circuit breaker, Redis rate-limit, Telegram-алерты на сбой. Не
маленькая фича — существенная подсистема.

**Важное открытие при анализе: слой перепутан.** HTTP-клиент и "Endpoint"-классы (низкоуровневый
CRUD против JSON API МойСклад) — уже совершенно универсальны, ни одного Eloquent-импорта. А вот
"Resource"-классы, которые должны были быть тонкой бизнес-обвязкой, вместо этого напрямую
импортируют `App\Models\Warehouse\Nomenclature`, `NomenclatureIntegration`, `App\Models\MpSale\
MpCard`, `MpCardIntegration` и делают Eloquent-запросы прямо внутри "SDK"-неймспейса. Так что это
не copy-paste в пакет, а разрубание слоя.

---

## Что переезжает в пакет `pkmstudio/moysklad-client` (общий, без бизнес-логики)

Критерий включения: класс не знает о `Nomenclature`/`Kit`/`MpCard`/любой доменной модели
dan-vehicles или dan-center, работает только с array/DTO in-out и настройками самого аккаунта
МойСклад (не бизнес-правилами конкретного домена).

| dan-center | Пакет (`PkmStudio\MoySkladClient\`) | Комментарий |
|---|---|---|
| `Contracts/MoySkladClientInterface.php` | `Contracts/MoySkladClientInterface.php` | Уже идеальная граница: `get/post/put/patch/delete`, array in/out. Без изменений. |
| `Http/MoySkladHttpClient.php` | `Http/MoySkladHttpClient.php` | Auth, retry, circuit breaker, proactive + Redis rate-limit — 1:1, ни одной бизнес-настройки внутри. |
| `Http/MoySkladCircuitBreaker.php` | `Http/MoySkladCircuitBreaker.php` | Без изменений. |
| `Http/NullMoySkladClient.php` | `Http/NullMoySkladClient.php` | No-op клиент на случай `enabled=false`. |
| `Traits/HidesTokensInLogs.php`, `Traits/BuildsMeta.php` | те же | Без изменений. |
| `Enums/EntityType.php` | `Enums/EntityType.php` | Универсальный список сущностей JSON API. Без изменений. |
| `Exceptions/MoySkladException.php`, `MoySkladApiDisabledException.php` | те же | Без изменений. |
| `Endpoints/Products/ProductEndpoint.php` | `Endpoints/ProductEndpoint.php` | Уже array in/out, ни одного Eloquent-импорта — переносится как есть. |
| `Endpoints/Bundles/BundleEndpoint.php` | `Endpoints/BundleEndpoint.php` | Аналогично. |
| `Endpoints/ProductFolders/ProductFolderEndpoint.php` | `Endpoints/ProductFolderEndpoint.php` | Аналогично. |
| `Endpoints/Counterparties/CounterpartyEndpoint.php` | `Endpoints/CounterpartyEndpoint.php` | Аналогично. |
| `Endpoints/Orders/CustomerOrderEndpoint.php` | `Endpoints/CustomerOrderEndpoint.php` | Аналогично — переносится, даже хотя сам OrderSync (см. ниже) в Warehouse не переезжает: эндпоинт универсален, пригодится, если/когда появится домен CRM/Sales. |
| `Jobs/Middleware/MoySkladJobMiddleware.php` | `Jobs/Middleware/MoySkladJobMiddleware.php` | Без бизнес-кода. **Но:** сейчас глотает любой `\Throwable`, кроме `MoySkladApiDisabledException`, только логируя — job считается "обработанной", ретрая/failed-записи не будет. При переносе нужно явно решить: сохранить как есть (задокументировав) или чинить (`$job->fail($e)` для непредвиденных ошибок) — см. «Открытые вопросы». |
| `Facade/MoySklad.php`, `MoySkladSdk.php`, `MoySkladServiceProvider.php` | `Facade/MoySkladClient.php`, `MoySkladClientSdk.php`, `MoySkladClientServiceProvider.php` | Регистрирует HTTP-клиент + все Endpoint'ы в контейнере, публикует `config/moysklad-client.php`. Не регистрирует Resource/мапперы — тех в пакете не будет. |
| `config/moysklad.php` (частично) | `config/moysklad-client.php` | См. таблицу конфигов ниже — только общие ключи. |

**Конфиг пакета (`config/moysklad-client.php`)** — переносится 1:1: `enabled`, `token`, `base_url`,
`connect_timeout`, `timeout`, `retry.*`, `circuit.*`, `rate_limit.*`. Плюс `organization_id`/
`store_id` — это статичные UUID **аккаунта** МойСклад (не бизнес-правило Warehouse), но раз ключи
как таковые общие для любой интеграции с МойСклад (Organization/Store meta нужны при создании
заказов) — оставляем в пакете как "account defaults", просто в dan-vehicles они пока не будут
использоваться (Warehouse не создаёт заказы).

**Инфраструктурная зависимость, которую нужно явно проверить:** rate-limit использует
`Redis::throttle()` — требует Redis как cache/lock-драйвер. В dan-vehicles `CACHE_STORE=redis` уже
настроен (подтверждено, `config/cache.php`), так что перенос 1:1 безопасен без доп. работы.

---

## Что НЕ переезжает в пакет и НЕ переезжает в Warehouse вообще

Целая ветка **OrderSync** (pull заказов маркетплейсов) — это CRM/`MpSale`/аналитика-территория, не
Warehouse:

- `Services/MoySklad/OrderSync/*` (`MsOrderSyncService`, `AgentMarketplaceResolver`,
  `KitBrandResolver`, `ProductFolderHierarchyResolver`, `MsOrderPositionComponentSyncService`).
- `Endpoints/Orders/OrderMapper.php`, `Endpoints/Orders/OrdersResource.php` (сам эндпоинт
  `CustomerOrderEndpoint` — переезжает в пакет, см. выше, а вот `OrdersResource`/`OrderMapper` —
  бизнес-обвязка над заказами — нет).
- `Jobs/MoySklad/ImportOrderJob.php`, `MsOrderSyncJob.php`, `UpdateOrderStatusJob.php`.
- Консольная `MoySkladSyncOrdersCommand`.
- Пишет в `App\Models\Stat\MsOrder`/`MsOrderPosition` — таблицы аналитики продаж, не склада.
- `Enums/OrderState.php` — бизнес-статусы заказа конкретного аккаунта МойСклад (`Новый`,
  `Подтвержден`, ...) — не универсальны, привязаны к бизнес-процессу конкретного магазина.

Аналогично **MpCard/комплекты маркетплейсов** — тоже не Warehouse, а CRM/`MpSale`-территория:

- `Endpoints/Bundles/BundlesResource.php`, `MpCardBundleMapper.php`, `DTOs/BundleComponentDTO.php`,
  `DTOs/BundlePayloadDTO.php` (сам `BundleEndpoint` — в пакет, см. выше).
- `Services/MoySklad/MpCardSync/*` целиком.
- `Jobs/MoySklad/MpCard/*`.
- Консольная `SyncReadyMpCardBundlesCommand`, `DispatchMpCardRelinksCommand`.
- `config/moysklad.php`: ключи `marketplaces.*` (захардкоженные названия контрагентов Yandex/Ozon)
  и `card_bundle_sync.*`.
- `Services/MoySklad/Analytics/*` (`MsAnalyticsBreakdownService`) — аналитика поверх синхронизированных
  заказов, тоже не Warehouse.

**Вывод:** если/когда в dan-vehicles появится домен CRM/Sales (сейчас его нет), эти куски
переедут туда как ещё один тонкий консьюмер того же пакета `pkmstudio/moysklad-client` — но не
раньше и не как часть Warehouse. Пока просто остаются в dan-center без изменений.

---

## Что переезжает в фичу `Warehouse/MoySklad` (dan-vehicles, тонкая обвязка)

Из dan-center — только всё, что реально относится к номенклатуре склада:

| dan-center | `Warehouse/MoySklad/*` (dan-vehicles) | Слой |
|---|---|---|
| `Endpoints/Products/NomenclatureProductMapper.php` | `Application/Services/NomenclatureProductMapper.php` | Application — маппинг Eloquent → array-payload, работает с **своей** копией `Nomenclature` (см. ARCHITECTURE.md — у фичи своя копия модели). |
| `Endpoints/Products/ProductsResource.php` | разрезается на: `Application/Services/NomenclatureSyncService.php` (Eloquent-чтение/запись `NomenclatureIntegration`, вызов локального `MoySkladProductClientInterface`) + `Infrastructure/Clients/MoySkladProductClient.php` (adapter к `ProductEndpoint`/`ProductFolderEndpoint` из пакета) | Application + Infrastructure |
| `Services/MoySklad/NomenclatureBackfillService.php` | `Application/Services/NomenclatureBackfillService.php` | Application |
| `Jobs/MoySklad/SyncNomenclatureJob.php` | `Infrastructure/Jobs/SyncNomenclatureJob.php` | Infrastructure |
| `Jobs/MoySklad/ArchiveNomenclatureJob.php` | `Infrastructure/Jobs/DeleteNomenclatureJob.php` | Infrastructure — в dan-vehicles при локальном удалении удаляем товар в МойСклад, не архивируем. |
| `Jobs/MoySklad/BackfillNomenclatureIntegrationJob.php` | `Infrastructure/Jobs/BackfillNomenclatureIntegrationJob.php` | Infrastructure |
| `Observers/Warehouse/NomenclatureObserver.php` | не переносится | Observer не нужен: фича `Warehouse/MoySklad` подписывается на shared events склада и ставит queue jobs через listeners. |
| `Console/Commands/MoySkladBackfillNomenclatureCommand.php` | `Presentation/Console/Commands/BackfillNomenclatureCommand.php` | Presentation |
| `config/moysklad.php` → `nomenclature_sync.*` | `config/warehouse/moysklad.php` | — |

**Важно про архитектурную границу Import ↔ MoySklad:** в dan-vehicles уже есть `Warehouse/Import`,
которая пишет `nomenclatures`/`kits`. По принятому в проекте правилу (ARCHITECTURE.md, «Dependency
Rule» + «своя копия модели на фичу») `Warehouse/MoySklad` **не должна** знать про `Import`
напрямую. `Warehouse/MoySklad` слушает публичные shared events:
`Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated`,
`NomenclatureUpdated`, `NomenclatureDeleted`. Catalog уже диспатчит эти события. Import сейчас
после загрузки диспатчит только `NomenclatureImportCompleted`, поэтому для полноценной
синхронизации после Excel/import нужно доработать Import: при upsert номенклатуры диспатчить
shared `NomenclatureCreated`/`NomenclatureUpdated` с тем же публичным контрактом. Сам
`NomenclatureImportCompleted` оставить как событие завершения импорта и использовать для
страховочного backfill/scan, а не как основной per-row sync trigger.

**Важно про архитектурную границу с пакетом:** application-слой `Warehouse/MoySklad` не должен
напрямую зависеть от endpoint-классов `pkmstudio/moysklad-client`. Внутри фичи вводим локальный
порт `Domain/Contracts/Clients/MoySkladProductClientInterface`, а adapter
`Infrastructure/Clients/MoySkladProductClient` вызывает публичный API пакета (`ProductEndpoint`,
`ProductFolderEndpoint`). `NomenclatureSyncService` зависит только от локального порта.

---

## Дерево изменений

```
packages/moysklad-client/                          # новый репозиторий, аналог packages/rabbit-transport
  composer.json                                     # pkmstudio/moysklad-client
  config/moysklad-client.php
  src/
    Contracts/MoySkladClientInterface.php
    Http/{MoySkladHttpClient,MoySkladCircuitBreaker,NullMoySkladClient}.php
    Traits/{HidesTokensInLogs,BuildsMeta}.php
    Enums/EntityType.php
    Exceptions/{MoySkladException,MoySkladApiDisabledException}.php
    Endpoints/{ProductEndpoint,BundleEndpoint,ProductFolderEndpoint,CounterpartyEndpoint,
               CustomerOrderEndpoint}.php
    Jobs/Middleware/MoySkladJobMiddleware.php
    Facade/MoySkladClient.php
    MoySkladClientSdk.php
    MoySkladClientServiceProvider.php
  tests/                                             # Pest + orchestra/testbench, как у rabbit-transport

app/Warehouse/MoySklad/                              # новая фича в dan-vehicles
  Domain/
    Contracts/Clients/MoySkladProductClientInterface.php
    Contracts/Services/NomenclatureSyncServiceInterface.php
  Application/
    Listeners/Nomenclature/
      SyncCreatedNomenclatureListener.php
      SyncUpdatedNomenclatureListener.php
      DeleteNomenclatureInMoySkladListener.php
    Services/
      NomenclatureProductMapper.php
      NomenclatureSyncService.php
      NomenclatureBackfillService.php
  Infrastructure/
    Clients/MoySkladProductClient.php
    Models/Nomenclature.php, NomenclatureIntegration.php   # свои копии, как у остальных фич Warehouse
    Jobs/{SyncNomenclatureJob,DeleteNomenclatureJob,BackfillNomenclatureIntegrationJob}.php
    Providers/MoySkladServiceProvider.php
  Presentation/
    Console/Commands/BackfillNomenclatureCommand.php

config/warehouse/moysklad.php                        # nomenclature_sync.* (enabled, delete_strategy)
```

**Регистрация:** пакет уже подключён через VCS-репозиторий
`https://github.com/pkmStudio/laravel-moysklad-client`, в `require` стоит
`"pkmstudio/moysklad-client": "dev-main"`. `MoySkladClientServiceProvider` подхватывается
автодискавери пакета, вручную в `bootstrap/providers.php` его добавлять не нужно.
В `bootstrap/providers.php` добавляется только feature provider:
`Warehouse\MoySklad\Infrastructure\Providers\MoySkladServiceProvider`.
`bootstrap/app.php` — путь `app/Warehouse/MoySklad/Presentation/Console/Commands` в
`withCommands([...])`.

---

## Открытые вопросы

1. **`MoySkladJobMiddleware` глотает непредвиденные исключения.** Сейчас любой `\Throwable`, кроме
   `MoySkladApiDisabledException`, только логируется — job считается успешно обработанной, ретрая
   не будет, в `failed_jobs` запись не попадёт. Перенести как есть (сознательно, задокументировав) —
   или исправить на `$job->fail($e)`? Влияет на наблюдаемость сразу у всех потребителей пакета.
2. **События вместо observer.** Observer не используем. Catalog уже диспатчит shared
   `NomenclatureCreated`/`NomenclatureUpdated`/`NomenclatureDeleted`. Import нужно доработать:
   при per-row upsert номенклатуры диспатчить те же shared events, иначе изменения из Excel/import
   не попадут в МойСклад до ручного backfill.
3. **`NomenclatureProductMapper` не покрывает новые поля** dan-vehicles-модели (`brand_id`,
   `material`, `vehicle_type`, `quantity_pak`, `quantity_in_pak`, `details`) — в dan-center мапятся
   только `name`/`country`/`part_number`/`color`/`weight`. Решить при реализации, нужно ли
   обогатить payload (например, `material`/`vehicle_type` как доп. атрибуты МойСклад) — не блокер,
   можно начать с 1:1 переноса текущей логики.
4. **`kits`/комплекты (Bundles) под товары склада (не MpCard)** — в dan-center `BundlesResource`
   целиком про `MpCard` (карточки маркетплейсов), не про `Warehouse\Kit`. Если бизнесу нужна
   синхронизация именно складских `kits` в МойСклад как отдельных "комплектов" (не только
   номенклатуры) — это отдельная, не проанализированная в dan-center задача, нужно уточнять у
   бизнеса, а не переносить существующий код (он про другую сущность).
5. **Удаление в МойСклад.** При локальном удалении номенклатуры `Warehouse/MoySklad` тоже удаляет
   товар в МойСклад. Для этого listener на `NomenclatureDeleted` ставит `DeleteNomenclatureJob`,
   job находит integration по `provider=moysklad` + локальному `nomenclature_id`/сохранённому
   external id, вызывает delete в клиенте МойСклад и после успеха обновляет integration: либо
   отвязывает `nomenclature_id`, либо помечает статусом `deleted`/`synced_at`. Перед реализацией
   проверить blockers удаления в Catalog: запись в `nomenclature_integrations` не должна навсегда
   запрещать удаление, если это integration МойСклад и есть delete workflow.
6. **`organization_id`/`store_id` захардкожены дефолтами в конфиге dan-center** (реальные UUID
   аккаунта) — при переносе в dan-vehicles завести отдельные env-значения, не копировать чужой
   аккаунт как дефолт "на всякий случай".

## Что сознательно остаётся вне скоупа (по итогам анализа)

- Весь **OrderSync** (pull заказов маркетплейсов) — CRM/аналитика, не Warehouse (см. таблицу выше).
- Весь **MpCard/Bundles sync** — CRM/маркетплейсы, не Warehouse.
- **Telegram-уведомления** (`TelegramMoySkladNotifier`) — не переносятся вместе с ядром; пакет
  оставляет точку расширения (`MoySkladNotifierInterface`), конкретную реализацию каждый
  консьюмер (в т.ч. dan-center) регистрирует сам.

---

## План работ (когда решим приступать)

1. ✅ **Сделано (2026-07-12).** Репозиторий `packages/moysklad-client` (`pkmstudio/moysklad-client`)
   создан по образцу `packages/rabbit-transport`: `composer.json`, `config/moysklad-client.php`,
   `src/*` (перенос файлов из таблицы выше 1:1, неймспейс `App\MoySklad` → `PkmStudio\
   MoySkladClient`), 43 теста (Pest + testbench, без реального Redis/МойСклад — `Http::fake()` +
   фейковый `Redis::throttle()` builder), все зелёные. Отличия от dan-center, зафиксированные при
   переносе:
   - `CustomerOrderEndpoint::findStateByName()` принимает `string $stateName` вместо enum
     `OrderState` — статусы заказа настраиваются per-account в МойСклад, это не универсальный enum
     (см. «Что НЕ переезжает» выше).
   - `MoySkladNotifierInterface` — в пакете, но без реализации; дефолтный биндинг —
     `NullMoySkladNotifier` (no-op). Потребитель переопределяет своей реализацией (Telegram/...).
   - `config('moysklad.organization_id'/'store_id')` — дефолты убраны (в dan-center были реальные
     UUID чужого аккаунта), каждое приложение задаёт свои через `.env`.
   - `MoySkladJobMiddleware` перенесён как есть, включая то, что он глотает любой `\Throwable`
     кроме `MoySkladApiDisabledException` — задокументировано в README и в самом классе как
     открытый вопрос (см. «Открытые вопросы», п.1), не исправлено.
   - **Добавлено сверх переноса из dan-center (по запросу пользователя):** `MoySkladHttpClient`
     диспатчит `Events\MoySkladRequestSucceeded`/`Events\MoySkladRequestFailed` ровно один раз на
     каждый вызов `request()` — после того, как retry/circuit-breaker уже решили исход. Пакет **не
     регистрирует ни одного слушателя** — это точка расширения (метрики/алерты/аудит), потребитель
     подписывается сам. OrderSync/customer orders и Telegram-уведомления сознательно не нужны в
     этом продукте — не переносились и не будут (подтверждено пользователем), но
     `CustomerOrderEndpoint` в пакете остаётся (универсален и не требует поддержки заказов в
    Warehouse — см. «Что переезжает в пакет» выше).
   - Репозиторий опубликован и подключается из dan-vehicles как VCS-зависимость — см. п.2 ниже.
2. ✅ **Сделано.** Пакет подключён через VCS-репозиторий
   `https://github.com/pkmStudio/laravel-moysklad-client`, `require` →
   `"pkmstudio/moysklad-client": "dev-main"`. `MoySkladClientServiceProvider` подхватывается
   автодискавери (`extra.laravel.providers` в composer.json пакета), в `bootstrap/providers.php`
   ничего вручную не добавлялось — как и у `rabbit-transport`. Полный набор тестов проекта (214)
   был зелёный после подключения.
3. Создать `app/Warehouse/MoySklad/*` — Infrastructure Models (свои копии `Nomenclature`/
   `NomenclatureIntegration`), локальный `MoySkladProductClientInterface` + adapter к пакету,
   `NomenclatureProductMapper`/`NomenclatureSyncService`/`NomenclatureBackfillService` (перенос
   `ProductsResource`+`NomenclatureBackfillService` с разрезанием на генерик/бизнес, как описано
   выше), event listeners, Jobs, консольная команда, провайдер.
4. `config/warehouse/moysklad.php` — только `nomenclature_sync.*`.
5. Доработать Import: при per-row upsert номенклатуры диспатчить shared
   `NomenclatureCreated`/`NomenclatureUpdated`, чтобы MoySklad sync не зависел от observer и не
   пропускал Excel/import изменения.
6. Реализовать delete workflow: на `NomenclatureDeleted` удалять товар в МойСклад и корректно
   обновлять/отвязывать `nomenclature_integrations`; при необходимости поправить Catalog deletion
   blockers.
7. Тесты: unit на `NomenclatureProductMapper`/`NomenclatureSyncService` (мокая локальный
   `MoySkladProductClientInterface`), feature-тест на Event→Listener→Job→client end-to-end,
   отдельный тест на delete workflow.
8. Решить открытые вопросы 1–6 выше до или во время реализации (не блокеры для старта, но должны
   быть решены до продакшена).
