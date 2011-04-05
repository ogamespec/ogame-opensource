<?php

// ========================================================================================
// Настройки Вселенной.

function Admin_Uni ()
{
    global $session;
    $unitab = LoadUniverse ();

    print_r ($unitab);

    echo "<table >\n";
    echo "<form action=\"index.php?page=admin&session=$session&mode=Uni\" method=\"POST\" >\n";
    echo "<tr><td class=c colspan=2>Настройки Вселенной ".$unitab['num']."</td></tr>\n";
    echo "<tr><th>Дата открытия</th><th>".date ("Y-m-d H:i:s", $unitab['startdate'])."</th></tr>\n";
    echo "<tr><th>Количество игроков</th><th>".$unitab['usercount']."</th></tr>\n";
    echo "<tr><th>Максимальное количество игроков</th><th><input type=\"text\" name=\"maxusers\" maxlength=\"10\" size=\"10\" value=\"".$unitab['maxusers']."\" /></th></tr>\n";
    echo "<tr><th>Количество галактик</th><th>".$unitab['galaxies']."</th></tr>\n";
    echo "<tr><th>Количество систем в галактике</th><th>".$unitab['systems']."</th></tr>\n";
    echo "<tr><th>Скорострел</th><th><input type=\"checkbox\" name=\"rapid\"  checked=checked /></th></tr>\n";
    echo "<tr><th>Луны и Звёзды Смерти</th><th><input type=\"checkbox\" name=\"moons\"  checked=checked /></th></tr>\n";
    echo "<tr><th colspan=2><input type=\"submit\" value=\"Сохранить\" /></th></tr>\n";
    echo "</form>\n";
    echo "</table>\n";
}

?>