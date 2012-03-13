<?php

// ========================================================================================
// Общее сообщение пользователям.

function Admin_Broadcast ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $write_error = "";

    // Обработка POST-запроса.
    if ( method () === "POST" )
    {
        $cat = $_POST['cat'];
        $subj = $_POST['subj'];
        if ( $subj === "" ) $write_error = "<center><font color=#FF0000>Заполните тему</font><br/></center>\n";
        $text = $_POST['text'];
        if ( $text === "" ) $write_error = "<center><font color=#FF0000>Введите текст сообщения</font><br/></center>\n";

        if ( $write_error === "" )
        {
            if ( $cat == 1 ) $query = "SELECT * FROM ".$db_prefix."users WHERE score1 < 5000;";        // Новичкам (менее 5.000 очков)
            else if ( $cat == 2 ) $query = "SELECT * FROM ".$db_prefix."users WHERE place1 < 100;";        // Игрокам из топ100
            else if ( $cat == 3 ) $query = "SELECT * FROM ".$db_prefix."users WHERE admin = 1;";        // Операторам
            else $query = "SELECT * FROM ".$db_prefix."users;";                // Всем

            $ownhome = GetPlanet ( $GlobalUser['hplanetid'] );

            $from = $GlobalUser['oname'] . " <a href=\"index.php?page=galaxy&galaxy=".$ownhome['g']."&system=".$ownhome['s']."&position=".$ownhome['p']."&session={PUBLIC_SESSION}\">[".$ownhome['g'].":".$ownhome['s'].":".$ownhome['p']."]</a>\n";
            $subj = $subj . " <a href=\"index.php?page=writemessages&session={PUBLIC_SESSION}&messageziel=".$GlobalUser['player_id']."&re=1&betreff=Re:".$subj."\">\n"
                        . "</a>\n";            

            $text = str_replace ( '\"', "&quot;", bb($text) );
            $text = str_replace ( '\'', "&rsquo;", $text );
            $text = str_replace ( '\`', "&lsquo;", $text );

            $result = dbquery ($query);
            $usernum = $rows = dbrows ($result);
            while ($rows--)
            {
                $user = dbarray ($result);
                SendMessage ( $user['player_id'], $from, $subj, $text, 5);
            }
            if ($usernum > 0) $write_error = "<center><font color=#00FF00>Сообщение отправлено $usernum пользователям.</font><br/></center>\n";
            else $write_error = "<center><font color=#00FF00>Адресаты не найдены.</font><br/></center>\n";
        }
    }

?>

<?=AdminPanel();?>

<?=$write_error;?>

<table>
<form action="index.php?page=admin&session=<?=$session;?>&mode=Broadcast" method="POST">

<tr><td>
Кому: <select name="cat">
<option value="0">Всем</option>
<option value="1">Новичкам (менее 5.000 очков)</option>
<option value="2">Игрокам из топ100</option>
<option value="3">Операторам</option>
</select>
</td></tr>

<tr><td>
Тема : <input name="subj" size=80>
</td></tr>

<tr><td>
<textarea cols='100' rows='20' name='text'></textarea>
</td></tr>

<tr><td>
<center><input type="submit" value="Отправить"></center>
</td></tr>

</form>
</table>

<?php
}

?>