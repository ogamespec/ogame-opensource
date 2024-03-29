# Introduction #

Add your content here.

# Типы планет #

| **Тип** | **Описание** | **Примечания** |
|:-----------|:---------------------|:-------------------------|
|0 |Луна|По отдельности от планеты существовать не может|
|1 |Планета|Картинка планеты зависит от ID|
|10000|Поле обломков|Исчезает в понедельник в 1:10 по серверу, если на/от него не летит ни одного флота и если там 0 ресурсов.|
|10001|Уничтоженная планета|Существует 1 сутки (24 часа) + остаток времени до 01-10 следующего дня|
|10002|Фантом колонизации|Существует во время полёта колонизатора |
|10003|Уничтоженная луна|Существует как Уничтоженная планета|
|20000|Бесконечные дали (для экспедиций)|Только на позициях 16 |

# Размер новой колоний #

Диаметр планеты задается следующим случайным распределением :

| **Позиция** | **Формула** |
|:-------------------|:-------------------|
|1-3| DIAM = RAND (50, 120) `*` 72|
|4-6| DIAM = RAND (50, 150) `*` 120|
|7-9| DIAM = RAND (50, 120) `*` 120|
|10-12| DIAM = RAND (50, 120) `*` 96|
|13-15| DIAM = RAND (50, 150) `*` 96|

Диаметр Главной планеты всегда 12800 км.

# База Данных #

Структура таблицы `planets` в базе данных игры :

| **столбец** | **SQL тип** | **описание** |
|:-------------------|:---------------|:---------------------|
| planet\_id| INT AUTO\_INCREMENT PRIMARY KEY| Порядковый номер, начинается с 10000|
| name| CHAR(20)| Название планеты|
| type| INT| тип планеты (см. выше) |
| g,s,p| INT| Координаты где расположена планета |
| owner\_id| INT| Порядковый номер пользователя-владельца|
| diameter| INT| Диаметр планеты|
| temp| INT| Минимальная температура|
| fields| INT| Количество застроенных полей|
| maxfields| INT| Максимальное количество полей|
| date| INT UNSIGNED| Дата создания time()|
| bXX| INT| Уровень постройки XX|
| dXX| INT| Количество оборонительных сооружений XX|
| fXX| INT| Количество флота каждого типа XX|
| m, k, d| DOUBLE| Металла, кристалла, дейтерия|
| mprod, kprod, dprod| DOUBLE| Процент выработки шахт металла, кристалла, дейтерия|
| sprod, fprod, ssprod| DOUBLE| Процент выработки солнечной электростанции, термояда и солнечных спутников|
| lastpeek| INT UNSIGNED| Время последнего обновления состояния планеты time()|
| lastakt| INT UNSIGNED| Время последней активности time()|
| gate_until| INT UNSIGNED| Время остывания ворот time()|
|remove|INT UNSIGNED|Время удаления планеты (0 - не удалять) time()|
