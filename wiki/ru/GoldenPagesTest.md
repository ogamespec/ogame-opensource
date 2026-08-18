# GoldenPagesTest

### Обзор

`GoldenPagesTest` — это класс PHPUnit-тестов, выполняющий snapshot-тестирование (тестирование golden files) фронтенд-страниц OGame. Он рендерит каждую игровую страницу с использованием тестовой вселенной с 3 игроками и сравнивает сгенерированный HTML-код с сохранёнными golden-снимками.

### Принцип работы

1. **Тестовая вселенная**: FixtureBuilder создаёт тестовую вселенную с 3 игроками (PlayerOne, PlayerTwo, PlayerThree) в **in-memory движке** (`game/core/db.php` с SQLite-бэкендом, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, см. `phpunit.xml`). Реальная схема БД создаётся через `CreateDBTables()` (из `install_tabs.php`), данные вставляются через реальную функцию `AddDBRow()` — без самодельных моков и ручной схемы.
2. **Рендеринг страниц**: Класс `PageRenderer` повторяет цикл загрузки `index.php` (LoadUniverse → AuthUser → роутер → MVC/классические страницы) и рендерит **настоящие игровые страницы** через реальный DB-слой. Каждый тест запускается в отдельном PHP-процессе (`#[RunTestsInSeparateProcesses]`), как и `NotesTest`/`DbSqliteTest`: только так bootstrap загружается в глобальной области видимости, где игровые модули объявляют свои глобальные переменные (`$GlobalUser`, `$LOCA`, `$resourcemap`, ...).
3. **Сравнение снимков**: Сгенерированный HTML сравнивается с golden-снимками, хранящимися в `testing/golden/`. Перед сравнением динамический контент (временные метки, ID, токены сессий) нормализуется.

### Golden-снимки

Golden-снимки хранятся в `testing/golden/` с соглашением об именовании:

```
testing/golden/{имя_страницы}_{индекс_игрока}.html
```

Например:
- `overview_p0.html` — страница Overview для PlayerOne
- `overview_p1.html` — страница Overview для PlayerTwo
- `buildings_shipyard_p0.html` — страница Buildings (вкладка Shipyard) для PlayerOne

### Запуск тестов

```bash
# Запуск всех golden-тестов страниц
vendor/bin/phpunit --testsuite "Golden Pages"

# Запуск конкретного теста
vendor/bin/phpunit --filter testOverviewPagePlayerOne

# Запуск тестов с пропуском сравнения golden (только вывод)
# HTML будет выведен в stdout, но не будет сравниваться
```

### Обновление golden-снимков

При изменении макетов страниц golden-снимки необходимо перегенерировать:

```bash
UPDATE_GOLDEN=1 vendor/bin/phpunit --testsuite "Golden Pages"
```

Это перезапишет все существующие golden-снимки новым сгенерированным HTML.

### Структура тестов

| Метод теста | Описание |
|-------------|----------|
| `testUniverseHasThreePlayers` | Проверяет, что тестовая вселенная содержит ровно 3 игрока |
| `testEachPlayerHasHomePlanet` | Проверяет, что каждый игрок имеет домашнюю планету |
| `testUniverseSettingsAreConfigured` | Проверяет настройки вселенной (num, galaxies, systems, lang) |
| `testOverviewPagePlayerOne` | Тестирование страницы Overview для PlayerOne |
| `testOverviewPagePlayerTwo` | Тестирование страницы Overview для PlayerTwo |
| `testOverviewPagePlayerThree` | Тестирование страницы Overview для PlayerThree |
| `testBuildingsPageShipyardPlayerOne` | Тестирование страницы Buildings (вкладка Shipyard) |
| `testBuildingsPageDefensePlayerOne` | Тестирование страницы Buildings (вкладка Defense) |
| `testBuildingsPageResearchPlayerOne` | Тестирование страницы Buildings (вкладка Research) |
| `testInfosPageMetalMinePlayerOne` | Тестирование страницы infos для Metal Mine |
| `testMessagesPagePlayerOne` | Тестирование страницы сообщений |
| `testNotesPagePlayerOne` | Тестирование страницы заметок |
| `testStatisticsPagePlayerOne` | Тестирование страницы статистики |
| `testOptionsPagePlayerOne` | Тестирование страницы настроек |
| `testChangelogPagePlayerOne` | Тестирование страницы changelog |
| `testResourcesPagePlayerOne` | Тестирование страницы ресурсов |
| `testFleetPage1PlayerOne` | Тестирование страницы флота |
| `testFleetTemplatesPagePlayerOne` | Тестирование страницы шаблонов флота |
| `testBuddyPagePlayerOne` | Тестирование страницы buddy |
| `testAllianzenPagePlayerOne` | Тестирование страницы альянса |
| `testImperiumPagePlayerOne` | Тестирование страницы imperium (империя) |
| `testGalaxyPagePlayerOne` | Тестирование страницы галактики |
| `testTechtreePagePlayerOne` | Тестирование страницы techtree |
| `testTraderPagePlayerOne` | Тестирование страницы трейдера |
| `testMicropaymentPagePlayerOne` | Тестирование страницы микроплатежей |
| `testPrangerPage` | Тестирование внешней страницы pranger |
| `testAinfoPage` | Тестирование внешней страницы ainfo |
| `testWriteMessagesPagePlayerOne` | Тестирование страницы написания сообщений |
| `testBewerbenPagePlayerOne` | Тестирование страницы bewerben (подача заявки в альянс) |
| `testPlayerTwoPlanetOverview` | Тестирование переключения планет для PlayerTwo |
| `testPlayerThreePlanetOverview` | Тестирование переключения планет для PlayerThree |
| `testOverviewPageDeterministic` | Проверка детерминированности рендеринга страниц |
| `testDifferentPlayersHaveDifferentContent` | Проверка, что разные игроки имеют разное содержимое страниц |
| `testResourcesPageShowsCorrectResources` | Проверка корректности отображения ресурсов |
| `testAvailablePagesCanBeListed` | Проверка возможности получения списка страниц из router.json |
| `testFixturePlanetCounts` | Проверка корректного количества планет на игрока |
| `testFleetDataForPlayerOne` | Проверка наличия данных флота для PlayerOne |
| `testMessagesForPlayerOne` | Проверка наличия сообщений для PlayerOne |
| `testNotesForPlayerOne` | Проверка наличия заметок для PlayerOne |

### Нормализация HTML

Перед сравнением HTML нормализуется для обработки динамического контента:

| Паттерн | Замена |
|---------|--------|
| Временные метки (`YYYY-MM-DD HH:MM:SS`) | `DATE_TIME` |
| Временные метки статистики (`YYYY-MM-DD, HH:MM:SS`) | `DATE_TIME` |
| Временные метки overview (`Tue Aug 18 15:38:36`) | `DATE_TIME` |
| Временные метки (`DD.MM.YYYY HH:MM:SS`, `MM-DD HH:MM:SS`) | `DATE_TIME` |
| Числовые временные метки (10+ цифр) | `TIMESTAMP` |
| Числа с плавающей точкой | `FLOAT` |
| `planet_id=X` | `planet_id=ID` |
| `player_id=X` | `player_id=ID` |
| `fleet_id=X` | `fleet_id=ID` |
| `cp=X` | `cp=ID` |
| `session=XXXX` | `session=SESSION` |
| `lastpeek=X` | `lastpeek=TIMESTAMP` |
| Несколько пробелов | Один пробел |

### Архитектура

```
testing/
├── GoldenPagesTest.php      # Основной класс тестов (каждый тест в отдельном процессе)
├── PageRenderer.php          # Рендеринг настоящих игровых страниц через реальный DB-слой
├── FixtureBuilder.php        # Создание тестовой вселенной через in-memory движок (SQLite)
├── bootstrap.php             # PHPUnit bootstrap: загрузка игрового ядра с SQLite-бэкендом
└── golden/                   # Golden-снимки
    ├── .gitignore
    ├── overview_p0.html
    ├── overview_p1.html
    └── ...
```

### Замечания

- Тесты используют **реальный in-memory DB-движок** из мастера (`game/core/db.php` +
  `game/core/db_sqlite.php`, `DB_CONNECTION=sqlite`). Схема создаётся из
  `install_tabs.php` через `CreateDBTables()`, данные — через `AddDBRow()`.
- Тесты запускаются в отдельных PHP-процессах: только так PHPUnit загружает
  bootstrap в глобальной области видимости, где игровые модули объявляют
  глобальные переменные, от которых зависят страницы.
- `PageRenderer` повторяет цикл загрузки `game/index.php` (LoadUniverse,
  AuthUser, роутер из `router.json`, MVC/классические страницы).
