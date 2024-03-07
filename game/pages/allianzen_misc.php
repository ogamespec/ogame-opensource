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
                loca_add ("ally", $user['lang']);
                SendMessage ( $user['player_id'], 
                    va(loca_lang("ALLY_MSG_FROM", $user['lang']), $ally['tag']), 
                    loca_lang ("ALLY_MSG_COMMON", $user['lang']), 
                    va(loca_lang ("ALLY_MSG_LEAVE", $user['lang']), $leaver['oname']), MTYP_ALLY);
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
<tr><td class=c colspan=2><?=va("ALLY_MISC_LEAVE_CONFIRM", $ally['tag']);?></td></tr>
<tr><th colspan=2><br><input type=submit value="<?=loca("ALLY_MISC_YES_FOR_SURE");?>"></th></tr></table></center></form>
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
        if ( ! ($myrank['rights'] & 0x020) ) $AllianzenError = "<center>\n".loca("ALLY_NO_WAY")."<br></center>";
        else if ( $now < $ally['tag_until'] ) $AllianzenError = "<center>\n".va(loca("ALLY_MISC_CHANGE_WAIT"), date ("Y-m-d H:i:s", $ally['tag_until']))."<br></center>";
        else if (mb_strlen ($_POST['newtag'], "UTF-8")  < 3) $AllianzenError = "<center>\n".loca("ALLY_MISC_CHANGE_TAG_SHORT")."<br></center>";
        else if (IsAllyTagExist ($_POST['newtag'])) $AllianzenError = "<center>\n".va(loca("ALLY_MISC_CHANGE_TAG_EXISTS"), $_POST['newtag'])."<br></center>";
        else
        {
            AllyChangeTag ( $ally['ally_id'], $_POST['newtag'] );
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><td class=c colspan=2><?=loca("ALLY_MISC_CONFIRM");?></td></tr>
<tr><th colspan=2><?=va(loca("ALLY_MISC_CHANGE_TAG_SUCCESS"), $ally['tag'], $_POST['newtag']);?></th><tr>
<tr><th colspan=2><input type=submit value="<?=loca("ALLY_MISC_CHANGE_OK");?>"></th></tr></table></center></form>
<?php
            return;
        }
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=9&weiter=1" method=POST>
<tr><td class=c colspan=2><?=va(loca("ALLY_MISC_CHANGE_TAG_HEAD"), $ally['tag']);?></td></tr>
<tr><th><?=loca("ALLY_MISC_CHANGE_TAG_NEW");?></th><th><input type=text name=newtag maxlength=8> <input type=submit value="<?=loca("ALLY_MISC_CHANGE_RENAME");?>"></th></tr>
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
        if ( ! ($myrank['rights'] & 0x020) ) $AllianzenError = "<center>\n".loca("ALLY_NO_WAY")."<br></center>";
        else if ( $now < $ally['name_until'] ) $AllianzenError = "<center>\n".va(loca("ALLY_MISC_CHANGE_WAIT"), date ("Y-m-d H:i:s", $ally['name_until']))."<br></center>";
        else if (mb_strlen ($_POST['newname'], "UTF-8")  < 3) $AllianzenError = "<center>\n".loca("ALLY_MISC_CHANGE_NAME_SHORT")."<br></center>";
        else
        {
            AllyChangeName ( $ally['ally_id'], $_POST['newname'] );
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><td class=c colspan=2><?=loca("ALLY_MISC_CONFIRM");?></td></tr>
<tr><th colspan=2><?=va(loca("ALLY_MISC_CHANGE_NAME_SUCCESS"), $ally['name'], $_POST['newname']);?></th><tr>
<tr><th colspan=2><input type=submit value="<?=loca("ALLY_MISC_CHANGE_OK");?>"></th></tr></table></center></form>
<?php
            return;
        }
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=10&weiter=1" method=POST>
<tr><td class=c colspan=2><?=va(loca("ALLY_MISC_CHANGE_NAME_HEAD"), $ally['name']);?></td></tr>
<tr><th><?=loca("ALLY_MISC_CHANGE_NAME_NEW");?></th><th><input type=text name=newname maxlength=30> <input type=submit value="<?=loca("ALLY_MISC_CHANGE_RENAME");?>"></th></tr>
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

    if ( method() === "POST" && $_GET['a'] == 12 && key_exists('weiter', $_GET) && $_GET['weiter'] == 1)
    {
        $now = time ();
        $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & 0x001) ) $AllianzenError = "<center>\n".loca("ALLY_NO_WAY")."<br></center>";
        else
        {
            // Послать всем игрокам сообщение о роспуске альянса.

            $result = EnumerateAlly ($ally['ally_id']);
            $rows = dbrows ($result);
            while ($rows--)
            {
                $user = dbarray ($result);
                loca_add ("ally", $user['lang']);
                $from = $ally['name'];      // Поле From содержит имя альянса при его роспуске
                $subj = va ( loca_lang("ALLY_MSG_DISMISS_SUBJ", $user['lang']), $ally['tag'] );
                $text = va ( loca_lang("ALLY_MSG_DISMISS", $user['lang']), $GlobalUser['oname'], $ally['tag'] );
                SendMessage ( $user['player_id'], $from, $subj, $text, MTYP_ALLY);
            }

            // Распустить альянс
            DismissAlly ( $ally['ally_id'] );

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script><table width="519">
<form action="index.php?page=allianzen&session=<?=$session;?>" method="POST">
 <tr><td class="c"><?=loca("ALLY_MISC_DISMISS_SUCCESS");?></td></tr>
 <tr><th><br><input type=submit value="<?=loca("ALLY_MISC_DISMISS_OK");?>"></th></tr>
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
<tr><td class=c colspan=2><?=va(loca("ALLY_MISC_DISMISS_CONFIRM"), $ally['name']);?></td></tr>
<tr><th><?=loca("ALLY_MISC_DISMISS_WARNING1");?></th><th><?=loca("ALLY_MISC_DISMISS_WARNING2");?></th></tr>
<tr><th colspan=2><br><input type=submit value="<?=loca("ALLY_MISC_YES_FOR_SURE");?>"></th></tr></table></center></form><br><br><br><br>
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
    if ( $_GET['a'] == 18 && key_exists('s', $_REQUEST) && $_REQUEST['s'] == 1)
    {
        $now = time ();
        $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & 0x100) ) $AllianzenError = "<center>\n".loca("ALLY_NO_WAY")."<br></center>";
        else
        {
            // Выслать всем участникам сообщение что власть поменялась (кроме самого главы).

            $result = EnumerateAlly ($ally['ally_id']);
            $rows = dbrows ($result);
            while ($rows--)
            {
                $user = dbarray ($result);
                if ( $user['player_id'] != $ally['owner_id'] ) {
                    loca_add ("ally", $user['lang']);
                    $from = va ( loca_lang ("ALLY_MSG_FROM", $user['lang']), $ally['tag'] );
                    $subj = va ( loca_lang ("ALLY_MSG_TAKEOVER_SUBJ", $user['lang']), $ally['tag'] );
                    $text = va ( loca_lang ("ALLY_MSG_TAKEOVER", $user['lang']), $GlobalUser['oname'], $ally['tag'] );
                    SendMessage ( $user['player_id'], $from, $subj, $text, MTYP_ALLY);
                }
            }

            // Поменять звания
            $newhead = LoadUser ( intval($_REQUEST['uid']) );
            $newhead_rank = LoadRank ( $ally['ally_id'], $newhead['allyrank'] );
            if ( $newhead['ally_id'] != $ally['ally_id'] || ($newhead_rank['rights'] & 0x100) == 0 ) {
                $AllianzenError = "<center>\n".loca("ALLY_NO_WAY")."<br></center>";
                return;
            }
            SetUserRank ( $newhead['player_id'], $GlobalUser['allyrank'] );
            SetUserRank ( $GlobalUser['player_id'], $newhead['allyrank'] );

            // Установить нового хозяина альянса
            AllyChangeOwner ( $ally['ally_id'], $newhead['player_id'] );

?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method="POST">
<tr><td class=c><?=loca("ALLY_MISC_TAKEOVER_SUCCESS");?></td></tr><tr><th><br><input type="submit" value="<?=loca("ALLY_MISC_TAKEOVER_OK");?>"></th></tr></form></table><br><br><br><br>
<?php
        }
        return;
    }

    // Ололош, любой игрок по этому параметру может взять на себя права главы, без всяких проверок.....
    if ( $_GET['a'] == 18 && key_exists('s', $_REQUEST) && $_REQUEST['s'] == 2)
    {
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method="POST">
<tr><td class=c><?=loca("ALLY_MISC_TAKEOVER_TAKEN");?></td></tr><tr><th><br><input type="submit" value="<?=loca("ALLY_MISC_TAKEOVER_OK");?>"></th></tr></form></table><br><br><br><br>
<?php
        return;
    }

    // Если открыть у НЕ главы страничку:
    if ( $ally['owner_id'] != $GlobalUser['player_id'] ) {
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=5" method=POST>
<tr><td class=c><?=loca("ALLY_MISC_TAKEOVER_STILL_ACTIVE");?></td></tr><tr><th><input type=submit value="<?=loca("ALLY_MISC_TAKEOVER_BACK");?>"></th></tr></form></table><br><br><br><br>
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
<tr><td class=c></th></tr><tr><th><input type=submit value="<?=loca("ALLY_MISC_TAKEOVER_BACK");?>"></th></tr></form></table><br><br><br><br>
<?php
    }
    else {    // Перечислить найденных пользователей с рангом "правая рука"
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=18" method=POST>
<input type=hidden name=s value=1>
<tr><td class=c colspan=2><?=va(loca("ALLY_MISC_TAKEOVER_HEAD"), $ally['name']);?></td></tr>
<tr><th><?=loca("ALLY_MISC_TAKEOVER_WHO");?></th><th><select name=uid>
<?php
    foreach ( $users as $i=>$user ) {
        echo "  <option value=".$user['player_id'].">".$user['oname']." (".va(loca("ALLY_MISC_TAKEOVER_RANK"), $user['rankname']).")\n";
    }
?></select></th></tr>
<tr><th colspan=2><input type=submit value="<?=loca("ALLY_MISC_TAKEOVER_SUBMIT");?>"></th></tr></form></table><br><br><br><br>
<?php
    }

}

// EOF

?>