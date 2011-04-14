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
        $now = time ();
        $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & 0x020) ) $AllianzenError = "<center>\nНедостаточно прав для проведения операции<br></center>";
        else if ( $now < $ally['tag_until'] ) $AllianzenError = "<center>\nПодождите до ".date ("Y-m-d H:i:s", $ally['tag_until'])."<br></center>";
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
        $now = time ();
        $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & 0x020) ) $AllianzenError = "<center>\nНедостаточно прав для проведения операции<br></center>";
        else if ( $now < $ally['name_until'] ) $AllianzenError = "<center>\nПодождите до ".date ("Y-m-d H:i:s", $ally['name_until'])."<br></center>";
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

?>