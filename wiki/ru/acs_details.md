# Introduction #

В данной заметке будет находится любая информация, связанная с САБ.

Для начала напомню структуру используемых таблиц в БД:

Запись в очереди событий `queue` :

| **поле** | **тип** | **описание** |
|:-------------|:-----------|:---------------------|
|task\_id|INT PRIMARY KEY|уникальный номер задания|
|owner\_id|INT|номер пользователя которому принадлежит задание|
|type|CHAR(20)|тип задания, каждый тип имеет свой обработчик<br>в случае заданий флота type=Fleet<br>
<tr><td>sub_id</td><td>INT</td><td>дополнительный номер, разный у каждого типа задания<br>для задания флота - ID флота (fleet_id)</td></tr>
<tr><td>obj_id</td><td>INT</td><td>дополнительный номер, разный у каждого типа задания<br>для флота не используется</td></tr>
<tr><td>level</td><td>INT</td><td>для флота не используется</td></tr>
<tr><td>start</td><td>INT UNSIGNED</td><td>время начала задания</td></tr>
<tr><td>end</td><td>INT UNSIGNED</td><td>время окончания задания</td></tr>
<tr><td>prio</td><td>INT</td><td>приоритет события</td></tr></tbody></table>

Запись в таблице флотов <code>fleet</code> :<br>
<br>
<table><thead><th> <b>поле</b> </th><th> <b>тип</b> </th><th> <b>описание</b> </th></thead><tbody>
<tr><td>fleet_id</td><td>INT PRIMARY KEY</td><td>Порядковый номер флота в таблице</td></tr>
<tr><td>owner_id</td><td>INT</td><td>Номер пользователя, которому принадлежит флот</td></tr>
<tr><td>union_id</td><td>INT</td><td>Номер союза, в котором летит флот</td></tr>
<tr><td>m, k, d</td><td>DOUBLE</td><td>Перевозимый груз (металл/кристалл/дейтерий)</td></tr>
<tr><td>fuel</td><td>DOUBLE</td><td>Загруженное топливо на полёт (дейтерий)</td></tr>
<tr><td>mission</td><td>INT</td><td>Тип задания</td></tr>
<tr><td>start_planet</td><td>INT</td><td>ID стартовой планеты</td></tr>
<tr><td>target_planet</td><td>INT</td><td>ID целевой планеты</td></tr>
<tr><td>deploy_time</td><td>INT</td><td>Время удержания флота в секундах</td></tr>
<tr><td>shipXX</td><td>INT</td><td>количество кораблей каждого типа XX=202...215</td></tr></tbody></table>

Запись в таблице союзов <code>union</code> :<br>
<br>
<table><thead><th> <b>поле</b> </th><th> <b>тип</b> </th><th> <b>описание</b> </th></thead><tbody>
<tr><td>union_id</td><td>INT PRIMARY KEY</td><td>ID союза</td></tr>
<tr><td>fleet_id</td><td>INT</td><td>ID головного флота САБа (исходной Атаки)</td></tr>
<tr><td>name</td><td>CHAR(20)</td><td>название союза. по умолчанию: "KV" + число</td></tr>
<tr><td>players</td><td>TEXT</td><td>ID приглашенных игроков, через запятую</td></tr></tbody></table>

Каждое задание флота оформляется в виде задания для очереди событий (тип "Fleet").<br>
<br>
<h1>Создание союза</h1>

<ol><li>Создается обычная Атака (fleet_id = 10000, union_id = 0)<br>
</li><li>При нажатии на кнопку "Союз" у этого флота, добавляется новая запись в таблице union. Поле union_id у флота fleet_id = 10000 становится отличным от нуля. Поле fleet_id в записи союза также обновляется. Данная Атака становится "паровозом" САБа.</li></ol>

Общее время полёта союза = queue->end - queue->start.<br>
Прошедшее время полёта союза = queue->end - time().<br>
<br>
<h1>Добавление совместных атак в союз</h1>

<h1>Удаление союза</h1>