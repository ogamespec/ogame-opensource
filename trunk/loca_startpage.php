<?php

require_once "loca.php";

// Конфигурация сервера на котором находятся БД LOCA.
$LocaHost = "localhost";
$LocaUser = "toor";
$LocaPass = "qwerty";
$LocaDB = "startpage";

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
        loca_add("SERVERNAME" , "OGame Open Source");
        loca_add("SERVERADDR" , "oldogame.ru");
        loca_add("BOARDADDR"  , "http://board.oldogame.ru");

        loca_add("META_CHARSET" , "utf-8");
        loca_add("META_KEYWORDS" , "онлайн игра, он-лайн игра, ММОГ, MMOG, дейтерий, стратегия");
        loca_add("META_DESCRIPTION" , "ОГейм - легендарная космическая стратегия! Откройте вселенную вместе с тысячами игроков!");

        loca_add("ERROR_0" , "OK");
        loca_add("ERROR_101" , "Это имя уже занято!");
        loca_add("ERROR_102" , "Этот адрес уже используется!");
        loca_add("ERROR_103" , "Ваше имя должно содержать от 3 до 20 символов!");
        loca_add("ERROR_104" , "Введите действительный адрес!");
        loca_add("ERROR_105" , "Ник - ОК");
        loca_add("ERROR_106" , "Адрес - ОК");
        loca_add("ERROR_107" , "Пароль должен содержать минимум 8 символов!");

        loca_add("TIP_201" , "Игровое имя: <br />Имя, которое Вы выбираете своему персонажу. Одно имя не может повторяться в одной вселенной.");
        loca_add("TIP_202" , "Электронный адрес: <br />Для активации аккаунта введите действительный адрес. Для активации даётся три дня, во  время которых Вы тоже сможете играть.");
        loca_add("TIP_203" , "");
        loca_add("TIP_204" , "Основные положения:<br /> Для начала игры Вы должны принять основные положения.");
        loca_add("TIP_205" , "Пароль: <br/>Пароль защищает Ваш игровой аккаунт от захода на него других людей. Никогда не давайте никому свой пароль.");

        loca_add("MENU_START",        "Главная");
        loca_add("MENU_ABOUT",        "Про ОГейм");
        loca_add("MENU_PICTURES",    "Картинки");
        loca_add("MENU_REG",            "Присоединиться");
        loca_add("MENU_BOARD",        "Форум");

        loca_add("LOGIN_LINK" , "Link Логин");
        loca_add("LOGIN_NAME" , "Имя");
        loca_add("LOGIN_PASS" , "Пароль");
        loca_add("LOGIN_CHOOSE_UNI" , "Вселенная...");
        loca_add("LOGIN_UNI" , "Вселенная");
        loca_add("LOGIN_CONFIRM" , "Заходя в игру, я принимаю");
        loca_add("LOGIN_IMPRESSUM" , "Основные положения");
        loca_add("LOGIN_REMIND" , "Забыли пароль?");

        loca_add("COPYRIGHT" , "Все права защищены.");
        loca_add("DOWN_RULES" , "Правила");
        loca_add("DOWN_IMPRINT" , "Impressum");
        loca_add("DOWN_TAC" , "Основные положения");

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