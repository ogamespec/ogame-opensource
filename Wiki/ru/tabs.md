# Таблицы БД

Таблицы создаются при установке вселенной (game/install.php).

Перед работой с БД, необходимо выполнить следующие заклинания MySQL:

```php
    dbquery("SET NAMES 'utf8';");
    dbquery("SET CHARACTER SET 'utf8';");
    dbquery("SET SESSION collation_connection = 'utf8_general_ci';");
```

Что они значат вы можете подробнее погуглить или спросить у чат жпт, но в целом они нужны для настройки кодировки строковых переменных (использовать универсальную кодировку utf8). Вся остальная игра также заточена под utf8.

## Префикс таблиц

Всем таблицам добавляется префикс из файла конфигурации config.php - $db_prefix. Сделано это для удобства на случай, если в одной БД будут находиться таблицы нескольких вселенных.

## Что нужно делать если хочется изменить формат таблиц

Все разработчики веба знают, что менять таблицы БД это тот ещё гимор. В целом резать "по живому" достаточно болезненно, тем более для такого развитого проекта.

Процедура:
- Внести все необходимые модификации столбцов и типов в скрипт install_tabs.php
- Добавить начальную установку автоинкрементов в install.php
- Добавить LOCK таблицы в db.php (LockTables)
- Если не предполагается чистая переустановка Вселенной, то тут нужно руками подправить столбцы на живой БД (ALTER TABLE + ADD COLUMN), используя phpMyAdmin или другой подобный инструмент. Крайне рекомендуется поставить Вселенную на паузу (freeze=1).

## Как часто меняются таблицы?

Ну... сейчас происходит более менее плавное развитие проекта. Хочется сделать небольшой рефакторинг, поэтому готовьтесь к небольшой перетасовке таблиц.

После этого вряд ли будут существенные пертрубации, т.к. все механики и фичи версии 0.84 практически реализованы.

## Похожи ли таблицы на таблицы из оригинальной игры?

Исходников версии 0.84 не утекало, поэтому сложно сказать насколько таблицы похожи на то что есть в оригинальной игре.

Но просто по большей части всё это выглядит как black box, поэтому особой разницы нет. Например, если у планеты есть имя и координаты G:S:P, то они очевидно хранятся в соответствующих столбцах таблицы "planets". И в остальном всё будет подобно.

Иными словами - нет причин переживать, что таблицы устроены немного "неоригинально". Всё равно со стороны клиента это не имеет смысла, главное чтобы все механики игры были соблюдены.

## Настройки и состояние Вселенной (uni)

|Столбец|Тип|Описание|
|---|---|---|
|num|INT PRIMARY KEY|Номер вселенной будет указан в заголовке окна и над главным меню в игре |
|speed|FLOAT|Ускорение влияет на скорость добычи ресурсов, длительность построек и проведение исследований, минимальную длительность Режима Отпуска. |
|fspeed|FLOAT|Ускорение влияет на скорость летящих флотов |
|galaxies|INT|Количество галактик |
|systems|INT|Количество систем |
|maxusers|INT|Максимальное количество аккаунтов. После достижения этого значения регистрация закрывается до тех пор, пока не освободится место. |
|acs|INT|Максимальное количество приглашенных игроков для Совместной атаки (исключая себя). Максимальное количство флотов в САБ вычисляется по формуле N^2, где N - количетсво участников. При N=0 САБ отключен. |
|fid|INT|Флот в Обломки. Указанное количество процентов флота выпадает в виде обломков. Если указано 0, то ФВО отключено. |
|did|INT|Оборона в Обломки. Указанное количество процентов обороны выпадает в виде обломков. Если указано 0, то ОВО отключено. |
|rapid|INT|Скорострел |
|moons|INT|Луны и Звезды Смерти |
|defrepair|INT|Процент восстановления обороны (напр. 70) |
|defrepair_delta|INT|Разброс процента восстановления обороны (напр. +/- 10) |
|usercount|INT|Количество игроков во вселенной |
|freeze|INT|1: остановить время во вселенной |
|news1|TEXT|1й заголовок новостей |
|news2|TEXT|2й заголовок новостей |
|news_until|INT UNSIGNED|Дата окончания новости time() |
|startdate|INT UNSIGNED|Дата открытия вселенной time() |
|battle_engine|TEXT|Путь к боевому движку |
|lang|CHAR(4)|Язык, используемый для вселенной. Раньше была настройка для каждого пользователя, но потом сделал глобально. |
|hacks|INT|Счётчик попыток взлома игры. Сбрасывает при релогине. |
|ext_board|TEXT|Внешняя ссылка на Форум. Если строка пустая, то пункт в меню не показывается.|
|ext_discord|TEXT|Внешняя ссылка на Дискорд. Если строка пустая, то пункт в меню не показывается.|
|ext_tutorial|TEXT|Внешняя ссылка на Туториал. Если строка пустая, то пункт в меню не показывается.|
|ext_rules|TEXT|Внешняя ссылка на Правила. Если строка пустая, то пункт в меню не показывается.|
|ext_impressum|TEXT|Внешняя ссылка на Импрессум ("О нас"). Если строка пустая, то пункт в меню не показывается.|
|php_battle|INT|1: Использовать запасной боевой движок на PHP (battle_engine.php) вместо реализации на C.|
|feedage|INT|период обновления RSS(Atom) в минутах, по умолчанию 60|

## Пользователи (users)

|Столбец|Тип|Описание|
|---|---|---|
|player_id|INT AUTO_INCREMENT PRIMARY KEY|Порядковый номер пользователя, начинается со 100000 | 
|regdate|INT UNSIGNED|Дата регистрации аккаунта time()| 
|ally_id|INT|Номер альянса в котором состоит игрок (0 - без альянса) | 
|joindate|INT UNSIGNED|Дата вступления в альянс time()| 
|allyrank|INT|Ранг игрока в альянсе | 
|session|CHAR(12)|Сессия для ссылок | 
|private_session|CHAR(32)|Приватная сессия для кукисов | 
|name|CHAR(20)|Имя пользователя lower-case для сравнения | 
|oname|CHAR(20)|Имя пользователя оригинальное | 
|name_changed|INT|Имя пользователя изменено? (1 или 0) | 
|**Q** name_until|INT UNSIGNED|Когда можно поменять имя пользователя в следующий раз time()| 
|password|CHAR(32)|MD5-хеш пароля и секретного слова | 
|temp_pass|CHAR(32)|MD5-хеш пароля для восстановления и секретного слова | 
|pemail|CHAR(50)|Постоянный почтовый адрес | 
|email|CHAR(50)|Временный почтовый адрес |
|email_changed|INT|Временный почтовый адрес изменен | 
|**Q** email_until|INT UNSIGNED|Когда заменить постоянный email на временный time()| 
|disable|INT|Аккаунт поставлен на удаление | 
|**Q** disable_until|INT UNSIGNED|Когда можно удалить аккаунт time()| 
|vacation|INT|Аккаунт в режиме отпуска | 
|vacation_until|INT UNSIGNED|Когда можно выключить режим отпуска time()| 
|banned|INT|Аккаунт заблокирован | 
|**Q** banned_until|INT UNSIGNED|Время окончания блокировки time()| 
|noattack|INT|Запрет на атаки | 
|**Q** noattack_until|INT UNSIGNED|Когда заканчивается запрет на атаки time()|
|lastlogin|INT UNSIGNED|Последняя дата входа в игру | 
|lastclick|INT UNSIGNED|Последний щелчок мышкой, для определения активности игрока | 
|ip_addr|CHAR(15)|IP адрес пользователя | 
|validated|INT|Пользователь активирован. Если пользователь не активирован, то ему запрещено посылать игровые сообщения и заявки в альянсы. | 
|validatemd|CHAR(32)|Код активации | 
|hplanetid|INT|Порядковый номер Главной планеты | 
|admin|INT|0 - обычный игрок, 1 - оператор, 2 - администратор | 
|sortby|INT|Порядок сортировки планет: 0 - порядку колонизации, 1 - координатам, 2 - алфавиту | 
|sortorder|INT|Порядок: 0 - по возрастанию, 1 - по убыванию |
|skin|CHAR(80)|Путь для скина (CHAR(80)). Получается путем слепления пути к хосту и названием скина, но длина строки не более 80 символов. | 
|useskin|INT|Показывать скин, если 0 - то показывать скин по умолчанию | 
|deact_ip|INT|Выключить проверку IP | 
|maxspy|INT|Кол-во шпионских зондов (1 по умолчанию, 0...99) | 
|maxfleetmsg|INT|Максимальные сообщения о флоте в Галактику (3 по умолчанию, 0...99, 0=1) | 
|lang|CHAR(4)|Язык интерфейса игры | 
|aktplanet|INT|Текущая выбранная планета |
|dm|INT UNSIGNED|Покупная ТМ | 
|dmfree|INT UNSIGNED|ТМ найденная в экспедиции |
|sniff|INT|Включить слежение за историей переходов (Админка) |
|debug|INT|Включить отображение отладочной информации |
|trader|INT|0 - скупщик не найден, 1 - скупщик покупает металл, 2 - скупщик покупает кристалл, 3 - скупщик покупает дейтерий | 
|rate_m|DOUBLE|курсы обмена скупщика ( например 3.0 : 1.8 : 0.6 ) |
|rate_k|DOUBLE| |
|rate_d|DOUBLE| |
|score1,2,3|BIGINT,INT,INT|Очки за постройки, флот, исследования | 
|place1,2,3|INT,INT,INT|Место за постройки, флот, исследования | 
|oldscore1,2,3|BIGINT,INT,INT|Старые очки за постройки, флот, исследования | 
|oldplace1,2,3|INT,INT,INT|старое место за постройки, флот, исследования | 
|scoredate|INT UNSIGNED|Время сохранения старой статистики time()|
|rXXX|INT|Уровень исследования XXX |
|flags|INT UNSIGNED|Флаги пользователя. Полный список ниже (USER_FLAG). Не сразу додумался до этой идеи, некоторые переменные также можно сделать флагами|
|feedid|CHAR(32)| feed id (eg 5aa28084f43ad54d9c8f7dd92f774d03) |
|lastfeed|INT UNSIGNED | Время последнего обновления Feed timestamp ()|
|com_until|INT UNSIGNED | заканчивается офицер: Командир timestamp ()|
|adm_until|INT UNSIGNED | заканчивается офицер: Адмирал timestamp ()|
|eng_until|INT UNSIGNED | заканчивается офицер: Инженер timestamp ()|
|geo_until|INT UNSIGNED | заканчивается офицер: Геолог timestamp ()|
|tec_until|INT UNSIGNED | заканчивается офицер: Технократ timestamp ()|

**Q** - для обработки этого события используется задание в очереди задач.

```php
// Маска флагов (свойство flags)
const USER_FLAG_SHOW_ESPIONAGE_BUTTON = 0x1;    // 1: Отображать иконку "Шпионаж"" в галактике
const USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON = 0x2;       // 1: Отображать иконку "Написать сообщение" в галактике
const USER_FLAG_SHOW_BUDDY_BUTTON = 0x4;        // 1: Отображать иконку "Предложение стать другом" в галактике
const USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON = 0x8;    // 1: Отображать иконку "Ракетная атака" в галактике
const USER_FLAG_SHOW_VIEW_REPORT_BUTTON = 0x10;     // 1: Отображать иконку "Просмотреть сообщение" в галактике
const USER_FLAG_DONT_USE_FOLDERS = 0x20;        // 1: Не сортировать сообщения по папкам в режиме Командира
const USER_FLAG_PARTIAL_REPORTS = 0x40;         // 1: Разведданные показывать частично
const USER_FLAG_FOLDER_ESPIONAGE = 0x100;           // Message Filter. 1: Show spy reports (pm=1)
const USER_FLAG_FOLDER_COMBAT = 0x200;              // Message Filter. 1: Show battle reports & missile attacks (pm=2)
const USER_FLAG_FOLDER_EXPEDITION = 0x400;          // Message Filter. 1: Show expedition results (pm=3)
const USER_FLAG_FOLDER_ALLIANCE = 0x800;            // Message Filter. 1: Show alliance messages (pm=4)
const USER_FLAG_FOLDER_PLAYER = 0x1000;             // Message Filter. 1: Show private messages (pm=0)
const USER_FLAG_FOLDER_OTHER = 0x2000;              // Message Filter. 1: Show all other messages (pm=5)
const USER_FLAG_HIDE_GO_EMAIL = 0x4000;                 // Show an in-game message icon instead of the operator's email (not all operators may like to publish their email)
const USER_FLAG_FEED_ENABLE = 0x8000;               // 1: feed enabled
const USER_FLAG_FEED_ATOM = 0x10000;                // 0 - use RSS format, 1 - use Atom format
```

## Планеты (planets)

|Столбец|Тип|Описание|
|---|---|---|
|planet_id|INT AUTO_INCREMENT PRIMARY KEY|Порядковый номер, начинается с 10000 | 
|name|CHAR(20)|Название планеты | 
|type|INT|тип планеты, 0 - луна, 1 - планета, 10000 - поле обломков, 10001 - уничтоженная планета, 10002 - фантом для колонизации, 10003 - уничтоженная луна, 10004 - покинутая колония, 20000 - бесконечные дали | 
|g|INT|координаты где расположена планета (Галактика) | 
|s|INT|координаты где расположена планета (Система) | 
|p|INT|координаты где расположена планета (Позиция) | 
|owner_id|INT|Порядковый номер пользователя-владельца | 
|diameter|INT|Диаметр планеты | 
|temp|INT|Минимальная температура | 
|fields|INT|Количество застроенных полей | 
|maxfields|INT|Максимальное количество полей | 
|date|INT UNSIGNED|Дата создания | 
|bXXX|INT|Уровень постройки XXX | 
|dXXX|INT|Количество оборонительных сооружений XXX| 
|fXXX|INT|Количество флота каждого типа XXX | 
|m|DOUBLE|Металла | 
|k|DOUBLE|кристалла | 
|d|DOUBLE|дейтерия | 
|mprod|DOUBLE|Процент выработки шахты металла (0...1)| 
|kprod|DOUBLE|Процент выработки шахты кристалла (0...1)| 
|dprod|DOUBLE|Процент выработки шахты дейтерия (0...1)| 
|sprod|DOUBLE|Процент выработки солнечной электростанции (0...1)| 
|fprod|DOUBLE|Процент выработки термояда (0...1)| 
|ssprod|DOUBLE|Процент выработки солнечных спутников (0...1)| 
|lastpeek|INT UNSIGNED|Время последнего обновления состояния планеты | 
|lastakt|INT UNSIGNED|Время последней активности time()| 
|gate_until|INT UNSIGNED|Время остывания  ворот time()| 
|remove|INT UNSIGNED|Время удаления планеты (0 - не удалять) time()|

## Альянсы (ally)

|Столбец|Тип|Описание|
|---|---|---|
|ally_id|INT AUTO_INCREMENT PRIMARY KEY|Порядковый номер альянса | 
|tag|TEXT|Аббревиатура альянса, 3-8 символов | 
|name|TEXT|Название альянса, 3-30 символов | 
|owner_id|INT|ID основателя | 
|homepage|TEXT|URL домашней страницы | 
|imglogo|TEXT|URL картинки логотипа | 
|open|INT|0 - заявки запрещены (набор в альянс закрыт), 1 - заявки разрешены. | 
|insertapp|INT| | 
|exttext|TEXT|Внешний текст | 
|inttext|TEXT|Внутренний текст | 
|apptext|TEXT|Текст заявки | 
|nextrank|INT|Порядковый номер следующего ранга | 
|old_tag|TEXT| | 
|old_name|TEXT| | 
|tag_until|INT UNSIGNED| | 
|name_until|INT UNSIGNED| |
|score1,2,3|BIGINT UNSIGNED,INT UNSIGNED,INT UNSIGNED| | 
|place1,2,3|INT,INT,INT| | 
|oldscore1,2,3|BIGINT UNSIGNED,INT UNSIGNED,INT UNSIGNED| | 
|oldplace1,2,3|INT,INT,INT| | 
|scoredate|INT UNSIGNED| |

## Ранги в альянсе (allyranks)

|Столбец|Тип|Описание|
|---|---|---|
|rank_id|INT|Порядковый номер ранга | 
|ally_id|INT|ID альянса, которому принадлжит ранг | 
|name|TEXT|Название ранга | 
|rights|INT|Права (OR маска) |

## Заявки в альянс (allyapps)

|Столбец|Тип|Описание|
|---|---|---|
|app_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|ally_id|INT| | 
|player_id|INT| | 
|text|TEXT| | 
|date|INT UNSIGNED| |

## Друзья (buddy)

|Столбец|Тип|Описание|
|---|---|---|
|buddy_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|request_from|INT| | 
|request_to|INT| | 
|text|TEXT| | 
|accepted|INT| |

## Сообщения (messages)

|Столбец|Тип|Описание|
|---|---|---|
|msg_id|INT AUTO_INCREMENT PRIMARY KEY|Порядковый номер сообщения| 
|owner_id|INT|Порядковый номер пользователя которому принадлежит сообщение|
|pm|INT|Тип сообщения, 0: личное сообщение (можно пожаловаться оператору), ... см. ниже|
|msgfrom|TEXT|От кого, HTML|
|subj|TEXT|Тема, HTML, может быть текст, может быть ссылка на доклад|
|text|TEXT|Текст сообщения, HTML|
|shown|INT|0 - новое сообщение, 1 - показанное.|
|date|INT UNSIGNED|Дата сообщения|
|planet_id|INT|Порядковый номер планеты/луны. Используется для сообщений шпионажа, чтобы отобразить общие шпионские доклады в галактике|

Типы сообщений (pm):
- 0: личное сообщение
- 1: шпионский доклад
- 2: ссылка на боевой доклад
- 3: сообщение из экспедиции
- 4: альянс
- 5: прочие
- 6: текст боевого доклада

## Заметки (notes)

|Столбец|Тип|Описание|
|---|---|---|
|note_id|INT AUTO_INCREMENT PRIMARY KEY|Порядковый номер заметки | 
|owner_id|INT|ID пользователя | 
|subj|TEXT|Тема заметки | 
|text|TEXT|Текст заметки | 
|textsize|INT|Размер текста заметки | 
|prio|INT|Приоритет (0: Неважно (зеленый), 1: Так себе (желтый), 2: Важно (красный) ) | 
|date|INT UNSIGNED|Дата создания/редактирования заметки |

## Ошибки (errors)

|Столбец|Тип|Описание|
|---|---|---|
|error_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|ip|TEXT| | 
|agent|TEXT| | 
|url|TEXT| | 
|text|TEXT| | 
|date|INT UNSIGNED| |

## Репорты пользователей на мат (reports)

|Столбец|Тип|Описание|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT|Хозяин сообщения (тот кто репортит)| 
|msg_id|INT|ID исходного сообщения |
|msgfrom|TEXT|От кого, HTML -- копируется из исходного сообщения |
|subj|TEXT|Тема, HTML, может быть текст, может быть ссылка на доклад -- копируется из исходного сообщения |
|text|TEXT|Текст сообщения, HTML -- копируется из исходного сообщения |
|date|INT UNSIGNED|Дата сообщения -- копируется из исходного сообщения |

## Отладочные сообщения (debug)

|Столбец|Тип|Описание|
|---|---|---|
|error_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|ip|TEXT| | 
|agent|TEXT| | 
|url|TEXT| | 
|text|TEXT| | 
|date|INT UNSIGNED| |

## История переходов (browse)

|Столбец|Тип|Описание|
|---|---|---|
|log_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|url|TEXT| | 
|method|TEXT| | 
|getdata|TEXT| | 
|postdata|TEXT| | 
|date|INT UNSIGNED| |

## Очередь событий (queue)

|Столбец|Тип|Описание|
|---|---|---|
|task_id|INT AUTO_INCREMENT PRIMARY KEY|уникальный номер задания | 
|owner_id|INT|номер пользователя которому принадлежит задание | 
|type|CHAR(20)|тип задания, каждый тип имеет свой обработчик| 
|sub_id|INT|дополнительный номер, разный у каждого типа задания, например для постройки - ID планеты, для задания флота - ID флота | 
|obj_id|INT|дополнительный номер, разный у каждого типа задания, например для постройки - ID здания | 
|level|INT|уровень постройки / количество заказанных единиц на верфи | 
|start|INT UNSIGNED|время начала задания | 
|end|INT UNSIGNED|время окончания задания | 
|prio|INT|приоритет события, используется для событий, которые заканчиваются в одно и тоже время, чем выше приоритет, тем раньше выполнится событие |

## Очередь построек (buildqueue)

|Столбец|Тип|Описание|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY|Порядковый номер, начинается с 1 | 
|owner_id|INT|ID пользователя | 
|planet_id|INT|ID планеты | 
|list_id|INT|порядковый номер внутри очереди | 
|tech_id|INT|ID постройки | 
|level|INT|целевой уровень | 
|destroy|INT|1 - снести, 0 - построить | 
|start|INT UNSIGNED|время запуска постройки | 
|end|INT UNSIGNED|время окончания строительства |

## Флот (fleet)

|Столбец|Тип|Описание|
|---|---|---|

|fleet_id|INT AUTO_INCREMENT PRIMARY KEY|Порядковый номер флота в таблице | 
|owner_id|INT|Номер пользователя, которому принадлежит флот | 
|union_id|INT|Номер союза, в котором летит флот | 
|m,k,d|DOUBLE,DOUBLE,DOUBLE|Перевозимый груз (металл/кристалл/дейтерий) | 
|fuel|INT|Загруженное топливо на полёт (дейтерий) | 
|mission|INT|Тип задания | 
|start_planet|INT|Старт | 
|target_planet|INT|Финиш | 
|flight_time|INT|Время полёта в одну сторону в секундах | 
|deploy_time|INT|Время удержания флота в секундах |
|ipm_amount|INT|Количество межлпланетных ракет | 
|ipm_target|INT|id цели для межпланетных ракет, 0 - все | 
|shipXXX|INT|количество кораблей каждого типа | 

## САБы (union)

|Столбец|Тип|Описание|
|---|---|---|
|union_id|INT AUTO_INCREMENT PRIMARY KEY|ID союза | 
|fleet_id|INT|ID головного флота САБа (исходной Атаки) | 
|target_player|INT| | 
|name|CHAR(20)|название союза. по умолчанию: "KV" + число | 
|players|TEXT|ID приглашенных игроков, через запятую |

## Данные для боевого движка (battledata)

:warning: deprecated.

|Столбец|Тип|Описание|
|---|---|---|
|battle_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|source|TEXT| | 
|title' => 'TEXT| | 
|report' => 'TEXT| | 
|date|INT UNSIGNED| |

## Логи полётов (fleetlogs)

|Столбец|Тип|Описание|
|---|---|---|
|log_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|target_id|INT| | 
|union_id|INT| | 
|pm|DOUBLE| | 
|pk|DOUBLE| | 
|pd|DOUBLE| | 
|m|DOUBLE| | 
|k|DOUBLE| | 
|d|DOUBLE| | 
|fuel|INT| | 
|mission|INT| | 
|flight_time|INT| | 
|deploy_time|INT| | 
|start|INT UNSIGNED| | 
|end|INT UNSIGNED| |
|origin_g|INT| | 
|origin_s|INT| | 
|origin_p|INT| | 
|origin_type|INT| | 
|target_g|INT| | 
|target_s|INT| | 
|target_p|INT| | 
|target_type|INT| | 
|ipm_amount|INT| | 
|ipm_target|INT| | 
|shipXXX|INT| | 

## Логи IP (iplogs)

|Столбец|Тип|Описание|
|---|---|---|
|log_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|ip|CHAR(16)| | 
|user_id|INT| | 
|reg|INT| | 
|date|INT UNSIGNED| |

## Столб позора (pranger)

|Столбец|Тип|Описание|
|---|---|---|
|ban_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|admin_name|CHAR(20)| | 
|user_name|CHAR(20)| | 
|admin_id|INT| | 
|user_id|INT| | 
|ban_when|INT UNSIGNED| | 
|ban_until|INT UNSIGNED| | 
|reason|TEXT| |

## Настройки экспедиции (exptab)

|Столбец|Тип|Описание|
|---|---|---|
|chance_success|INT| | 
|depleted_min|INT| | 
|depleted_med|INT| | 
|depleted_max|INT| | 
|chance_depleted_min|INT| | 
|chance_depleted_med|INT| | 
|chance_depleted_max|INT| |
|chance_alien|INT| | 
|chance_pirates|INT| | 
|chance_dm|INT| | 
|chance_lost|INT| | 
|chance_delay|INT| | 
|chance_accel|INT| | 
|chance_res|INT| | 
|chance_fleet|INT| |

## Стандартные флоты (template)

|Столбец|Тип|Описание|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|name|CHAR(30)| | 
|date|INT UNSIGNED| |
|shipXXX|INT| | 

## Переменные бота (botvars)

|Столбец|Тип|Описание|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| |
|owner_id|INT| |
|var|TEXT| |
|value|TEXT| |

## Логи действий пользователей и операторов (userlogs)

|Столбец|Тип|Описание|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| |
|owner_id|INT| |
|date|INT UNSIGNED| |
|type|TEXT| |
|text|TEXT| |

## Стратегии бота (botstrat)

|Столбец|Тип|Описание|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| |
|name|TEXT| |
|source|TEXT| |
