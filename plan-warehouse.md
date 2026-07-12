# План: перенос домена Warehouse из dan-center в dan-vehicles

> Статус: зафиксированы результаты анализа и архитектурные решения (сессия 2026-07-12).
> Детального пошагового плана реализации (структура классов, порядок миграций, конкретные файлы)
> ещё нет — это следующий шаг, когда решим приступать к реализации (см. «Что дальше» внизу).
> См. также память: `warehouse_migration_plan`, `templates_shared_kernel`.

## Источник

Домен переносится из `/home/user/projects/dan-center` (классический Laravel MVC с доменными
подпапками `app/Models/Warehouse/*`, `app/Services/Warehouse/*` и т.д., без feature-first
структуры) в `dan-vehicles` (`app/<Domain>/<Feature>/{Domain,Application,Infrastructure,
Presentation}`). Warehouse становится доменом, соседним с `Vehicles` — в перспективе рядом
появится ещё и `Applicability` (перенос отдельно, позже).

---

## Что переезжает (таблицы/сущности)

Изначальный список пользователя (совпадает с реальностью, с 2 поправками именования):
- `nomenclatures`
- `kits`
- `nomenclature_integrations`
- `pak_dimensions` — реальное имя в БД (опечатка "pak" вместо "pack" закреплена везде: модель,
  Filament-ресурс и т.д. в dan-center; в задании было "Pak_dimesions"/"Pack_dimensions" — не то
  же самое написание, но та же таблица). Решение по опечатке при переносе — открытый вопрос,
  см. «Открытые вопросы».
- `types`
- `kit_nomenclature` — pivot, единственное число (в задании было "Kit_nomenclatures", реальное
  имя без "s").

Добавлено по итогам анализа кода dan-center (подтверждено пользователем):
- **`brands`** — обязательный `NOT NULL` FK у `nomenclatures.brand_id`, без него нельзя
  наполнять `nomenclatures`. Лежит в том же namespace `Warehouse` в dan-center.

Итого 7 таблиц/сущностей: `Type`, `Nomenclature`, `Brand`, `PakDimension`, `Kit`,
`NomenclatureIntegration`, pivot `kit_nomenclature`.

## Что НЕ переезжает

**Домен Applicability (перенос отдельно, позже):**
- `kit_oem_numbers`, `kit_kit_group`, `kit_groups`, `kit_applicabilitables`.
- Модель `Kit` в dan-center сама объявляет `morphToMany` на `Engine/Vehicle/Modification/
  PartSpecification` через `kit_applicabilitables` — при переносе Warehouse эта связь
  **не переносится**. Тот же разрыв уже сделан для `Vehicle`/`PartSpecification` при выделении
  Vehicles-домена (см. `Applicability-dizajn.md`, раздел «Уже удалено из Vehicles»). Когда дойдём
  до Applicability — там появится `KitsProvider`/`ApplicabilityProvider`-порты, а не Eloquent-связь.
- `kit_groups`/`kit_kit_group` — в dan-center это недоделанный рефакторинг (одна миграция частично
  откатывает другую, Kit-форма читает `kit_groups` напрямую через raw SQL ради полей
  `is_active`/`is_sale_separately`, которые уже есть и в самой `kits`). Игнорируем этот техдолг
  целиком — при переносе `kits.is_active`/`is_sale_separately` читаются из `kits`, `kit_groups`
  как будто не существует.

**`detail_templates` — не переезжает вообще, таблица не нужна в новом виде.**
Вместо DB-driven DSL-схемы JSON-поля `details` у `Nomenclature` (17 типов: BrakePad, SparkPlug,
Wiper, OilFilter, AirFilter, CabinFilter, WiperAdapter, BallJoint, CvJoint, WheelHub, TieRod,
StabilizerLink, TimingBelt, PolyVBelt, Generic и т.д.) — делаем то же самое, что уже сделано в
`Templates` для Vehicles: типизированные `Data`-классы + Factory/Presenter вместо DSL-таблицы.

**Не переезжает, осознанно отложено:**
- **MoySklad-синхронизация** номенклатуры — остаётся в dan-center. В перспективе станет отдельным
  SDK-пакетом + отдельной фичей поверх него. Warehouse в dan-vehicles не проектируется под эту
  интеграцию сейчас, но не должен закрывать возможность подключить её позже (не встраивать
  предположений о МоёмСкладе в доменную модель).
- **MpCard-инвалидация** (CRM/MpSale в dan-center) — будет на событиях: Warehouse публикует факт
  изменения (Nomenclature/Kit обновились), CRM в dan-center сам решает, инвалидировать ли карточки.
  Warehouse ничего не знает про MpCard/CRM. Конкретный транспорт события — вопрос отдельной сессии,
  когда дойдём до этой интеграции (сейчас не блокирует перенос данных/моделей).
- **Filament-админка** (CRUD для Nomenclature/Kit/Type/PakDimension) — не переезжает, сервис
  headless, как и остальные фичи dan-vehicles.

**Не относится к домену (проверено, но не будет упомянуто отдельно):** `vendors`, `drivers` —
не имеют FK-связей с переносимыми таблицами, `mp_card_attributes`/`rich_content_templates`/
`ms_order_positions`/`ms_order_components` — таблицы других доменов dan-center с FK на `types`/
`nomenclatures`/`brands`, но сами не переезжают (dan-center продолжит на них ссылаться через БД
напрямую, т.к. БД общая — см. ниже).

---

## Архитектурные решения

1. **Стратегия переноса — сразу правильно, не 1:1 копия с последующим рефакторингом.**
   Feature-first (`Domain/Application/Infrastructure/Presentation`), без прямых Eloquent-связей на
   Vehicles/Applicability, через query-порты и DTO там, где нужна междоменная связь. Прецедент —
   уже так сделали при выделении Vehicles (`Applicability-dizajn.md`): главный принцип — сервис не
   знает о моделях/таблицах другого домена, только контракты (query-порт и/или integration-событие).

2. **Общая БД с Vehicles** (как сейчас и как запланировано для Applicability). Это упрощает
   реализацию — не нужны event-проекции/синхронизация данных между базами, только контракты на
   уровне кода (query-порты), FK между доменами физически возможны, но осознанно не используются
   ради чистоты границ.

3. **`Templates` поднимается до `app/Templates/*`** — на уровень выше, соседняя папка с
   `app/Vehicles` и `app/Warehouse`, а не фича внутри Vehicles. Причина: как только второй домен
   (Warehouse) начинает пользоваться той же типизацией `details`-полей, оставлять её физически
   внутри Vehicles значило бы, что Warehouse читает внутренности чужого домена. Статус —
   **shared kernel** (DDD-термин): без своей БД-таблицы/состояния/бизнес-правил о
   Vehicle/Engine/Kit/Nomenclature, другие домены обращаются к нему напрямую кодом
   (`use App\Templates\...`), не через порт — осознанное и единственное исключение из правила
   изоляции доменов. Подробности и обоснование — память `templates-shared-kernel`.

4. **`Templates` остаётся полностью кодом, без БД** (обсуждали идею schema-registry в таблице —
   отложено, YAGNI, нет конкретного триггера типа "бизнес хочет сам заводить типы через админку").

5. **`App\Enums\TypeEnum` в dan-center (хардкод id⇄тип, используется в MpSale/MoySklad-коде) —
   убрать, не дублировать/синхронизировать.** Раз БД общая, dan-center резолвит нужные типы через
   саму таблицу `types`, ориентируясь на колонку `char` (двухбуквенный бизнес-код: BP/SP/WB/...)
   как на стабильный ключ, а не на auto-increment `id`. Внутри самого Warehouse/Templates
   диспетчеризационный enum по типам всё равно нужен (аналог `DetailTemplateEnum`) — это не
   убирается, убирается только внешнее дублирование в dan-center.

---

## Открытые вопросы — решено (2026-07-12, продолжение)

- **Опечатка `pak_dimensions` — не тянуть.** Таблица переезжает под правильным именем
  **`pack_dimensions`**. Значит, при переносе это не просто копирование структуры — переименование
  таблицы и всех связанных FK/имён (модель, миграция, `kits.pak_dimension_id` →
  `kits.pack_dimension_id`, и т.д.) через все слои.
- **Стабильность `types.char` — сейчас стабильно, но не навсегда.** Типы сейчас не редактируются
  через UI/API в dan-center (только `ListTypes`), но планируется сделать их редактируемыми
  (Create/Edit) в будущем. Это **усиливает**, а не ослабляет решение убрать `App\Enums\TypeEnum` в
  dan-center: раз типы со временем станут динамически добавляемыми через UI, захардкоженный enum в
  dan-center сломается при первом же новом типе — dan-center обязан резолвить типы через живую
  таблицу `types` (по `char`), а не через копию-enum, которая гарантированно разъедется.
  **Важный нюанс, который остаётся открытым для реализации:** сама *схема* JSON-поля `details`
  (какие именно поля хранит конкретный тип) — это код (`Templates`, Data-классы), не данные. Значит
  добавление нового `Type` через будущую админку само по себе не создаст ему форму `details` —
  либо новый тип должен по умолчанию использовать `Generic`-форму (уже есть такой кейс в списке
  17 типов dan-center), либо на новый тип с нестандартной формой полей всё равно нужен деплой
  (новый Data-класс + case в диспетчеризационном enum). Держать в уме при проектировании Nomenclature.
- **Разбивка Warehouse на фичи — решено: по функции, как в Vehicles, НЕ по сущностям, как в
  dan-center.** Подход dan-center (`Models/Warehouse`, `Services/Warehouse`, `Imports/Warehouse` —
  плоские папки по техническому типу файла, внутри вперемешку все сущности) признан неправильным.
  Ориентируемся на паттерн Vehicles (`Templates`/`Import`/`Export`/`Maintenance`/`Shared`, где
  Import/Export держат каждый свои модели на одни и те же таблицы). `Assembly` как одна большая
  "всё умеющая" фича признана слишком широкой: сервисный слой `Services/Warehouse/Kit/*` из
  dan-center режется на более мелкие функциональные фичи — `Packaging`, `KitProperties`,
  `KitGrouping`, `WiperAdapterAudit`; `Maintenance` остаётся тонкой обёрткой над ними.
- **Транспорт события "Nomenclature/Kit изменились" — решено, максимально просто.** Warehouse
  просто диспатчит доменное событие о факте изменения (например: номенклатура изменилась, набор
  изменился) — и на этом ответственность Warehouse заканчивается. Как именно CRM/dan-center это
  событие подхватит и что с транспортом между приложениями (если БД общая — возможно, достаточно
  обычного Laravel-события/слушателя, без кросс-сервисной шины) — решать отдельно, когда дойдём до
  этой интеграции, не сейчас.
- **Ownership `brands` и `types` — решено: не отдельная фича.** Прецедент — `Manufacturer` в
  Vehicles (та же роль: обязательный справочник-FK, без своей сложной логики) не выделен в
  отдельную фичу, а продублирован тонкой моделью в каждой фиче, которой он нужен
  (`Import/Infrastructure/Models/Manufacturer.php`, `Export/Infrastructure/Models/Manufacturer.php`,
  `Maintenance/Infrastructure/Models/Manufacturer.php`), реальная логика (upsert из Excel, DTO,
  события) живёт только там, где нужна — в `Import`. `Brand` и `Type` в Warehouse — той же роли,
  делаем так же: своя модель на общую таблицу в каждой фиче, которой нужен справочник (Import,
  Export, Packaging/KitProperties/KitGrouping/WiperAdapterAudit при необходимости), без отдельной
  "справочник"-фичи.
  **Исключение на будущее:** если `Type` получит полноценный CRUD через API/UI (уже запланировано
  пользователем), само администрирование справочника (создание/редактирование типов) может стать
  поводом для отдельной небольшой фичи — но это про функцию "администрирование", а не про то, что
  `Type` как сущность должен жить в своей фиче уже сейчас.

---

## Общий план реализации — фичи Warehouse (2026-07-12)

Разбивка по функции (как в Vehicles), не по сущности (как в dan-center):

1. **`Import`** — Excel-импорт Nomenclature/Kit/PakDimension (chunked, queued — как сейчас в
   dan-center). Резолвит `type_id`/`brand_id`/номенклатуры по строкам, кэширует ошибки импорта
   (аналог `CachesImportFailures`), шлёт события завершения импорта (`NomenclatureImportCompleted`
   и т.п.). Для `details` Nomenclature опирается на расширенный `Templates` (17 типов — это работа
   в shared kernel `Templates`, НЕ отдельная фича Warehouse, но обязательный пререквизит для
   Import/Export). Для сборки производных свойств Kit (упаковка/вес/комплектация) не считает сам —
   вызывает `KitProperties`/`Packaging`, по аналогии с тем, как Import в Vehicles вызывает
   `Templates` для деталей.
   Здесь же — доменные события `NomenclatureUpdated`/`KitUpdated` ("просто выкидываем событие" для
   будущей MpCard-инвалидации в CRM) — не отдельная фича, а часть той фичи, где реально происходит
   запись (пока это `Import`; в будущем — если появится прямой API на запись, событие переедет туда).

2. **`Export`** — Excel-экспорт Nomenclature/Kit + рендер отчёта по несовпадению адаптеров
   дворников. Стили Excel из dan-center (`WithStyles`: серая жирная шапка, autosize колонок) **не
   переносим**: сервис headless, контракт — данные и структура листов, не визуальное оформление.
   Расчёт несовпадений адаптеров не живёт в Export — Export только принимает готовые строки отчёта
   от `WiperAdapterAudit` и сохраняет файл.

   Для `Nomenclature` обязательно сверить Excel-контракт с dan-center: строки `details` уже
   рендерятся через `Templates`, но справочный лист должен строиться из того же источника, что и
   headings/cells, а не отдельным ручным `match` в Export. Иначе легко разъедутся 17 шаблонов.
   Зафиксированные расхождения после сравнения с dan-center, которые надо исправить в Templates/
   Export:
   - `PositionEnum`: старые лейблы Warehouse — `Переднее` / `Универсальное` / `Заднее`, не
     `Передняя` / `Универсальная` / `Задняя`;
   - `SeasonEnum`: старые лейблы — `Зима` и `На любой сезон, Демисезон`, не `Зимняя` и
     `Всесезонная`;
   - заголовки detail-колонок должны повторять старые `FieldTemplate` labels (`Длина (мм)`,
     `Вид колодки`, `Шаг резьбы (мм)`, `Межконтактный зазор (мм)`, и т.д.), если внешний Excel
     контракт должен остаться совместимым.

   Для `Kit` экспорт должен принимать явные фильтры во входящем payload, а не наружный аналог
   Filament `Builder`. Минимальный wire-контракт:
   ```
   filters: {
       ids: [1, 2, 3],
       type_ids: [2, 3],
       is_active: true,
       is_sale_separately: false,
       nomenclature_part_numbers: ["A1", "B2"],
       search: "text"
   },
   sort: {
       field: "id",
       direction: "asc"
   }
   ```
   `ids` — точечная выгрузка наборов; `type_ids` — замена старого Filament-фильтра по типу;
   `is_active`/`is_sale_separately` — фильтры по явным флагам набора;
   `nomenclature_part_numbers` — набор содержит хотя бы одну номенклатуру из списка;
   `search` — совместимость с табличным поиском (`complectation`, `nomenclatures.part_number`,
   `nomenclatures.name`), не основной интеграционный ключ. Сортировка по умолчанию — `id asc`.

3. **`Packaging`** — подбор/создание упаковки (`pack_dimensions`). Сюда переезжают 8 стратегий из
   dan-center `Services/Warehouse/Kit/PakDimension/*` и query-слой подбора упаковки. Используется
   `KitProperties` при расчёте набора и `Maintenance` при пересчётах/очистке. Это отдельная фича,
   потому что подбор упаковки имеет свою стратегическую логику, свой read/write к `pack_dimensions`
   и не должен разрастаться внутри "сборки всего".

4. **`KitProperties`** — расчёт свойств уже заданного набора: комплектация, вес, `quantity_*`,
   `complement`, `type_id`, выбор упаковки через `Packaging`. Источник в dan-center —
   `Services/Warehouse/Kit/KitService.php` и `Services/Warehouse/Kit/Assembly/*`. Используется
   `Import` при создании/обновлении Kit и `Maintenance` при принудительном пересчёте.

5. **`KitGrouping`** — автогруппировка номенклатур в кандидаты на наборы: `Grouping/SparkPlug/*`,
   `Grouping/Wiper/*`, совместимость щёток и адаптеров как алгоритм предложения новых наборов.
   Это не то же самое, что пересчёт свойств уже существующего Kit, поэтому не смешиваем с
   `KitProperties`.

6. **`WiperAdapterAudit`** — расчёт несовпадений адаптеров дворников по старому
   `KitWiperAdapterMatcher`: выбрать наборы с адаптерами, сравнить адаптеры из комплекта и из
   номенклатуры, вернуть строки отчёта (`kit_id`, `kit`, `matched_adapters`, `place`). Файл отчёта
   рендерит `Export`, но расчёт живёт здесь.

   **Явно исключено из Warehouse Kit-фич** (уходит в будущий домен Applicability, не Warehouse):
   `Applicability/SparkPlug`, `Applicability/Wiper`, `KitApplicabilityImport`, `KitApplicabilityJob`,
   `KitApplicabilityCalculated`.

7. **`Maintenance`** — консольные команды (пересчёт китов колодок, очистка `pack_dimensions`) —
   тонкие обёртки, делегирующие реальную работу в `Packaging`/`KitProperties`/`KitGrouping`/`Import`.

**Справочники (`Brand`, `Type`) — без своей фичи** (решено ранее): тонкая модель на общую таблицу
в каждой фиче, которой нужен справочник (по прецеденту `Manufacturer` в Vehicles).

**`NomenclatureIntegration`** — таблица+модель переезжают "про запас", без активной логики: ни одна
из 4 фич выше её не использует, реальный функционал (MoySklad-синхронизация) отложен на будущую
SDK-фичу.

**Итоговая структура (предварительно):**
```
app/Warehouse/{Import,Export,Packaging,KitProperties,KitGrouping,WiperAdapterAudit,Maintenance}/{Domain,Application,Infrastructure,Presentation}
app/Templates/*          (shared kernel, расширяется под 17 типов Nomenclature)
```

## Организация инфраструктуры: миграции и конфиги по доменным папкам (2026-07-12)

**Миграции — вариант Б: co-located внутри домена.** Не единый `database/migrations`, а миграции
физически лежат рядом с доменом, который ими владеет:
```
app/Vehicles/Infrastructure/Database/Migrations/*
app/Warehouse/Infrastructure/Database/Migrations/*
```
Каждый домен регистрирует свой путь через `$this->loadMigrationsFrom(...)` в `boot()` собственного
ServiceProvider (по аналогии с тем, как `TemplatesServiceProvider` уже регистрирует биндинги).
Технически это не ломает ни `migrate`, ни `migrate:rollback`/`status` — Laravel определяет порядок
выполнения по timestamp-префиксу имени файла и хранит в таблице `migrations` только имя файла, не
путь. Плата: `php artisan make:migration` по умолчанию создаёт файл в `database/migrations` — нужно
либо руками переносить после генерации, либо сразу указывать `--path=`.

**Конфиги приложения (наши, не пакетные) — тоже по доменным папкам.** Начиная с Laravel 11
(проект на Laravel 13) `LoadConfiguration` рекурсивно сканирует `config/` и превращает вложенность
в префикс через точку — `config/vehicles/export.php` → `config('vehicles.export.*')`. Работает из
коробки, регистрировать ничего не нужно (в отличие от миграций). Переносим:
```
config/vehicles-export.php  → config/vehicles/export.php   (config('vehicles-export.*') → config('vehicles.export.*'), 5 use-мест)
config/vehicles-import.php  → config/vehicles/import.php   (config('vehicles-import.*') → config('vehicles.import.*'), 11 use-мест)
config/warehouse/export.php, config/warehouse/import.php   — по аналогии для Warehouse
```

**Исключение: `config/rabbit-transport.php` — НЕ разбивать, оставить единым файлом.** Пакет
`pkmstudio/rabbit-transport` (`/home/user/projects/packages/rabbit-transport`) хардкодит один
плоский ключ во всех местах (`config('rabbit-transport.connection')`,
`config('rabbit-transport.inbound', [])`, `config('rabbit-transport.outbound.'.$name)`,
`config('rabbit-transport.setup.bindings')` и т.д., через `mergeConfigFrom` в
`RabbitTransportServiceProvider`). Laravel не сливает содержимое двух разных файлов конфига в один
и тот же ключ автоматически (авто-merge работает только для фиксированного списка core-конфигов
Laravel — `auth`/`cache`/`queue`/и т.п., не расширяемо на сторонние пакеты). Если положить файл в
`config/warehouse/rabbit-transport.php`, ключ станет `warehouse.rabbit-transport`, и пакет его не
найдёт. Решение: при добавлении Warehouse-событий просто дописывать новые записи в те же массивы
`inbound`/`outbound`/`setup.bindings` единого `config/rabbit-transport.php`, помечая комментарием,
какому домену принадлежит блок (как уже частично сделано для Vehicles: `VEHICLES_IMPORT_FILE_REQUESTED`,
`ENGINES_IMPORT_FILE_REQUESTED` и т.д. сгруппированы вместе).

## Реализация — прогресс (2026-07-12)

Сделано: `Templates` перенесён в `app/Templates/*` (73 файла обновлены), миграции Vehicles
co-located в `app/Vehicles/Infrastructure/Database/Migrations` (`VehiclesServiceProvider`),
конфиги `vehicles-export.php`/`vehicles-import.php` → `config/vehicles/{export,import}.php`,
создан первоначальный скелет `app/Warehouse/{Import,Export,Assembly,Maintenance}/{Domain,
Application,Infrastructure,Presentation}` + 7 миграций (`types`, `brands`, `nomenclatures`,
`pack_dimensions`, `kits`, `kit_nomenclature`, `nomenclature_integrations`) в
`app/Warehouse/Infrastructure/Database/Migrations` + `WarehouseServiceProvider`. Все 76 тестов
зелёные, `migrate:fresh` проходит целиком. Позже принято решение не развивать `Assembly` как одну
широкую фичу: целевое разбиение — `Packaging`/`KitProperties`/`KitGrouping`/`WiperAdapterAudit`.

**Дополнительное решение по ходу реализации: никакого `onDelete('cascade')` в FK.** Ни в одной
миграции Warehouse (в отличие от dan-center, где `pak_dimensions.type_id`, `kits.pak_dimension_id`/
`type_id`, `kit_nomenclature.nomenclature_id`/`kit_id`, `nomenclature_integrations.nomenclature_id`
были все `cascadeOnDelete()`). Причина: каскадное удаление на уровне БД — "магическое" побочное
действие, невидимое в коде и трудно тестируемое/трассируемое. Матчит уже существующий стиль
Vehicles-миграций (там `foreignId(...)->constrained(...)` без единого `onDelete` вообще). Удаление
родительских записей при наличии зависимых теперь либо блокируется БД по умолчанию (RESTRICT),
либо должно явно обрабатываться в Application-коде (например, явный сервис, который сначала
удаляет/переносит зависимые записи, тестируемый и видимый в трассировке).

**Также: во всех колонках всех миграций — `->comment()`**, поясняющий назначение поля (включая
предупреждения там, где смысл поля не задокументирован в dan-center, например `brands.date_start`/
`date_begin`, и там, где тип поля — унаследованная странность, например `nomenclatures.weight`
строкой, а не числом).

**Templates расширен под все 17 типов Nomenclature (2026-07-12), готово для использования
будущими Import/Export.** Переиспользованы 1:1 (совпали количество и состав кейсов) уже
существовавшие в Templates enum'ы: `Filter\{FormEnum,PerformanceEnum,OilFilterFatherEnum,
OilFilterThreadEnum}`, `SparkPlug\{ThreadSizeEnum,ThreadPitchEnum,ThreadLengthEnum,
ElectrodeGapEnum,WrenchJawWidthEnum}`, `Wiper\{FrontAdapterTypeEnum,RearAdapterTypeEnum}` — эти
словари общие для Vehicles (потребность ТС) и Warehouse (характеристика товара) не случайно,
а потому что описывают одну и ту же физическую номенклатуру резьб/зазоров/адаптеров. Новые
enum'ы, которых не было: `PositionEnum`, `BooleanOptionEnum` (select "Да"/"Нет" → `bool` в
Data-классе, наружу не течёт), `BrakePad\{BrakePadTypeEnum,LiningMaterialEnum}`,
`Wiper\{ConstructionEnum,SeasonEnum,SteeringCompatibilityEnum}` (сознательно НЕ то же самое, что
`Vehicles\Shared...\SteeringTypeEnum` — не стали ради 3 кейсов тянуть Warehouse-код на внутренности
Vehicles), `Filter\FilterMediaTypeEnum`, `SparkPlug\ElectrodeSideCountEnum`,
`TieRod\ApplicationEnum` (в dan-center было литеральным массивом, здесь типизировано).
21 Data-класс в `Domain/ModelData/Nomenclature/*` (17 top-level + `NomenclatureMetricsData` +
3 nested), 17 Builder + 17 Presenter классов (по образцу `WiperDetailsBuilder`/`...Presenter`),
новый диспетчеризационный `NomenclatureDetailTemplateEnum` (отдельный от `DetailTemplateEnum` —
разные формы для одноимённых шаблонов, см. докблок), новая пара портов
`NomenclatureDetailsDataFactory(Interface)`/`NomenclatureDetailsDataPresenter(Interface)`,
забинжены в `TemplatesServiceProvider`. Round-trip Factory→Presenter вручную проверен (tinker)
для нескольких сложных случаев (вложенные объекты, boolean-select, conditional `father`,
числовые `#[MapName]`-поля, пустая заглушка) — работает корректно. Как и Vehicle-side классы —
без тестового покрытия, пока не подключены к реальному Import/Export (это уже работа фичей).

## Реализация — Warehouse/Import (2026-07-12)

Реализован полный вертикальный срез `app/Warehouse/Import/{Domain,Application,Infrastructure,
Presentation}` — Excel-импорт **Nomenclature + PackDimension** (chunked, `ShouldQueue`), с полным
RabbitMQ-триггером симметрично `Warehouse/Export` (`ImportFileRequestedHandler` →
`StartExternalFileImportUseCase` → идемпотентность/cleanup через cache → `AfterImport` →
`NomenclatureImportCompleted`/`PackDimensionImportCompleted` → `ReportImportResultListener` +
`CleanupExternalImportFileListener`), плюс тонкие Artisan-команды
(`warehouse:import-nomenclature`, `warehouse:import-pack-dimensions`) для ручного запуска.

Резолв `type_id`/`brand_id` — предзагрузка карт по имени через свои `TypeRepository`/
`BrandRepository` (своя копия моделей/Data на фичу, как и у Export). Форма `details` номенклатуры
собирается через уже готовый shared kernel `Templates\NomenclatureDetailsDataFactory`
(`buildFromRow()`), `TypeTemplateResolver` — своя копия резолвера из `Warehouse\Export`
(осознанное дублирование, фичи не должны знать друг о друге). Материал/вид техники номенклатуры
переводятся из русских Excel-лейблов в ключи через обратные таблицы, симметричные
`NomenclatureExportRow::MATERIAL_LABELS`/`VEHICLE_TYPE_LABELS` (в проекте нет реальных backed enum
`MaterialEnum`/`VehicleTypeEnum`, несмотря на комментарий в миграции — только лейбл-словари).

Добавлены/изменены: `config/warehouse/import.php` (новый), `bootstrap/providers.php`
(`ImportServiceProvider`/`ImportEventServiceProvider` с алиасами `WarehouseImport*`),
`bootstrap/app.php` (путь Presentation-команд), `config/rabbit-transport.php`
(`WAREHOUSE_NOMENCLATURE_IMPORT_FILE_REQUESTED`/`WAREHOUSE_PACK_DIMENSION_IMPORT_FILE_REQUESTED`
inbound, `WAREHOUSE_IMPORT_COMPLETED` outbound, соответствующие `setup.bindings`). 27 новых
unit/feature-тестов (`tests/{Unit,Feature}/Warehouse/Import/*`), полный набор проекта (133 теста)
зелёный.

**Явный TODO — Kit-импорт не реализован.** Зависит от ещё не построенной `Warehouse/KitProperties`
(расчёт свойств набора — комплектация/вес/`quantity_*`/`complement`/`type_id`, сейчас
`Services/Warehouse/Kit/KitService.php`+`Assembly/*` в dan-center; сама она зовёт `Packaging` для
подбора упаковки — см. «Общий план реализации — фичи Warehouse»). При реализации нужно добавить в
`ImportTypeEnum` кейс `Kit`, адаптер `KitImport` (резолв номенклатур по артикулам, вызов
`KitProperties` для расчёта производных свойств — по аналогии с тем, как dan-center `KitImport`
вызывает `KitService`) и соответствующие RabbitMQ inbound-записи.

**Важно для реализации KitImport:** вызов `Import → KitProperties` — это межфичевый вызов **внутри
одного домена** Warehouse, не shared kernel вроде `Templates` (у `KitProperties`/`Packaging` есть
своя БД-логика и бизнес-правила, в отличие от `Templates`, который декларативно без состояния). Он
должен идти через `Domain/Contracts` вызываемой фичи (`KitProperties` публикует свой порт, `Import`
зависит только от интерфейса), а не через прямой `use` конкретного класса — то же правило «порт у
каждого инъектируемого класса» (ARCHITECTURE.md §5), применённое между фичами одного домена, а не
только внутри одной фичи. Не копировать сюда паттерн `Templates` (прямой `use` без порта) — это
неверная аналогия: `Templates` — намеренное единственное исключение из правила изоляции.

## Реализация — Warehouse/Packaging + Warehouse/KitProperties (2026-07-12)

Реализованы обе фичи, на которые опирался явный TODO Kit-импорта. Источник — dan-center
`Services/Warehouse/Kit/{PakDimension,Assembly}/*`, прочитан полностью напрямую (не по памяти).

**`Packaging`** (`app/Warehouse/Packaging/*`) — подбор/создание упаковки. Все 8 стратегий dan-center
перенесены 1:1 (`BrakePads/Wiper/CabinFilter/OilFilter/Generic/SparkPlugs/WiperAdapter/AirFilter`,
включая хардкод-исключения по конкретным артикулам — перенесены как приватные const, не как
конфиг/БД, по прецеденту `TypeTemplateResolver::BY_CHAR/BY_ID/BY_NAME`). Диспетчеризация — по
`NomenclatureDetailTemplateEnum` (общий с Templates/Export/Import), не по хардкод-`TypeEnum` из
dan-center (такого enum в dan-vehicles нет). Стратегии — простые классы без порта (как билдеры
`NomenclatureDetailsDataFactory`), выбираются `match` внутри `PackagingService`. Ошибка "нет
подходящей коробки" (только `OilFilterPackagingStrategy`) — именованное
`PackDimensionNotResolvableException`, не общий `Throwable`, как было у dan-center.

**`KitProperties`** (`app/Warehouse/KitProperties/*`) — расчёт свойств набора (комплектация/вес/
quantity/type), зовёт `Packaging` через порт (`PackagingServiceInterface`) — межфичевый вызов
внутри домена, не shared kernel, поэтому на границе явный перевод своих `TypeData`/
`NomenclatureData` в Packaging-шные (обе фичи держат собственные копии этих Data-классов).
`WordNumberConverter`/`KitComplectationService` перенесены дословно (числительные словами на
русском, склонения); словарь материалов — своя копия лейбл-таблицы (в dan-vehicles нет backed
`MaterialEnum`, только приватные таблицы у Export/Import/здесь для той же цели — это уже 3-е
дублирование одних и тех же 12 пар, кандидат на будущий вынос в реальный shared enum, если
понадобится 4-е место). Две стратегии состава (`SingleTypeStrategy` fallback,
`WiperWithAdapterStrategy`) — единственный случай в Warehouse, где стратегии реально полиморфны
(перебор chain-of-responsibility, не `match`), поэтому у них есть настоящий интерфейс
(`KitCompositionStrategyInterface`); DI — closure-биндинг с явным упорядоченным массивом в
`KitPropertiesServiceProvider` (как `KitServiceProvider` в dan-center), не `*_BINDINGS`-константа.

**Явно вне скоупа (как и планировалось):** сам upsert `Kit`/`kit_nomenclature`
(`KitService::upsert()` из dan-center) — не перенесён, это будущий `KitImport`, который вызовет
`KitPropertiesServiceInterface::build()` так же, как `Import` уже вызывает `Templates`. У
`Packaging`/`KitProperties` пока нет ни Console, ни RabbitMQ-слоя — их единственный потребитель
появится вместе с `KitImport`.

46 новых unit-тестов (`tests/Unit/Warehouse/{Packaging,KitProperties}/*`), полный набор проекта
(179 тестов) зелёный.

## Реализация — Kit-импорт (2026-07-12)

Закрыт TODO из раздела «Реализация — Warehouse/Import»: `ImportTypeEnum` пополнен кейсом `Kit`,
добавлен полный вертикальный срез внутри уже существующей `Warehouse/Import` — Excel-адаптер
`KitImport` (тот же паттерн `ShouldQueue`+chunked+`CachesImportFailures`, что у Nomenclature/
PackDimension), `KitCommand` (транзакционный `find по id/import_hash → update|create → detach+
attach` состава — 1:1 порт persist-части dan-center `KitService::upsert()`), новый порт
`NomenclatureRepositoryInterface::findByPartNumbers()` (Import раньше только писал номенклатуру,
читать её обратно по артикулу было нечем — потребовалось для резолва состава набора) и Artisan-
команда `warehouse:import-kits`.

`UpsertKitFromRowService` — межфичевый вызов `Import → KitProperties` через порт
(`KitPropertiesServiceInterface::build()`), с явным переводом `NomenclatureForKitData`/`TypeData`
Import-фичи в `KitProperties`-шные Data-объекты на границе (тот же принцип, что уже закреплён для
`KitProperties → Packaging`). Правило dan-center «нет упаковки — нет Kit» (`KitService::upsert()`
кидал `RuntimeException` при `pakDimensionId === null`) перенесено как явная валидация
(`InvalidArgumentException` до вызова Command) — в dan-vehicles это же ограничение задано и в
схеме (`kits.pack_dimension_id` — NOT NULL FK), так что валидация просто ловит проблему раньше и с
понятным сообщением, а не полагается на ошибку БД.

RabbitMQ: `WAREHOUSE_KIT_IMPORT_FILE_REQUESTED` добавлен в тот же inbound-блок и обрабатывается
тем же `ImportFileRequestedHandler` (диспетчеризация по `import_type` уже была общей — правки
самого handler'а не потребовалось), `crm.warehouse.kits.import` — в `setup.bindings`.

8 новых тестов (`UpsertKitFromRowServiceTest`, `KitImportTest` — последний реальным образом
прогоняет `KitProperties`+`Packaging` через контейнер, не моки, включая auto-create упаковки для
generic-типа без detail-колонок), плюс новый кейс в `ImportFileRequestedHandlerTest` и обновлённая
registration-проверка `rabbit-transport.php`. Полный набор проекта — 186 тестов, зелёный.

**Warehouse-домен на этом закрыт по изначальному скоупу** (`Import`: Nomenclature/PackDimension/
Kit; `Export`; `Packaging`; `KitProperties`; `WiperAdapterAudit`). Не сделано осознанно (см. выше
по документу) — `Maintenance` (пустая заготовка, консольные команды пересчёта/очистки),
`KitGrouping` (авто-группировка остатков в кандидаты на наборы), `Applicability`-домен целиком,
MoySklad/MpCard-интеграции.

## Реализация — сквозная проверка на реальных данных + сидеры (2026-07-12)

Прогнал полный пайплайн (`Import` → `KitProperties` → `Packaging` → `Export`) на реальных
production-масштаба файлах (7 файлов номенклатуры + файл наборов на 19267 строк), через симуляцию
RabbitMQ-сообщений (не просто консольные команды) — включая проверку, что файл реально удаляется
после внешнего импорта. Round-trip Import→Export сверен один-в-один на реальной строке.

**Найден и исправлен реальный баг**: `ExternalImportCacheService` хранил DTO очистки как объект —
в Redis (боевой кэш проекта) это разваливалось в `__PHP_Incomplete_Class` при чтении обратно, из-за
чего файл после внешнего импорта никогда не удалялся. Тесты не ловили — гоняются на `array`-кэше
без реальной сериализации. Хранится теперь как plain-массив.

**Найдена и закрыта операционная дыра**: 4 из 8 Packaging-стратегий (Wiper/SparkPlugs/WiperAdapter/
OilFilter) никогда не создают коробку автоматически — на чистой БД без единой `pack_dimensions`
они падают. Портированы 1:1 из dan-center сидеры `WarehouseTypeSeeder`/`WarehouseBrandSeeder`/
`WarehousePackDimensionSeeder` (`database/seeders/`, вызываются из `DatabaseSeeder`) — реальные 17
типов, 3 бренда с настоящими сертификатами, 20 боевых упаковок для этих 4 типов (плюс AirFilter/
CabinFilter — те умеют auto-create, но с сидом используют проверенные бизнесом размеры, а не
что попало с первого товара).

## Что дальше (2026-07-12, актуальный срез)

Из 7 фич первоначального плана (§«Общий план реализации») готовы 5: `Import` (Nomenclature/
PackDimension/Kit), `Export`, `Packaging`, `KitProperties`, `WiperAdapterAudit`. Осталось:

1. **`Maintenance`** — пустая заготовка (4 пустые папки), ни одной команды. Нужно перенести из
   dan-center `warehouse:cleanup-brake-pads-pak-dimensions` (удаление неиспользуемых коробок
   колодок, `--dry-run`) и `warehouse:recalculate-brake-pads-kits` (пересчёт упаковки/веса
   существующих китов батчами через `KitProperties`, `--dry-run`/`--chunk`) — обе тонкие обёртки
   над уже готовыми `Packaging`/`KitProperties`/`Import`'овским `KitCommand`, блокеров нет.
2. **Доменные события `NomenclatureUpdated`/`KitUpdated`** — запланированы в этом же документе
   (см. §1 «Общий план реализации», пункт `Import`) для будущей MpCard-инвалидации в CRM
   dan-center, но не реализованы: `Import` сейчас диспатчит только `*ImportCompleted` (на весь
   прогон), не событие на каждую изменённую запись. Не блокер (CRM пока не подключена), но открытый
   пункт исходного плана.
3. **`KitGrouping`** — авто-группировка остатков номенклатуры в кандидаты на новые наборы. Не
   начато вообще. Открытый вопрос из этого документа остаётся открытым: возможно, стоит развести
   на отдельный триггер/фичу, а не считать частью Warehouse-Kit-фич буквально.
4. **Мелкая доработка робастности `Import`**: все 3 Excel-адаптера (`Nomenclature`/`PackDimension`/
   `KitImport`) ловят только `InvalidArgumentException` в `collection()` — любое другое исключение
   (например, `TypeError` из Packaging, как было найдено при тесте на реальных данных до фикса
   сидов) прерывает **весь** прогон вместо того, чтобы записать одну строку в failures и продолжить
   остальные. Сознательно не трогал при переносе (1:1 с тем, как задумано), но стоит взвесить
   расширение catch на `Throwable` с логированием, если такое поведение нежелательно в проде.
5. **`rabbit-transport:setup` не запускался** — RabbitMQ publish (уведомления о завершении
   импорта/экспорта) сейчас реально падает в `NO_ROUTE`, т.к. exchange/очередь/bindings ещё не
   объявлены в брокере. Сам код и конфиг готовы (`config/rabbit-transport.php`), нужен только
   реальный прогон setup-команды в целевом окружении.

## Что отложено (сознательно, не в этом скоупе)

- **`KitGrouping`** — см. п.3 выше (по сути открыт, но не обязателен для текущего Warehouse MVP).
- **Домен `Applicability`** целиком — перенос отдельно, позже (`kit_oem_numbers`, `kit_kit_group`,
  `kit_groups`, `kit_applicabilitables`, `Applicability/SparkPlug`, `Applicability/Wiper`,
  `KitApplicabilityImport`/`Job`/`Calculated`).
- **MoySklad-синхронизация** и **MpCard-инвалидация** (сам транспорт/интеграция с CRM, не только
  события из п.2) — будущая SDK-фича, вне текущего плана.
- **`larastan`/`phpstan` + CI** — отдельная, независимая от Warehouse задача (см. `plan-new.md`).
- **Реструктуризация `app/`** (`Domains/`+`Features/`) — намеренно отложена до момента, когда весь
  параллельный код (уже закоммичен на данный момент) осядет; сама по себе не связана с Warehouse.
- **Filament-админка** — не нужна, сервис headless по дизайну.
