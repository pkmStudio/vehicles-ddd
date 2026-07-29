# Vehicle module — аудит событий

## 1) Уже существующие доменные события

| Класс | Строка | Что это | Зачем |
|---|---:|---|---|
| `app/Modules/Vehicles/Features/Import/Domain/Events/AbstractImportCompleted.php:9` | 9 | `AbstractImportCompleted` (базовый абстрактный payload) | Базовый класс результата импорта с `userId` и `cacheKey`. |
| `app/Modules/Vehicles/Features/Import/Domain/Events/Manufacturer/ManufacturerCommandImported.php:10` | 10 | `ManufacturerCommandImported` | Факт завершения импорта производителей. |
| `app/Modules/Vehicles/Features/Import/Domain/Events/Vehicle/VehicleCommandImported.php:10` | 10 | `VehicleCommandImported` | Факт завершения импорта ТС. |
| `app/Modules/Vehicles/Features/Import/Domain/Events/Engine/EngineCommandImported.php:10` | 10 | `EngineCommandImported` | Факт завершения импорта двигателей. |
| `app/Modules/Vehicles/Features/Import/Domain/Events/Modification/ModificationCommandImported.php:10` | 10 | `ModificationCommandImported` | Факт завершения импорта модификаций. |
| `app/Modules/Vehicles/Features/Import/Domain/Events/Vehicle/VehicleImportCompleted.php:9` | 9 | `VehicleImportCompleted` (наследует `AbstractImportCompleted`) | Завершение много-листового импорта ТС + ключ отчёта. |
| `app/Modules/Vehicles/Features/Import/Domain/Events/Engine/EngineImportCompleted.php:9` | 9 | `EngineImportCompleted` (наследует `AbstractImportCompleted`) | Завершение много-листового импорта двигателей + ключ отчёта. |
| `app/Modules/Vehicles/Features/Import/Domain/Events/Engine/EngineCrossImportCompleted.php:9` | 9 | `EngineCrossImportCompleted` (наследует `AbstractImportCompleted`) | Завершение кросс-импорта кодов двигателей в группы + ключ отчёта. |
| `app/Modules/Vehicles/Features/Import/Domain/Events/EnginesAndModificationsReady.php:14` | 14 | `EnginesAndModificationsReady` | Служебный факт: оба импорта (engine + modification) завершены. |

## 2) Где сейчас диспатчатся события

| Класс | Строка | Событие | Что делает |
|---|---:|---|---|
| `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Manufacturer/ManufacturerCommandImport.php:81` | 81 | `ManufacturerCommandImported` | Запускается после импорта производителей (`AfterImport`). |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Vehicle/VehicleCommandImport.php:86` | 86 | `VehicleCommandImported` | Запускается после импорта ТС (`AfterImport`). |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Engine/EngineCommandImport.php:76` | 76 | `EngineCommandImported` | Запускается после импорта двигателей (`AfterImport`). |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Modification/ModificationCommandImport.php:86` | 86 | `ModificationCommandImported` | Запускается после импорта модификаций (`AfterImport`). |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Vehicle/VehicleMultiSheetImport.php:76` | 76 | `event(new VehicleImportCompleted($userId, $cacheKey))` | После импорта всех листов ТС. |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Engine/EngineMultiSheetImport.php:76` | 76 | `event(new EngineImportCompleted($userId, $cacheKey))` | После импорта всех листов двигателей. |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Engine/EngineSparkPlugSpecificationImport.php:117` | 117 | `event(new EngineImportCompleted($userId, $cacheKey))` | После импорта свечей по модификациям (тоже итоговый импорт). |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Engine/EngineCrossImport.php:125` | 125 | `event(new EngineCrossImportCompleted($userId, $cacheKey))` | После импорта кросс-строк группировки двигателей. |

## 3) Где сейчас обрабатываются в listeners (EventServiceProvider)

| Класс | Строка | На каком событии | Что делает |
|---|---:|---|---|
| `app/Modules/Vehicles/Features/Import/Infrastructure/Providers/ImportEventServiceProvider.php:32` | 32 | `ManufacturerCommandImported` | Инициализирует `StartVehicleCommandImportListener` и `StartEngineCommandImportListener`. |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Providers/ImportEventServiceProvider.php:35` | 35 | `VehicleCommandImported` | Инициализирует `StartModificationCommandImportListener`. |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Providers/ImportEventServiceProvider.php:41` | 41 | `EngineCommandImported`, `ModificationCommandImported` (через `subscribe`) | `EngineModificationReadinessSubscriber` собирает обе фазы и выставляет готовность. |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Providers/ImportEventServiceProvider.php:46` | 46 | `EnginesAndModificationsReady` | Инициализирует `StartEngineModificationCommandImportListener`. |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Providers/ImportEventServiceProvider.php:51` | 51 | `VehicleImportCompleted` | Инициализирует `ReportImportResultListener`. |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Providers/ImportEventServiceProvider.php:52` | 52 | `EngineImportCompleted` | Инициализирует `ReportImportResultListener`. |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Providers/ImportEventServiceProvider.php:53` | 53 | `EngineCrossImportCompleted` | Инициализирует `ReportImportResultListener`. |

## 4) Рекомендуемый следующий слой событий (не реализовано)

> Ниже — предложения по местам, где событийность полезна для реакций/интеграций.

| Класс | Предложение по строке | Событие | Зачем |
|---|---:|---|---|
| `app/Modules/Vehicles/Features/Export/Infrastructure/Exports/Vehicle/VehicleMultiSheetExport.php:26` | метод `download()` | `VehicleExportCompleted`, `VehicleExportFailed` | Единый сигнал об успехе/ошибке экспорта ТС для нотификаций и аудита. |
| `app/Modules/Vehicles/Features/Export/Infrastructure/Exports/Engine/EngineMultiSheetExport.php:26` | метод `download()` | `EngineExportCompleted`, `EngineExportFailed` | То же для экспорта двигателей. |
| `app/Modules/Vehicles/Features/Export/Application/Services/VehicleExportService.php:38` | метод `buildExportPlan()` / `getMainRows()` | `VehicleExportRequested` | Отдельный факт запроса на экспорт (ограничения, логирование, очереди). |
| `app/Modules/Vehicles/Features/Export/Application/Services/EngineExportService.php:35` | метод `buildExportPlan()` / `getMainRows()` | `EngineExportRequested` | Отдельный факт запроса на экспорт двигателей. |
| `app/Modules/Vehicles/Features/Import/Application/Services/Engine/AssignEngineGroupService.php:23` | метод `execute()` после `setGroupId()` | `EngineGroupAssigned`, `EngineGroupReassigned` | Для downstream-реакций на изменение связки кода двигателя с группой. |
| `app/Modules/Vehicles/Features/Import/Application/Services/EngineModification/LinkEngineModificationFromRowService.php:27` | метод `execute()` после `syncWithoutDetaching()` | `EngineModificationLinkCreated` | Реакции на установку связи `engine <-> modification` (кэш, индексация, отчёты). |
| `app/Modules/Vehicles/Features/Import/Application/Services/Vehicle/VehicleWiperSpecificationImportService.php:34` | метод `execute()` после батчевого апсерта | `VehicleWiperSpecsUpdated` | Событие факта массового обновления дворников. |
| `app/Modules/Vehicles/Features/Import/Application/Services/Engine/UpsertSparkPlugSpecByModificationService.php:33` | метод `execute()` после цикла по двигателям | `EngineSparkPlugSpecsUpdated` | Факт пакетного обновления свечей по модификации (включая skipped-результат). |
| `app/Modules/Vehicles/Features/Import/Application/Services/Engine/UpsertEngineSparkPlugSpecService.php:31` | метод `execute()` после upsert | `EngineSparkPlugSpecUpdated` | Мелкий факт обновления свечей конкретного двигателя. |
| `app/Modules/Vehicles/Features/Import/Application/Services/Reporting/ReportImportResultService.php:25` | метод `execute()` после `reporter->store()` / catch | `ImportReportGenerated`, `ImportReportFailed` | Убрать `TODO`-развязку и формализовать итог импорта. |
| `app/Modules/Vehicles/Features/Import/Infrastructure/Messaging/Enums/OutboundEventsEnum.php:14-17` | enum | `IMPORT_SUCCEEDED`, `IMPORT_FAILED`, `VEHICLE_UPSERTED`, `ENGINE_UPSERTED` | Интеграционные события для внешних сервисов (например, Filament). |
