<?php

// Registerunden, loginen, email restorationichtenunden

$LOCA["de"]["REG_MAIL_TITLE"] = "Übersicht";
$LOCA["de"]["REG_MAIL_SEND"] = "Passwort senden";
$LOCA["de"]["REG_MAIL_NOTE"] = "Bitte die permanente E-Mail Adresse des Spielaccounts eingeben.";
$LOCA["de"]["REG_MAIL_EMAIL"] = "E-Mail:";
$LOCA["de"]["REG_MAIL_SUBMIT"] = "Zugangsdaten Senden";

$LOCA["de"]["REG_FORGOT_TITLE"] = "#1 Passwort versenden";
$LOCA["de"]["REG_FORGOT_ERROR"] = "Keine gültige permanente Emailaddresse.";
$LOCA["de"]["REG_FORGOT_OK"] = "Das Passwort wurde an #1 verschickt.";
$LOCA["de"]["REG_FORGOT_SUBJ"] = "#1 Passwort";
$LOCA["de"]["REG_FORGOT_MAIL"] = "Hallo #1,\n\n" .
"dein Passwort für das #5 Universum #2 lautet:\n\n" .
"#3\n\n" .
"Du kannst dich damit unter #4 einloggen.\n\n" .
"Wir verschicken Passwörter nur an die von dir im Spiel angegebenen Mailadressen. Solltest du diese Mail nicht angefordert haben kannst du sie also einfach ignorieren.\n\n" .
"Wir wünschen dir weiterhin viel Erfolg beim Spielen von #5!\n\n" .
"Dein #5-Team";

// Sitzungsfehler beim Laden der Seite

$LOCA["de"]["REG_SESSION_INVALID"] = "Die Sitzung ist null und nichtig.";
$LOCA["de"]["REG_SESSION_ERROR"] = "Es ist ein Fehler aufgetreten";
$LOCA["de"]["REG_SESSION_ERROR_BODY"] = "    <br /><br />
    Die Sitzung ist null und nichtig.<br/><br/>Dafür kann es mehrere Gründe geben: 
<br>- Sie haben sich mehrmals bei demselben Konto angemeldet; 
<br>- Ihre IP-Adresse hat sich seit Ihrer letzten Anmeldung geändert; 
<br>- Sie nutzen das Internet über AOL oder einen Proxy. Schalten Sie die IP-Überprüfung im Menü \"Einstellungen\" Ihres Kontos aus.    
    <br /><br />
";

$LOCA["de"]["REG_NOT_ACTIVATED"] = "Ihr Spielkonto wurde noch nicht aktiviert. Gehe zu <a href=index.php?page=options&session=#1>Einstellungen</a>, geben Sie Ihre E-Mail-Adresse ein und erhalten Sie einen Aktivierungslink dazu";
$LOCA["de"]["REG_PENDING_DELETE"] = "Ihr Konto wurde zur Löschung vorgemerkt. Datum der Löschung: #1";

// Spieler versucht zu schreiben, ohne das Konto zu aktivieren
$LOCA["de"]["REG_NOT_ACTIVATED_MESSAGE"] = "Diese Funktion ist erst nach der Aktivierung des Kontos verfügbar.";

// errorpage

$LOCA["de"]["REG_ERROR"] = "Fehler";
$LOCA["de"]["REG_ERROR_21"] = "Sie haben versucht, Universum #1 unter dem Nickname #2 zu betreten.";
$LOCA["de"]["REG_ERROR_22"] = "Dieses Konto existiert nicht oder Sie haben Ihr Passwort falsch eingegeben. ";
$LOCA["de"]["REG_ERROR_23"] = "Eingabe <a href='#1'>korrektes Passwort</a> oder verwenden <a href='mail.php'>Passwort-Wiederherstellung</a>.";
$LOCA["de"]["REG_ERROR_24"] = "Sie können auch erstellen <a href='new.php'>neues Konto</a>.";
$LOCA["de"]["REG_ERROR_31"] = "Dieses Konto wurde auf #1 gesperrt, bitte sehen Sie weitere Details <a href=../pranger.php>hier</a>.<br> Wenn Sie Fragen haben, wenden Sie sich bitte an den Blocker <a href='#'>Operator</a>.<br><br>WARNUNG: Der Commander-Status wird nicht beendet, wenn er blockiert ist, die Beendigung erfolgt separat!";

// new.php

$LOCA["de"]["REG_NEW_ERROR_AGB"] = "Um das Spiel zu beginnen, müssen Sie die Grundbestimmungen akzeptieren!";
$LOCA["de"]["REG_NEW_ERROR_IP"] = "Registrierung von einer ipi nicht mehr als einmal pro 10 Minuten!";
$LOCA["de"]["REG_NEW_ERROR_CHARS"] = "Name #1 enthält ungültige Zeichen oder zu wenige/viele Zeichen!";
$LOCA["de"]["REG_NEW_ERROR_EXISTS"] = "Name #1 existiert bereits";
$LOCA["de"]["REG_NEW_ERROR_EMAIL"] = "Adresse #1 ist ungültig!";
$LOCA["de"]["REG_NEW_ERROR_EMAIL_EXISTS"] = "Adresse #1 existiert bereits!";
$LOCA["de"]["REG_NEW_ERROR_MAX_PLAYERS"] = "Die maximale Anzahl von Spielern (#1) wurde erreicht!";
$LOCA["de"]["REG_NEW_TITLE"] = "OGame Universe #1 Registrierung";
$LOCA["de"]["REG_NEW_SUCCESS"] = "Die Anmeldung ist gut gelaufen!";
$LOCA["de"]["REG_NEW_TEXT"] = "Glückwunsch, <span class='fine'>#1</span>!<br /><br />Sie haben sich erfolgreich bei OGame registriert (<span class='fine'>#2</span>). <br />\n".
            "Sie werden erhalten <span class='fine'>#3</span> eine E-Mail mit einem Passwort und einigen wichtigen Links.<br />\n".
            "Um spielen zu können, müssen Sie eingeloggt sein über <a href='#4'>Hauptseite</a>.<br />\n".
            "In der folgenden Abbildung sehen Sie, wie man es richtig macht.<br /><br />\n" .
            "<center><a href='#5' style='text-decoration: underline;font-size: large;'>Los geht's!</a></center><br /><br /> \n" .
            "Viel Glück!<br /> \n" .
            "Ihr OGame-Team</th>";
$LOCA["de"]["REG_NEW_UNI"] = "Universum #1";
$LOCA["de"]["REG_NEW_CHOOSE_UNI"] = "Wählen Sie ein Universum";
$LOCA["de"]["REG_NEW_NAME"] = "Einen Namen eingeben";
$LOCA["de"]["REG_NEW_PASSWORD"] = "Und das Passwort gesendet!";
$LOCA["de"]["REG_NEW_ERROR"] = "Fehler";
$LOCA["de"]["REG_NEW_PLAYER_INFO"] = "Spieler-Daten";
$LOCA["de"]["REG_NEW_PLAYER_NAME"] = "Name im Spiel";
$LOCA["de"]["REG_NEW_PLAYER_EMAIL"] = "E-Mail Adresse";
$LOCA["de"]["REG_NEW_ACCEPT"] = "Ich stimme zu mit";
$LOCA["de"]["REG_NEW_AGB"] = "Grundlegende Bestimmungen";
$LOCA["de"]["REG_NEW_SUBMIT"] = "Registrieren Sie sich";
$LOCA["de"]["REG_NEW_INFO"] = "Infos";

$LOCA["de"]["REG_NEW_MESSAGE_0"] = "OK";
$LOCA["de"]["REG_NEW_MESSAGE_101"] = "Ein solcher Name existiert bereits!";
$LOCA["de"]["REG_NEW_MESSAGE_102"] = "Diese Adresse ist bereits in Gebrauch!";
$LOCA["de"]["REG_NEW_MESSAGE_103"] = "Der Name muss zwischen 3 und 20 Zeichen lang sein!";
$LOCA["de"]["REG_NEW_MESSAGE_104"] = "Die Adresse ist ungültig!";
$LOCA["de"]["REG_NEW_MESSAGE_105"] = "Der Name des Spielers ist in Ordnung";
$LOCA["de"]["REG_NEW_MESSAGE_106"] = "Eine Adresse ist in Ordnung";
$LOCA["de"]["REG_NEW_MESSAGE_107"] = "Die Adresse ist ungültig!";
$LOCA["de"]["REG_NEW_MESSAGE_108"] = "Registrierung von einer ipi nicht mehr als einmal pro 10 Minuten!";
$LOCA["de"]["REG_NEW_MESSAGE_109"] = "Die maximale Anzahl von Spielern wurde erreicht!";
$LOCA["de"]["REG_NEW_MESSAGE_201"] = "Name im Spiel: <br />Dies ist der Name deines Charakters im Spiel. Keine zwei Namen können im selben Universum gleich sein.";
$LOCA["de"]["REG_NEW_MESSAGE_202"] = "E-Mail: <br />Ihr Passwort wird an diese Adresse geschickt. Wenn Sie eine falsche oder ungültige Adresse eingeben, können Sie nicht spielen.";
$LOCA["de"]["REG_NEW_MESSAGE_203"] = "";
$LOCA["de"]["REG_NEW_MESSAGE_204"] = "Um mit dem Spiel beginnen zu können, müssen Sie den Grundregeln zustimmen.";

// user.php

$LOCA["de"]["REG_GREET_MAIL_SUBJ"] = "Willkommen bei #1 ";
$LOCA["de"]["REG_GREET_MAIL_BODY"] = "Grüße #1,\n\n" .
            "Du hast beschlossen, dein eigenes Imperium in #2 des #7-Universums zu gründen!\n\n" .
            "Klicken Sie auf diesen Link, um Ihr Konto zu aktivieren:\n" .
            "#3\n\n" .
            "Ihre Spieldetails:\n" .
            "Name des Spiels: #4\n" .
            "Passwort: #5\n" .
            "Universum: #6\n\n\n";
$LOCA["de"]["REG_GREET_MAIL_BOARD"] = "Wenn du Hilfe oder Ratschläge von anderen Kaisern brauchst, findest du sie alle in unserem Forum (#1).\n\n";
$LOCA["de"]["REG_GREET_MAIL_TUTORIAL"] = "Hier (#1) finden Sie alle Informationen, die von Spielern und Teammitgliedern zusammengetragen wurden, um Neueinsteigern zu helfen, das Spiel so schnell wie möglich zu verstehen.\n\n";
$LOCA["de"]["REG_GREET_MAIL_FOOTER"] = "Wir wünschen euch viel Erfolg beim Aufbau eures Imperiums und viel Glück in den kommenden Schlachten!\n\nIhr #1-Team";

$LOCA["de"]["REG_CHANGE_MAIL_SUBJ"] = "Deine E-Mail Adresse im Spiel wurde geändert ";
$LOCA["de"]["REG_CHANGE_MAIL_BODY"] = "Grüße #1,\n\n" .
            "Die temporäre E-Mail-Adresse Ihres Kontos im Universum #2 wurde in den Einstellungen auf #3 geändert.\n" .
            "Wenn Sie sie nicht innerhalb einer Woche ändern, wird sie dauerhaft.\n\n" .
            "Bestätigen Sie Ihre neue E-Mail-Adresse über den folgenden Link, um ohne Probleme weiterspielen zu können:\n\n" .
            "#4\n\n" .
            "Ihr OGame-Team";

$LOCA["de"]["REG_GREET_MSG_SUBJ"] = "Willkommen bei #1!";
$LOCA["de"]["REG_GREET_MSG_TEXT"] = "Willkommen bei [b]#3[/b] !\n" .
        "\n" .
        "Erstens müssen Sie die Minen entwickeln.\n" .
        "Sie können dies im Menü \"Gebäude\" tun.\n" .
        "Wählen Sie eine Metallmine und drücken Sie \"bauen\".\n" .
        "Jetzt haben Sie etwas Zeit, um sich mit dem Spiel vertraut zu machen.\n" .
        "Unter diesen Links finden Sie Hilfe für das Spiel: \n" .
        "[url=#1/]Tutorial[/url]\n" .
        "[url=#2/]Forum[/url]\n" .
        "\n" .
        "In der Zwischenzeit sollte Ihr Bergwerk bereits gebaut sein.\n" .
        "Die Minen brauchen Energie für ihren Betrieb, also bauen Sie ein Solarkraftwerk, um sie zu gewinnen.\n" .
        "Gehen Sie dazu zurück zum Menü \"Gebäude\" und klicken Sie auf das Kraftwerk.\n" .
        "Um zu sehen, wie weit Sie in Ihrer Entwicklung gekommen sind, gehen Sie zum Menü \"Technology\".\n" .
        "Ihr Siegeszug durch das Universum hat also begonnen... Viel Glück!\n";

// logout

$LOCA["de"]["REG_LOGOUT"] = "Bis bald!!";

?>