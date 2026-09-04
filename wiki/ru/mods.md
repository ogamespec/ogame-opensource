# Руководство разработчика модификаций

> **Статус документа:** актуальное руководство по подсистеме модификаций
> (issue [#280](https://github.com/ogamespec/ogame-opensource/issues/280)).
> Раньше эта страница была черновиком ("Драфт по модификациям") — теперь это
> полный мануал. Одноимённый HTML-мануал со стилями скина Evolution лежит в
> `docs/` репозитория: [mod-manual-ru.html](../../docs/mod-manual-ru.html).

Модификации (моды) — это способ расширять и менять игру, **не трогая исходный
код базового движка**. Всё, что нужно знать модификации, собрано в этом
руководстве:

- как устроена подсистема модов и как мод подключается к игре;
- полный справочник хуков (все точки, где мод может вмешаться в игру);
- справочник функций-диспетчеров (`ModsExec*`);
- примеры из реальных модов репозитория: `BogusMod`, `GalaxyTool`,
  `SpaceStorm`, `DeepSpaceHorror`.

Исходники подсистемы: `game/core/mods.php`, админка модов:
`game/pages_admin/admin_mods.php`.

---

## 1. Введение

### 1.1. Что такое мод

Мод — это **самодостаточная папка** внутри `game/mods/`, которая содержит весь
код и ресурсы, нужные модификации:

```
game/mods/
├── BogusMod/          # демо-мод: новый ресурс, пункт меню, своя страница
├── GalaxyTool/        # инструмент с игровой и админ-страницей
├── SpaceStorm/        # «Космический шторм»: новая механика + новое здание
├── DeepSpaceHorror/   # «Глубокий космос»: монстры, кастомные объекты, миссии
└── Wanderer/          # «Странствующий Торговец»: режим игрока, станция, торговля
```

Мод объявляет класс-наследник абстрактного класса `GameMod` (файл
`game/core/mods.php`). Ядро само находит мод по имени папки, загружает его и
вызывает его методы в нужные моменты. Базовый движок при этом не меняется —
вместо этого в нём расставлены вызовы **хуков** (см. раздел 8).

В репозитории уже есть пять модов, которые служат эталонными примерами и
покрывают почти все возможности системы. Их код — лучшая «живая»
документация:

| Мод | Чему учит |
|---|---|
| `BogusMod` | Минимальный мод: колонка в БД, свой ресурс, пункт меню, страница, периодическое событие |
| `GalaxyTool` | Мод-инструмент: своя игровая страница и свой раздел админки, 6 языков локали |
| `SpaceStorm` | Новое здание, правки глобальных таблиц движка, боевые/экономические хуки, тесты |
| `DeepSpaceHorror` | Кастомные объекты галактики, новые юниты, кастомные миссии флота, хуки изображений, тесты |
| `Wanderer` | Целый второй игровой режим: переключение режима игрока, кастомный объект галактики, свои страницы/экономика/прыжки/заказы, три новых хука движка, тесты |

### 1.2. Как мод попадает в игру

1. Мод кладётся папкой в `game/mods/<ИмяМода>/`. Админка «находит» его по
   наличию `main.php` и `manifest.json`.
2. Администратор **устанавливает** мод в админке (раздел *Моды*,
   `index.php?page=admin&mode=Mods`). При установке:
   - имя мода дописывается в колонку `modlist` таблицы `uni` (список имён через
     `;`, слева направо — порядок активации);
   - вызывается метод `install()` мода (обычно: создаёт колонки/таблицы БД и
     ставит свои события в очередь);
3. При **каждом** запросе к игре (и в `cron.php`) ядро вызывает `ModsInit()`,
   который для каждого имени из `modlist` загружает `main.php`, создаёт экземпляр
   класса и вызывает `init()` мода.
4. Во время работы движок вызывает хуки модов (см. раздел 8). Хуки срабатывают
   **в порядке активации** модов; первый мод, вернувший из хука `true`,
   останавливает цепочку.
5. При **удалении** мода (кнопка в админке) вызывается `uninstall()` — мод
   обязан убрать свои колонки/таблицы и события.

Подробнее о функциях управления (`ModsInstall`, `ModsRemove`, `ModsMoveUp` …) —
раздел 12.

---

## 2. Структура папки мода

Обязательные элементы:

| Путь | Назначение |
|---|---|
| `main.php` | Главный модуль мода. Определяет константы мода и класс `<ИмяМода> extends GameMod` |
| `manifest.json` | Метаданные для админки (название, версия, автор, описание, сайт) |

Рекомендуемые / часто используемые:

| Путь | Назначение |
|---|---|
| `Readme.md` | Человекочитаемое описание мода, инструкция, список изменений (см. `DeepSpaceHorror/Readme.md`) |
| `img/bg.png` | Картинка-подложка (600×200) для карточки мода в админке |
| `img/…` | Картинки мода (иконки ресурсов, объектов, кнопок) |
| `loca/<lang>_<lang>/<section>.php` | Файлы локализации мода (см. раздел 6) |
| `pages/` | PHP-файлы игровых страниц мода |
| `pages_admin/` | PHP-файлы страниц админки мода |
| `testing/` | Собственный PHPUnit-набор тестов мода (см. раздел 10) |

> ВАЖНО: папки с исходным кодом (`pages/`, `pages_admin/`, `loca/`) должны быть
> закрыты от прямого доступа из браузера — внутрь каждой кладётся `.htaccess`
> с содержимым:
>
> ```apache
> Order allow,deny
> Deny from all
> ```
>
> Папка `img/` (и прочие папки со статикой) остаются открытыми — на них
> ссылаются страницы игры. Пример см. в любом моде репозитория.

## 3. manifest.json

Файл `manifest.json` лежит в корне папки мода и содержит его метаданные. По нему
админка строит карточку мода (функция `ModsGetInfo`, см. раздел 12):

```json
{
  "name": "My Awesome Mod",
  "version": "1.0.0",
  "author": "YourName",
  "description": "Добавляет новые возможности для игроков",
  "website": "https://github.com/yourname/mod-name"
}
```

| Поле | Назначение |
|---|---|
| `name` | Название мода (заголовок карточки в админке) |
| `version` | Версия мода |
| `author` | Автор |
| `description` | Краткое описание возможностей |
| `website` | Ссылка на сайт/репозиторий мода |

## 4. main.php и класс GameMod

### 4.1. Минимальный скелет

```php
<?php

class MyAwesomeMod extends GameMod {

    // Установка мода (вызывается один раз при активации в админке).
    public function install() : void {
    }

    // Удаление мода (вызывается один раз при деактивации).
    public function uninstall() : void {
    }

    // Инициализация (вызывается при каждом запросе, когда мод активен).
    public function init() : void {
    }

    // ... любые хуки из класса GameMod, которые нужны моду ...
}
```

> Имя класса должно совпадать с именем папки мода (регистр первой буквы —
> верхний): папка `MyAwesomeMod` ⇒ класс `MyAwesomeMod`. Именно так его ищет
> `ModInitOne()`.

### 4.2. Три обязательных метода жизненного цикла

| Метод | Когда вызывается | Что обычно делает |
|---|---|---|
| `install()` | один раз, при активации мода (админка) | `ALTER TABLE` — добавляет свои колонки/таблицы; заводит свои события в очереди; рассылает анонс игрокам |
| `uninstall()` | один раз, при деактивации мода | убирает свои колонки/таблицы, удаляет свои события из очереди, чистит за собой |
| `init()` | при каждом запросе, пока мод активен | грузит локализацию мода (`loca_add`), дополняет глобальные таблицы движка (`$buildmap`, `$initial`, `$UnitParam` …), регистрирует обработчики |

`install()` и `uninstall()` исполняются в контексте админки. Работать с БД в них
нужно так же, как и в любом другом коде игры: через `dbquery()` и друзей
(раздел 8.10), а изменение структуры таблиц — под
`LockTables()`/`UnlockTables()`, как в примерах ниже.

**Пример `install()` / `uninstall()` из `BogusMod`** — добавляет колонку
`tritium` в таблицу `users` и заводит периодическое событие:

```php
public function install() : void {
    global $db_prefix;

    LockTables();

    // Колонка для запасов трития
    $query = "ALTER TABLE ".$db_prefix."users ADD COLUMN tritium INT DEFAULT 0;";
    dbquery ($query);

    // Событие «начислить тритий» — раз в час
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_ADD_TRITIUM."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) {
        AddQueue (USER_SPACE, QTYP_ADD_TRITIUM, 0, 0, 0, time(), BOGUS_MOD_TRITIUM_CREDIT_PERIOD_SECONDS);
    }

    UnlockTables();
    Debug ("BogusMod install success.");
}

public function uninstall() : void {
    global $db_prefix;

    LockTables();

    $query = "ALTER TABLE ".$db_prefix."users DROP COLUMN tritium;";
    dbquery ($query);

    $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_ADD_TRITIUM."'";
    dbquery ($query);

    UnlockTables();
    Debug ("BogusMod uninstall success.");
}
```

Правила, которые стоит соблюдать:

- операции изменения схемы — идемпотентные и повторяемые (админ может
  переустановить мод);
- при активации проверяйте, не заведено ли уже ваше событие в `queue` (пример
  выше), иначе при повторной установке появятся дубли;
- **не удаляйте чужие данные**: `uninstall()` должен убирать только то, что
  создал сам мод;
- после деактивации мода ядро «лечит» список: если для имени из `modlist` нет
  `manifest.json`, мод автоматически удаляется из списка (см. `admin_mods.php`).

### 4.3. init() и глобальные таблицы движка

Моду доступны все глобальные переменные и функции ядра (`$GlobalUser`,
`$GlobalUni`, `$db_prefix`, ...). Самое важное — в `init()` мод может **дополнить
глобальные таблицы**, на которых построена игра. Эти таблицы объявляются в
`game/core/techs.php` и `game/core/prod.php`:

| Глобальная переменная | Что содержит | Как мод её меняет |
|---|---|---|
| `$buildmap` | список ID построек | `$buildmap[] = GID;` — добавить постройку |
| `$resmap` | список ID исследований | добавить исследование |
| `$fleetmap` | список ID кораблей | добавить корабль |
| `$defmap` | список ID обороны | добавить оборону |
| `$rakmap` | список ID ракет (оборона, строящаяся в ракетной шахте) | добавить ракету |
| `$resourcemap` | все ID ресурсов | добавить ресурс |
| `$initial` | таблица начальной стоимости объектов (`metal`, `crystal`, `deuterium`, `energy`, `factor`) | `$initial[GID] = [GID_RC_METAL=>.., GID_RC_CRYSTAL=>.., ..., 'factor'=>N];` |
| `$UnitParam` | параметры юнитов: `[0]` структура, `[1]` щит, `[2]` атака, `[3]` груз, `[4]` скорость, `[5]` расход | `$UnitParam[GID] = [структура, щит, атака, груз, скорость, расход];` |
| `$RapidFire` | таблица скорострела: `$RapidFire[$gid][$target] = кол-во` | добавить/изменить значения |
| `$requirements` | дерево требований объектов | `$requirements[GID] = [GID_B_RES_LAB=>3, ...];` |
| `$CanBuildTab` | какие постройки/юниты видны на странице постройки для типа объекта галактики | `$CanBuildTab[PTYP_PLANET][] = GID;` |
| `$PlanetProd` | правила производства/потребления по объектам (`prod`/`cons` замыкания на ресурс) | добавить правило производства нового ресурса |
| `$prodPriority` / `$resourcesWithNonZeroDerivative` | порядок и список «производственных» ресурсов | расширить, если добавлен производственный ресурс |

Пример из `SpaceStorm` (`init()`) — добавление **нового здания** в игру:

```php
public function init() : void {
    global $buildmap, $initial, $requirements, $CanBuildTab;

    // Добавить новое здание «Стабилизатор реальности»
    $buildmap[] = GID_B_REALITY_STAB;                                  // 57384
    $initial[GID_B_REALITY_STAB] = array (
        GID_RC_METAL    => 50000,
        GID_RC_CRYSTAL  => 125000,
        GID_RC_DEUTERIUM=> 50000,
        GID_RC_ENERGY   => 0,
        'factor'        => 3,
    );
    $requirements[GID_B_REALITY_STAB] = array (GID_B_RES_LAB => 3, GID_B_TERRAFORMER => 1);
    $CanBuildTab[PTYP_PLANET][] = GID_B_REALITY_STAB;

    global $GlobalUser;
    loca_add ("space_storm", $GlobalUser['lang'], __DIR__);
}
```

Пример из `DeepSpaceHorror` (`init()`) — добавление **новых кораблей**:

```php
public function init() : void {
    global $fleetmap, $UnitParam, $RapidFire, $requirements;

    $fleetmap[] = GID_LEVI_AMOEBA;                     // новый юнит
    $UnitParam[GID_LEVI_AMOEBA] = array (250000000, 10000, 5000, 0, 100, 0);
    $RapidFire[GID_LEVI_AMOEBA] = array (GID_F_SC => 1000, GID_F_LF => 500, ...);
    $requirements[GID_LEVI_AMOEBA] = [];
    // ... аналогично для остальных юнитов ...
}
```

Шаги «добавить в игру новое здание» целиком (по мотивам `SpaceStorm`):

1. В `install()`: `ALTER TABLE planets ADD COLUMN \`<GID>\` INT DEFAULT 0;`
   (уровень здания хранится в колонке планеты с именем GID).
2. В `install_tabs_included()`: прописать ту же колонку в схему (раздел 8.10),
   чтобы проверки/сериализация БД не ругались.
3. В `init()`: `$buildmap[]`, `$initial[]`, `$requirements[]`, `$CanBuildTab[]`.
4. В `init()`: `loca_add()` и локализованные имена `NAME_<GID>` / описания
   `LONG_<GID>` (раздел 6).
5. `get_object_image()` — вернуть картинку здания (раздел 8.5).
6. При необходимости — хуки `can_build`, `build_end`, `page_infos`,
   `page_buildings_get_bonus` и т.п.

Новые **ресурсы** (как `tritium` в `BogusMod`) добавляются чуть иначе — это не
колонки планет с производством, а счётчики (например, на аккаунте) плюс пункт в
панели ресурсов через хук `add_resources` (раздел 8.2).

---

## 5. Страницы мода

### 5.1. Роутер игры

Роутер игры — это JSON-файл `game/router.json`. При каждом запросе
`game/index.php` загружает его и даёт модам добавить свои страницы:

```php
$router = LoadJsonFirst ("router.json");
ModsExecRef ('route', $router);
```

Запись роутера (для страницы мода) может содержать ключи:

| Ключ | Назначение |
|---|---|
| `path` | путь к PHP-файлу страницы относительно `game/` (обязателен) |
| `loca` | список секций локали, которые нужно загрузить для страницы (обязателен) |
| `external` | `true` — страницу могут смотреть гости без сессии (как `ainfo`) |
| `menu` | `false` — не рисовать левое меню |
| `header` | `false` — не рисовать верхнюю панель (ресурсы и планеты) |
| `bare` | `true` — вообще без обрамления `PageHeader`/`EndContent` |
| `mvc` | `true` — страница это класс `Page` (controller/view), а не include-файл |
| `admin_update_queue` | `false` — для админов не обновлять очередь (как `overview`) |
| `update_activity` | `false` — не обновлять активность игрока |
| `redirect_page`, `redirect_sec` | авто-редирект на другую страницу через N секунд |

Если `menu`/`header` не указаны — для запроса с сессией они считаются `true`, то
есть страница получает стандартное обрамление игры (левое меню + панель
ресурсов), которое строится в `PageHeader`.

### 5.2. Регистрация своей страницы (хук `route`)

Пример из `BogusMod` — страница «Совет дня»:

```php
public function route(array &$router) : bool {
    $router['tipoftheday'] = array (
        'path' => "mods/BogusMod/pages/tipoftheday.php",
        'loca' => [ "menu" ]
    );
    return false;   // не останавливаем цепочку — другие моды тоже могут добавить страницы
}
```

После этого страница доступна по адресу `index.php?page=tipoftheday&session=...`,
а пункт меню на неё добавляется хуком `add_menuitems` (раздел 8.2).

### 5.3. Классический (include) файл страницы

Файл страницы просто включается движком (`include $router[$pk]['path'];`). Ему
доступны глобальные переменные: `$now`, `$aktplanet`, `$session`, `$PageMessage`,
`$PageError`, `$GlobalUser`, `$GlobalUni`, а также любые другие глобалы ядра.
Вывод идёт прямо в поток (echo/HTML). Пример — весь файл страницы BogusMod:

```php
<?=loca("BOGUS_MOD_TIP1");?>
```

Страница может свободно работать с БД, отправлять сообщения и т.п., как обычная
страница ядра.

### 5.4. MVC-страницы (класс Page)

Современные страницы ядра — это классы, наследующие абстрактный `Page`
(`game/core/page.php`):

```php
abstract class Page {
    public function controller () : bool { return true; }   // логика запроса
    public function view () : void { }                      // вывод
}
```

Запись роутера для MVC-страницы помечается `"mvc": true`, а имя класса —
`ucfirst($pk)` (имя страницы с заглавной буквы). Мод может сделать свою
MVC-страницу точно так же: положить класс в свой файл и прописать путь в роутер.

### 5.5. Страницы админки (хук `route_admin`)

Админка (`index.php?page=admin&mode=<Раздел>`) имеет собственный роутер
`game/pages_admin/admin_router.json`. Мод добавляет свои разделы хуком:

```php
public function route_admin(array &$router) : bool {
    $router['MyTool'] = array (
        'path' => "mods/MyAwesomeMod/pages_admin/admin_mytool.php",
        'img'  => "mods/MyAwesomeMod/img/tool.png",   // иконка в меню админки
        'loca' => "ADM_MENU_MYTOOL",
    );
    return false;
}
```

Класс в файле админ-страницы должен называться `Admin_<Режим>`, где режим —
ключ в роутере (`Admin_MyTool`), и наследовать `Page`. См. `GalaxyTool`:
`pages_admin/admin_galaxytool.php` + `route_admin` в `main.php`.

> Учтите: доступ к админке есть только у операторов/админов, а страницы режимов
> с `"panel" => false` (как `Home`) не показывают меню админки.

---

## 6. Локализация мода

Ядро загружает локализацию секциями через `loca_add($section, $lang, $dir)`.
Секция — это файл `loca/<lang>_<lang>/<section>.php` относительно `$dir` (по
умолчанию — корень `game/`). Для мода третьим параметром передаётся `__DIR__`,
поэтому файлы лежат в папке мода:

```php
public function init() : void {
    global $GlobalUni;
    loca_add ("bogusmod", $GlobalUni['lang'], __DIR__);
}
```

…подхватит файл `game/mods/BogusMod/loca/ru_ru/bogusmod.php` (для языка `ru`).

Формат файла локали — тот же, что у ядра (`game/loca/ru_ru/…`):

```php
<?php

$LOCA["ru"]["BOGUS_MOD_TRITIUM"]    = "Тритий";
$LOCA["ru"]["BOGUS_MOD_MENU_ITEM"]  = "Совет дня";
$LOCA["ru"]["BOGUS_MOD_TIP1"]       = "Мойте руки перед едой.";
```

Получение строки — функции `loca($key)` (текущий язык) и
`loca_lang($key, $lang)` (для конкретного языка, например, при отправке
сообщений игрокам с другим языком).

Соглашения по ключам:

- `NAME_<GID>` — короткое имя игрового объекта (строение/юнит/ресурс);
  `LONG_<GID>` — длинное описание. Если мод добавляет объекты — эти ключи
  используются страницами автоматически.
- `MENU_*` — пункты меню; `ADM_MENU_*` — пункты меню админки; ключи страниц
  мода можно называть свободно, но лучше с префиксом мода (`BOGUS_MOD_*`,
  `STORM_*`, `LEVI_*`), чтобы не пересекаться с ядром и другими модами.

Готовые языки мода: `ru_ru`, `en_en`, а при желании — все языки из списка
`$Languages` (`game/core/loca.php`). `GalaxyTool` локализован на 6 языков:
`de_de`, `en_en`, `es_es`, `fr_fr`, `it_it`, `ru_ru`.

---

## 7. События мода в очереди

### 7.1. Очередь событий

Вся временная логика игры построена на общей очереди событий (таблица `queue`,
модуль `game/core/queue.php`). Событие — это строка с полями `owner_id`, `type`,
`sub_id`, `obj_id`, `level`, `start`, `end`, `prio`, `freeze`/`frozen`.

API очереди для модов:

```php
AddQueue (int $owner_id, string $type, int $sub_id, int $obj_id, int $level,
          int $now, int $seconds, int $prio = QUEUE_PRIO_LOWEST) : int   // id задачи
RemoveQueue (int $task_id) : void
ProlongQueue (int $task_id, int $seconds) : void    // продлить задачу (для периодических)
```

Обработка очереди (`UpdateQueue`) запускается при каждом действии игрока и из
`cron.php`. Дойдя до события с незнакомым `type` (нет обработчика в ядре), движок
вызывает хук:

```php
default:
    $res = ModsExecRef ('update_queue', $queue);
    if (!$res) {
        RemoveQueue ( $queue['task_id'] );
        Debug ( loca_lang("DEBUG_QUEUE_UNKNOWN", $GlobalUni['lang']) . $queue['type']);
    }
    break;
```

### 7.2. Кастомные события мода

Мод вводит свой `type` — строковую константу — и обрабатывает её в хуке
`update_queue`. Если мод «признал» событие, он обязан либо удалить его
(`RemoveQueue`), либо продлить (`ProlongQueue`) — тогда событие станет
периодическим. Если хук вернул `false` для всех модов — событие удаляется с
диагностикой в лог.

Пример из `BogusMod` — начисление трития раз в час:

```php
public function update_queue(array &$queue) : bool {
    global $db_prefix;
    if ($queue['type'] === QTYP_ADD_TRITIUM) {          // "AddTritium"

        // Начислить тритий всем и продлить событие ещё на час
        $query = "UPDATE ".$db_prefix."users SET tritium = tritium + 1;";
        dbquery ( $query );
        ProlongQueue ($queue['task_id'], BOGUS_MOD_TRITIUM_CREDIT_PERIOD_SECONDS);
        return true;     // событие обработано
    }
    else {
        return false;    // не наше — пропустить к следующим модам
    }
}
```

Общие события вселенной (не привязанные к игроку) заводятся на технический
аккаунт `USER_SPACE` (99999) — так делает и ядро, и моды (см. `install()`
выше). Приоритет периодических фоновых событий можно оставить
`QUEUE_PRIO_LOWEST`.

Примеры посложнее:

- `SpaceStorm` — часовое событие `"SpaceStorm"`: генерирует новый шторм,
  продлевает себя, каждый тик применяет эффекты (см. `update_queue` в
  `SpaceStorm/main.php`);
- `DeepSpaceHorror` — отложенное возрождение монстра: событие `"DeepSpaceHorror"`
  с таймером 24–72 часа пересоздаёт левиафана, когда тот был убит.

---

## 8. Хуки

### 8.1. Общий механизм

Хуки — это методы класса `GameMod`, которые ядро вызывает из разных мест игры,
чтобы мод мог изменить поведение базового движка. Объявления всех хуков с
описанием параметров есть в `game/core/mods.php` — это канонический источник.

Хук вызывается **для всех активных модов в порядке активации** до тех пор, пока
какой-то мод не вернёт `true`:

- `false` — «продолжить»: мод либо ничего не сделал, либо сделал, но разрешает
  сработать и другим модам (так обычно добавляют пункты/ресурсы/бонусы — чтобы
  их могли добавлять несколько модов);
- `true` — «стоп»: хук обработан, остальные моды этот вызов не получат (так
  обычно обрабатывают кастомные события очереди, разрешения и пр.).

Диспетчеры (функции `ModsExec*`) различаются сигнатурами вызова — количество и
типы параметров (по значению / по ссылке / целое + ссылка и т.д.). Полный список
— раздел 12.3 «Диспетчеры хуков».

Параметры-ссылки (`&$...`) — это «выходные» данные: мод меняет их содержимое, и
изменение видит ядро. Например, хук `add_menuitems(array &$json)` получает
ссылку на массив пунктов левого меню — мод добавляет свой пункт прямо в `$json`.

### 8.2. Меню, панель ресурсов и бонусы

Игра строит эти элементы по JSON-схемам («JSON-first»): `leftmenu.json`,
`res_panel.json` — плюс данные из БД. Моды получают доступ на этапе сборки.

**add_menuitems — пункты левого меню.** Вызывается из `page.php` (`LeftMenu`)
после загрузки `pages/leftmenu.json`. Мод добавляет пункт, вставляя элемент в
массив (удобно — функцией `array_insert_after_key` из `utils.php`):

```php
public function add_menuitems(array &$json) : bool {

    array_insert_after_key ($json, "options", "tipoftheday",
        array (
            'type' => 'internal',        // внутренняя страница игры
            'page' => 'tipoftheday',     // имя страницы из роутера
            'loca' => 'BOGUS_MOD_MENU_ITEM',   // ключ локали пункта
        ) );

    return false;   // другие моды тоже могут добавить пункты
}
```

Допустимые типы пунктов (см. `LeftMenu` в `page.php`): `img` (картинка),
`internal` (ссылка на страницу), `internal_buggy` (спец-случай офицеров),
`popup` (окно), `external` (внешняя ссылка). Полезные ключи: `param`
(доп. GET-параметры ссылки), `accesskey` (клавиша доступа), `color`, `notes`.

**add_resources — панель ресурсов.** Вызывается из `page.php` (`ResourceList`).
Второй параметр — активная планета. Мод добавляет свой ресурс в конец панели
(иконка, имя, значение, цвет):

```php
public function add_resources(array &$json, array $aktplanet) : bool {

    global $GlobalUser;

    array_insert_after_key ($json, (string)GID_RC_DM, (string)GID_RC_TRITIUM,
        array (
            'skin'  => false,                          // картинка НЕ из скина
            'img'   => "mods/BogusMod/img/tritium.png",// путь к иконке мода
            'loca'  => "BOGUS_MOD_TRITIUM",            // ключ локали имени
            'val'   => $GlobalUser['tritium'],         // значение (можно из планеты)
            'color' => '',
        ) );

    return false;
}
```

Записи панели ресурсов (см. `res_panel.json`) содержат: `skin` (брать картинку из
скина), `img`, `loca`, `val`, `val2` (для энергии — производство/потребление),
`color`, опционально `href` и `title`.

**add_bonuses — панель бонусов в шапке.** Вызывается из `page.php`
(`BonusList`). Изначально в панели — офицеры; мод добавляет свои бонусы-иконки:

```php
public function add_bonuses (array &$bonuses) : bool {
    $bonuses['storm'] = array (
        'href'     => "",                     // ссылка (или пусто)
        'img'      => "mods/SpaceStorm/img/storm_ikon.png",
        'alt'      => loca("STORM_STORM"),
        'overlib'  => "<b>".loca("STORM_STORM")."</b><br>".loca("STORM_DESC"),
    );
    return false;
}
```

Формат записи бонуса: `href`, `img`, `alt`, `overlib` (текст всплывашки),
опционально `accesskey`.

### 8.3. Свой контент на каждой странице

**begin_content / end_content.** Вызываются из `page.php` (`BeginContent` /
`EndContent`) — до и после контента каждой страницы с обрамлением. Мод может
просто `echo` свой HTML; текущую страницу можно определить обычным способом —
`$_GET['page']`:

```php
public function begin_content() : bool {
    if ( ($_GET['page'] ?? '') === 'overview' ) {
        echo "<div style='color:lime'>Добро пожаловать!</div>";
    }
    return false;
}
```

### 8.4. Хуки конкретных страниц (page_*)

Список таких хуков (все они описаны в классе `GameMod`):

| Хук | Страница | Что позволяет |
|---|---|---|
| `page_buildings_get_bonus(int $id, array &$bonuses)` | `buildings`, `b_building` | показать у объекта доп. бонусы/эффекты |
| `page_flotten1_get_bonus(array $param, array &$bonuses)` | `flotten1` | доп. бонусы на первой странице флота |
| `page_flotten2_planet_types(array &$planet_types)` | `flotten2` | изменить типы целей (планета/луна/обломки/…) |
| `page_flottenversand_ajax_spy_planets(array &$planet_types)` | `flottenversand_ajax` | то же для AJAX-шпионажа |
| `page_galaxy_custom_object(array $planet, array &$info)` | `galaxy` | показать кастомный объект галактики: заполнить `$info['overlib']` (HTML подсказка) и вернуть `true` |
| `page_infos(int $id, array &$planet)` | `infos` | доп. информация/действия для объекта |
| `page_overview_get_bonus(array $param, array &$bonuses)` | `overview` | доп. бонусы на обзоре планеты |
| `page_resources_get_bonus(array $param, array &$bonuses)` | `resources` | доп. бонусы на странице «Сырьё» |

Пример из `SpaceStorm` — на странице информации о здании показать, какие эффекты
шторма он компенсирует (`page_infos` просто выводит HTML), и пометить бонус на
странице исследований (`page_buildings_get_bonus`):

```php
public function page_buildings_get_bonus(int $id, array &$bonuses) : bool {
    $storm = $this->GetStorm();
    if ($id == GID_R_ESPIONAGE && ($storm & SPACE_STORM_MASK_CHRONO_SPY) != 0) {
        $bonuses[] = array (
            'value' => "-2",
            'color' => "red",
            'img'   => "mods/SpaceStorm/img/storm_ikon.png",
            'alt'   => loca("STORM_STORM"),
            'descr' => "<b>".loca("STORM_4")."</b><br/>".loca("STORM_DESC_4"),
            'overlib_width' => 200,
        );
    }
    return false;
}
```

### 8.5. Изображения игровых объектов

Картинки объектов игра получает из скина по ID. Моды, добавляющие свои объекты
(здания, юниты, планеты-объекты), перехватывают запрос картинки и возвращают
свой путь — записав его в `$img['path']` и вернув `true`:

| Хук | Где используется | Когда срабатывает |
|---|---|---|
| `get_object_image(int $id, array &$img)` | `GetObjectImage()` — картинка объекта 120×120 (страницы построек, инфо, дерево технологий) | для любого ID |
| `get_planet_small_image(int $type, array &$img)` | `GetPlanetSmallImage()` — маленькая картинка планеты (30–50px) | по типу объекта галактики |
| `get_planet_image(int $type, array &$img)` | `GetPlanetImage()` — большая картинка планеты | по типу объекта галактики |

Пример из `DeepSpaceHorror`:

```php
public function get_planet_small_image(int $type, array &$img) : bool {
    if ($type == PTYP_LEVI_AMOEBA || $type == PTYP_LEVI_GUARDIAN || $type == PTYP_LEVI_JUGGERNAUT) {
        $img['path'] = "mods/DeepSpaceHorror/img/".$this->GetLeviathanName($type).".jpg";
        return true;
    }
    return false;
}
```

Типы объектов галактики описаны в `game/core/defs.php`: планета `PTYP_PLANET`,
луна `PTYP_MOON`, обломки `PTYP_DF` и т.д.; **все типы >= `PTYP_CUSTOM`
(20001) зарезервированы для объектов модов** (в `DeepSpaceHorror` левиафаны —
22848…22851).

### 8.6. Экономика и производство

**bonus_prod / bonus_cons.** Вызываются из `game/core/prod.php`
(`ProdBonus`/`ConsBonus`) для каждого производственного ресурса при пересчёте
планеты. Вход: `$param` с ключами `uni`, `user`, `planet`, `rc`. Выход: список
**множителей** (по ссылке) — базовые бонусы складываются в массив, а мод
добавляет свой множитель в конец:

```php
public function bonus_prod (array $param, array &$bonus) : bool {
    $planet = $param['planet'];
    // Стабилизатор реальности: +3% к добыче дейтерия за уровень
    if ($param['rc'] == GID_RC_DEUTERIUM && $planet[GID_B_REALITY_STAB] > 0) {
        $bonus[] = 1 + 0.03 * $planet[GID_B_REALITY_STAB];
    }
    return false;
}
```

**prod_post_process.** Вызывается из `prod.php` (`ProdResources`) после расчёта
всех балансов. По ссылке передаются планета и экономическая сводка `$eco` с
ключами `prod`, `prod_with_bonus`, `cons`, `cons_with_bonus`, `net_prod`,
`net_cons`, `balance` (по ресурсам). Здесь мод может перенести часть
производства в другой ресурс, заморозить энергетику и т.п. (пример — эффект
«Сигнатура материи» в `SpaceStorm`).

### 8.7. Постройки, исследования, очередь

**can_build / can_research.** Вызываются в конце проверок
`CanBuild`/`CanResearch` (`game/core/queue.php`) — **после** всех стандартных
проверок ядра. На вход — `$info` с ключами `id`, `level`, `user`, `planet` (и
`destroy`/`enqueue` для построек). Если мод хочет **запретить** — пишет в
`$info['result']` строку-ошибку (обычно ключ локали) и возвращает `true`; если
разрешить — возвращает `false`:

```php
public function can_build(array &$info) : bool {
    $storm = $this->GetStorm();
    if ($info['id'] == GID_B_REALITY_STAB && $storm == 0) {
        $info['result'] = loca ("STORM_REQUIRED");  // «можно строить только во время шторма»
        return true;
    }
    return false;
}
```

**build_end / research_end.** Вызываются по завершении строительства/сноса
(здания) и исследования (см. `Queue_Build_End`/`Queue_Research_End` в
`queue.php`). `build_end(int $planet_id, array &$queue)` получает ID планеты и
строку события (в `$queue['obj_id']` — объект, в `$queue['type']` — `"Build"`/
`"Demolish"`). Удобно для начисления бонусов, установки масок, запуска цепочек.

**update_queue — события мода.** Описан в разделе 7.

### 8.8. Флот, шпионаж, технологии

Хуки этой группы вызываются из `game/core/fleet.php`.

| Хук | Смысл |
|---|---|
| `fleet_available_missions(array $param, array &$missions)` | изменить список доступных миссий флота (в `$missions` — константы `FTYP_*`). Вход `$param`: координаты откуда/куда (`thisgalaxy`, `thissystem`, `thisplanet`, `thisplanettype`, `galaxy`, `system`, `planet`, `planettype`) и состав флота `fleet` |
| `bonus_fleet_speed(array $param, array &$bonus)` | изменить скорость корабля: в `$bonus['value']` лежит текущая скорость; мод переписывает значение |
| `bonus_fleet_cons(array $param, array &$bonus)` | то же для расхода дейтерия кораблём |
| `bonus_max_fleet(array $param, array &$bonus)` | то же для максимального числа флотов игрока |
| `bonus_technology(int $id, array &$bonus)` | модифицировать «уровень» технологии. Используется для шпионажа: `$bonus['level']` — эффективный уровень шпионажа (см. `SpyArrive`) |
| `spy_protection(array $args, array &$bonus)` | дать планете-цели защиту от шпионажа: `$args['planet']`/`$args['target_user']`; увеличить `$bonus['level']` |
| `fleet_handler(array $param)` | обработать **кастомную миссию флота** (см. ниже) |

Кастомные миссии. Когда у флота миссия, которую ядро не знает, `Queue_Fleet_End`
отдаёт управление модам:

```php
default:
    $param = [];
    $param['queue'] = $queue;        // строка события очереди
    $param['fleet_obj'] = $fleet_obj;// строка флота
    $param['fleet'] = $fleet;        // состав (по кораблям)
    $param['origin'] = $origin;      // планета отправления
    $param['target'] = $target;      // планета цели
    ModsExecArr ('fleet_handler', $param);
    break;
```

Базовые миссии занимают диапазон `FTYP_ATTACK`…`FTYP_ACS_ATTACK_HEAD` (1–21);
моды добавляют к своей миссии `FTYP_RETURN` (100) для «обратного» полёта.
Определение: в `defs.php` есть `FTYP_CUSTOM = 1000` — миссии от этого значения
считаются кастомными. Пример: `DeepSpaceHorror` использует миссию
`FTYP_LEVI_PREPARE_JUMP = 22855` и обрабатывает и прилёт, и возврат в
`fleet_handler` (мод сам диспетчеризует фазы по `fleet_obj['mission']`).

> Нюанс: `fleet_handler` — единственный диспетчер, передающий массив по
> значению (`ModsExecArr`), а после вызова ядро, как и для обычных миссий,
> удаляет строку флота и событие очереди. Если ваша миссия должна продолжаться
> (например, флот должен вернуться), мод сам создаёт новый флот и событие
> (`DispatchFleet`/`AddQueue`). Обработанную миссию нужно «заявлять» возвратом
> `true`.

### 8.9. Бой

Оба хука вызываются из `game/core/battle.php`.

**battle_unit_stats(array $args, array &$unit_param)** — вызывается один раз на
бой из фронтенда боя (`GenBattleSourceData`) — перед сериализацией данных для
боевого движка. `$args` содержит `attackers` и `defenders` (участники с
технологиями и составом); второй параметр — глобальная таблица параметров
юнитов `$UnitParam` **по ссылке** (структура/щит/атака/груз/скорость/расход).
Мод может масштабировать характеристики для этого конкретного боя:

```php
public function battle_unit_stats(array $args, array &$unit_param) : bool {
    // Полярное искажение: броня -20%, щиты +30% для всех
    foreach ($unit_param as $gid => $p) {
        $unit_param[$gid][0] = (int)round($p[0] * SPACE_STORM_POLAR_ARMOR);
        $unit_param[$gid][1] = (int)round($p[1] * SPACE_STORM_POLAR_SHIELD);
    }
    return false;
}
```

> Изменения применяются только к данным конкретного боя: сразу после
> сериализации ядро восстанавливает исходный `$UnitParam`.

**battle_post_process(array &$res)** — вызывается после боя из
`PostProcessBattleResult`, когда результат уже обогащён именами/координатами
участников. `$res` содержит `before` (атакующие/защитники), `rounds` (по раундам
с потерями), `result` и т.д., а мод может наполнить `$res['extra']` (его
содержимое попадает в отчёт) и выполнить свои действия после боя — например,
применить потери к кастомным юнитам (`DeepSpaceHorror`), добавить «эхо атаки»
(`SpaceStorm`).

### 8.10. База данных мода

Хуки этой группы вызываются из слоя БД (`db.php`, `db_mysql.php`,
`db_sqlite.php`).

| Хук | Когда | Что делает |
|---|---|---|
| `install_tabs_included(array &$tabs)` | при создании БД (`CreateDBTables`), проверке/сериализации БД в админке | мод дописывает свои колонки/таблицы в схему `$tabs` (см. ниже) |
| `add_db_row(array &$row, string $tabname)` | при каждой вставке строки через `AddDBRow` | мод может докинуть свои поля в добавляемую строку (например, в строку флота — свои кастомные поля) |
| `lock_tables(array &$tabs)` | `LockTables()` перед серией запросов | мод добавляет свои таблицы в список блокируемых |

**install_tabs_included.** Схема БД игры описана в `game/core/install_tabs.php`:
массив `$tabs['<таблица>']['<колонка>'] = '<тип SQL>'`. Мод обязан объявить там
же всё, что добавляет в `install()`, — тогда создание БД, проверки целостности и
сериализация будут знать о колонках мода:

```php
public function install_tabs_included (array &$tabs) : bool {
    $tabs['users']['tritium'] = 'INT DEFAULT 0';
    return false;
}
```

**Правило:** колонки, добавляемые в `install()` через `ALTER TABLE`, и колонки
в `install_tabs_included()` должны совпадать по имени и типу.

**Прямая работа с БД.** Модам доступны те же функции, что и ядру: `dbquery()`,
`dbrows()`, `dbarray()`, `dbfree()`; `AddDBRow($row, $tabname)` — вставка строки
с автоподстановкой ID (именно она триггерит хук `add_db_row`); блокировки —
`LockTables()`/`UnlockTables()`. Префикс таблиц — глобальная `$db_prefix`
(всегда добавляйте его к именам таблиц!). Игра поддерживает два бэкенда БД —
MySQL (`db_mysql.php`) и SQLite для тестов (`db_sqlite.php`); API одинаков.

---

## 9. Расширенные сценарии

### 9.1. Кастомные объекты галактики (по мотивам DeepSpaceHorror)

«Левиафаны» — это планеты-объекты особых типов, принадлежащие техническому
аккаунту `USER_SPACE` и появляющиеся на обычных координатах галактики. Такие
объекты — это обычные строки таблицы `planets` с `type >= PTYP_CUSTOM`; галактика
показывает их отдельной колонкой (см. `EnumCustomPlanetsGalaxy` и
`ShowCustomObjects` в ядре). Мод:

1. Вводит свои константы типов (>= `PTYP_CUSTOM`):
   ```php
   const PTYP_LEVI_AMOEBA = 22849;   // тип объекта-монстра
   const PTYP_LEVI_PORTAL = 22848;   // точка выхода
   const GID_LEVI_AMOEBA = 22852;    // «корабль»-монстр (колонка во fleet)
   const FTYP_LEVI_PREPARE_JUMP = 22855;  // кастомная миссия
   const QTYP_LEVI_RESPAWN = "DeepSpaceHorror";  // кастомный тип события
   ```
2. В `install()`: добавляет колонки во `fleet`/`fleetlogs` под свои юниты;
   вызывает `init()`; создаёт объекты (`CreateLeviathan`) — планеты типа
   `PTYP_LEVI_*` и флот `USER_SPACE` с миссией `FTYP_LEVI_PREPARE_JUMP`.
3. В `init()`: `$fleetmap[]`, `$UnitParam[]`, `$RapidFire[]`,
   `$requirements[]` (раздел 4.3); `loca_add`.
4. Хуки изображений `get_planet_*_image`/`get_object_image` — картинки монстров.
5. `page_galaxy_custom_object` — показать монстра в галактике (overlib), а
   `page_flotten2_planet_types` / `page_flottenversand_ajax_spy_planets` —
   разрешить наводить на него флот/шпионаж.
6. `fleet_handler` — обработать прилёт игрока к монстру: запустить бой
   (`GenBattleSourceData`/`ExecuteBattle`), раздать добычу, применить потери;
   обработать возврат.
7. `update_queue` — событие `QTYP_LEVI_RESPAWN` возрождает убитого монстра
   через 24–72 часа.
8. В `uninstall()` — удалить свои объекты, флоты, события и колонки.

### 9.2. Мод-инструмент с игровой и админ-страницей (по мотивам GalaxyTool)

`GalaxyTool` — «галактический инструмент»: показывает статистику по галактике.
Из интересного:

- своя страница игрока (`pages/galaxytool.php`) регистрируется хуком `route`
  как `'bare' => true` — страница рисует собственный полный HTML (без
  обрамления движка), открывается как всплывающее окно пунктом меню
  `add_menuitems` (тип `popup`);
- свой раздел админки `Admin_GalaxyTool` (страница `pages_admin/`), добавленный
  хуком `route_admin` с иконкой меню;
- колонка `uni.galaxytool_update` (объявлена в `install_tabs_included`) и
  еженедельное событие `"GalaxyTool"`, которое пересобирает снимки галактики в
  `temp/`;
- локализация на 6 языков; у страницы-поп-апа секция локали пустая
  (`'loca' => []`) — файлы подключает сам мод.

Это хороший образец, если мод — самостоятельный инструмент, а не «правка
механики».

### 9.3. Резюме: как добавить в игру

| Хочу добавить | Что делаю |
|---|---|
| Ресурс-счётчик | колонка в БД (`users`…), `add_resources` для панели, событие для начисления |
| Здание | колонка в `planets` + `install_tabs_included`, `$buildmap`/`$initial`/`$requirements`/`$CanBuildTab` в `init()`, локали `NAME_`/`LONG_`, `get_object_image`, хуки страниц |
| Корабль/оборону | колонки во `fleet` (+`fleetlogs` при необходимости), `$fleetmap`/`$UnitParam`/`$RapidFire`/`$requirements` |
| Объект галактики | тип >= `PTYP_CUSTOM`, планета `USER_SPACE`, `get_planet_*_image`, `page_galaxy_custom_object` |
| Страницу игрока | файл в `pages/`, запись в `route`, пункт меню в `add_menuitems`, локали |
| Раздел админки | файл класса `Admin_<Mode>`, запись в `route_admin` |
| Периодическое действие | событие в `AddQueue` (при install) + `update_queue` + `ProlongQueue` |
| Правку механики | нужный хук из раздела 8 |

---

## 10. Тестирование модов

Моды могут (и должны) носить собственный PHPUnit-набор в папке `testing/` —
рядом с кодом мода, не загрязняя общий набор репозитория. Так сделано в
`SpaceStorm` и `DeepSpaceHorror`.

Структура:

```
game/mods/<Name>/testing/
├── phpunit.xml          # конфигурация набора
├── bootstrap.php        # подключение ядра и мода
├── <Name>Test.php       # «чистые» тесты логики (без БД)
└── <Name>DbTest.php     # тесты с in-memory SQLite
```

Ключевые моменты `bootstrap.php` (см. `SpaceStorm/testing/bootstrap.php`):

- включается `vendor/autoload.php`, затем ядро игры с **in-memory SQLite**
  (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) — MySQL не нужен;
- рабочий каталог меняется на `game/` (`chdir`), потому что ядро и страницы
  резолвят относительные include;
- подключается `game/core/core.php` (объявляет `GameMod` и диспетчеры), затем
  `main.php` мода.

Запуск набора:

```bash
vendor/bin/phpunit -c game/mods/SpaceStorm/testing/phpunit.xml
```

Тесты, работающие с БД, строят минимальную вселенную через настоящие функции
(`CreateDBTables()`, `AddDBRow()`, ...) — без моков схемы. Некоторые моды
выносят в защищённые методы «случайность» (например, `Rnd()` в
`DeepSpaceHorror`) и переопределяют её в тестах для воспроизводимости.

---

## 11. Публикация и сопровождение

- **Readme.md внутри мода** — обязателен для внятного мода: что делает мод,
  как установить, как работает, какие хуки использует. Образец —
  `DeepSpaceHorror/Readme.md` (переписан в issue #279 как читаемый документ мода:
  описание, правила, таблица хуков, команда запуска тестов).
- **Версии.** Версия из `manifest.json`; при изменении схемы БД продумайте
  миграцию для уже установленных вселенных (повторный `install()` или отдельный
  скрипт).
- **Деактивация.** Админ снимает мод в админке; `uninstall()` должен оставить
  игру в исходном состоянии (удалить свои колонки, события, объекты).
- **Совместимость.** Мод работает на базе движка 0.84; версия ядра —
  `$CoreVersion` (`game/core/core.php`) — для проверок совместимости.

---

## 12. Админка модов и функции управления

### 12.1. Страница админки

Раздел админки *Моды* (`index.php?page=admin&mode=Mods`,
`game/pages_admin/admin_mods.php`):

![admin_mods](/wiki/imgstore/admin_mods.png)

- Слева — установленные моды (в порядке активации), справа — доступные (лежащие
  в `game/mods/`). Карточка мода — фоновая картинка `img/bg.png` мода, название,
  описание, версия, автор, сайт.
- Установленный мод можно сдвинуть выше/ниже (порядок активации хуков!) или
  удалить; доступный — установить.
- Если для имени из списка `modlist` пропал `manifest.json`, ядро само уберёт
  его из списка.

### 12.2. Функции управления (game/core/mods.php)

| Функция | Назначение |
|---|---|
| `ModsInit()` | инициализировать все моды из `modlist` (вызывается ядром при каждом запросе и в cron) |
| `ModInitOne($modname)` | загрузить один мод по имени (include `main.php`, `new <Имя>`, `init()`) |
| `ModInstallOne($modname)` | загрузить мод и вызвать `install()` (без добавления в `modlist`) |
| `ModsInstall($modname)` | добавить мод в `modlist` (uni) и установить |
| `ModsRemove($modname)` | убрать из `modlist` и вызвать `uninstall()` |
| `ModsMoveUp($modname)` / `ModsMoveDown($modname)` | изменить порядок активации |
| `ModsList()` | вернуть `['available' => [...], 'installed' => [...]]` |
| `ModsGetInfo($modname)` | метаданные из `manifest.json` (+`folder`, `bg_image`) или `null` |

### 12.3. Диспетчеры хуков

Все диспетчеры обходят `$modlist` в порядке активации и останавливаются на
первом `true`:

| Функция | Параметры хука |
|---|---|
| `ModsExec($method)` | без параметров |
| `ModsExecArr($method, $arr)` | массив по значению |
| `ModsExecRef($method, &$arr)` | массив по ссылке |
| `ModsExecRefArr($method, &$arr, $arr2)` | ссылка + значение |
| `ModsExecArrRef($method, $arr, &$arr2)` | значение + ссылка |
| `ModsExecRefRef($method, &$arr, &$arr2)` | две ссылки |
| `ModsExecIntRef($method, $val, &$arr)` | int + ссылка |
| `ModsExecRefStr($method, &$arr, $str)` | ссылка + строка |

---

## 13. Приложение: сводная таблица хуков

Полный список методов-хуков класса `GameMod` (см. `game/core/mods.php`), точки
вызова в ядре и смысл:

| Хук | Вызов в ядре | Смысл |
|---|---|---|
| `route` | `index.php` | добавить страницы в роутер игры |
| `route_admin` | `pages_admin/admin.php` | добавить разделы в роутер админки |
| `update_queue` | `queue.php` (`UpdateQueue`, default) | обработать кастомное событие очереди |
| `add_resources` | `page.php` (`ResourceList`) | добавить ресурс в панель ресурсов |
| `add_menuitems` | `page.php` (`LeftMenu`) | добавить пункты левого меню |
| `add_bonuses` | `page.php` (`BonusList`) | добавить бонусы в шапку |
| `lock_tables` | `db_mysql.php`/`db_sqlite.php` (`LockTables`) | добавить таблицы в блокировку |
| `install_tabs_included` | `db.php`/`db_mysql.php`/`db_sqlite.php`/`admin_db.php` | дополнить схему БД |
| `get_planet_small_image` | `page.php` (`GetPlanetSmallImage`) | картинка маленькой планеты |
| `get_planet_image` | `page.php` (`GetPlanetImage`) | картинка планеты |
| `get_object_image` | `page.php` (`GetObjectImage`) | картинка объекта 120×120 |
| `begin_content` | `page.php` (`BeginContent`) | HTML до контента страницы |
| `end_content` | `page.php` (`EndContent`) | HTML после контента страницы |
| `add_db_row` | `db_mysql.php`/`db_sqlite.php` (`AddDBRow`) | докинуть поля в добавляемую строку |
| `can_build` | `queue.php` (`CanBuild`) | запретить/разрешить стройку |
| `can_research` | `queue.php` (`CanResearch`) | запретить/разрешить исследование |
| `build_end` | `queue.php` (`Queue_Build_End`) | завершение стройки/сноса |
| `research_end` | `queue.php` (`Queue_Research_End`) | завершение исследования |
| `fleet_available_missions` | `fleet.php` (`FleetAvailableMissions`) | список доступных миссий |
| `fleet_handler` | `fleet.php` (`Queue_Fleet_End`, default) | кастомная миссия флота |
| `prod_post_process` | `prod.php` (`ProdResources`) | пост-обработка производства |
| `battle_post_process` | `battle.php` (`PostProcessBattleResult`) | пост-обработка боя |
| `battle_unit_stats` | `battle.php` (`GenBattleSourceData`) | параметры юнитов для конкретного боя |
| `page_buildings_get_bonus` | `buildings.php`, `b_building.php` | бонусы объекта на страницах построек |
| `page_flotten1_get_bonus` | `flotten1.php` | бонусы первой страницы флота |
| `page_flotten2_planet_types` | `flotten2.php` | типы целей флота |
| `page_flottenversand_ajax_spy_planets` | `flottenversand_ajax.php` | типы целей при AJAX-шпионаже |
| `page_infos` | `infos.php` | доп. инфо/действия объекта |
| `page_galaxy_custom_object` | `galaxy.php` | кастомный объект галактики |
| `page_overview_get_bonus` | `overview.php` | бонусы обзора планеты |
| `page_resources_get_bonus` | `resources.php` | бонусы страницы «Сырьё» |
| `bonus_technology` | `fleet.php` (`SpyArrive`), `event_list.php` | изменить уровень технологии |
| `spy_protection` | `fleet.php` (`SpyArrive`) | шпион-защита планеты-цели |
| `bonus_prod` | `prod.php` (`ProdBonus`) | множитель производства |
| `bonus_cons` | `prod.php` (`ConsBonus`) | множитель потребления |
| `bonus_max_fleet` | `fleet.php` (`GetMaxFleet`) | максимум флотов |
| `bonus_fleet_cons` | `fleet.php` (`FleetCons`) | расход топлива корабля |
| `bonus_fleet_speed` | `fleet.php` (`FleetSpeed`) | скорость корабля |
| `skip_planet_update` | `prod.php` (`GetUpdatePlanet`) | «заморозить» планету: не начислять производство (консервация империи, режим странника) |
| `page_veto` | `index.php` (перед отрисовкой страницы) | перехватить запрос страницы целиком (блокировка классических разделов в другом режиме игры) |
| `fleet_dispatch_veto` | `fleet.php` (`DispatchFleet`, исходящие миссии) | запретить отправку нового флота (защита объектов мода — станция, планеты странника) |

> Хуки `skip_planet_update`, `page_veto` и `fleet_dispatch_veto` добавлены в
> движок для мода `Wanderer` («Странствующий Торговец»); работают через
> штатные диспетчеры `ModsExecRef`/`ModsExecArr` и ничего не меняют, когда
> мод не установлен.

## Ссылки

- Исходники подсистемы модов: [`game/core/mods.php`](../../game/core/mods.php)
- Хуки и их объявления: класс `GameMod` в `game/core/mods.php`
- Эталонные моды: `game/mods/{BogusMod,GalaxyTool,SpaceStorm,DeepSpaceHorror}`
- HTML-мануал со скином Evolution: [`docs/mod-manual-ru.html`](../../docs/mod-manual-ru.html)
- Английская версия этой страницы: [`en/mods.md`](../en/mods.md)

