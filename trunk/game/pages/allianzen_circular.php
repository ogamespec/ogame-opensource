<?php

// Общее сообщение.

function AllyPage_CircularMessage ()
{
    global $db_prefix;
    global $GlobalUser;
    global $session;
    global $ally;
    global $AllianzenError;

    if ( method () === "POST" && key_exists ('r', $_POST) )
    {
        $ally_id = $ally['ally_id'];
        $myrank = LoadRank ( $ally_id, $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & 0x080) )
        {
            $AllianzenError = "<center>\nНедостаточно прав для проведения операции<br></center>";
            return;
        }
        $rank_id = intval($_POST['r']);
        if ( $rank_id == 0 ) $query = "SELECT * FROM ".$db_prefix."users WHERE ally_id = $ally_id";
        else $query = "SELECT * FROM ".$db_prefix."users WHERE ally_id = $ally_id AND allyrank = $rank_id";
        $result = dbquery ($query);
        $rows = dbrows ( $result );
        if ( $rows )
        {
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><td class=c>Следующие игроки получили Ваше общее послание</td></tr>
<tr><th>
<?php

            $text = str_replace ( '\"', "&quot;", bb($_POST['text']) );
            $text = str_replace ( '\'', "&rsquo;", $text );
            $text = str_replace ( '\`', "&lsquo;", $text );

            while ($rows--)
            {
                $user = dbarray ($result);
                SendMessage ( $user['player_id'], 
                                       va ( "Альянс [#1]", $ally['tag'] ),
                                       va ( "Общее послание Вашему альянсу [#1]", $ally['tag'] ), 
                                       va ( "Игрок #1 сообщает Вам следующее:<br>#2", $GlobalUser['oname'], $text ), 0 );
                echo $user['oname'] . "<br>\n";
            }
?>
</th></tr>
<tr><th><input type=submit value="Ok"></th></tr>
</table></center></form>
<?php
        }
        else
        {
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=17" method=POST>
<tr><td class=c>Ошибка</td></tr>
<tr><th>К сожалению, получатели не найдены</th></tr>
<tr><th><input type=submit value="Назад"></th></tr>
</table></center></form>
<?php
        }
        return;
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=17&sendmail=1" method=POST>
<tr><td class=c colspan=2>Отправить общее сообщение</td></tr>
<tr><th>Получатель</th><th>
<select name=r>
    <option value=0>Все игроки</option>
<?php
    $result = EnumRanks ( $ally['ally_id'] );
    $rows = dbrows ($result);
    while ($rows--)
    {
        $rank = dbarray ($result);
        if ( $rank['rank_id'] == 0 || $rank['rank_id'] == 1 ) continue;    // Основателя и новичка не показываем
        echo "    <option value=".$rank['rank_id'].">Только определённому рангу: ".$rank['name']."</option>\n";
    }
?>
</select></th></tr>
<tr><th>Текст сообщения (<span id="cntChars">0</span> / 2000 Симв.)</th><th><textarea name=text cols=60 rows=10 onkeyup="javascript:cntchar(2000)"></textarea></th></tr>
<tr><th colspan=2><input type=submit value="Отправить"></th></tr></table></center></form>
<?php
}

?>