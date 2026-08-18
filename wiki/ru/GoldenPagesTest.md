# GoldenPagesTest

### Обзор

`GoldenPagesTest` — это класс PHPUnit-тестов, выполняющий snapshot-тестирование (тестированиеgolden files) фронтенд-страниц OGame. Он рендерит каждую игровую страницу с использованием тестовой вселенной с 3 игроками и сравнивает сгенерированный HTML-код с сохранёнными golden-снимками.

### Принцип работы

1. **Тестовая вселенная**: FixtureBuilder создаёт in-memory базу данных SQLite с 3 игроками (PlayerOne, PlayerTwo, PlayerThree), каждый из которых имеет 3 планеты, данные флота, сообщения и заметки.
2. **Рендеринг страниц**: Класс `PageRenderer` имитирует точку входа `index.php` игры, загружая все необходимые core-модули, файлы локализации и файлы страниц. Он захватывает сгенерированный HTML-вывод.
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
├── GoldenPagesTest.php      # Основной класс тестов
├── PageRenderer.php          # Имитация рендеринга игровых страниц
├── FixtureBuilder.php        # Создание тестовой вселенной
├── bootstrap_golden.php      # PHPUnit bootstrap для golden-тестов
└── golden/                   # Golden-снимки
    ├── .gitkeep
    ├── overview_p0.html
    ├── overview_p1.html
    └── ...
```
