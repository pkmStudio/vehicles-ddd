# Implementation Plan: типизация CRM read repository через локальные DTO

Branch: master
Created: 2026-08-02

## Settings

- Testing: yes
- Logging: minimal
- Docs: no

## Research Context

Source: conversation / `$aif-explore`

Goal: привести `VehicleCrmReadRepository` к архитектурной границе, где repository возвращает типизированные локальные DTO/read projections, а не массивы и не внешние `dan-wire-contracts` DTO.

Constraints:
- Не возвращать Eloquent-модели за пределы Infrastructure.
- Не использовать `ModelData` для CRM projections, если результат не является снимком сущности.
- Допустимо возвращать локальные Domain DTO из repository по существующим прецедентам `Applicability/Export` и `Warehouse/Catalog`.
- Внешний JSON CRM API должен остаться обратно совместимым.
- `PkmStudio\DanWireContracts\...` не должен создаваться внутри repository; wire mapping должен жить ближе к Presentation boundary.
- Не делать массовый перевод всех `DB::table(...)` на Eloquent без явной пользы.

Decisions:
- Для CRM read flow использовать локальные `final readonly` DTO в `Vehicles/Features/Catalog/Domain/DTOs/Vehicle/Crm/`.
- Repository может читать через `DB::table(...)` или feature-local Eloquent внутри Infrastructure, но на выходе возвращает DTO/`Collection<int, DTO>`.
- `VehicleData` оставить для entity snapshots в mutation/read сценариях, не натягивать на CRM list/detail projection.
- Формирование массивов/wire DTO вынести из repository в отдельный presenter/mapper или в тонкий controller-level boundary.

Open questions:
- Нужен ли отдельный presenter port/interface или достаточно concrete helper рядом с `VehicleCrmController`.
- Стоит ли добавить feature-local Eloquent models `Feature`/`FeatureValue`, если options останутся простыми SQL reads.

## Commit Plan

- **Commit 1** (after tasks 1-3): `refactor: introduce crm read dto contracts`
- **Commit 2** (after tasks 4-5): `refactor: move crm response mapping to presentation boundary`

## Tasks

### Phase 1: Зафиксировать локальный контракт CRM read

- [x] Task 1: Инвентаризировать текущий внешний JSON shape CRM endpoints и существующие wire DTO.
  Files: `app/Modules/Vehicles/Features/Catalog/Presentation/Http/Controllers/VehicleCrmController.php`, `app/Modules/Vehicles/Features/Catalog/Infrastructure/Repositories/VehicleCrmReadRepository.php`, `tests/Feature/Vehicles/Catalog/VehicleCrmReadApiTest.php`, `vendor/pkmstudio/dan-wire-contracts/src/Vehicles/Modules/Vehicles/Features/Catalog/Read/DTO/*`.
  Expected behavior: явно понять поля `index`, `show`, `search`, `features`, `feature-values`, `detail-templates`, `manufacturers` и не потерять совместимость ответа.
  Logging requirements: новых runtime-логов не добавлять; successful read API не должен логироваться. Если во время реализации обнаружится несовместимый payload shape, фиксировать это тестом, а не логом.

- [x] Task 2: Добавить локальные Domain DTO для CRM read projections.
  Files to create: `app/Modules/Vehicles/Features/Catalog/Domain/DTOs/Vehicle/Crm/VehicleCrmListItemDTO.php`, `VehicleCrmDetailDTO.php`, `VehicleCrmSearchItemDTO.php`, `VehicleCrmPaginationMetaDTO.php`, `VehicleCrmPageDTO.php`, `VehicleCrmModificationDTO.php`, `VehicleCrmEngineDTO.php`, `VehicleCrmPartSpecificationDTO.php`, `VehicleCrmFeatureOptionDTO.php`, `VehicleCrmFeatureValueOptionDTO.php`, `VehicleCrmDetailTemplateOptionDTO.php`, `VehicleCrmManufacturerOptionDTO.php`.
  Expected behavior: DTO описывают сценарный read projection, а не entity snapshot; классы `final readonly`, без Laravel/Eloquent/wire dependencies, с докблоками по правилам проекта.
  Logging requirements: DTO не логируют. Не добавлять `Log`/facade/PSR logger в Domain.

- [x] Task 3: Обновить repository/use case contracts на возврат локальных DTO.
  Files: `app/Modules/Vehicles/Features/Catalog/Domain/Contracts/Repositories/VehicleCrmReadRepositoryInterface.php`, `app/Modules/Vehicles/Features/Catalog/Domain/Contracts/UseCases/Vehicle/ListVehiclesForCrmUseCaseInterface.php`, `ShowVehicleForCrmUseCaseInterface.php`, `SearchVehiclesForCrmUseCaseInterface.php`, `app/Modules/Vehicles/Features/Catalog/Application/UseCases/Vehicle/*VehicleForCrmUseCase.php`.
  Expected behavior: `paginate()` возвращает `VehicleCrmPageDTO`, `find()` возвращает `?VehicleCrmDetailDTO`, `search()` и options возвращают `Collection<int, ...DTO>` или typed DTO containers; Application больше не оперирует `array<string, mixed>` для CRM read flow.
  Logging requirements: новых логов не добавлять; use cases остаются thin orchestration без логирования успешных read operations.

### Phase 2: Перенести mapping из repository к Presentation boundary

- [x] Task 4: Переписать `VehicleCrmReadRepository` на сборку локальных DTO вместо массивов/wire DTO.
  Files: `app/Modules/Vehicles/Features/Catalog/Infrastructure/Repositories/VehicleCrmReadRepository.php`; при осознанной пользе можно добавить feature-local models/relations в `app/Modules/Vehicles/Features/Catalog/Infrastructure/Models/`.
  Expected behavior: repository инкапсулирует SQL/Eloquent reads, filters, search, sorting, pagination и nested loads, но не импортирует `PkmStudio\DanWireContracts\...` и не вызывает `toArray()` для HTTP response shape.
  Logging requirements: successful reads не логировать. Не добавлять предупреждения для пустых результатов или not found; это нормальный control flow для read API.

- [x] Task 5: Добавить boundary mapper/presenter и обновить controller на прежний JSON contract.
  Files to create/update: `app/Modules/Vehicles/Features/Catalog/Presentation/Http/Controllers/VehicleCrmController.php`; возможно `app/Modules/Vehicles/Features/Catalog/Presentation/Http/Presenters/VehicleCrmReadPresenter.php` или `app/Modules/Vehicles/Features/Catalog/Application/Services/Vehicle/VehicleCrmReadPresenter.php` с port в `Domain/Contracts/Services/Vehicle/`, если нужен DI-паттерн.
  Expected behavior: controller остаётся тонким: guard/request parsing/use case call/response. Presenter превращает локальные DTO в прежние arrays или `dan-wire-contracts` DTO на внешней границе. HTTP responses `data/meta`, `data`, `message` остаются совместимыми.
  Logging requirements: не логировать успешные responses и unauthorized/not found. Если presenter встретит невозможное состояние DTO, предпочесть fail-fast exception или тестовую защиту; не скрывать ошибку WARN-логом.

### Phase 3: Проверка поведения

- [x] Task 6: Обновить focused tests CRM read API и добавить контрактные проверки типов при необходимости.
  Files: `tests/Feature/Vehicles/Catalog/VehicleCrmReadApiTest.php`; при необходимости `tests/Unit/Vehicles/Catalog/VehicleCrmReadRepositoryTest.php` или ближайшая существующая директория.
  Expected behavior: покрыть прежние сценарии list/show/search/options, unauthorized guard, not found, filters, search, sort и shape `data/meta`; добавить регрессию, что repository/use case больше не возвращает raw arrays для основных CRM read методов.
  Logging requirements: тесты не должны зависеть от логов; новых log assertions не добавлять, потому что логирование не является контрактом read API.

### Phase 4: Локальная чистка затронутого кода

- [x] Task 7: Привести только затронутые файлы к локальным style rules.
  Files: все новые DTO/presenter/contracts и изменённые CRM read controller/repository/use cases/tests.
  Expected behavior: докблок у каждого класса/метода, многострочные именованные аргументы при нескольких параметрах, отсутствие inline `new DTO(...)` внутри вызовов кроме явно разрешённых исключений, без массового форматирования несвязанных файлов.
  Logging requirements: новых логов не добавлять; проверить, что Domain/Application не получили Laravel `Log` facade.
