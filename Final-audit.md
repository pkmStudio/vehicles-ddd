# Финальный аудит dan-vehicles — план

Пять задач от владельца + мои пометки (часть содержит развилки с нашим `ARCHITECTURE.md` — не исполняю слепо).

## П.1 — Соответствие каждого файла ответственности своего слоя
Пройти все слои, найти классы, делающие не своё. Известные кандидаты:
- **Мёртвые `firstWhere(string $column, mixed $value)`** в `Feature/Modification/PartSpecification` репозиториях — нигде не вызываются (подтверждено) + «дырявый» порт (строка-колонка протекает схему). → **удалить** (из портов и реализаций).
- Presentation-команды `ChangeProviderManufacturersToTD`, `UpdateModificationYears`, `UpdateVehicleYears` — толстые (инлайн-Eloquent + bulk-логика в `handle()`), `@deprecated GroupEnginesCommand`. Из `Otchet.md`. → пометить; чинить отдельно (не входит в текущий заход, если не решим иначе).
- Остальное — выборочная проверка, сейчас слои в целом консистентны.

## П.2 — Domain: Services и Templates → Application/Common? (РАЗВИЛКА)
- **Templates** (`Domain/Templates/*`) — декларативные описания полей = «данные/декларация». **Сильный довод оставить в Domain:** `DetailTemplateEnum::templateClass()` ссылается на классы Template; если Template уедет в Application — доменный enum будет ссылаться на Application (стрелка наружу). → **рекомендую оставить в Domain.**
- **`WiperSpecificationService`** — чистое доменное правило (структура details дворника), без инфраструктуры. Используется и Application (use-case), и Infrastructure (export).
  - Вариант A (DDD-канон): доменный сервис живёт в `Domain/Services`. ✓ как сейчас.
  - Вариант B (трактовка «Domain = только данные/контракты, поведение → Application»): переезд в `Application/Common/Services`.
  - **Требует решения владельца** (вкусовщина, но должна быть консистентной).

## П.3 — «Интерфейс у каждого класса, нет → добавить в Domain» (РАЗВИЛКА — противоречит нашему правилу)
Наш `ARCHITECTURE.md` явно: **порт нужен только driven-адаптерам** (Repository/Command/Import/Export/Notification — то, что use-case зовёт наружу), и «не плодить интерфейсы на каждый use-case». Слепое «интерфейс каждому» = ~40 бессмысленных интерфейсов у UseCases/Factories/Listeners/Services/Support/DTO — прямое нарушение.
- **Уже за портами:** Repositories, Commands, Imports, Exports, Notifications. ✓
- **НЕ интерфейсим** (по правилу): UseCases, Factories, Listeners/Subscriber, Application Services, Domain Services, Support-хелперы, DTO/ModelData/Events/Templates.
- **Единственный реальный пробел** — `RabbitMQPublisher` (Infrastructure/Messaging): публикация в внешний брокер RabbitMQ без порта, используется `RabbitMqFileNotificationService`. По принципу «всё внешнее за портом» + упомянутый в доке будущий `Contracts/Messaging/`. → **рекомендую добавить порт `MessagePublisherInterface`** в `Domain/Contracts/Messaging/`, биндинг в провайдере.
- **Вывод:** не добавляем интерфейсы всем; добавляем только `MessagePublisherInterface`. Требует подтверждения трактовки.

## П.4 — `IMPORT_EXPORT_BINDINGS` → `Interface::class => Impl::class`
Сейчас массив строк с двойными бэкслешами (грабли при sed, не ловится анализатором). → **заменить на FQCN `::class`** (рефактор-safe). Однозначно, делаю.

## П.5 — Репозитории возвращают только модель/коллекцию
Проверено. Возвращают `?Entity`/`Entity`/`Collection`, **кроме** скалярных агрегатов:
- `VehicleRepository::minMsId(): int`, `ManufacturerRepository::minMfaId(): int` — используются в `UpsertVehicleFromSheetUseCase` (генерация синтетических отрицательных id).
- **Вердикт:** это легитимные read-агрегаты (как `count()`/`exists()`); репозиторий-порт не обязан возвращать только сущности. **Оставляем**, рефакторинг не оправдан. (Зафиксировать как осознанное.)

---

## Порядок исполнения
1. Развилки П.2 и П.3 — уточнить у владельца (один вопрос).
2. П.4 — заменить биндинги на `::class`.
3. П.1 — удалить мёртвые `firstWhere` (+ из портов).
4. П.3 — добавить `MessagePublisherInterface` (если подтверждено).
5. П.2 — перенести `WiperSpecificationService` (если выбран вариант B); Templates оставить.
6. Прогон: lint, autoload, контейнер, pint, тесты.
