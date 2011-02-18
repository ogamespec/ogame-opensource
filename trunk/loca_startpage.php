<?php

require_once "loca.php";

// Конфигурация сервера на котором находятся БД LOCA.
$LocaHost = "localhost";
$LocaUser = "toor";
$LocaPass = "qwerty";
$LocaDB = "ogame";

// Язык стартовой страницы
$LocaLang = "ru";

// Создать таблицы LOCA и добавить проект OGame Startpage.
$res = loca_init ( $LocaHost, $LocaUser, $LocaPass, $LocaDB, $LocaLang, "OGame Startpage" );
if ($res == false) {
    loca_reset ( $LocaHost, $LocaUser, $LocaPass, $LocaDB );

    // Добавим проект OGame Startpage
    loca_add_project ( "OGame Startpage" );

    // "ru"
    loca_init ( $LocaHost, $LocaUser, $LocaPass, $LocaDB, "ru", "OGame Startpage" );
    {
        loca_add("MENU_START",        "Главная");
        loca_add("MENU_ABOUT",        "Про ОГейм");
        loca_add("MENU_PICTURES",    "Картинки");
        loca_add("MENU_REG",            "Присоединиться");
        loca_add("MENU_BOARD",        "Форум");

        loca_add("HOME_TITLE",  "Добро пожаловать в ОГейм");
        loca_add("HOME_TEXT1",  "<strong>ОГейм</strong> - это <strong>космическая стратегия</strong>. \n" .
              "<strong>Тысячи игроков</strong> выступают <strong>одновременно</strong> против друг друга. Для игры Вам нужен всего лишь нормальный браузер.");
        loca_add("HOME_TEXT2",  "Зарегистрируйтесь и откройте для себя фантастический мир ОГейм!");
        loca_add("HOME_BIGBUTTON",  "РЕГИСТРИРУЙТЕСЬ И ИГРАЙТЕ!");

        loca_add("ABOUT_TITLE",  "Что такое ОГейм?");
        loca_add("ABOUT_TEXT1",  "ОГейм - это игра, в которой Вы - межгалактический завоеватель.");
        loca_add("ABOUT_TEXT2",  "Вы начинаете на одной малоразвитой планете и превращаете её в <strong>мощную империю </strong>, стоящую на защите Вами с большим трудом развитых колоний.");
        loca_add("ABOUT_TEXT3",  "Создайте <strong>экономическую и военную инфраструктуру</strong> и это облегчит Вам достижение развития новейших технологий.");
        loca_add("ABOUT_TEXT4",  "<strong>Ведите войны</strong> против других империй, т.к. только в бою Вы сможете одержать победу в войне за ресурсы.");
        loca_add("ABOUT_TEXT5",  "<strong>Ведите торговлю</strong> с другими императорами и создавайте альянсы  или доставайте необходимые ресурсы при помощи торговли.");
        loca_add("ABOUT_TEXT6",  "<strong>Постройте флот</strong> для поддержания своих интересов во всей вселенной.");
        loca_add("ABOUT_TEXT7",  "<strong>Храните ресурсы</strong> под непреодолимой  планетарной защитой.");
        loca_add("ABOUT_TEXT8",  "<strong>ОГейм</strong> предлагает Вам  <strong>неограниченные возможности.</strong>");
        loca_add("ABOUT_TEXT9",  "Желаете потерроризировать соседей? Или хотите мстить за слабых?");
        loca_add("ABOUT_STORY",  "Тогда прочтите историю ОГейма");
    }
    loca_close ();

    loca_init ( $LocaHost, $LocaUser, $LocaPass, $LocaDB, $LocaLang, "OGame Startpage" );
}

?>