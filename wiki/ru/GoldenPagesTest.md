# GoldenPagesTest

### Обзор

`GoldenPagesTest` — это класс PHPUnit-тестов, выполняющий snapshot-тестирование (тестирование golden files) фронтенд-страниц OGame. Он рендерит каждую игровую страницу с использованием тестовой вселенной с 3 игроками и сравнивает сгенерированный HTML-код с сохранёнными golden-снимками.

Тестовая вселенная специально насыщена данными (issue #256 «Максимальное покрытие Golden тестов всех игровых страниц»): у каждого игрока есть луны, летящие флоты всех типов миссий (атака, шпионаж, транспорт, развёртывание, переработка, колонизация, экспедиция и **уничтожение лун**), активные очереди построек/исследований/верфей, сообщения всех типов (личные, шпионаж, бой, экспедиция, альянс, прочее), альянс с рангами и заявками, друзья, шаблоны флота, баны, обломки и дальний космос. Благодаря этому golden-снимки проверяют реальные ветки кода страниц, а не пустые оболочки.

### Принцип работы

1. **Тестовая вселенная**: FixtureBuilder создаёт тестовую вселенную с 3 игроками (PlayerOne, PlayerTwo, PlayerThree) в **in-memory движке** (`game/core/db.php` с SQLite-бэкендом, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, см. `phpunit.xml`). Реальная схема БД создаётся через `CreateDBTables()` (из `install_tabs.php`), данные вставляются через реальную функцию `AddDBRow()` — без самодельных моков и ручной схемы.
2. **Рендеринг страниц**: Класс `PageRenderer` повторяет цикл загрузки `index.php` (LoadUniverse → AuthUser → роутер → MVC/классические страницы) и рендерит **настоящие игровые страницы** через реальный DB-слой. Каждый тест запускается в отдельном PHP-процессе (`#[RunTestsInSeparateProcesses]`), как и `NotesTest`/`DbSqliteTest`: только так bootstrap загружается в глобальной области видимости, где игровые модули объявляют свои глобальные переменные (`$GlobalUser`, `$LOCA`, `$resourcemap`, ...). Страницы, работающие только через POST (flotten2, flotten3, flottenversand, sprungtor), рендерятся через `withPost()`, который заполняет `$_POST` и переключает метод запроса на POST. Начиная с issue #258 («Добавить больше страниц с запросом POST в GoldenPages»), **каждая страница, обрабатывающая `method() === "POST"`**, дополнительно рендерится через `withPost()` (POST-снимки с суффиксом `*_post_*`), чтобы страница, которая выглядит нормально при GET, но ломается при взаимодействии (POST), обнаруживалась сравнением снимков.
3. **Сравнение снимков**: Сгенерированный HTML сравнивается с golden-снимками, хранящимися в `testing/golden/`. Перед сравнением динамический контент (временные метки, обратные отсчёты, ID, токены сессий) нормализуется.

### Golden-снимки

Golden-снимки хранятся в `testing/golden/` с соглашением об именовании:

```
testing/golden/{имя_страницы}_{индекс_игрока}.html
```

Например:
- `overview_p0.html` — страница Overview для PlayerOne
- `overview_p1.html` — страница Overview для PlayerTwo
- `buildings_shipyard_p0.html` — страница Buildings (вкладка Shipyard) для PlayerOne
- `buildings_shipyard_post_p0.html` — та же страница, отрендеренная через её POST-форму (issue #258)

POST-снимки именуются `{страница}_{вариант}_post_p{индекс}.html`; POST-only
страницы флота из issue #256 (flotten2/flotten3/flottenversand/sprungtor)
сохраняют свои обычные имена, потому что их снимки уже рендерят POST-поток.

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
| `testPlayersHaveMoons` | Проверяет, что у каждого игрока есть хотя бы одна луна |
| `testFleetQueueEventsExist` | Проверяет события очереди для флотов (источник списка событий Overview) |
| `testActiveBuildAndResearchQueue` | Проверяет наличие очередей построек/исследований/верфей |
| `testAllMessageTypesExist` | Проверяет наличие всех типов сообщений MTYP_* у PlayerOne |
| `testOverviewPagePlayerOne/Two/Three` | Тестирование страницы Overview для каждого игрока |
| `testOverviewShowsFleetEvents` | Проверяет события флотов (свои и чужие, включая «Уничтожить» на луну) |
| `testOverviewShowsMoonAndBuildQueue` | Проверяет отображение луны и активной постройки |
| `testOverviewMoonView` | Тестирование Overview самой луны (cp=луна) |
| `testBuildingsPageShipyardPlayerOne` | Тестирование страницы Buildings (вкладка Shipyard) с кораблями и активным заказом |
| `testBuildingsPageDefensePlayerOne` | Тестирование страницы Buildings (вкладка Defense) |
| `testBuildingsPageResearchPlayerOne` | Тестирование страницы Buildings (вкладка Research) с активным исследованием |
| `testBuildingPagePlayerOne` | Тестирование страницы b_building (интерфейс очереди построек) |
| `testInfosPage*` | Тестирование страницы infos: шахты металла/кристалла/дейтерия, солнечная, термоядерная, хранилища, ракетная шахта, склад альянса, сенсорная фаланга (на луне), прыжковые врата (на луне), Малый транспорт, Лёгкий истребитель, Звезда смерти, Ракетная установка, Плазменная пушка, шпионаж, оружейная технология и обычное здание (робототехника) |
| `testMessagesPagePlayerOne` | Тестирование страницы сообщений со всеми включёнными папками |
| `testMessages*FolderPlayerOne` | Тестирование каждой папки сообщений (шпионаж/бой/экспедиция/альянс/личные) |
| `testBerichtBattleReportPlayerOne` | Тестирование просмотра боевого рапорта |
| `testBerichtSpyReportPlayerOne` | Тестирование просмотра шпионского рапорта |
| `testWriteMessagesPagePlayerOne` | Тестирование страницы написания сообщений |
| `testNotesPagePlayerOne` | Тестирование страницы заметок |
| `testFleetPage1PlayerOne` | Тестирование страницы флота 1 (список флотов + выбор кораблей) |
| `testFleetPage2PlayerOne` | Тестирование страницы флота 2 (POST: координаты и состав флота) |
| `testFleetPage3PlayerOne` | Тестирование страницы флота 3 (POST: список миссий и ресурсы) |
| `testFleetDispatchAttackPlayerOne` | Тестирование отправки флота (POST: миссия атаки) |
| `testFleetTemplatesPagePlayerOne` | Тестирование страницы шаблонов флота |
| `testGalaxyPagePlayerOne` | Тестирование галактики с лунами |
| `testGalaxyPageEnemySystem` | Тестирование галактики чужой системы (луна + обломки) |
| `testGalaxyPageFromMoon` | Тестирование галактики, открытой с луны |
| `testPhalanxScanPlayerOne` | Тестирование сканирования сенсорной фалангой с обнаружением флотов |
| `testPhalanxScanNoFleets` | Тестирование сканирования фалангой без флотов в цели |
| `testSprungtorPagePlayerOne` | Тестирование прыжковых врат (POST-состояние ошибки) |
| `testImperiumPagePlayerOne` | Тестирование imperium (империя), вид планет |
| `testImperiumPageMoonsPlayerOne` | Тестирование imperium, вид лун |
| `testTechtreePagePlayerOne` | Тестирование страницы techtree |
| `testTechtreeDetailsPagePlayerOne` | Тестирование страницы techtreedetails (дерево Звезды смерти) |
| `testAllianzenPagePlayerOne` | Тестирование главной страницы альянса |
| `testAllianzenMembersPagePlayerOne` | Тестирование списка участников альянса (a=4) |
| `testAllianzenRanksPagePlayerOne` | Тестирование рангов альянса (a=6) |
| `testAllianzenSettingsPagePlayerOne` | Тестирование настроек альянса (a=5) |
| `testAllianzenMemberSettingsPagePlayerOne` | Тестирование управления участниками (a=7) |
| `testBewerbungenPagePlayerOne` | Тестирование списка заявок в альянс |
| `testBewerbungenDetailPagePlayerOne` | Тестирование просмотра заявки в альянс |
| `testBewerbenPagePlayerOne` | Тестирование страницы bewerben (подача заявки в альянс) |
| `testBuddyPagePlayerOne` | Тестирование списка друзей (принятая заявка) |
| `testBuddyRequestsPagePlayerOne` | Тестирование входящих заявок в друзья (action=5) |
| `testStatisticsPagePlayerOne` | Тестирование страницы статистики |
| `testOptionsPagePlayerOne` | Тестирование страницы настроек |
| `testChangelogPagePlayerOne` | Тестирование страницы changelog |
| `testResourcesPagePlayerOne` | Тестирование страницы ресурсов |
| `testTraderPagePlayerOne` | Тестирование страницы трейдера (активное предложение) |
| `testMicropaymentPagePlayerOne` | Тестирование страницы микроплатежей |
| `testPaymentPagePlayerOne` | Тестирование страницы payment (форма купона) |
| `testPrangerPage` | Тестирование внешней страницы pranger с баном |
| `testAinfoPage` | Тестирование внешней страницы ainfo |
| `testSuchePagePlayerOne` | Тестирование страницы поиска |
| `testRenamePlanetPagePlayerOne` | Тестирование меню планеты (renameplanet) |
| `testLogoutPagePlayerOne` | Тестирование страницы выхода |
| `testAdminPage` | Тестирование главной страницы админ-панели |
| `testPlayerTwoPlanetOverview` | Тестирование переключения планет для PlayerTwo |
| `testPlayerThreePlanetOverview` | Тестирование переключения планет для PlayerThree |
| `testOverviewPageDeterministic` | Проверка детерминированности рендеринга страниц |
| `testDifferentPlayersHaveDifferentContent` | Проверка, что разные игроки имеют разное содержимое страниц |
| `testResourcesPageShowsCorrectResources` | Проверка корректности отображения ресурсов |
| `testAvailablePagesCanBeListed` | Проверка возможности получения списка страниц из router.json |
| `testEveryRouterPageHasGoldenCoverage` | Проверка, что у каждой страницы router.json есть golden-снимок |
| `testFixturePlanetCounts` | Проверка корректного количества планет на игрока |
| `testFleetDataForPlayerOne` | Проверка наличия данных флота для PlayerOne |
| `testMessagesForPlayerOne` | Проверка наличия сообщений для PlayerOne |
| `testNotesForPlayerOne` | Проверка наличия заметок для PlayerOne |

Единственная страница router.json без снимка — `allianzdepot`: она безусловно
редиректит на `infos` (`MyGoto()` → `die()`) до вывода контента и не может быть
отрендерена в процессе. Её интерфейс (форма пополнения склада альянса) покрыт
снимком `infos_ally_depot`.

### POST-тесты (issue #258)

Каждая игровая страница, обрабатывающая `method() === "POST"`, получает POST
golden-снимок (`{страница}*_post_p{индекс}.html`). Тесты отправляют те же POST-
данные, что и реальные формы, поэтому POST-обработчик страницы выполняется
против реальной базы данных:

| Метод теста | Проверяемое POST-действие |
|-------------|---------------------------|
| `testFleet1RecallPostPlayerOne` | flotten1: отзыв летящего флота атаки (`order_return`) |
| `testBuildingsShipyardBuildPostPlayerOne` | buildings (вкладка Shipyard): постройка 2 Лёгких истребителей (`fmenge[LF]`) |
| `testBuildingsDefenseBuildPostPlayerOne` | buildings (вкладка Defense): постройка 2 Ракетных установок (`fmenge[RL]`) |
| `testResourcesPostPlayerOne` | resources: установка производства всех объектов на 100% (`last{gid}`) |
| `testMessagesDeleteAllPostPlayerOne` | messages: удаление всех сообщений (`deletemessages=deleteall`) |
| `testOptionsPostPlayerOne` | options: сохранение формы настроек |
| `testPaymentCheckPostPlayerOne` | payment: проверка неизвестного кода купона (`action=check`) |
| `testRenamePlanetPostPlayerOne` | renameplanet: переименование текущей планеты (`aktion=Rename`) |
| `testSuchePlayerPostPlayerOne` | suche: поиск по имени игрока (`type=playername`) |
| `testSucheAllyPostPlayerOne` | suche: поиск по тегу альянса (`type=allytag`) |
| `testTraderCallPostPlayerOne` | trader: вызов торговца при нехватке ТМ (состояние ошибки) |
| `testTraderExchangePostPlayerOne` | trader: нулевой запрос обмена (ветка POST) |
| `testGalaxyNavigatePostPlayerOne` | galaxy: форма выбора системы (POST session/galaxy/system) |
| `testGalaxyRocketPostPlayerOne` | galaxy: запуск межпланетной ракеты (`aktion`/`anz`/`pziel`) |
| `testFleetTemplatesSavePostPlayerOne` | fleet_templates: сохранение нового шаблона флота (`mode=save`) |
| `testBewerbenSubmitPostPlayerTwo` | bewerben: подача заявки в альянс (`weiter=Submit`) |
| `testAllianzenSettingsTextPostPlayerOne` | allianzen a=11&d=1: сохранение текста альянса |
| `testAllianzenSettingsOptionsPostPlayerOne` | allianzen a=11&d=2: сохранение open/homepage/logo/имени основателя |
| `testAllianzenRanksCreatePostPlayerOne` | allianzen a=15: создание нового ранга (`newrangname`) |
| `testAllianzenMemberRankPostPlayerOne` | allianzen a=16&u=2: назначение ранга участнику (`newrang`) |
| `testAllianzenCircularPostPlayerOne` | allianzen a=17: рассылка циркулярного сообщения |
| `testAllianzenChangeTagPostPlayerOne` | allianzen a=9: смена тега альянса (`newtag`) |
| `testAllianzenChangeNamePostPlayerOne` | allianzen a=10: смена названия альянса (`newname`) |
| `testAllianzenDismissPostPlayerOne` | allianzen a=12: роспуск альянса |
| `testAllianzenTakeoverPostPlayerOne` | allianzen a=18: передача статуса основателя (`s=1&uid=2`) |
| `testEveryPostPageHasGoldenCoverage` | покрытие: у каждой страницы с `method() === "POST"` есть POST-снимок |

POST-действия, которые **всегда редиректят** (`MyGoto()` → `die()`) до вывода
контента, невозможно снять в процессе-изоляции: `bewerbungen` (принять/отклонить
заявку) и `payment` (активация купона) задокументированы в
`testEveryPostPageHasGoldenCoverage()`. POST-обработчики `admin` требуют прав
администратора и для обычных игроков фикстуры не выполняются.

### Нормализация HTML

Перед сравнением HTML нормализуется для обработки динамического контента:

| Паттерн | Замена |
|---------|--------|
| Временные метки (`YYYY-MM-DD HH:MM:SS`) | `DATE_TIME` |
| Временные метки статистики (`YYYY-MM-DD, HH:MM:SS`) | `DATE_TIME` |
| Временные метки overview (`Tue Aug 18 15:38:36`, включая однозначный час) | `DATE_TIME` |
| Временные метки (`Tue Aug 18 15:38:36 2026`, `Tue Aug 18 2026 15:38:36`) | `DATE_TIME` |
| Временные метки (`DD.MM.YYYY HH:MM:SS`, `MM-DD HH:MM:SS`) | `DATE_TIME` |
| Числовые временные метки (10+ цифр) | `TIMESTAMP` |
| Обратные отсчёты (`pp="480"`, `ss=500;`, `g = 60;`, `title='540'star=...` в списке событий) | `SECONDS` |
| Числа с плавающей точкой | `FLOAT` |
| `planet_id=X` / `player_id=X` / `fleet_id=X` / `cp=X` / `spid=X` | `ID` |
| `session=XXXX` | `session=SESSION` |
| `lastpeek=X` | `lastpeek=TIMESTAMP` |
| Несколько пробелов | Один пробел |

### Архитектура

```
testing/
├── GoldenPagesTest.php      # Основной класс тестов (каждый тест в отдельном процессе)
├── PageRenderer.php          # Рендеринг настоящих игровых страниц через реальный DB-слой
├── FixtureBuilder.php        # Создание тестовой вселенной через in-memory движок (mysqlite)
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
- Каждая страница, обрабатывающая `method() === "POST"`, рендерится через
  `PageRenderer::withPost()` (issue #258), а не только POST-only страницы флота
  (flotten2/flotten3/flottenversand/sprungtor). POST-страницы, чей обработчик
  всегда редиректит (`MyGoto()` → `die()`), завершают дочерний тестовый процесс
  и не могут быть отрендерены вовсе — они задокументированы в
  `testEveryPostPageHasGoldenCoverage()`.
- Строка альянса в фикстуре задаёт `nextrank`/`tag_until`/`name_until`/`old_tag`/
  `old_name` как в `CreateAlly()` (game/core/ally.php): `AddRank()` (POST рангов
  альянса) возвращает `$ally['nextrank']`, а проверки смены тега/имени сравнивают
  `$now < $ally['tag_until']` / `$now < $ally['name_until']`.
- В фикстуре события очереди флотов используют `start = now - 60 с`, чтобы
  антиспам-проверка flottenversand (`abs(time() - start) < 1`) не делала редирект.
- В `game/pages/flotten1.php` обломки исключены из колонки владельца цели
  (защита от null-пользователя), на что указал снимок миссии переработки.
