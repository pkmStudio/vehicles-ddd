# plan-new.md — что сделано и что осталось

> Заменяет предыдущий `plan-new.md` (удалён владельцем). Это не история, а текущий срез:
> что сделано, что отложено, что в работе.


---

## 0. Сделано

- **Реструктуризация `app/` в `app/Modules`** выполнена 2026-07-18:
  `Vehicles` и `Warehouse` перенесены в `app/Modules/{Vehicles,Warehouse}/Features/*` +
  module-level `Shared/`, `Templates` перенесён в `app/Modules/Templates` без дополнительного
  `Features/`.

---

## 1. Осознанно отложено (не трогать без явного запроса)

- **`larastan`/`phpstan` + CI** (`.github/workflows`) — дешёвая, полезная задача (ловит баги
  класса «необъявленное свойство», как было в старом `plan.md` §6.1), но пока отложена.
- **Схема БД**: `timestamps()`, индексы на FK, уникальный индекс `engine_modification` — не
  добавляем, пока не понятна реальная нагрузка.

---

## 2. Открыто, не начато

- **Доменные события для MpCard-инвалидации** — CRM dan-center (карточки MpCard) зависит не только
  от Warehouse (номенклатура/наборы), но и от Vehicles (машины/модификации): изменение любой из
  сторон должно инвалидировать связанные карточки. Сейчас ни одна из сторон не диспатчит событие на
  каждую изменённую запись — Warehouse `Import` шлёт только `*ImportCompleted` на весь прогон
  (см. `plan-warehouse.md`), а у Vehicles такого события нет вообще. Нужно спроектировать как единую
  кросс-доменную задачу (`NomenclatureUpdated`/`KitUpdated` со стороны Warehouse + аналог со стороны
  Vehicles), а не решать по частям в каждом домене отдельно. Не блокер — сама MpCard-интеграция/
  транспорт в CRM ещё не подключены (см. §1 выше).
- **Typed DTO для Warehouse MoySklad client** — сейчас
  `Warehouse/Features/MoySklad/Domain/Contracts/Clients/MoySkladProductClientInterface` работает с
  сырыми `array<string, mixed>` payload'ами МойСклад, а `NomenclatureSyncService` знает про поля
  внешнего API (`id`, `externalCode`, `meta.href`). Нужно ввести DTO на границе клиента
  (`MoySkladProductData`, `MoySkladProductFolderData` или аналог) и перенести парсинг/сравнение
  внешнего payload в infrastructure client. Отложено, чтобы не смешивать с текущим выравниванием
  Cache/Storage/Repository/Command.

---
