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

// Изменить аббревиатуру альянса.
function PageAlly_ChangeTag ()
{
    global $GlobalUser;
    global $session;
    global $ally;
    global $AllianzenError;

    if ( method() === "POST" && $_GET['a'] == 9 && $_GET['weiter'] == 1)
    {
        $_POST['newtag'] = str_replace ( "\"", "", $_POST['newtag']);
        $_POST['newtag'] = str_replace ( "'", "", $_POST['newtag']);

        $now = time ();
        $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & 0x020) ) $AllianzenError = "<center>\nНедостаточно прав для проведения операции<br></center>";
        else if ( $now < $ally['tag_until'] ) $AllianzenError = "<center>\nПодождите до ".date ("Y-m-d H:i:s", $ally['tag_until'])."<br></center>";
        else if (mb_strlen ($_POST['newtag'], "UTF-8")  < 3) $AllianzenError = "<center>\nАббревиатура альянса слишком коротка<br></center>";
        else if (IsAllyTagExist ($_POST['newtag'])) $AllianzenError = "<center>\nАльянс ".$_POST['newtag']." к сожалению уже существует!<br></center>";
        else
        {
            AllyChangeTag ( $ally['ally_id'], $_POST['newtag'] );
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><td class=c colspan=2>Подтвердить</td></tr>
<tr><th colspan=2>Альянс с аббревиатурой "<?=$ally['tag'];?>" имеет теперь аббревиатуру "<?=$_POST['newtag'];?>"</th><tr>
<tr><th colspan=2><input type=submit value="Ok"></th></tr></table></center></form>
<?php
            return;
        }
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=9&weiter=1" method=POST>
<tr><td class=c colspan=2>Как следует переименовать альянс "<?=$ally['tag'];?>"?</td></tr>
<tr><th>Новая аббревиатура: #1</th><th><input type=text name=newtag maxlength=8> <input type=submit value="Переименовать"></th></tr>
</table></center></form>
<?php
}

// Изменить название альянса.
function PageAlly_ChangeName ()
{
    global $GlobalUser;
    global $session;
    global $ally;
    global $AllianzenError;

    if ( method() === "POST" && $_GET['a'] == 10 && $_GET['weiter'] == 1)
    {
        $_POST['newname'] = str_replace ( "\"", "", $_POST['newname']);
        $_POST['newname'] = str_replace ( "'", "", $_POST['newname']);

        $now = time ();
        $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & 0x020) ) $AllianzenError = "<center>\nНедостаточно прав для проведения операции<br></center>";
        else if ( $now < $ally['name_until'] ) $AllianzenError = "<center>\nПодождите до ".date ("Y-m-d H:i:s", $ally['name_until'])."<br></center>";
        else if (mb_strlen ($_POST['newname'], "UTF-8")  < 3) $AllianzenError = "<center>\nНазвание альянса слишком короткое<br></center>";
        else
        {
            AllyChangeName ( $ally['ally_id'], $_POST['newname'] );
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><td class=c colspan=2>Подтвердить</td></tr>
<tr><th colspan=2>Альянс "<?=$ally['name'];?>" переименован в "<?=$_POST['newname'];?>"</th><tr>
<tr><th colspan=2><input type=submit value="Ok"></th></tr></table></center></form>
<?php
            return;
        }
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=10&weiter=1" method=POST>
<tr><td class=c colspan=2>Как следует переименовать альянс "<?=$ally['name'];?>"?</td></tr>
<tr><th>Новое название:</th><th><input type=text name=newname maxlength=30> <input type=submit value="Переименовать"></th></tr>
</table></center></form>
<?php
}

// Распустить альянс.
function PageAlly_Dismiss ()
{
    global $GlobalUser;
    global $session;
    global $ally;
    global $AllianzenError;

    if ( method() === "POST" && $_GET['a'] == 12 && $_GET['weiter'] == 1)
    {
        $now = time ();
        $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & 0x001) ) $AllianzenError = "<center>\nНедостаточно прав для проведения операции<br></center>";
        else
        {
            // Послать всем игрокам сообщение о роспуске альянса.
            $from = $ally['name'];
            $subj = va ( "Членство в альянсе[#1]окончено", $ally['tag'] );
            $text = va ( "Игрок #1 распустил альянс [#2].<br>Теперь Вы можете вступить в другой альянс или создать свой собственный", $GlobalUser['oname'], $ally['tag'] );

            $result = EnumerateAlly ($ally['ally_id']);
            $rows = dbrows ($result);
            while ($rows--)
            {
                $user = dbarray ($result);
                SendMessage ( $user['player_id'], $from, $subj, $text, 0);
            }

            // Распустить альянс
            DismissAlly ( $ally['ally_id'] );

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script><table width="519">
<form action="index.php?page=allianzen&session=<?=$session;?>" method="POST">
 <tr><td class="c">Альянс был распущен.</td></tr>
 <tr><th><br><input type=submit value="Ok"></th></tr>
</form>
</table><br><br><br><br>
<?php
        }
        return;
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=12&weiter=1" method=POST>
<tr><td class=c colspan=2>Вы действительно хотите распустить "<?=$ally['name'];?>" альянс?</td></tr>
<tr><th>Внимание!</th><th>Восстановление альянса будет невозможно<br>
и все его члены покинут его!</th></tr>
<tr><th colspan=2><br><input type=submit value="Да, хочу!"></th></tr></table></center></form><br><br><br><br>
<?php
}

// Передать права главый "правой руке"
// Передавать права может только глава альянса.
function AllyPage_Takeover ()
{
    global $GlobalUser;
    global $session;
    global $ally;
    global $AllianzenError;

    // Обменять званиями главу и "правую руку".
    if ( $_GET['a'] == 18 && $_REQUEST['s'] == 1)
    {
        $now = time ();
        $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & 0x100) ) $AllianzenError = "<center>\nНедостаточно прав для проведения операции<br></center>";
        else
        {
            // Выслать всем участникам сообщение что власть поменялась (кроме самого главы).
            $from = va ( "Альянс [#1]", $ally['tag'] );
            $subj = va ( "Смена власти в альянсе [#1]", $ally['tag'] );
            $text = va ( "Игрок #1, обладающий титулом основателя альянса, покинул его", $GlobalUser['oname'], $ally['tag'] );

            $result = EnumerateAlly ($ally['ally_id']);
            $rows = dbrows ($result);
            while ($rows--)
            {
                $user = dbarray ($result);
                if ( $user['player_id'] != $ally['owner_id'] ) SendMessage ( $user['player_id'], $from, $subj, $text, 0);
            }

            // Поменять звания
            $newhead = LoadUser ( intval($_REQUEST['uid']) );
            $newhead_rank = LoadRank ( $ally['ally_id'], $newhead['allyrank'] );
            if ( $newhead['ally_id'] != $ally['ally_id'] || ($newhead_rank['rights'] & 0x100) == 0 ) {
                $AllianzenError = "<center>\nНедостаточно прав для проведения операции<br></center>";
                return;
            }
            SetUserRank ( $newhead['player_id'], $GlobalUser['allyrank'] );
            SetUserRank ( $GlobalUser['player_id'], $newhead['allyrank'] );

            // Установить нового хозяина альянса
            AllyChangeOwner ( $ally['ally_id'], $newhead['player_id'] );

?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method="POST">
<tr><td class=c>Вы утратили свой статус</td></tr><tr><th><br><input type="submit" value="OK"></th></tr></form></table><br><br><br><br>
<?php
        }
        return;
    }

    // Ололош, любой игрок по этому параметру может взять на себя права главы, без всяких проверок.....
    if ( $_GET['a'] == 18 && $_REQUEST['s'] == 2)
    {
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method="POST">
<tr><td class=c>Звание передано</td></tr><tr><th><br><input type="submit" value="OK"></th></tr></form></table><br><br><br><br>
<?php
        return;
    }

    // Если открыть у НЕ главы страничку:
    if ( $ally['owner_id'] != $GlobalUser['player_id'] ) {
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=5" method=POST>
<tr><td class=c>Глава ещё активен</td></tr><tr><th><input type=submit value="Назад"></th></tr></form></table><br><br><br><br>
<?php
        return;
    }

    // Перечислить всех игроков альянса с правами "правая рука". Если никого нет, то просто вывести кнопку "назад".
    $users = array ();
    $rank_result = EnumRanks ( $ally['ally_id'] );
    while ( $rank = dbarray ($rank_result) )
    {
        if ( $rank['rights'] & 0x100 ) {
            $result = LoadUsersWithRank ( $ally['ally_id'], $rank['rank_id'] );
            while ( $user = dbarray ($result) ) {
                if ( $user['player_id'] == $ally['owner_id'] ) continue;    // не показывать главу
                $user['rankname'] = $rank['name'];
                $users[] = $user;
            }
        }
    }
    
    if ( count($users) == 0 ) {    // Никто не найден, вывести кнопку "назад"
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=5" method=POST>
<tr><td class=c></th></tr><tr><th><input type=submit value="Назад"></th></tr></form></table><br><br><br><br>
<?php
    }
    else {    // Перечислить найденных пользователей с рангом "правая рука"
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=18" method=POST>
<input type=hidden name=s value=1>
<tr><td class=c colspan=2><?=va("Перенять руководство над #1", "\"".$ally['name']."\"");?></td></tr>
<tr><th>Передача звания</th><th><select name=uid>
<?php
    foreach ( $users as $i=>$user ) {
        echo "  <option value=".$user['player_id'].">".$user['oname']." (Rang: ".$user['rankname'].")\n";
    }
?></select></th></tr>
<tr><th colspan=2><input type=submit value="Передать"></th></tr></form></table><br><br><br><br>
<?php
    }

}

// EOF

?>