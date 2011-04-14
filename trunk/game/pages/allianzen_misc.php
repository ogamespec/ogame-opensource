<?php

// Всякие мелочи.

// Покинуть альянс.
function PageAlly_Leave ()
{
    global $db_prefix;
    global $GlobalUser;
    global $session;
    global $ally;

    if ( method () === "POST" )
    {
        if ( $_GET['weiter'] == 1) 
        {
            $player_id = $GlobalUser['player_id'];
            $leaver = LoadUser ($player_id);

            $query = "UPDATE ".$db_prefix."users SET ally_id = 0 WHERE player_id = $player_id";
            dbquery ($query);

            // Разослать сообщения членам альянса о том что игрок покинул альянс.
            $result = EnumerateAlly ($ally['ally_id']);
            $rows = dbrows ($result);
            while ($rows--)
            {
                $user = dbarray ($result);
                SendMessage ( $user['player_id'], va("Альянс [#1]", $ally['tag']), "Общее сообщение", va("Игрок #1 покинул альянс.", $leaver['oname']), 0);
            }

            // Сделать редирект на страницу Мой альянс.
            ob_end_clean ();
            $url = "index.php?page=allianzen&session=$session";
            echo "<html><head><meta http-equiv='refresh' content='0;url=$url' /></head><body></body>";
            die ();
        }
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=3&weiter=1" method=POST>
<tr><td class=c colspan=2><?=va("Вы действительно хотите покинуть альянс \"#1\"?", $ally['tag']);?></td></tr>
<tr><th colspan=2><br><input type=submit value="Да, хочу!"></th></tr></table></center></form>
<?php
}

?>