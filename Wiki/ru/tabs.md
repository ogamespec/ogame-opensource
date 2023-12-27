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
- Внести все необходимые модификации столбцов и типов в скрипт install.php
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
|num|INT PRIMARY KEY| |
|speed|FLOAT| |
|fspeed|FLOAT| |
|galaxies|INT| |
|systems|INT| |
|maxusers|INT| |
|acs|INT| |
|fid|INT| |
|did|INT| |
|rapid|INT| |
|moons|INT| |
|defrepair|INT| |
|defrepair_delta|INT| |
|usercount|INT| |
|freeze|INT| |
|news1|TEXT| |
|news2|TEXT| |
|news_until|INT UNSIGNED| |
|startdate|INT UNSIGNED| |
|battle_engine|TEXT| |
|lang|CHAR(4)| |
|hacks|INT| |
|ext_board|TEXT|Внешняя ссылка на Форум. Если строка пустая, то пункт в меню не показывается.|
|ext_discord|TEXT|Внешняя ссылка на Дискорд. Если строка пустая, то пункт в меню не показывается.|
|ext_tutorial|TEXT|Внешняя ссылка на Туториал. Если строка пустая, то пункт в меню не показывается.|
|ext_rules|TEXT|Внешняя ссылка на Правила. Если строка пустая, то пункт в меню не показывается.|
|ext_impressum|TEXT|Внешняя ссылка на Импрессум ("О нас"). Если строка пустая, то пункт в меню не показывается.|

## Пользователи (users)

|Столбец|Тип|Описание|
|---|---|---|
|player_id|INT AUTO_INCREMENT PRIMARY KEY|Порядковый номер пользователя | 
|regdate|INT UNSIGNED|Дата регистрации аккаунта | 
|ally_id|INT|Номер альянса в котором состоит игрок (0 - без альянса) | 
|joindate|INT UNSIGNED|Дата вступления в альянс | 
|allyrank|INT|Ранг игрока в альянсе | 
|session|CHAR(12)|Сессия для ссылок | 
|private_session|CHAR(32)|Приватная сессия для кукисов | 
|name|CHAR(20)|Имя пользователя lower-case для сравнения | 
|oname|CHAR(20)|Имя пользователя оригинальное | 
|name_changed|INT|Имя пользователя изменено? (1 или 0) | 
|name_until|INT UNSIGNED|Когда можно поменять имя пользователя в следующий раз | 
|password|CHAR(32)|MD5-хеш пароля и секретного слова | 
|temp_pass|CHAR(32)|MD5-хеш пароля для восстановления и секретного слова | 
|pemail|CHAR(50)|Постоянный почтовый адрес | 
|email|CHAR(50)|Временный почтовый адрес |
|email_changed|INT|Временный почтовый адрес изменен | 
|email_until|INT UNSIGNED|Когда заменить постоянный email на временный | 
|disable|INT|Аккаунт поставлен на удаление | 
|disable_until|INT UNSIGNED|Когда можно удалить аккаунт | 
|vacation|INT|Аккаунт в режиме отпуска | 
|vacation_until|INT UNSIGNED|Когда можно выключить режим отпуска | 
|banned|INT|Аккаунт заблокирован | 
|banned_until|INT UNSIGNED|Время окончания блокировки | 
|noattack|INT|Запрет на атаки | 
|noattack_until|INT UNSIGNED|Когда заканчивается запрет на атаки |
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
|scoredate|INT UNSIGNED|Время сохранения старой статистики |
|rXXX|INT|Уровень исследования XXX |

## Планеты (planets)

|Столбец|Тип|Описание|
|---|---|---|
|planet_id|INT AUTO_INCREMENT PRIMARY KEY|Порядковый номер | 
|name|CHAR(20)|Название планеты | 
|type|INT|тип планеты, если 0 - то это луна, 1 - планета, если 10000 - это поле обломков, 10001 - уничтоженная планета, 10002 - фантом для колонизации, 10003 - уничтоженная луна, 10004 - покинутая колония, 20000 - бесконечные дали | 
|g|INT|координаты где расположена планета (Галактика) | 
|s|INT|координаты где расположена планета (Система) | 
|p|INT|координаты где расположена планета (Позиция) | 
|owner_id|INT|Порядковый номер пользователя-владельца | 
|diameter|INT|Диаметр планеты | 
|temp|INT|Минимальная температура | 
|fields|INT|Количество застроенных полей | 
|maxfields|INT|Максимальное количество полей | 
|date|INT UNSIGNED|Дата создания | 
|b1|INT|Уровень постройки | 
|b2|INT| | 
|b3|INT| | 
|b4|INT| | 
|b12|INT| | 
|b14|INT| | 
|b15|INT| | 
|b21|INT| | 
|b22|INT| | 
|b23|INT| | 
|b24|INT| | 
|b31|INT| | 
|b33|INT| | 
|b34|INT| | 
|b41|INT| | 
|b42|INT| | 
|b43|INT| | 
|b44|INT| |
|d401|INT|Количество оборонительных сооружений | 
|d402|INT| | 
|d403|INT| | 
|d404|INT| | 
|d405|INT| | 
|d406|INT| | 
|d407|INT| | 
|d408|INT| | 
|d502|INT| | 
|d503|INT| |
|f202|INT|Количество флота каждого типа | 
|f203|INT| | 
|f204|INT| | 
|f205|INT| | 
|f206|INT| | 
|f207|INT| | 
|f208|INT| | 
|f209|INT| | 
|f210|INT| | 
|f211|INT| | 
|f212|INT| | 
|f213|INT| | 
|f214|INT| | 
|f215|INT| |
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
|lastakt|INT UNSIGNED|Время последней активности | 
|gate_until|INT UNSIGNED|Время остывания  ворот | 
|remove|INT UNSIGNED|Время удаления планеты (0 - не удалять) |

## Альянсы (ally)

|Столбец|Тип|Описание|
|---|---|---|
|ally_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|tag|TEXT| | 
|name|TEXT| | 
|owner_id|INT| | 
|homepage|TEXT| | 
|imglogo|TEXT| | 
|open|INT| | 
|insertapp|INT| | 
|exttext|TEXT| | 
|inttext|TEXT| | 
|apptext|TEXT| | 
|nextrank|INT| | 
|old_tag|TEXT| | 
|old_name|TEXT| | 
|tag_until|INT UNSIGNED| | 
|name_until|INT UNSIGNED| |
|score1|BIGINT UNSIGNED| | 
|score2|INT UNSIGNED| | 
|score3|INT UNSIGNED| | 
|place1|INT| | 
|place2|INT| | 
|place3|INT| |
|oldscore1|BIGINT UNSIGNED| | 
|oldscore2|INT UNSIGNED| | 
|oldscore3|INT UNSIGNED| | 
|oldplace1|INT| | 
|oldplace2|INT| | 
|oldplace3|INT| | 
|scoredate|INT UNSIGNED| |

## Ранги в альянсе (allyranks)

|Столбец|Тип|Описание|
|---|---|---|
|rank_id|INT| | 
|ally_id|INT| | 
|name|TEXT| | 
|rights|INT| |

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
|msg_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|pm|INT| | 
|msgfrom|TEXT| | 
|subj|TEXT| | 
|text|TEXT| | 
|shown|INT| | 
|date|INT UNSIGNED| |

## Заметки (notes)

|Столбец|Тип|Описание|
|---|---|---|
|note_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|subj|TEXT| | 
|text|TEXT| | 
|textsize|INT| | 
|prio|INT| | 
|date|INT UNSIGNED| |

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

|fleet_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|union_id|INT| | 
|m|DOUBLE| | 
|k|DOUBLE| | 
|d|DOUBLE| | 
|fuel|INT| | 
|mission|INT| | 
|start_planet|INT| | 
|target_planet|INT| | 
|flight_time|INT| | 
|deploy_time|INT| |
|ipm_amount|INT| | 
|ipm_target|INT| | 
|ship202|INT| | 
|ship203|INT| | 
|ship204|INT| | 
|ship205|INT| | 
|ship206|INT| | 
|ship207|INT| | 
|ship208|INT| | 
|ship209|INT| | 
|ship210|INT| | 
|ship211|INT| | 
|ship212|INT| | 
|ship213|INT| | 
|ship214|INT| | 
|ship215|INT| |

## САБы (union)

|Столбец|Тип|Описание|
|---|---|---|
|union_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|fleet_id|INT| | 
|target_player|INT| | 
|name|CHAR(20)| | 
|players|TEXT| |

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
|ship202|INT| | 
|ship203|INT| | 
|ship204|INT| | 
|ship205|INT| | 
|ship206|INT| | 
|ship207|INT| | 
|ship208|INT| | 
|ship209|INT| | 
|ship210|INT| | 
|ship211|INT| | 
|ship212|INT| | 
|ship213|INT| | 
|ship214|INT| | 
|ship215|INT| |

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
|ship202|INT| | 
|ship203|INT| | 
|ship204|INT| | 
|ship205|INT| | 
|ship206|INT| | 
|ship207|INT| | 
|ship208|INT| | 
|ship209|INT| | 
|ship210|INT| | 
|ship211|INT| | 
|ship212|INT| | 
|ship213|INT| | 
|ship214|INT| | 
|ship215|INT| |

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
