<?php

// Верфь, Оборона и Исследования.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

// Обработка POST-запросов.
if ( method () === "POST" )
{
    print_r ( $_POST );
}

// Обработка GET-запросов.
if ( method () === "GET" )
{
	if ( $_GET['mode'] === "Forschung" ) {
		$result = GetResearchQueue ( $GlobalUser['player_id'] );
		$resqueue = dbarray ($result);
		if ( $resqueue == null )		// Исследование не ведется (запустить)
		{
			if ( key_exists ( 'bau', $_GET ) ) StartResearch ( $GlobalUser['player_id'], $aktplanet['planet_id'], $_GET['bau'] );
		}
		else	// Ведется исследования (отменить)
		{
			if ( key_exists ( 'unbau', $_GET ) ) StopResearch ( $GlobalUser['player_id'], $_GET['unbau'] );
		}
	}
}

PageHeader ("buildings");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

echo "<title> \n";
echo "Постройки#Gebaeude\n";
echo "</title> \n";
echo "<script type=\"text/javascript\"> \n\n";
echo "function setMax(key, number){\n";
echo "    document.getElementsByName('fmenge['+key+']')[0].value=number;\n";
echo "}\n";
echo "</script> \n";

if ( $_GET['mode'] === "Verteidigung" || $_GET['mode'] === "Flotte" ) {
    echo "<form action=index.php?page=buildings&session=$session&mode=".$_GET['mode']." method=post>";
}

// Краткое описание

$shortdesc[106] = "С помощью этой технологии добываются данные о других планетах.";
$shortdesc[108] = "С увеличением мощности компьютеров можно командовать всё большим количеством флотов. Каждый уровень компьютерной технологии увеличивает максимальное количество флотов на один.";
$shortdesc[109] = "Оружейная технология делает системы вооружения эффективней. Каждый уровень увеличивает мощность вооружения войсковых частей на 10% от основной мощности.";
$shortdesc[110] = "Эта технология делает щиты кораблей и оборонительных сооружений эффективней. Каждый уровень повышает эффективность щитов на 10% от основной мощности.";
$shortdesc[111] = "Специальные сплавы улучшают броню космических кораблей. Так, устойчивость брони может увеличиваться с каждым уровнем на 10% .";
$shortdesc[113] = "Обладание различными видами энергии необходимо для многих новых технологий.";
$shortdesc[114] = "Путём сплетения 4-го и 5-го измерения стало возможным исследовать новый более экономный и эффективный двигатель.";
$shortdesc[115] = "Дальнейшее развитие этих двигателей делает некоторые корабли быстрее, однако каждый уровень повышает скорость лишь на 10%.";
$shortdesc[117] = "Импульсный двигатель основывается на принципе отдачи. Дальнейшее развитие этих двигателей делает некоторые корабли быстрее, однако с каждым уровнем повышает скорость лишь на 20%.";
$shortdesc[118] = "Выгибает пространство вокруг корабля. Дальнейшее развитие этих двигателей делает некоторые корабли быстрее, каждый уровень повышает скорость на 30%.";
$shortdesc[120] = "Благодаря фокусированию света возникает луч, который при попадании на объект наносит ему повреждения.";
$shortdesc[121] = "Поистине смертоносный наводимый луч из ускоренных ионов. При попадании на какой-либо объект они наносят огромный ущерб.";
$shortdesc[122] = "Дальнейшее развитие ионной технологии, которая ускоряет не ионы, а высокоэнергетическую плазму. Она оказывает опустошительное действие при попадании на какой-либо объект.";
$shortdesc[123] = "Посредством этой сети учёные с различных планет могут обмениваться информацией.";
$shortdesc[124] = "Теперь корабли можно оснащать исследовательским модулем, обеспечивающим обработку собранных данных  в условиях длительных полётов.";
$shortdesc[199] = "Путём запуска концентрированного заряда частиц гравитона можно создавать искусственное гравитационное поле, благодаря которому можно уничтожать корабли или даже луны.";

$shortdesc[202] = "Малый транспорт - это маневренный корабль, который может быстро транспортировать сырье на другие планеты.";
$shortdesc[203] = "Дальнейшее развитие малых транспортов позволило создать корабли, обладающие большей вместительностью и, благодаря более развитому двигателю, способными передвигаться быстрее, чем малый транспорт, до тех пор, пока на малых транспортах не устанавливаются импульсные двигатели";
$shortdesc[204] = "Лёгкий истребитель - это манёвренный корабль, который можно найти почти на каждой планете. Затраты на него не особо велики, однако щитовая мощность и вместимость очень малы.";
$shortdesc[205] = "Дальнейшее развитие лёгкого истребителя, он лучше защищён и обладает большей силой атаки.";
$shortdesc[206] = "Крейсеры почти втрое сильней защищены, чем тяжёлые истребители, а по огневой мощи они превосходят тяжёлые истребители более, чем в два раза. К тому же они очень быстры.";
$shortdesc[207] = "Линкоры как правило составляют основу флота. Их тяжёлые орудия, высокая скорость и большой грузовой тоннаж делают их серьёзными противниками.";
$shortdesc[208] = "Этот корабль может колонизировать свободные планеты.";
$shortdesc[209] = "С помощью переработчика добывается сырьё из обломков.";
$shortdesc[210] = "Шпионские зонды - это маленькие манёвренные корабли, которые доставляют с больших расстояний данные о флотах и планетах.";
$shortdesc[211] = "Бомбардировщик был разработан специально для того, чтобы уничтожать планетарную защиту.";
$shortdesc[212] = "Солнечные спутники - это простые платформы из солнечных батарей, которые находятся на высокой орбите. Они собирают солнечный свет и передают энергию на наземную станцию. Солнечный спутник производит 25 энергии на этой планете.";
$shortdesc[213] = "Уничтожитель - король среди военных кораблей.";
$shortdesc[214] = "Уничтожительная мощь звезды смерти непревосходима.";
$shortdesc[215] = "Линейный крейсер специализируется на перехвате вражеских флотов.";

$shortdesc[401] = "Ракетная установка - простое и дешёвое средство обороны.";
$shortdesc[402] = "При помощи концентрированного обстрела цели фотонами можно достичь значительно больших разрушений, чем при применении обычного баллистического вооружения.";
$shortdesc[403] = "Тяжелый лазер представляет собой дальнейшее развитие лёгкого лазера.";
$shortdesc[404] = "Пушка Гаусса ускоряет многотонные заряды с гигантскими затратами энергии.";
$shortdesc[405] = "Ионное орудие направляет на цель волну ионов, которая дестабилизирует щиты и повреждает электронику.";
$shortdesc[406] = "Плазменные орудия выпускают сгустки высокотемпературной плазмы и превосходят по своему разрушительному действию даже уничтожитель.";
$shortdesc[407] = "Малый щитовой купол защищает планету и поглощает удары атаки.";
$shortdesc[408] = "Дальнейшее развитие малого щитового купола. Он может сдерживать ещё более сильные атаки на планету, поглощая значительно большее количество энергии.";
$shortdesc[502] = "Ракеты-перехватчики уничтожают атакующие межпланетные ракеты";
$shortdesc[503] = "Межпланетные ракеты уничтожают защиту противника.";

$unitab = LoadUniverse ( );
$speed = $unitab['speed'];

// ************************************************ Верфь ************************************************ 

$fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

if ( $_GET['mode'] === "Flotte" )
{
    // Проверить не строится ли Верфь или Фабрика нанитов.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['obj_id'] == 21 || $queue['obj_id'] == 15 ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>Невозможно строить ни корабли ни оборонительные сооружения, так как верфь либо фабрика нанитов усовершенствуются</font><br><br>";
    }
    echo "<table align=top><tr><td style='background-color:transparent;'>  <table width=530>          <tr> \n";
    echo "          <td class=l colspan=\"2\">Описание</td> \n";
    echo "          <td class=l><b>Кол-во</b></td> \n";
    echo "          </tr> \n\n";

    // Проверить есть ли Верфь на планете.
    if ( $aktplanet['b21'] ) {
        // Вывести объекты, которые можно построить на Верфи.
        foreach ( $fleetmap as $i => $id ) {
            if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) continue;

            echo "<tr>    			<td class=l>\n";
            echo "    			<a href=index.php?page=infos&session=$session&gid=$id>\n";
            echo "    			<img border='0' src=\"".UserSkin()."gebaeude/$id.gif\" align='top' width='120' height='120'>\n";
            echo "    			</a>\n";
            echo "    			</td>\n";
            echo "        <td class=l><a href=index.php?page=infos&session=$session&gid=$id>".$desc[$id]."</a>";
            if ($aktplanet['f'.$id]) echo "</a> (в наличии ".$aktplanet['f'.$id].")";
            $m = $k = $d = $e = 0;
            ShipyardPrice ( $id, &$m, &$k, &$d, &$e );
            echo "<br>".$shortdesc[$id]."<br>Стоимость:";
            if ($m) echo " Металл: <b>".nicenum($m)."</b>";
            if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
            if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
            if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
            $t = ShipyardDuration ( $id, $aktplanet['b21'], $aktplanet['b15'], $speed );
            echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k >";
            if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<input type=text name='fmenge[$id]' alt='".$desc[$id]."' size=6 maxlength=6 value=0 tabindex=1> ";
            echo "</td></tr>";
        }

        // Кнопка строительства.
        echo "<td class=c colspan=2 align=center><input type=submit value=\"Строить\"></td></tr>";
    }
    else {
        if (!$busy) echo "<table><tr><td class=c>Для этого необходимо построить верфь!</td></tr></table>";
    }
}


// ************************************************ Оборона ************************************************ 

$defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );

if ( $_GET['mode'] === "Verteidigung" )
{
    // Проверить не строится ли Верфь или Фабрика нанитов.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['obj_id'] == 21 || $queue['obj_id'] == 15 ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>Невозможно строить ни корабли ни оборонительные сооружения, так как верфь либо фабрика нанитов усовершенствуются</font><br><br>";
    }
    echo "<table align=top><tr><td style='background-color:transparent;'>  <table width=530>          <tr> \n";
    echo "          <td class=l colspan=\"2\">Описание</td> \n";
    echo "          <td class=l><b>Кол-во</b></td> \n";
    echo "          </tr> \n\n";

    // Проверить есть ли Верфь на планете.
    if ( $aktplanet['b21'] ) {
        // Вывести объекты, которые можно построить на Верфи.
        foreach ( $defmap as $i => $id ) {
            if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) continue;

            echo "<tr>    			<td class=l>\n";
            echo "    			<a href=index.php?page=infos&session=$session&gid=$id>\n";
            echo "    			<img border='0' src=\"".UserSkin()."gebaeude/$id.gif\" align='top' width='120' height='120'>\n";
            echo "    			</a>\n";
            echo "    			</td>\n";
            echo "        <td class=l><a href=index.php?page=infos&session=$session&gid=$id>".$desc[$id]."</a>";
            if ($aktplanet['d'.$id]) echo "</a> (в наличии ".$aktplanet['d'.$id].")";
            $m = $k = $d = $e = 0;
            ShipyardPrice ( $id, &$m, &$k, &$d, &$e );
            echo "<br>".$shortdesc[$id]."<br>Стоимость:";
            if ($m) echo " Металл: <b>".nicenum($m)."</b>";
            if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
            if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
            if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
            $t = ShipyardDuration ( $id, $aktplanet['b21'], $aktplanet['b15'], $speed );
            echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k >";
            if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<input type=text name='fmenge[$id]' alt='".$desc[$id]."' size=6 maxlength=6 value=0 tabindex=1> ";
            echo "</td></tr>";
        }
    
        // Кнопка строительства.
        echo "<td class=c colspan=2 align=center><input type=submit value=\"Строить\"></td></tr>";
    }
    else {
        if (!$busy) echo "<table><tr><td class=c>Для этого необходимо построить верфь!</td></tr></table>";
    }
}

// ************************************************ Исследования ************************************************ 

$resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );

if ( $_GET['mode'] === "Forschung" )
{
    // Проверить не строится ли Исследовательская лаборатория.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['obj_id'] == 31 ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>Проведение исследований невозможно, так как исследовательская лаборатория усовершенствуется.</font><br /><br />";
    }
    echo "<table align=top><tr><td style='background-color:transparent;'>  <table width=530>          <tr> \n";
    echo "          <td class=l colspan=\"2\">Описание</td> \n";
    echo "          <td class=l><b>Кол-во</b></td> \n";
    echo "          </tr> \n\n";

    // Проверить есть ли лаборатория на планете.
    if ( $aktplanet['b31'] ) {
        // Вывести список доступных исследований.
        foreach ( $resmap as $i => $id ) {
            if ( !ResearchMeetRequirement ($GlobalUser, $aktplanet, $id) ) continue;

            $reslab = ResearchNetwork ( $aktplanet['planet_id'], $id );

            $level = $GlobalUser['r'.$id]+1;
            echo "<tr>             <td class=l>\n";
            echo "                <a href=index.php?page=infos&session=$session&gid=$id>\n";
            echo "                <img border='0' src=\"".UserSkin()."gebaeude/$id.gif\" align='top' width='120' height='120'>\n";
            echo "                </a>\n";
            echo "                </td>\n";
            echo "        <td class=l><a href=index.php?page=infos&session=$session&gid=$id>".$desc[$id]."</a>";
            if ($GlobalUser['r'.$id]) echo "</a> (уровень ".$GlobalUser['r'.$id].")";
            $m = $k = $d = $e = 0;
            ResearchPrice ( $id, $level, &$m, &$k, &$d, &$e );
            echo "<br>".$shortdesc[$id]."<br>Стоимость:";
            if ($m) echo " Металл: <b>".nicenum($m)."</b>";
            if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
            if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
            if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
            $t = ResearchDuration ( $id, $level, $reslab, $speed );
            echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k>";
            if ($GlobalUser['r'.$id]) {
                if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo " <a href=index.php?page=buildings&session=$session&mode=Forschung&bau=$id><font color=#00FF00>Исследовать<br> уровень  $level</font></a>";
                else echo "<font color=#FF0000>Исследовать<br> уровень  $level</font>";
            }
            else {
                if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo " <a href=index.php?page=buildings&session=$session&mode=Forschung&bau=$id><font color=#00FF00> исследовать </font></a>";
                else echo "<font color=#FF0000> исследовать </font></a>";
            }
            echo "</td></tr>";
        }
    }
    else {
        if (!$busy) echo "<table><tr><td class=c>Для этого необходимо построить исследовательскую лабораторию!</td></tr></table>";
    }
}

// ***********************************************************************

echo "</table>";
if ( $_GET['mode'] === "Verteidigung" || $_GET['mode'] === "Flotte" ) echo "</form>";
echo "</table>\n";

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>