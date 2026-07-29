# Генерация enum из справочников

## Цель

Сделать управляемый механизм для справочников вроде категорий товаров:

- хранить справочные значения в БД;
- генерировать актуальные PHP enum до деплоя;
- использовать enum в доменной логике;
- не смешивать справочник с функциональными связями вроде шаблонов деталей, упаковки и импорта.

## Базовое решение

Для категорий товаров завести явную справочную таблицу вместо абстрактного `types`.

Например:

```text
list_product_categories
id
code
name
is_active
sort
created_at
updated_at
```

Поля:

- `id` - стабильный идентификатор для FK.
- `code` - стабильный короткий код категории, например `BP`, `SP`, `WB`.
- `name` - человекочитаемое русское название.
- `is_active` - доступность значения для пользовательских сценариев.
- `sort` - порядок отображения в UI/Excel-справочниках.

## Источник данных

Чтобы генерация была воспроизводимой, источник справочника должен лежать в репозитории, а не браться из случайной локальной БД.

Например:

```text
database/dictionaries/product_categories.php
```

Формат:

```php
<?php

return [
    [
        'id' => 1,
        'code' => 'BP',
        'enum' => 'BRAKE_PADS',
        'name' => 'Колодки тормозные',
        'is_active' => true,
        'sort' => 10,
    ],
    [
        'id' => 2,
        'code' => 'SP',
        'enum' => 'SPARK_PLUGS',
        'name' => 'Свечи зажигания',
        'is_active' => true,
        'sort' => 20,
    ],
];
```

Этот файл используется и для генерации enum, и для синхронизации БД.

## Генерируемый enum

Enum хранит только идентичность справочного значения и простые атрибуты.

```php
enum ProductCategoryEnum: int
{
    case BRAKE_PADS = 1;
    case SPARK_PLUGS = 2;

    public function code(): string
    {
        return match ($this) {
            self::BRAKE_PADS => 'BP',
            self::SPARK_PLUGS => 'SP',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BRAKE_PADS => 'Колодки тормозные',
            self::SPARK_PLUGS => 'Свечи зажигания',
        };
    }
}
```

Важно: enum не должен знать про шаблоны деталей, упаковку, импорт, экспорт или применяемость.

## Команды

### Генерация enum

```bash
php artisan dictionaries:generate-enums
```

Команда:

- читает файлы из `database/dictionaries`;
- генерирует PHP enum;
- форматирует файл по стандартам проекта.

### Проверка актуальности enum

```bash
php artisan dictionaries:generate-enums --check
```

Команда:

- генерирует enum во временную строку/файл;
- сравнивает результат с текущим файлом в репозитории;
- падает с ошибкой, если есть diff.

Эту проверку нужно запускать в CI, чтобы сгенерированные файлы не забывали коммитить.

### Синхронизация БД

```bash
php artisan dictionaries:sync
```

Команда:

- читает тот же источник из `database/dictionaries`;
- делает upsert в `list_*` таблицы;
- не удаляет отсутствующие значения автоматически без отдельного явного режима;
- обновляет `code`, `name`, `is_active`, `sort`.

## Функциональные связи

Связь категории с конкретным поведением выносится в отдельные resolver/factory-классы.

Например:

```php
final readonly class ProductCategoryTemplateResolver
{
    public function resolve(ProductCategoryEnum $category): NomenclatureDetailTemplateEnum
    {
        return match ($category) {
            ProductCategoryEnum::BRAKE_PADS => NomenclatureDetailTemplateEnum::BRAKE_PADS,
            ProductCategoryEnum::SPARK_PLUGS => NomenclatureDetailTemplateEnum::SPARK_PLUGS,
            default => throw UnsupportedProductCategoryException::forTemplate($category),
        };
    }
}
```

Возможные resolver’ы:

- `ProductCategoryTemplateResolver` - какой `DetailsData`/template использовать.
- `ProductCategoryPackagingResolver` - поддерживается ли расчет упаковки.
- `ProductCategoryKitCompositionResolver` - можно ли использовать категорию в наборах.
- `ProductCategoryExportResolver` - поддерживается ли экспорт с деталями.

Если категория есть в справочнике, но фича ее не поддерживает, выбрасывается доменная ошибка.

Например:

```text
Для категории "Амортизаторы" пока не поддержан импорт номенклатуры.
```

## Изменения в текущем коде

1. Переименовать/заменить `types` на явный справочник:
   `list_product_categories` или `list_nomenclature_types`.

2. Оставить FK `type_id` в:
   - `nomenclatures`;
   - `kits`;
   - `pack_dimensions`.

3. Заменить текущий `TypeTemplateResolver`.

   Сейчас он резолвит шаблон по:

   - `char`;
   - историческому `id`;
   - `name`.

   После перехода:

   - из `type_id` получаем `ProductCategoryEnum`;
   - через `ProductCategoryTemplateResolver` получаем `NomenclatureDetailTemplateEnum`;
   - fallback по историческим `id/name` убрать после стабилизации данных.

4. Переделать импорт упаковок.

   Сейчас упаковки принимают числовой `type_id`.
   Нужно заменить на пользовательский ввод:

   - `Тип товара`;
   - или `Код типа`.

   Внутри импорта резолвить это значение в `type_id`.

5. В экспортах добавлять справочный лист там, где он нужен.

   Для категорий товаров справочный лист должен показывать:

   - `id`;
   - `code`;
   - `name`.

## Правила использования

- `id` можно использовать как enum value только если ID стабильны и одинаковы во всех окружениях.
- `code` должен оставаться стабильным внешним идентификатором для Excel, RabbitMQ, логов и интеграций.
- Новая запись в справочнике не означает автоматическую поддержку во всех фичах.
- Каждая фича явно описывает поддержанные категории через свой resolver/factory.
- Неподдержанная категория должна давать доменную ошибку, которая попадает в отчет импорта.

## Тесты

Минимальный набор:

- enum генерируется из dictionary-файла;
- `--check` падает при рассинхроне;
- `dictionaries:sync` делает upsert;
- импорт номенклатуры резолвит категорию по имени/коду;
- импорт упаковок больше не требует числовой `type_id`;
- неподдержанная категория дает доменную ошибку;
- доменная ошибка попадает в отчет импорта.

## Порядок внедрения

1. Добавить dictionary-файл для категорий товаров.
2. Добавить генератор enum и check-режим.
3. Добавить sync-команду для БД.
4. Сгенерировать `ProductCategoryEnum`.
5. Добавить resolver’ы для функциональных связей.
6. Перевести `TypeTemplateResolver` на новый enum/resolver.
7. Переделать импорт упаковок с `type_id` на `Тип товара`/`Код типа`.
8. Обновить экспортные справочники.
9. Добавить тесты.
