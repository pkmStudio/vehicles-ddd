<div style="font-size: 22px; line-height: 1.55;">

# <span style="color:#0f766e;">Проверка Импортов</span>

<span style="color:#334155;">Файл нужен как чеклист полного прохода по импортам. Проверяем не по классам вразнобой, а по реальным flow:</span>

<span style="color:#7c3aed;">entrypoint → infrastructure adapter/handler → mapper/validator → row/request DTO → application service/use case → factory → Data → repository/command → event/notification</span>

## <span style="color:#2563eb;">1. Vehicles: TecDoc Command Cascade</span>

<span style="color:#475569;">Локальный каскад из `storage/vehicles/*.csv`, стартует с `TecDocImportCars`.</span>

1. <span style="color:#dc2626;">ManufacturerCommandImport</span>  
   <span style="color:#475569;">Файл: `storage/vehicles/manufacturers.csv`</span>

2. <span style="color:#dc2626;">VehicleCommandImport</span>  
   <span style="color:#475569;">Файл: `storage/vehicles/vehicles.csv`</span>  
   <span style="color:#9333ea;">Стартует после `ManufacturerCommandImported`.</span>

3. <span style="color:#dc2626;">EngineCommandImport</span>  
   <span style="color:#475569;">Файл: `storage/vehicles/engines.csv`</span>  
   <span style="color:#9333ea;">Стартует после `ManufacturerCommandImported`.</span>

4. <span style="color:#dc2626;">ModificationCommandImport</span>  
   <span style="color:#475569;">Файл: `storage/vehicles/modifications.csv`</span>  
   <span style="color:#9333ea;">Стартует после `VehicleCommandImported`.</span>

5. <span style="color:#dc2626;">EngineModificationImport</span>  
   <span style="color:#475569;">Файл: `storage/vehicles/engine_modification.csv`</span>  
   <span style="color:#9333ea;">Стартует после готовности и engines, и modifications.</span>

6. <span style="color:#dc2626;">EnginesCodeImport</span>  
   <span style="color:#475569;">Отдельный command import port, не основной шаг TecDoc cascade.</span>

## <span style="color:#2563eb;">2. Vehicles: External/Rabbit File Imports</span>

<span style="color:#475569;">Запускаются через `ImportFileRequestedHandler → StartExternalFileImportUseCase → ExternalFileImportFactory`.</span>

1. <span style="color:#16a34a;">vehicle_multi_sheet</span> → <span style="color:#dc2626;">VehicleMultiSheetImport</span>  
   <span style="color:#475569;">Внутренние sheets: `VehicleMainSheetImport`, `VehicleWipersSheetImport`</span>

2. <span style="color:#16a34a;">engine_multi_sheet</span> → <span style="color:#dc2626;">EngineMultiSheetImport</span>  
   <span style="color:#475569;">Внутренние sheets: `EngineMainSheetImport`, `EngineSparkPlugsSheetImport`</span>

3. <span style="color:#16a34a;">engine_cross</span> → <span style="color:#dc2626;">EngineCrossImport</span>  
   <span style="color:#475569;">Проверить отдельно: группы/связки двигателей.</span>

4. <span style="color:#16a34a;">engine_spark_plugs_by_modification</span> → <span style="color:#dc2626;">EngineSparkPlugSpecificationImport</span>

5. <span style="color:#16a34a;">manufacturer</span> → <span style="color:#dc2626;">ManufacturerImport</span>

6. <span style="color:#16a34a;">modification_catalog</span> → <span style="color:#dc2626;">ModificationCatalogImport</span>

7. <span style="color:#16a34a;">engine_modifications</span> → <span style="color:#dc2626;">EngineModificationsImport</span>

## <span style="color:#2563eb;">3. Warehouse Imports</span>

<span style="color:#475569;">Запускаются через warehouse `ImportFileRequestedHandler → StartExternalFileImportUseCase → ImportFileFactory`.</span>

1. <span style="color:#16a34a;">nomenclature</span> → <span style="color:#dc2626;">NomenclatureImport</span>

2. <span style="color:#16a34a;">pack_dimension</span> → <span style="color:#dc2626;">PackDimensionImport</span>

3. <span style="color:#16a34a;">kit</span> → <span style="color:#dc2626;">KitImport</span>

<span style="color:#475569;">Для этих типов также есть console request/import команды.</span>

## <span style="color:#2563eb;">4. Applicability Imports</span>

1. <span style="color:#16a34a;">kit_applicability</span> → <span style="color:#dc2626;">KitApplicabilityImport</span>

## <span style="color:#ea580c;">Рекомендуемый Порядок Проверки</span>

### <span style="color:#7c2d12;">A. Сначала Vehicles TecDoc cascade</span>

1. <span style="color:#0f766e;">ManufacturerCommandImport</span>
2. <span style="color:#0f766e;">VehicleCommandImport</span>
3. <span style="color:#0f766e;">EngineCommandImport</span>
4. <span style="color:#0f766e;">ModificationCommandImport</span>
5. <span style="color:#0f766e;">EngineModificationImport</span>

### <span style="color:#7c2d12;">B. Потом Vehicles Rabbit imports</span>

1. <span style="color:#0f766e;">manufacturer</span>
2. <span style="color:#0f766e;">vehicle_multi_sheet</span>
3. <span style="color:#0f766e;">engine_multi_sheet</span>
4. <span style="color:#0f766e;">modification_catalog</span>
5. <span style="color:#0f766e;">engine_modifications</span>
6. <span style="color:#0f766e;">engine_cross</span>
7. <span style="color:#0f766e;">engine_spark_plugs_by_modification</span>

### <span style="color:#7c2d12;">C. Потом Warehouse</span>

1. <span style="color:#0f766e;">nomenclature</span>
2. <span style="color:#0f766e;">pack_dimension</span>
3. <span style="color:#0f766e;">kit</span>

### <span style="color:#7c2d12;">D. Последним Applicability</span>

1. <span style="color:#0f766e;">kit_applicability</span>

## <span style="color:#ea580c;">Единый Чеклист Для Каждого Импорта</span>

- <span style="color:#334155;">raw `array $row` не выходит из `Infrastructure/Imports/*/Mappers`;</span>
- <span style="color:#334155;">mapper читает реальные колонки файла и не придумывает обязательность без проверки файла/миграции;</span>
- <span style="color:#334155;">mapper возвращает typed row DTO;</span>
- <span style="color:#334155;">factory имеет единый `make(<RowDTO> $row)`, без `makeFromCommandRow` / `makeFromSheetRow`;</span>
- <span style="color:#334155;">factory валидирует DTO и возвращает `<Entity>Data`;</span>
- <span style="color:#334155;">`Data` не подставляет default для обязательных бизнес-полей;</span>
- <span style="color:#334155;">`provider` / source / operation id задаются явно на boundary, где источник известен;</span>
- <span style="color:#334155;">service/use case не знает формат Excel-файла и не парсит raw rows;</span>
- <span style="color:#334155;">repository возвращает `Data`, `Collection<Data>` или `Generator<Data>`, не Eloquent/raw arrays;</span>
- <span style="color:#334155;">command принимает `Data` и инкапсулирует запись;</span>
- <span style="color:#334155;">completed event / notification несет DTO или scalar fields, не случайный raw snapshot;</span>
- <span style="color:#334155;">архитектурные файлы соответствуют фактическому flow.</span>

</div>
