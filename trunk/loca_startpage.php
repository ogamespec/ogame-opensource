<?php

// Start page can be found on internet archive : http://archive.org

require_once "loca.php";

// Создать таблицы LOCA

    // "en"
    $LocaLang = "en";
    {
        loca_add("SERVERNAME" , "OGame Open Source");
        loca_add("SERVERADDR" , "oldogame.ru");
        loca_add("BOARDADDR"  , "http://board.oldogame.ru");

        loca_add("META_CHARSET" , "utf-8");
        loca_add("META_KEYWORDS" , "ogame, old, old design, online game, on-line game, MMOG, deuterium, strategy");
        loca_add("META_DESCRIPTION" , "META_DESCRIPTION");

        loca_add("ERROR_0" , "OK");
        loca_add("ERROR_101" , "Это имя уже занято!");
        loca_add("ERROR_102" , "Этот адрес уже используется!");
        loca_add("ERROR_103" , "Ваше имя должно содержать от 3 до 20 символов!");
        loca_add("ERROR_104" , "Введите действительный адрес!");
        loca_add("ERROR_105" , "Ник - ОК");
        loca_add("ERROR_106" , "Адрес - ОК");
        loca_add("ERROR_107" , "Пароль должен содержать минимум 8 символов!");
        loca_add("ERROR_108" , "Регистрация с одного айпи не чаще одного раза за 10 минут!");

        loca_add("TIP_201" , "Игровое имя: <br />Имя, которое Вы выбираете своему персонажу. Одно имя не может повторяться в одной вселенной.");
        loca_add("TIP_202" , "Электронный адрес: <br />Для активации аккаунта введите действительный адрес. Для активации даётся три дня, во  время которых Вы тоже сможете играть.");
        loca_add("TIP_203" , "");
        loca_add("TIP_204" , "Основные положения:<br /> Для начала игры Вы должны принять основные положения.");
        loca_add("TIP_205" , "Пароль: <br/>Пароль защищает Ваш игровой аккаунт от захода на него других людей. Никогда не давайте никому свой пароль.");

        loca_add("MENU_START",        "Start");
        loca_add("MENU_ABOUT",        "About OGame");
        loca_add("MENU_PICTURES",    "Pictures");
        loca_add("MENU_REG",            "Join Now!");
        loca_add("MENU_BOARD",        "Board");

        loca_add("LOGIN_LINK" , "Link Login");
        loca_add("LOGIN_NAME" , "Username");
        loca_add("LOGIN_PASS" , "Password");
        loca_add("LOGIN_CHOOSE_UNI" , "Choose a universe...");
        loca_add("LOGIN_UNI" , "Universe");
        loca_add("LOGIN_CONFIRM" , "By logging in, I accept the");
        loca_add("LOGIN_IMPRESSUM" , "T&C's");
        loca_add("LOGIN_REMIND" , "Forgot your password?");

        loca_add("CHOOSELANG" , "Choose your language");
        loca_add("COPYRIGHT" , "All rights reserved.");
        loca_add("DOWN_RULES" , "Rules");
        loca_add("DOWN_IMPRINT" , "Imprint");
        loca_add("DOWN_TAC" , "T&C's");

        loca_add("HOME_TITLE",  "Welcome to OGame");
        loca_add("HOME_TEXT1",  "<strong>OGame</strong> is a <strong>strategic space simulation game</strong>with \n" .
              "<strong>thousands of players</strong> across the world competing with each other <strong>simultaneously</strong>. All you need to play is a standard web browser.");
        loca_add("HOME_TEXT2",  "Register now and enter the fantastic world of OGame!");
        loca_add("HOME_BIGBUTTON",  "Play for free now!");

        loca_add("ABOUT_TITLE",  "What is OGame?");
        loca_add("ABOUT_TEXT1",  "OGame is a game of intergalactic conquest.");
        loca_add("ABOUT_TEXT2",  "You start out with just one undeveloped world and turn that into a <strong>mighty empire</strong> able to defend your hard earned colonies.");
        loca_add("ABOUT_TEXT3",  "Create an <strong>economic and military infrastructure</strong> to support your quest for the next greatest technological achievements.");
        loca_add("ABOUT_TEXT4",  "<strong>Wage war</strong> against other empires as you struggle with other players to gain the materials.");
        loca_add("ABOUT_TEXT5",  "<strong>Negotiate</strong> with other emperors and create an alliance or trade for much needed resources. ");
        loca_add("ABOUT_TEXT6",  "<strong>Build an armada</strong> to enforce your will throughout the universe.");
        loca_add("ABOUT_TEXT7",  "<strong>Hoard your resources</strong> behind an impregnable wall of planetary defences.");
        loca_add("ABOUT_TEXT8",  "Whatever you wish to do, <strong>OGame can let you do it.</strong>");
        loca_add("ABOUT_TEXT9",  "Will you terrorize the area around you? Or will you strike fear into the hearts of those who attack the helpless?");
        loca_add("ABOUT_STORY",  "Read the Ogame Story");
    }

    // "ru"
    $LocaLang = "ru";
    {
        loca_add("SERVERNAME" , "OGame Open Source");
        loca_add("SERVERADDR" , "oldogame.ru");
        loca_add("BOARDADDR"  , "http://board.oldogame.ru");

        loca_add("META_CHARSET" , "utf-8");
        loca_add("META_KEYWORDS" , "ogame, old, старый дизайн, онлайн игра, он-лайн игра, ММОГ, MMOG, дейтерий, стратегия");
        loca_add("META_DESCRIPTION" , "ОГейм - легендарная космическая стратегия! Откройте вселенную вместе с тысячами игроков!");

        loca_add("ERROR_0" , "OK");
        loca_add("ERROR_101" , "Это имя уже занято!");
        loca_add("ERROR_102" , "Этот адрес уже используется!");
        loca_add("ERROR_103" , "Ваше имя должно содержать от 3 до 20 символов!");
        loca_add("ERROR_104" , "Введите действительный адрес!");
        loca_add("ERROR_105" , "Ник - ОК");
        loca_add("ERROR_106" , "Адрес - ОК");
        loca_add("ERROR_107" , "Пароль должен содержать минимум 8 символов!");
        loca_add("ERROR_108" , "Регистрация с одного айпи не чаще одного раза за 10 минут!");

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

        loca_add("CHOOSELANG" , "Выберите свой язык");
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

    // Язык стартовой страницы
    $LocaLang = $_COOKIE['ogamelang'];
    if ($LocaLang !== 'en' && $LocaLang !== 'ru') $LocaLang = "en";        // restrict unsupported languages

?>