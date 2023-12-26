# База данных #

Настройки экспедиции хранятся в специальной таблице `uni_exptab`.
Настройки можно поменять через админку.

| **столбец** | **SQL тип** | **описание** |
|:-------------------|:---------------|:---------------------|
|chance\_success| INT | Шанс удачной экспедиции |
|depleted\_min| INT | см. `Счетчик посещений` |
|depleted\_med| INT | см. `Счетчик посещений` |
|depleted\_max| INT | см. `Счетчик посещений` |
|chance\_depleted\_min| INT | см. `Счетчик посещений` |
|chance\_depleted\_med| INT | см. `Счетчик посещений` |
|chance\_depleted\_max| INT | см. `Счетчик посещений` |
|chance\_alien| INT | см. `Удачная экспедиция` |
|chance\_pirates| INT | см. `Удачная экспедиция` |
|chance\_dm| INT | см. `Удачная экспедиция` |
|chance\_lost| INT | см. `Удачная экспедиция` |
|chance\_delay| INT | см. `Удачная экспедиция` |
|chance\_accel| INT | см. `Удачная экспедиция` |
|chance\_res| INT | см. `Удачная экспедиция` |
|chance\_fleet| INT | см. `Удачная экспедиция` |

# Счетчик посещений #

0 ... depleted\_min => depleted\_min+1 ... depleted\_med => depleted\_med+1 ... depleted\_max => depleted\_max+1 ... ∞

Каждый диапазон влияет на шанс получения удачного события:

0 ... depleted\_min: Шанс успешной экспедиции не уменьшается<br>
depleted_min+1 ... depleted_med: Шанс успешной экспедиции уменьшается на chance_depleted_min%<br>
depleted_med+1 ... depleted_max: Шанс успешной экспедиции уменьшается на chance_depleted_med%<br>
depleted_max+1 ... ∞: Шанс успешной экспедиции уменьшается на chance_depleted_max%<br>
<br>
<h1>Алгоритм экспедиции</h1>

<ol><li>Бросаем кубик, случайное число [0; 99] => chance<br>
</li><li>Если chance >= (chance_success+hold_time), то ничего не произошло, разворачиваем флот, иначе 3.<br>
</li><li>Бросаем кубик, случайное число [0; 99] => chance<br>
</li><li>Определяем chance_depleted в зависимости от счетчика посещений.<br>
</li><li>Если chance >= chance_depleted, то переходим на 7.<br>
</li><li>Иначе - ничего не произошло, разворачиваем флот<br>
</li><li>Удачная экспедиция.</li></ol>

<h1>Удачная экспедиция</h1>

Как бросается кубик:<br>
<ol><li>Случайное число в интервале [0; 99] = chance.<br>
</li><li>Каскадная проверка:if ( chance >= chance_alien) Встреча с чужими<br>
else if ( chance >= chance_pirates) Встреча с пиратами<br>
else if ( chance >= chance_dm) Нахождение Тёмной материи<br>
else if ( chance >= chance_lost) Потеря всего флота <br>
else if ( chance >= chance_delay) Задержка возврата экспедиции<br>
else if ( chance >= chance_accel) Ускорение возврата экспедиции<br>
else if ( chance >= chance_res) Нахождение ресурсов<br>
else if ( chance >= chance_fleet) Нахождение кораблей<br>
else Нахождение Скупщика}}}</code></pre>