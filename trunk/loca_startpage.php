<?php

// Start page can be found on internet archive : http://archive.org

require_once "loca.php";

    // "de"
    $LocaLang = "de";
    {
        loca_add("SERVERNAME" , "OGame Open Source");
        loca_add("SERVERADDR" , "oldogame.ru");
        loca_add("BOARDADDR"  , "http://board.oldogame.ru");
        loca_add("WIKIADDR"   , "http://wiki.oldogame.ru");

        loca_add("META_CHARSET" , "utf-8");
        loca_add("META_KEYWORDS" , "OGame, Browsergame, Onlinegame, Browsergames, Browsergame, Spiel, Spiele, Onlinespiel, Onlinespiele, old, old design");
        loca_add("META_DESCRIPTION" , "OGame - Top Browsergame im Weltraum. Kommandiere deine Flotten.");

        loca_add("ERROR_0" , "OK");
        loca_add("ERROR_101" , "Spielername bereits vergeben!");
        loca_add("ERROR_102" , "Email Adresse wird bereits benutzt!");
        loca_add("ERROR_103" , "Dein Name muss zwischen 3 und 20 Zeichen lang sein!");
        loca_add("ERROR_104" , "Du musst eine gültige Email Adresse angeben!");
        loca_add("ERROR_105" , "Spielername OK");
        loca_add("ERROR_106" , "E-Mail Adresse OK");
        loca_add("ERROR_107" , "Das Passwort muss mindestens 8 Zeichen lang sein!");
        loca_add("ERROR_108" , "Kann nicht von gleichen IP in den nächsten 10 Minuten registrieren!");

        loca_add("TIP_201" , "Name im Spiel: <br />Der Name, den Du im Spiel als Herrscher verwenden möchtest. Er kann pro Universum nur einmal vergeben werden.");
        loca_add("TIP_202" , "E-Mail Adresse: <br />Gib eine gültige E-Mail Adresse ein, um dein Spielerkonto zu bestätigen. Du hast hierfür drei Tage Zeit, in denen Du bereits spielen kannst.");
        loca_add("TIP_203" , "");
        loca_add("TIP_204" , "AGB:<br />Stimme den AGB (Allgemeine Geschäftsbedingungen) zu, um bei OGame mitspielen zu können.");
        loca_add("TIP_205" , "Passwort:<br/>Das Passwort schützt Dein Spielerkonto vor fremdem Zugriff. Gib dein Passwort nicht weiter.");

        loca_add("MENU_START",       "Startseite");
        loca_add("MENU_ABOUT",       "Über OGame");
        loca_add("MENU_PICTURES",    "Bilder");
        loca_add("MENU_REG",         "Mitspielen");
        loca_add("MENU_BOARD",       "Forum");
        loca_add("MENU_WIKI",        "Wiki");

        loca_add("LOGIN_LINK" , "Link Login");
        loca_add("LOGIN_NAME" , "Spielername");
        loca_add("LOGIN_PASS" , "Passwort");
        loca_add("LOGIN_CHOOSE_UNI" , "Universum auswählen...");
        loca_add("LOGIN_UNI" , "Universum");
        loca_add("LOGIN_CONFIRM" , "Mit dem Login akzeptiere ich die");
        loca_add("LOGIN_IMPRESSUM" , "AGB");
        loca_add("LOGIN_REMIND" , "Passwort vergessen?");
        loca_add("LOGIN_NOTCHOSEN" , "Du hast kein Universum ausgewählt.");

        loca_add("CHOOSELANG" , "Wählen Sie Ihre Sprache");
        loca_add("COPYRIGHT" , "Alle Rechte vorbehalten.");
        loca_add("DOWN_RULES" , "Regeln");
        loca_add("DOWN_IMPRINT" , "Impressum");
        loca_add("DOWN_TAC" , "AGB");

        loca_add("HOME_TITLE",  "Willkommen bei OGame");
        loca_add("HOME_TEXT1",  "<strong>OGame</strong> ist ein <strong>Strategiespiel, das im Weltraum</strong> spielt. <strong>Tausende Spieler</strong> treten zur <strong>gleichen Zeit</strong> gegeneinander an. Zum Spielen brauchst du nur einen normalen Webbrowser.");
        loca_add("HOME_TEXT2",  "Melde dich an und lerne die fantastische Welt von OGame kennen!");
        loca_add("HOME_BIGBUTTON",  "Jetzt kostenlos mitspielen!");

        loca_add("ABOUT_TITLE",  "Was ist OGame?");
        loca_add("ABOUT_TEXT1",  "OGame ist ein Spiel der intergalaktischen Eroberung.");
        loca_add("ABOUT_TEXT2",  "Du startest mit nur einem unentwickelten Planeten und verwandelst diesen in ein <strong>mächtiges Imperium</strong>, fähig deine hart erarbeiteten Kolonien zu verteidigen.");
        loca_add("ABOUT_TEXT3",  "Erschaffe eine <strong>wirtschaftliche und militärische Infrastruktur</strong> um dein Streben nach den neuesten technologischen Errungenschaften zu ermöglichen.");
        loca_add("ABOUT_TEXT4",  "<strong>Führe Kriege</strong> gegen andere Imperien, da du dich gegen andere beim Kampf um die Rohstoffe durchsetzen musst.");
        loca_add("ABOUT_TEXT5",  "<strong>Verhandle</strong> mit anderen Imperatoren und bilde Allianzen oder besorge dir dringend benötigte Rohstoffe über den Handel. ");
        loca_add("ABOUT_TEXT6",  "<strong>Baue eine Flotte</strong> um deinen Interessen im ganzen Universum Nachdruck zu verleihen.");
        loca_add("ABOUT_TEXT7",  "<strong>Lagere deine Rohstoffe</strong> hinter einer unüberwindbaren planetaren Verteidigung.");
        loca_add("ABOUT_TEXT8",  "<strong>OGame</strong> bietet dir <strong>grenzenlose Möglichkeiten.</strong>");
        loca_add("ABOUT_TEXT9",  "Wirst du deine Nachbarn terrorisiern? Oder wirst Du der Rächer der Hilflosen sein?");
        loca_add("ABOUT_STORY",  "Lese die Ogame Geschichte");

        loca_add("STORY_TITLE", "Geschichte");
        loca_add("STORY_HEAD", "Die OGame Geschichte");
        loca_add("STORY_TEXT", "
<p><img src=\"img/ogame_technokrat.jpg\" class=\"imageRight\"> Ich möchte euch hier eine Geschichte einer Rasse erzählen, unserer Rasse, der Menschheit.</p> <p > Das komische daran ist, dass dies in eurer Gegenwart noch nicht geschehen ist. Trotzdem erzähle ich sie euch, denn irgendwann werdet ihr feststellen, dass die Zeit parallel läuft. Alles, was in der Vergangenheit geschehen ist, bildet die Gegenwart und die Gegenwart bildet die Zukunft. Wir haben eine Art herausgefunden womit man, ohne dass man die Zukunft verändert, in die Vergangenheit reisen kann. Man kann die Zukunft nämlich nur ändern, wenn man sie selbst durchlebt. Das ist schwer zu begreifen, aber es ist nun mal so. Denn erst wenn man weiß, dass etwas geschehen ist kann man es verändern, da es ja Teil der eigenen Vergangenheit ist. Du selbst wirst einfach ab diesem Zeitpunkt verschwinden, verändern wird sich nichts, da es ja Vergangenheit für mich ist.
                      Doch ich will euch nicht mehr länger auf die Folter spannen, sondern fange endlich einmal an.</p>
      <p >
<img src=\"img/fight.gif\" class=\"imageRight\">
                      Es begann im Jahre 2250, man nannte es auch das Jahr Alpha, in dem der erste Mensch einen interstellaren Flug wagte, der länger als 3 Minuten dauerte, und ihn damit weiter beförderte als jede Sonde jemals gekommen war.</p>
<img src=\"img/ogame_ingenieur.jpg\" class=\"imageLeft\">
                   <p>   Diese neue Art von Triebwerk (Impulstriebwerk - basierend auf dem Rückstoßprinzip) erlaubte dem Menschen, genau so schnell wie das Licht zu fliegen. Sicherlich gab es schon früh erste Raumflüge, doch waren die Strecken sehr gering. Man hatte einfach keine Erfahrung mit Raumreisen. Der Abenteuerdrang aber treibt jeden weiter und bald kamen erste Neuerungen, die die Reichweite erhöhten. Dabei half sicherlich der Konkurrenzdruck, der unter den einzelnen Herstellern entfachte. Boeing setzte immer noch auf eine weiterentwickelte Form des Verbrennungstriebwerkes. Dieses war auf der Erde wegen zu hoher Schadstoffwerte verboten, der Raum hatte aber auch in dieser Hinsicht andere Gesetze. Airbus Industries wollte das noch nicht ausgereifte und zu dem unsichere Impulstriebwerk weiter erforschen. Explosionen von mit diesem Antrieb ausgestatten Schiffen dämpften zunächst die Erwartungen. Allerdings erfand der Diplomkernteilchenphysiker Albert Ainstein eine neue, sichere Variante, in der nicht mehr die Y-Teilchen selbst, sondern eine vorher mit diesen reagierte Verbindung zum Einsatz kam. Aber auch andere hatten mit der Erforschung der Galaxien schon begonnen und nach den ersten Interplanetaren Flügen wurden sehr bald neue, bessere Triebwerke entwickelt um die langen Flugzeiten zu verkürzen. Die neuen Antriebe flogen mit viel energiereichem Deuterium, so dass auch andere Galaxien mit relativ kurzer Flugzeit zu erreichen waren.</p>
<img src=\"img/ogame_geologe.jpg\" class=\"imageRight\">
                     <p>
                      Allerdings war Deuterium sehr rar, insgesamt 0,015% der gesamten Wasserbestände der Erde bestanden aus dem Isotop. Doch das bestmögliche Deuterium sollte nicht reichen. Darüber, wie es nun weiter gehe, stritten sich die Wissenschaftler aller Völker Jahrzehnte lang, doch die verbreitetste These war die, dass durch eine Quasarenexplosion ein Gas Y freigesetzt würde, mit dem man ein neues Triebwerk entwickeln könne. Dieses würde mit einem Gemisch aus dem Gas Y und Deuterium angetrieben. Der Verbrennungsprozess dieser beiden Gase zusammen setzte so viel Energie frei, dass die Schiffe viel schneller werden würden. Der Wissenschaftler Friedrich Terrane baute auf dieser These basierend ein Triebwerk. Das, was viele Wissenschaftler in der Theorie stark kritisiert hatten und was eigentlich nach dem Stand des Wissens nicht funktionieren dürfte, funktioniert dann aber in der Praxis. Das Triebwerk brachte dann den erhofften Durchbruch, wodurch die Völker mit einer noch um vieles verkürzten Flugzeit Galaxien noch schneller Anfliegen konnten. Raum und kolonisierbare Welten gab es genug, und deshalb vergingen wiederum Jahrzehnte, ja, sogar Jahrhunderte des Friedens und des Wohlstandes. Es entstanden viele Länder, die dem Idealbild des 2-Strom Landes glichen. Es horteten sich unschätzbare Goldwerte, es bauten sich neue Kulturen auf. Das Universum war sehr still und friedlich:</p> 
<img src=\"img/light.gif\" class=\"imageRight\">
                    
                                        </p><p align=\"justify\"><span class=\"sizeh1\">Die Ruhe vor dem Sturm....</span></p>
                                       <p> Selbst nach dieser langen Zeit wurden Technologien entwickelt, um noch schneller zu reisen. Und dann, als man abermals an die Grenzen stieß, wurde durch einen Fehlversuch einiger Studenten ein Triebwerk entwickelt, das alles bis dahin dagewesene in den Schatten stellte, der Hyperraumantrieb: Der Hyperraumantrieb erzeugt eine dimensional übergeordnete Blase, die sowohl 4- als auch 5-dimensionale Einflüsse abschirmt. Diese bewegt sich sozusagen zwischen 4. und 5. Dimension. Innerhalb der Blase herrschen die Gesetze des 4D-Raums, das Schiff wird nicht entmaterialisiert. In einem engen Bereich in Flugrichtung ist die normale Galaxie sichtbar. Mit diesem konnte man noch weiter in den Raum vordringen und noch viel mehr Welten entdecken. Zu dieser Zeit blühte die Diplomatie und es gab wenige Konflikte bis, ja, bis zu der Entdeckung des Xentroniums, einem neuen Element, das einer der Völker auf einer seiner Kolonien fand. Man wusste, wer dieses Element besitze, würde über alles herrschen.</p>
                                      <p align=\"justify\"><span class=\"sizeh1\">Der Krieg sollte bald beginnen.....</span><br /></p> <img src=\"img/omega.gif\" class=\"imageRight\">
                                       <p> Dieses Element ist so selten, dass in den folgenden Jahren nur 1,5 Mikrogramm gewonnen werden konnten. Da es auf terranischem Raum gefunden wurden, durften es nur die Laboratorien des Volkes Terraner untersuchen und ergründen. Xentronium besitzt ein Energiepotential von 10.000 terranischen Sonnen pro Mikrogramm! Und schon begannen Neid und Missgunst, die Völker zerstritten sich und ein Krieg entbrannte. Es kristallisierten sich zwei Bündnisse heraus, die Pro Terranische Fraktion und die Contra Terranische Fraktion. Der Kampf, um etwas sehr Mächtiges, soll 150 Jahre wüten, prophezeite einst Nostradamus. Doch es sollten mehr als 300 werden. Abertausende Völker zersplitterten oder wurden ausgelöscht. Einige Wenige, die den härtesten Teil des Krieges schon überstanden hatten, hatten den Plan gefasst einen Ausweg zu suchen, aber sie wussten, dass das keine leichte Aufgabe werden würde. Doch wohin sollte man fliehen? Im Jahre 125 nach Beginn des Krieges entdeckte ein Wissenschaftsteam der VfV [Vereinigung flüchtiger Völker] unter Führung eines genialen Wissenschaftlers, dass man durch die Zugabe von instabilem Beryllium ein Schwarzes Loch aufladen kann, so dass ein Schiff hindurch fliegen kann ohne zerrissen zu werden. Der Nachteil ist jedoch, das jedes schwarze Loch in einem weißen Loch endet wodurch es eine Einbahnstrasse ist ...<br /></p>
<img src=\"img/legorians.jpg\" class=\"bigImage\">
                                     <p>   Nach einigen positiv geglückten Versuchen begann die VfV ihre Fluchtschiffe vorzubereiten. Nach weiteren 25 Jahren vernichtenden Kriegen stand es schlecht um die Pro Terraner. Die Contra-Terraner setzten die Omegawaffe ein und das Universum, in dem die Völker lebten, begann vernichtet zu werden. Die Omegawaffen hatten ihrem Namen alle Ehre gemacht und ein Teil der Galaxie wurde von einer absolut tödlichen Energiewelle ergriffen. Hunderte Jahre nach dem Beginn der neuen Zeitzählung, dem Jahre Alpha, wurde das gesamte Universum durch die schrecklichsten Waffen, die das Universum je gesehen hat und je sehen wird, vernichtet. Immer mehr Systeme verschwanden und wurden ausgelöscht und man hatte berechnet, dass nach nur 24 weiteren Jahren alles im bekannten Bereich der Galaxie ausgelöscht werde. Die wenigen Überlebenden die fliehen wollten und auch konnten nutzten ihr Wissen um schwarze Löcher zu öffnen und flogen in je eines der vorbereiteten schwarzen Löcher. Dies war die einzige Chance! Es begann ein Weg ohne Wiederkehr...<br />
                                      </p>
<img src=\"img/ogame_admiral_left.jpg\" class=\"imageLeft\">
<p align=\"justify\"><span class=\"sizeh1\">Ein neues Leben</span><br /></p>
                    <p>
                                        Und als sie sahen dass ihre Schiffe tatsächlich durch ein weißes Loch ausgespuckt wurden, sahen sie, dass es in dieser ganzen, für sie ganz neue, Galaxie nur einen bewohnten Planeten gab. Als sie auf ihm landeten, begegneten sie einem seltsamen Volk, den Legorianern. Es war auf dem gleichen technischen Stand und sehr großzügig, die Terraner und die anderen Völkern wurden gut bewirtschaftet. Das Volk bot ihnen an, die neue Galaxie zu besiedeln, doch stellte es zwei Bedingungen: Jedes Volk dürfe maximal neun Planeten besiedeln, damit der Platz für alle Flüchtenden reiche, und es solle ein Rat, auf dem Planeten, dem neutralen Ratsplaneten gebildet werden, wo alle Sprachrecht erhalten sollen. Die Völker willigten ein und bildeten unter dem Rate noch die Handelsgilden und die Ehrengarde, als Hilfsmittel und als Werkzeuge des Friedens.<br />
                                      </p><p align=\"justify\"><span class=\"sizeh1\">Eine Neue Zivilisation begann..</span><br />
                                        Du fragst dich jetzt sicher, weshalb ich dir das alles erzählt habe. Nun, die Antwort ist die: Ich gebe dir hier und jetzt die Chance, mit mir in die Zukunft zu kommen. Folge mir, und du wirst dein Glück auch versuchen können. Ich gewähre dir für den Anfang die Herrschaft über einen Planeten, der sehr prachtvoll ist und viel Platz für dein zukünftiges Volk bietet. Du wirst ihn selber aufbauen müssen, um an Macht zu kommen. Doch ich sage dir, es lohnt sich. Schreite nur durch dieses Zeitportal, und du wirst eintauchen in eine Welt voller neuer Dinge. Es wird schwer für dich. Es wird sich zeigen, ob du genug Willenskraft und Stärke besitzt um dein Volk an die Macht zu führen. Ich verlasse dich nun, doch die Entscheidung liegt ganz alleine bei dir. Viel Glück!</p>
" );
        loca_add("STORY_JOIN", "Mach mit!");

        loca_add("PICS_TITLE", "Screenshots");
        loca_add("PICS_SCREENSHOTS", "Bilder");
        loca_add("PICS_WALLPAPERS", "Wallpapers");
        loca_add("PICS_WALL1", "Übersicht");
        loca_add("PICS_WALL2", "Gebäude");
        loca_add("PICS_WALL3", "Schiffswerft");
        loca_add("PICS_WALL4", "Imperium");

        loca_add("JOIN_TITLE", "Mitspielen");
        loca_add("JOIN_HEAD", "Um Mitzuspielen musst du nur einen <strong>Spielernamen</strong>, ein <strong>Passwort</strong> und eine <strong>E-Mail-Adresse</strong> angeben, sowie das Kästchen anwählen, um die AGB zu akzeptieren.");
        loca_add("JOIN_NAME", "Spielername:");
        loca_add("JOIN_EMAIL", "E-Mail-Adresse:");
        loca_add("JOIN_PASS", "Passwort:");
        loca_add("JOIN_ADVICE", "Universum:");
        loca_add("JOIN_TIP", "empfohlen");
        loca_add("JOIN_UNIS", "Besonderheiten der Universen");
        loca_add("JOIN_IACCEPT", "Ich akzeptiere die");
        loca_add("JOIN_TAC", "AGB");
        loca_add("JOIN_REGISTER", "Registrierung abschicken");

        loca_add("INSTALL_MDB", "Master Database Einstellungen");
        loca_add("INSTALL_MDB_TIP", "Die zentrale Datenbank auf einem anderen Server befinden (in der Regel in der gleichen Stelle und der Startseite) und speichert Informationen über alle Universen, Gutscheine und andere allgemeine Informationen.");
        loca_add("INSTALL_MDB_HOST", "Host");
        loca_add("INSTALL_MDB_USER", "Benutzer");
        loca_add("INSTALL_MDB_PASS", "Passwort");
        loca_add("INSTALL_MDB_NAME", "DB name");
        loca_add("INSTALL_INSTALL", "Installieren");
        loca_add("INSTALL_ERROR1", "Kann nicht speichern Konfigurationsdatei.");
        loca_add("INSTALL_DONE", "Installieren abzuschließen. Config Datei erstellt.");
    }

    // "en"
    $LocaLang = "en";
    {
        loca_add("SERVERNAME" , "OGame Open Source");
        loca_add("SERVERADDR" , "oldogame.ru");
        loca_add("BOARDADDR"  , "http://board.oldogame.ru");
        loca_add("WIKIADDR"   , "http://wiki.oldogame.ru");

        loca_add("META_CHARSET" , "utf-8");
        loca_add("META_KEYWORDS" , "OGame, Browsergame, Onlinegame, Browsergames, Browsergame, Spiel, Spiele, Onlinespiel, Onlinespiele, old, old deisgn");
        loca_add("META_DESCRIPTION" , "OGame - Top Browsergame im Weltraum. Kommandiere deine Flotten.");

        loca_add("ERROR_0" , "OK");
        loca_add("ERROR_101" , "Player\'s name is already taken!");
        loca_add("ERROR_102" , "E-Mail-Address is already in use!");
        loca_add("ERROR_103" , "The name must be between 3 and 20 characters long!");
        loca_add("ERROR_104" , "You need to enter a valid e-mail-address!");
        loca_add("ERROR_105" , "Player\'s name OK");
        loca_add("ERROR_106" , "E-Mail-Address OK");
        loca_add("ERROR_107" , "Password must be at least 8 characters long!");
        loca_add("ERROR_108" , "Cannot register from same IP in next 10 minutes!");

        loca_add("TIP_201" , "Name in the game: <br />This is the name you use in the game. It is unique throughout the universe.");
        loca_add("TIP_202" , "E-Mail-Address: <br />Enter a valid E-Mail address to activate your account. You have 3 days to activate your account during those 3 days you are already able to play.");
        loca_add("TIP_203" , "");
        loca_add("TIP_204" , "T&C:<br /> Accept the T&C (Terms and Conditions) to be able to play OGame.");
        loca_add("TIP_205" , "Password:<br/>Your password works as a safety meassure when login in to your account. Do not give your password to anyone!");

        loca_add("MENU_START",       "Start");
        loca_add("MENU_ABOUT",       "About OGame");
        loca_add("MENU_PICTURES",    "Pictures");
        loca_add("MENU_REG",         "Join Now!");
        loca_add("MENU_BOARD",       "Board");
        loca_add("MENU_WIKI",        "Wiki");

        loca_add("LOGIN_LINK" , "Link Login");
        loca_add("LOGIN_NAME" , "Username");
        loca_add("LOGIN_PASS" , "Password");
        loca_add("LOGIN_CHOOSE_UNI" , "Choose a universe...");
        loca_add("LOGIN_UNI" , "Universe");
        loca_add("LOGIN_CONFIRM" , "By logging in, I accept the");
        loca_add("LOGIN_IMPRESSUM" , "T&C's");
        loca_add("LOGIN_REMIND" , "Forgot your password?");
        loca_add("LOGIN_NOTCHOSEN" , "You haven\'t chosen a universe.");

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

        loca_add("STORY_TITLE", "Story");
        loca_add("STORY_HEAD", "The Ogame Story");
        loca_add("STORY_TEXT", "
<p><img src=\"img/ogame_technokrat.jpg\" class=\"imageRight\"> This is the story of a species, a race - its our race, the humans.</p> <p >Interestingly enough the story has not yet happened, but it should still be told. Once in a time you will find that time runs in parallel, that everything that was in the past, forms the present as well as the present is the basis for the future. There has been found a way to visit the past without altering the future. Because one can only alter the future when living it for oneself. This might be hard to understand, but its true. Only when you know that something has happened, you can change it, because it then is part of one's own past. You will simply disappear from that point on, no changes will take place, because this is already the past.. your past.</p>
      <p >
       <img src=\"img/fight.gif\" class=\"imageRight\">
      It began in the year 2250 - the year 'alpha' - when the first man took the risk of an interstellar flight of more than three minutes and thus farther than any probe had gone before. It was a new kind of engine called impulse engine, which allowed mankind to travel at the speed of light. Yes there were earlier space flights, but those were limited, a mere glimpse into space compared to the later achievements. But mankind's natural impulse for new adventures continued to push the development further and thus prolonging the space flights little by little over the years. </p>
      <img src=\"img/ogame_ingenieur.jpg\" class=\"imageLeft\">
      <p >Certainly the competitive struggle of the involved aerospace companies has helped for new inventions and technological advancement. While Boing Industries still relied on their old combustion engine that has been banned from the earths atmosphere, rival ErBus tried to put aside the various troubles they encountered with their impulse engine. Drawbacks and explosions at first dampened the vision of a new kind of technology, but in the process of development the Y-particles were replaced by a chemical compound being more safe, thanks to the theoretical contributions of the well-known particle physicist Al Ainstone. Only slightly aside the main stage, smaller companies and private groups also felt the need for space exploration and the demand for better and faster yet more efficient propulsion systems was immanent. </p>
      <img src=\"img/ogame_geologe.jpg\" class=\"imageRight\">
       <p >Modern engines were using highly energetic deuterium fuel allowing them to travel from galaxy to galaxy in relatively short amounts of time. Unfortunately, this deuterium was very rare, on earth only 0.015% of all water was in the form of this heavy isotope. Later, even the best deuterium wasn't good enough anymore, and a new scientific debate was born, discussing over new ways to generate an even better fuel than deuterium itself already was. Based on a well known theory, it was believed to be possible to combine deuterium with a mysterious gas emitted by a quasar's explosion. The quantum-chemical reaction with this mixture would set free huge amounts of energy, so much more that ships could travel faster and faster. </p><p >Scientist Fred Terrane put his hands together and started to design an engine based on the above theory. Despite the criticism from almost every scientific side, the new propulsion engine finally showed the theory to work - although based on the current level of scientific knowledge, it shouldn't have to. In real-world applications the new machinery proved to live up to its promises and cultures all around were able to head for outer space even more easily and even faster.</p>
       <img src=\"img/light.gif\" class=\"imageRight\">
       <p > There was well enough space and places to colonize and so peace and prosperity quickly spread in the universe and remained so for many decades. Ever more new cultures were discovered and also created and life spread into every corner of space. The Universe was light and peaceful. But it was the quiet before the storm... </p><p >Even after this prosperous period, new and faster technologies for propulsion systems were investigated. Based on old theories and eventually combined with the eager of young student, a completely revolutionary propulsion technology was created that put everything away known so far... </p><p ><img src=\"img/hyper.gif\" class=\"imageLeft\">The Hyper-space engine technology. Basically a hyper-dimensional bubble being in the fourth and the fifth dimensions yet isolating its innards from these influences and obeying the laws of the four-dimensional space. There is a narrow field of vision on the galaxy in direction of movement. Using this outstanding Engine technology, explorers could go even further into the deep space and colonization took place much faster due to decreased travel times. Again, a new era was entered, the development speed increased again and many new worlds were discovered. Diplomacy was big in these times and there were not many conflicts between races. Until an previously unknown chemical element named Xentronium was discovered on a some people's colony. The war should begin soon... </p><p >This chemical element was extremely rare to find, and during the first years after its discovery, only 1.5 micrograms could be harvested. Because it had been found on terran ground, only laboratories of the terrans would be able to investigate on this new element. Xentronium was believed to have the power of 10'000 terran suns per microgram! Once this knowledge spread across the universe, envy and ill will arose from the dark and caused conflicts among the nations which sadly enough ended in a war. Quickly two parties were established, those to the terran fraction and those against the terrans. </p>
      <img src=\"img/omega.gif\" class=\"imageRight\">
       <p >It was Nostradamus who once saw that the war would last for 150 years, but reality proved him wrong as the war wasted more than 300 years. Thousands and thousands of nations were drawn apart, fell or completely disappeared of annihilation.The side of the enemies to the terran federation started to use some ultimate weapon by the name \"omega bomb\". As the name already suggests, usage of this bomb wipes out entire parts of a galaxy, freeing it from any civilization. Hundreds of years after the new time system and the year alpha was established, the universe was faced with the most dangerous and most deadly weapons ever created, solarsystem after solarsystem was destroyed and literally removed. Calculations were conducted to predict the outcome: Only a mere 24 years after the first omega bomb was dropped, known space within the universe would be wiped out leaving lifeless space behind...</p><p > Only few were able to survive, and even lesser were able to flee through wormholes and make their way out into new areas of the universe - but it was a way of no return to them. A new life...</p><p > With great relief the people found themselves in an entirely different universe where they had never been before.. interestingly enough, they only found a single planet in the entire area and as far as they were able to see...</p>
       <img src=\"img/legorians.jpg\" class=\"bigImage\">
       <p >They dared to settle on this peace of universal driftwood and encountered quite a strange life-form, a nation to the name \"legorians\". The visitors were treated well, and the legorians had a similar technical knowledge. Eventually, the legorians agreed to let the strangers settle in the near environment of the legorian's inhabited world. However there were two conditions: Every nation, every race, would only colonize 9 planets, so that others would also get the change to settle for a new civilization after those long conflicts and their escape. The second condition was to create a senate on the homeworld of every rising empire allowing the people to debate and talk. Of course the nations and their representatives agreed thankfully and even established trading associations and guarding troops to ensure peace and fairness. A new civilization arose... </p>
      <img src=\"img/ogame_admiral_left.jpg\" class=\"imageLeft\">
       <p >You might be asking by now, why we wanted to tell you this story. The answer is very simple, it is for you come with us to build the future! Follow us, and see what YOU can do with a nation thats awaits a new emperor desperately ready to grow and succeed! You will have to put much work into this task, and the times wont always be peaceful, but it is up to you, to take part in this part of the universe and save your nation a respected seat among all the empires. Follow us through this time portal, and enjoy this world full of new things and an big future. It might be hard from time to time, it might be easy now and then, but your will and your power could create a powerful and prosperous nation. </p><p >I will leave you now... hoping you would join us... yet its your decision... Dare it! </p>
" );
        loca_add("STORY_JOIN", "Join now!");

        loca_add("PICS_TITLE", "Pictures");
        loca_add("PICS_SCREENSHOTS", "Screenshots");
        loca_add("PICS_WALLPAPERS", "Wallpapers");
        loca_add("PICS_WALL1", "Overview");
        loca_add("PICS_WALL2", "Buildings");
        loca_add("PICS_WALL3", "Shipyard");
        loca_add("PICS_WALL4", "Empire");

        loca_add("JOIN_TITLE", "Registration");
        loca_add("JOIN_HEAD", "In order to play you only have to enter a <strong>username</strong>, a <strong>password</strong> and an <strong>E-Mail address</strong> and <strong>proceed to read the terms and conditions</strong> before activating the check box about your agreement to them.");
        loca_add("JOIN_NAME", "Username:");
        loca_add("JOIN_EMAIL", "E-Mail-Address:");
        loca_add("JOIN_PASS", "Password:");
        loca_add("JOIN_ADVICE", "Universe:");
        loca_add("JOIN_TIP", "recommended");
        loca_add("JOIN_UNIS", "Specials of the universes");
        loca_add("JOIN_IACCEPT", "I accept the");
        loca_add("JOIN_TAC", "T&C's");
        loca_add("JOIN_REGISTER", "Join now!");

        loca_add("INSTALL_MDB", "Master Database Settings");
        loca_add("INSTALL_MDB_TIP", "The central database can be located on another server (usually in the same place where start page located) and stores information about all the universes, coupons and other general information.");
        loca_add("INSTALL_MDB_HOST", "Host");
        loca_add("INSTALL_MDB_USER", "User");
        loca_add("INSTALL_MDB_PASS", "Password");
        loca_add("INSTALL_MDB_NAME", "DB name");
        loca_add("INSTALL_INSTALL", "Install");
        loca_add("INSTALL_ERROR1", "Cannot save config file.");
        loca_add("INSTALL_DONE", "Install complete. Config file created.");
    }

    // "ru"
    $LocaLang = "ru";
    {
        loca_add("SERVERNAME" , "OGame Open Source");
        loca_add("SERVERADDR" , "oldogame.ru");
        loca_add("BOARDADDR"  , "http://board.oldogame.ru");
        loca_add("WIKIADDR"   , "http://wiki.oldogame.ru");

        loca_add("META_CHARSET" , "utf-8");
        loca_add("META_KEYWORDS" , "ogame, old, старый дизайн, онлайн игра, он-лайн игра, ММОГ, MMOG, дейтерий, стратегия, old deisgn");
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

        loca_add("MENU_START",       "Главная");
        loca_add("MENU_ABOUT",       "Про ОГейм");
        loca_add("MENU_PICTURES",    "Картинки");
        loca_add("MENU_REG",         "Присоединиться");
        loca_add("MENU_BOARD",       "Форум");
        loca_add("MENU_WIKI",        "Вики");

        loca_add("LOGIN_LINK" , "Link Логин");
        loca_add("LOGIN_NAME" , "Имя");
        loca_add("LOGIN_PASS" , "Пароль");
        loca_add("LOGIN_CHOOSE_UNI" , "Вселенная...");
        loca_add("LOGIN_UNI" , "Вселенная");
        loca_add("LOGIN_CONFIRM" , "Заходя в игру, я принимаю");
        loca_add("LOGIN_IMPRESSUM" , "Основные положения");
        loca_add("LOGIN_REMIND" , "Забыли пароль?");
        loca_add("LOGIN_NOTCHOSEN" , "Вы не выбрали вселенную.");

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

        loca_add("STORY_TITLE", "История");
        loca_add("STORY_HEAD", "История ОГейма");
        loca_add("STORY_TEXT", "
<p><br /><img src=\"img/ogame_technokrat.jpg\" class=\"imageRight\"> Здесь я хотел бы рассказать вам историю одной расы, нашей расы, человечества. Интересно то, что этого ещё не произошло в вашей действительности. Но я всё равно расскажу, так как когда-нибудь вы установите, что время течёт параллельно. Всё, что произошло в прошлом определяет настоящее, а настоящее определяет будущее. Мы изобрели способ, при помощи которого можно путешествовать в прошлое не изменяя будущее. Вообще, будущее можно изменить только тогда, когда переживаешь его сам. Это тяжело понять, но это так. Так как только тогда, когда известно, что что-то случилось, можно это изменить, так как это - часть прошлого. С этого момента ты просто исчезаешь, ничто не изменяется, так как для меня это уже прошлое. Но я больше не хочу вас мучать ожиданием, а просто начну рассказ. </p> 
<img src=\"img/fight.gif\" class=\"imageRight\"> 
<p>Всё начиналось в 2250 году, который также называли \"годом альфа\", когда первый человек отважился на межзвёздный полёт, который длился более 3-х минут и который доставил его дальше того предела, до которого долетали исследовательские зонды. Этот новый тип двигателя (импульсный двигатель - основанный на принципе отдачи) позволил человеку летать со скоростью света. Естественно, до этого существовали первые космические полёты, но расстояния были очень маленькими. Просто отсутствовал опыт космических путешествий. Но страсть к приключениям толкала всех всё дальше, и скоро стали появляться первые изобретения, которые позволили увеличить дальность полёта. При этом, конечно же, помогало давление конкуренции, которая развернулась между производителями. \"Боинг\" всё ещё делал ставку на более развитую форму двигателя внутреннего сгорания. На Земле он был запрещён из-за высокой степени вредности, но у космоса и в этом плане другие законы. \"Эйрбас Индастриз\" хотели дальше исследовать ещё не созревший и, к тому же, ненадёжный импульсный двигатель. </p> 
<img src=\"img/ogame_ingenieur.jpg\" class=\"imageLeft\"> 
<p>Взрывы оснащённых этим двигателем кораблей поначалу сдерживали ожидания, однако ядерный физик Альберт Айнштайн изобрёл новый, надёжный вариант, в котором применялись не только сами игрек-частицы, но и реагирующее с ними соединение. Другие учёные также начали исследования и после первых межпланетных полётов для сокращения долгих перелётов были разработаны новые и более совершенные двигатели. Эти новые двигатели работали на более богатом энергией дейтерии, что позволяло теперь достичь даже других галактик. Однако дейтерий был очень дефицитным - в общей сложности из этого изотопа состояло 0,015% всех водных ресурсов Земли. Но и этого дейтерия было мало. </p> 
<img src=\"img/ogame_geologe.jpg\" class=\"imageRight\"> 
<p>О том, как же быть дальше, учёные из всех стран спорили десятилетиями, и самой распространённой версией была та, гласившая, что при взрыве квазаров освобождается газ игрек, на основе которого теоретически можно построить новый двигатель. Он должен работать на смеси газа игрек с дейтерием. Процесс сгорания этих обоих газов высвобождал бы такое количество энергии, которое позволяло бы кораблям летать быстрее. Учёный Фридрих Террейн построил двигатель, основываясь на этой теории. То, что многие учёные так сильно критиковали и что, согласно знаниям того времени, не должно было работать, работало на практике. Этот двигатель был прорывом в кораблестроении, при помощи которого народы смогли достигать других галактик за ещё более короткое время. </p> 
<img src=\"img/light.gif\" class=\"imageRight\"> 
<p>Пространства и пригодных для колонизации миров хватало, поэтому десятилетия, даже столетия длился мир и благосостояние. Возникло много стран, соответствовавших по благосостоянию идеальной картине Междуречья. Скапливались бесценные золотые запасы, появлялись новые культуры. Вселенная находилась в мире и спокойствии...</p> 
 
<p>Затишье перед бурей...</p> 
<p>После этого долгого периода были изобретены технологии для ещё более быстрых полётов. И вот, после того, как люди в очередной раз достигли предела, в результате неудачной попытки несколькими студентами была разработана новая технология, которая затмевала всё имевшееся на тот момент - гиперпространственный двигатель: он создаёт межпространственную раковину, которая задерживает как 4-х, так и 5-мерные влияния. Она двигается, как бы между 4-м и 5-м измерением. Внутри раковины действуют законы 4-мерного пространства, корабль не подвергается дематериализации. Нормальная галактика видна в направлении полёта в узком спектре. При помощи этого можно было проникать ещё дальше и открывать новые миры. </p> 
<img src=\"img/omega.gif\" class=\"imageRight\"> 
<p>До этого времени процветала дипломатия и было мало конфликтов до тех пор, пока... да, до тех пор, пока не был открыт ксенторний, новый элемент, который был найден одним из народов на одной из своих колоний. Стало очевидно, что владеющий этим элементом будет властвовать над всеми. </p> 
<p>Война...</p> 
<p>Этот элемент был настолько редким, что в последующие годы смогли добыть всего 1,5 микрограмма. Так как он был найден на территории терранов, то его могли исследовать только в лабораториях народа терранов. Ксентроний обладает потенциалом в 10 тыс. терранских солнц на микрограмм! Вскоре началась зависть и недоброжелательность, народы стали враждовать и разгорелась война. </p> 
<img src=\"img/legorians.jpg\" class=\"bigImage\"> 
<p>Выделилось два союза - протерранская фракция и антитерранская фракция. \"Бой за нечто очень могущественное будет длиться 150 лет\", пророчил когда-то Нострадамус. Но он продлился больше - 300. Тысячи народов вымирали либо были уничтожены. Те немногие, которые пережили тяжелейший период войны, составили план бегства, но они знали, что это будет нелегко. Куда же они хотели бежать? </p> 
<p>В 125 году с начала войны группа учёных СБН (Союз Беглых Народов) под руководством одного гениального учёного обнаружила, что при добавлении нестабильного бериллия можно так зарядить чёрную дыру, что через неё может пролететь корабль не будучи разорванным. Однако недостаток состоит в том, что каждая чёрная дыра оканчивается в белой дыре, т.е. это улица с односторонним движением... </p> 
<p>После нескольких удавшихся попыток СБН начали готовить корабли для бегства. После дальнейших 25 лет войны дела у протерранцев были плохи. Антитерранцы применяли омега-орудия и та вселенная, в которой жили враждебные им народы, начала уничтожаться. Омега-орудия сделали им имя. Часть галактики была захвачена смертельной энергитической волной. Сотни лет после начала нового летоисчисления - после года Альфа - вся вселенная была уничтожена ужаснейшим оружием, которое только было и будет известно. Всё больше и больше систем исчезало либо было уничтожено и было рассчитано, что всего в течение 24 лет всё, находящееся в исследованных пределах галактики, исчезнет. </p> 
<p>Те немногие выжившие, которые хотели и могли бежать, использовали свои знания, чтобы открывать чёрные дыры и улетали через них. Это был единственный шанс! Начался путь без возврата... </p><img src=\"img/ogame_admiral_left.jpg\" class=\"imageLeft\"> 
<p>Новая жизнь...</p> 
<p>И когда они увидели, что их корабли в самом деле вылетали из белых дыр, то обнаружили, что во всей этой для новой для них галактике существует только одна заселённая планета. Когда они приземлились на ней, им повстречался странный народ - легорианцы. Они находились на таком же техническом уровне, что и терранцы, и были очень великодушными. Терранцам и другим народам были предоставлены собственные владения. Этот народ предложил им заселить новую галактику, но поставил два условия: у каждого народа может быть максимум девять планет, чтобы хватило места другим беженцам, и на одной нейтральной планете должен был быть основан Совет, где бы все имели право голоса.</p> 
<p>Так возникла Планета Советов (как знакомо звучит!!!). Народы согласились и основали в Совете торговые гильдии и гвардию как средство и инструмент мира. </p><p>Новая цивилизация...</p> 
<p>Ты точно спрашиваешь, зачем я тебе это всё рассказал. Ответ таков: здесь и сейчас я даю тебе шанс попасть в будущее. Следуй за мной и ты тоже сможешь попытать своё счастье. Для начала я предоставляю тебе правление над одной богатой планетой, на которой достаточно места для твоего будущего народа. Ты должен будешь сам её застраивать, чтобы прийти к власти. Но поверь мне, это стоит того. Только переступи этот временной портал и окунись в мир, полный Нового. Это будет тяжело. И выяснится, обладаешь ли ты достаточной силой воли, чтобы привести свой народ к власти. Я покидаю тебя, но принятие решения полностью зависит только от тебя. Удачи!</p> 
");
        loca_add("STORY_JOIN", "Join now!");

        loca_add("PICS_TITLE", "Картинки");
        loca_add("PICS_SCREENSHOTS", "Картинки");
        loca_add("PICS_WALLPAPERS", "Обои");
        loca_add("PICS_WALL1", "Обзор");
        loca_add("PICS_WALL2", "Постройки");
        loca_add("PICS_WALL3", "Верфь");
        loca_add("PICS_WALL4", "Империя");

        loca_add("JOIN_TITLE", "Присоединиться");
        loca_add("JOIN_HEAD", "Для игры Вам необходимо ввести <strong>игровое имя</strong>, <strong>пароль</strong> и <strong>электронный адрес</strong> и поставить галочку, чтобы принять основные положения.");
        loca_add("JOIN_NAME", "Игровое имя:");
        loca_add("JOIN_EMAIL", "Электронный адрес:");
        loca_add("JOIN_PASS", "Пароль:");
        loca_add("JOIN_ADVICE", "Мы советуем вселенную");
        loca_add("JOIN_TIP", "очень советуем!");
        loca_add("JOIN_UNIS", "Особенности во вселенных");
        loca_add("JOIN_IACCEPT", "Я принимаю");
        loca_add("JOIN_TAC", "Основные положения");
        loca_add("JOIN_REGISTER", "Отправить регистрацию");

        loca_add("INSTALL_MDB", "Настройки центральной базы данных");
        loca_add("INSTALL_MDB_TIP", "Центральная база данных может находится на другом сервере (обычно там же, где и стартовая страница) и хранит информацию обо всех вселенных, купоны и пр. общую информацию");
        loca_add("INSTALL_MDB_HOST", "Хост");
        loca_add("INSTALL_MDB_USER", "Пользователь");
        loca_add("INSTALL_MDB_PASS", "Пароль");
        loca_add("INSTALL_MDB_NAME", "Название БД");
        loca_add("INSTALL_INSTALL", "Инсталлировать");
        loca_add("INSTALL_ERROR1", "Не удалось сохранить файл конфигурации.");
        loca_add("INSTALL_DONE", "Установка завершена. Файл конфигурации создан.");
    }

/*
    // "tr"
    $LocaLang = "tr";
    {
        loca_add("SERVERNAME" , "OGame Open Source");
        loca_add("SERVERADDR" , "oldogame.ru");
        loca_add("BOARDADDR"  , "http://board.oldogame.ru");

        loca_add("META_CHARSET" , "utf-8");
        loca_add("META_KEYWORDS" , "OGame, Browsergame, Onlinegame, Browsergames, Browsergame, Spiel, Spiele, Onlinespiel, Onlinespiele, old, old design");
        loca_add("META_DESCRIPTION" , "ОГейм - легендарная космическая стратегия! Откройте вселенную вместе с тысячами игроков!");

        loca_add("ERROR_0" , "OK");
        loca_add("ERROR_101" , "Bu oyuncu ismi zaten kullanılıyor!");
        loca_add("ERROR_102" , "Bu email adresi zaten kullanılıyor!");
        loca_add("ERROR_103" , "Verilen ismin uzunluğu 3 ila 20 karakter arasında olmalıdır!");
        loca_add("ERROR_104" , "Geçerli bir email adresi vermen gerekiyor!");
        loca_add("ERROR_105" , "Oyuncu ismi OK");
        loca_add("ERROR_106" , "Email adresi OK");
        loca_add("ERROR_107" , "Şifre en az 8 karakter uzunluğunda olmalı!");
        loca_add("ERROR_108" , "Sadece bir kez 10 dakika içinde bir IP den Kayıt!");

        loca_add("TIP_201" , "Oyuncu ismi:<br />Oyun içinde imparator ismi olarak kullanacağın isimdir. Her evren içinde bir isimden sadece bir adet bulunabilir.");
        loca_add("TIP_202" , "E-posta Adresi:<br /> Oyun şifresinin gönderildiği adrestir. Eger verilen adres geçersizse, oyuna katılım da mümkün olmayacaktır; çünkü oyuncu hesabı aktivasyon linki bu adrese gönderilecektir. Bu işlemi tamamlamak için 3 günlük vaktin var,  ve bu süre tamamlanana kadar oyuna normal devam edebilirsin.");
        loca_add("TIP_203" , "");
        loca_add("TIP_204" , "GIS:<br />OGame oyunumuza katılabilmek için sunulan GISleri( Genel Iş Sartları) onaylaman gerekiyor.");
        loca_add("TIP_205" , "Sifre:<br/>Sifren sadece sana özel kalmalıdır. Lütfen başka kimseye gösterme, paylasma ve kullanması için izin verme. Hesap güvenliğin açısından bunlara uyman çok önemlidir.");

        loca_add("MENU_START",       "Başlangıç Sayfası");
        loca_add("MENU_ABOUT",       "OGame Hakkında");
        loca_add("MENU_PICTURES",    "Ekran Görüntüleri");
        loca_add("MENU_REG",         "Oyuna Katılmak");
        loca_add("MENU_BOARD",       "Forum");

        loca_add("LOGIN_LINK" , "Link Giriş");
        loca_add("LOGIN_NAME" , "Oyuncu Ismi");
        loca_add("LOGIN_PASS" , "Şifre");
        loca_add("LOGIN_CHOOSE_UNI" , "Evreni seç lütfen...");
        loca_add("LOGIN_UNI" , "Evren");
        loca_add("LOGIN_CONFIRM" , "Girişimle beraber kabul ediyorum:");
        loca_add("LOGIN_IMPRESSUM" , "GIS");
        loca_add("LOGIN_REMIND" , "Sifreni unuttuysan?");
        loca_add("LOGIN_NOTCHOSEN" , "Evren seçmedin!");

        loca_add("CHOOSELANG" , "Dilinizi seçin");
        loca_add("COPYRIGHT" , "Tüm hakları saklıdır.");
        loca_add("DOWN_RULES" , "Kurallar");
        loca_add("DOWN_IMPRINT" , "Kurumsal");
        loca_add("DOWN_TAC" , "GIS");

        loca_add("HOME_TITLE",  "OGame dünyamıza hoşgeldin!");
        loca_add("HOME_TEXT1",  "<strong>OGame</strong>bir çesit <strong>uzay stratejisi </strong>simulasyonudur. <strong>Aynı anda binlerce</strong>oyuncu tarafından <strong>karsılıklı</strong>olarak oynanır. Oynayabilmek için sadece normal bir internet gezgini gerekir.");
        loca_add("HOME_TEXT2",  "Sen de başvur ve fantastik OGame dünyasıyla tanış!");
        loca_add("HOME_BIGBUTTON",  "Ücretsiz oyunumuza sen de katıl!");

        loca_add("ABOUT_TITLE",  "OGame nedir?");
        loca_add("ABOUT_TEXT1",  " OGame galaksiler arası imparatorluk mücadelesidir.");
        loca_add("ABOUT_TEXT2",  "Kendi küçük bir gezegenin üzerinde basladığın oyunda amacın <strong>güclü bir imparatorluk kurup</strong>, zorlukla elde ettiğin kolonilerini savunmak olmalıdır. ");
        loca_add("ABOUT_TEXT3",  "Hem ekomomik hem de askeri alanda<strong>gereken teknolojileri geliştirip</strong> diğer imparatorluklar içinde yükselen yıldız olmaya çalıs. ");
        loca_add("ABOUT_TEXT4",  "<strong>Savaşlara katıl</strong> ve diğer imparatorluklarla girdiğin hammadde mücadelesinde bir adim öne geç!");
        loca_add("ABOUT_TEXT5",  "<strong>Ticaret yap!</strong> Savaşmak istemediğin zamanlarda gerekenleri ticaret yoluyla sağlamaya çalış. ");
        loca_add("ABOUT_TEXT6",  "<strong>Güçlü bir filo </strong> inşa ederek ismini tüm evrene duyur!");
        loca_add("ABOUT_TEXT7",  "<strong>Kaynaklarını saldırılar</strong> karşısında çökmeyecek bir savunma duvarı arkasında saklamaya özen göster.");
        loca_add("ABOUT_TEXT8",  "<strong>OGame</strong> sınırsız<strong>imkanlar sunar.</strong>");
        loca_add("ABOUT_TEXT9",  "Saldırgan bir tutumla komsularını yıldırmak mı istersin? Yoksa güçsüzün yanında yer alıp koruyucu melek mi olmak istersin? ");
        loca_add("ABOUT_STORY",  "OGame hikayesini okumayı unutma");

        loca_add("STORY_TITLE", "История");
        loca_add("STORY_HEAD", "История ОГейма");
        loca_add("STORY_TEXT", "
        <p><img src=\"img/ogame_technokrat.jpg\" class=\"imageRight\">Size burada bir irkin hikayesini ,bizim irkimizin hikayesini,anlatmak istiyorum.Isin komik yani bu hikayenin aslinda sizin zamaninizda henüz yasanmamis olmasi.Gercekte her seyin paralel yasandigini anlamaniz icin ,size bu hikayeyi yine de anlatacam. Gecmiste yasanan her sey günümüzü, günümüzde yasanan her sey de gelecegimizi olusturur. Ve biz de aslinda, insanin gelecegi degistirmeden gecmise nasil dönebilecegini, kesfettik .Insan gelecegi,sadece onu kendisi yasarsa, degistirebilir.Bunu anlamasi belki cok zor ama gercek bu iste. Bir sey ancak yasanirsa gercekten degistirilebilir,yani gecmisin yasanmis bir parcasini olusturuyorsa. Sen de su andan itibaren kaybolacaksin ama yine de hersey ayni kalacak; cünkü bu yasananlar benim gecmisim.
Neyse ben en iyisi size bu sekilde iskence cektirmeyi birakip,hikayemi anlatmaya baslayim.</p>
<p>
<img src=\"img/fight.gif\" class=\"imageRight\">

Her sey 2250 yilinda basladi,bu yila Alfa yili da denirdi aramizda. Bu yilda insanlar galaksiler arasi 3 dakikadan biraz uzunca bir ucusu gerceklestirerek ,o zamana kadar hic bir sondanin basaramadigi inanilmaz önemli bir basari elde ettiler.</p>
<img src=\"img/ogame_ingenieur.jpg\" class=\"imageLeft\">
<p>
Yanmali motortakimi adini verdigimiz teknigin bu yeni cesidi ile ( Motortakimlari eski yöntem arkadan itme sistemine göre çalisiyorlardi) insanlar isik hizi ile ucabilmeyi kesfettiler. Tabi ki cok eskiden de ucabilirdi insanoglu, ama su ana kiyasla inanilmaz az mesafe katedebiliyordu. Insanoglunun yeterince uzay seyahati tecrübesi yoktu. Daha uzak bölgelere ulasabilmek, yeni macera cesidi olmus ve insanlar bu amacla yeni tekniklerin pesinde kosmaya baslamislardi. Bu noktada insanlar arasinda rekabetin cok yüksek olmasi gelisime büyük katkida bulundu . Boeing bu motor teknolojisinin daha da gelismesi icin büyük caba sarfetti. Dünya yüzeyinde bu motorlari kullanmak,cevreye verdikleri zarar yüzünden ,yasakti; ama uzayda bu konuda gecerli olan kurallar daha farkliydi tabi ki. Airbus firmasi ise bu fazla güvenilir olmayan ve henüz cok gelismemis teknolojiyi degistirmek niyetindeydi.En baslarda bu motor takimlarini kullanan gemilerin cok sik patlamalari olumsuz bir hava estirdi. Ancak Yüksek Atom Fizikcisi Albert Ainstein Y-parcalarinin tek baslarina degil de, bu olusumdan önce baska gazlar ile birlikte denkleme girdikleri ve yandiklari,cok daha saglam ve etkili olan bir teknoloji cesidi kesfetti. Ama baskalari da galaksileri arastirmaya baslamisti ve ilk gezegenlerarasi ucuslardan cok kisa bir süre sonra cok daha güclü motorlar kullanilmaya baslandi.Amac uzun ucus sürelerini kisaltmakti. Yeni motorlarin calismasi icin Deuterium maddesine ihtiyac vardi.</p>
<img src=\"img/ogame_geologe.jpg\" class=\"imageRight\">
                      <p>
Dünyadaki tüm su kaynaklarinin ne yazik ki sadece yüzde 0,015 i Deuterium izotopundan olusuyordu. Bu da varolan bu izotop miktarinin yeterli olmayacagini gösteriyordu.Ülkelerin bilim adamlari on yillarca Deuterium miktarinin nasil arttirilabilecegi konusunda arastirmalar yaptilar ve sonunda Quasar patlamasi sonucu ortaya cikan bir cesit Y-gazinin yardimiyla yeni bir motor gelistirmeyi basardilar.Bunun calismasi Y-Gazinin ve Deuterium maddesinin birlesimi sonucu mümkün oluyordu. Bu iki gazin yanmasi sonucu ortaya cikan cok büyük miktardaki enerji ile gemiler daha hizli gidebiliyorlardi. Friedrich Terrane isimli bilim adami bu tezden yola cikarak yeni bir mekanizma gelistirdi.Bu tez bir cok bilimadami tarafindan teorik anlamda cok elestirildi ve eldeki bilim seviyesine göre böyle bir mekanizmanin asla gelistirilemeyecegi iddia edildi.Ama bu mekanizma pratikte de calisti!. Bu yeni motor takimi inanilmaz bir yenilige imza atti, ve insanlara galaksiler arasi yolculugu cok daha kisa sürelerde yapma firsati sundu. Bos alan ve kolonilestirilebilecek bölge baslangicta cok fazlaydi, ve böylece on yillar , hatta refah ve baris dolu yüzyillar gecti. Yüzlerce yeni ülke kuruldu. Yeni hazineler , yeni kültürler yaratildi. Evren sakin ve huzurluydu:</p>
<img src=\"img/light.gif\" class=\"imageRight\">

</p><p align=\"justify\"><span class=\"sizeh1\">Firtina öncesi sessizlik...</span><br /></p>
<p>

Daha uzak yerlere yolculuk edebilmek icin ,bu uzun zamandan sonra bile yeni teknolojiler gelistirildi. Insanlar yine sinirlari zorlamaya basladiklarinda yeni yeni arayislar icine girdiler ve bir gün birkac ögrencinin yanlislikla kesfettigi Hiper Ortam Motoru tüm bunlara gölge düsürdü : Bu yeni motor ,boyutlar arasina gecerek onlarin etkilerini yok eden bir kabarcik üretiyordu,ve asil önemlisi 4. ve 5. boyut arasinda da ise yaramasiydi. Bu kabarcik icinde 4-Boyutlu ortamin kurallari gecerliydi ve bu da eldeki gemilere hic zarar gelmemesini sagliyordu. Normalde ucus yönünde tüm galaksi görülebilir.Ama bu yeni mekanizma ile cok daha ilerdeki dünyalar kesfedilebiliyordu. Xentronium elementinin, bir halk tarafindan kolonilerin birinde kesfine kadar ,gezegenler arasi var olan az bir sorun da diplomasi araciligi ile kolayca cözülüyordu . Sorun ,bu elemente sahip olanin tüm galaksiye hükmedebilecek güce sahip olacaginin anlasilmasiyla basladi .</p>
                                
</p><p align=\"justify\"><span class=\"sizeh1\">Savasin yakinda baslamasi gerekiyordu.....</span><br /></p><img src=\"img/omega.gif\" class=\"imageRight\"><p>

Önümüzdeki yillarda bu elementten sadece 1,5 Mikrogram cikarilabilirdi. Terran irkinin bölgesinde bulundugu icin sadece Terran Halki bundan yararlanabilirdi. Xentroniumun bir mikrogrami 10.000 terran Günesinin verdigi enerjiyi sagliyordu!!! Ve kiskanclik ,acgözlülük sonucu ilk düsmanliklar baslamisti bile. Ilk basta Terran Yanlilari ve Terran Karsitlari olarak iki Ittifak kuruldu. Nostradamus zamaninda cok büyük bir güc ugruna 150 yil sürecek bir savasi iddia etmisti. Ama bu savas önümüzdeki 300 yildan daha uzun sürecekti. Binlerce halk ya yok edildi ya da baska halklarin boyunduruguna girdi. Hayatta kalmayi basarabilen bir kac halk , bu savasin nasil sona erecegi konusunda coktan kafa yormaya baslamislardi. Ama kacacak yer var miydi? Savasin baslamasindan 125 yil sonra, Kacak Halklar Bilim Birligine bagli bilim adamlari tarafindan, instabil Beryllium kullanimi ile, bir geminin icinden gecerken yok olmayacagi Siyah Delik yaratilabilecegi kesfedildi. Bu kesfin tek eksi yani,acilan Siyah Deligin tek bir yönde ilerleyen Beyaz bir Delikle birlesme ihtimali......<br /></p>
<img src=\"img/legorians.jpg\" class=\"bigImage\">

<p>Birkac basarili denemeden sonra kacaklar icin özgürlük gemilerinin hazirlanisina baslanildi . 25 yil daha gecmisti ve durum Terran Yanlilari Ittifaki icin hic de iyi görünmüyordu. Karsitlar evren icin cok yikici olan Omega silahini devreye sokmuslardi. Bu omega silahi ve yaydigi enerji galaksinin bazi kesimlerini tamamen yoketmisti. Tüm hizlariyla gezegenleri yokediyor, irklarin kökünü kurutuyor ve insan yasamini yokediyorlardi; hesaplara göre bu sürecin devami durumunda 24 yil icinde tüm galaksi yokolabilirdi. Kacmaya calisanlar da, bilimin yardimi ile siyah delikler arasi yolculuk yapiyor ve hayatta kalmak icin son sanslarini da kullaniyorlardi...<br /></p>
<img src=\"img/ogame_admiral_left.jpg\" class=\"imageLeft\">

</p><p align=\"justify\"><span class=\"sizeh1\">Yeni bir hayat</span><br /></p>
<p>

Günün birinde Beyaz Delik insanlari icinde sadece tek bir gezegende hayat olan yepyeni bir galaksiye cikardi. Bu gezegende insanlar cok garip bir halk olan Lejyonerler ile karsilastilar. Teknik acidan insanlar ile ayni seviyedeydiler ve en önemlisi Terranli olsun olmasin herkese cok iyi davrandilar. Bu halk insanlara galaksilerine yerlesme izni verdi ama iki de sart kostu: Her halk en fazla 9 gezegende yerlesim kurabilir,ki mevcut yer herkese yetsin.Ayrica her irktan bagimsiz olarak, tüm galaksi üzerinde söz sahibi olan ve kararlar alabilen bir meclis kurulmaliydi. Halklar bu olusumu onayladilar ve ticareti,iliskileri kontrol eden, barisin sembolü olan bagimsiz bir meclis de kuruldu .<br />

</p><p align=\"justify\"><span class=\"sizeh1\">Yeni bir uygarlik olusumu basladi..</span><br />

Soruyorsundur kendine bunu sana niye anlattigimi. Söyle desem: Sana burada benimle birlikte gelecege gelme sansi taniyorum. Takip et beni,ve sansini dene. Baslangic icin sana görkemli bir gezegen üzerinde hakimiyet sagliyorum. Ayni zamanda bu gezegenin üzerinde gelecekteki uygarligini kurman ve halkini yerlestirmen icin yeterince bos alan da var. Güce ulasabilmek icin her seyi kendin kurman ve gelistirmen lazim. Kesinlikle eminim ki, cok memnun kalacaksin. Su önündeki zaman kapisindan gec ve yeniliklerle dolu bambaska bir dünyaya gir. Baslangic cok zor olacak. Halkina refah ve huzur getirip getiremeyecegini zaman gösterecek. Simdi seni yalniz birakiyorum, karar tamamen sana ait.Bol Sans!

</p><p align=\"justify\"><font size=\"2\">by <a href=\"mailto:Huelli787756@hotmail.com\">Midas</a></font></p><br/> 
");
        loca_add("STORY_JOIN", "Join now!");

        loca_add("PICS_TITLE", "Resimler");
        loca_add("PICS_SCREENSHOTS", "Resimler");
        loca_add("PICS_WALLPAPERS", "Duvar Kağıtları");
        loca_add("PICS_WALL1", "Genel Durum");
        loca_add("PICS_WALL2", "Bina");
        loca_add("PICS_WALL3", "Tersane");
        loca_add("PICS_WALL4", "Imparatorluk");

        loca_add("JOIN_TITLE", "Oyuna katılmak");
        loca_add("JOIN_HEAD", "Oyuna katılabilmek icin sadece<strong>oyuncu ismi</strong>,bir<strong>şifre</strong> ve<strong>email adresi </strong>bildirmen ve bundan sonra da GISleri kabul etmek için ilgili kutucuğu işaretlemen gerekiyor.");
        loca_add("JOIN_NAME", "Oyuncu Ismi:");
        loca_add("JOIN_EMAIL", "E-Posta Adresi:");
        loca_add("JOIN_PASS", "Sifre:");
        loca_add("JOIN_ADVICE", "Evren:");
        loca_add("JOIN_TIP", "tavsiye edilen");
        loca_add("JOIN_UNIS", "Özel evrenlerin özellikleri");
        loca_add("JOIN_IACCEPT", "Kabul ediyorum:");
        loca_add("JOIN_TAC", "GIS");
        loca_add("JOIN_REGISTER", "Başvuruyu gönder");
    }
*/

    // Язык стартовой страницы
    if ( !isset ($_COOKIE['ogamelang']) ) $LocaLang = "ru";
    else $LocaLang = $_COOKIE['ogamelang'];
//    if ($LocaLang !== 'de' && $LocaLang !== 'en' && $LocaLang !== 'ru') $LocaLang = "en";        // restrict unsupported languages


