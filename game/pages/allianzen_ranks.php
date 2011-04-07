<?php

// Управление рангами пользователей.

function PageAlly_Ranks ()
{
    global $GlobalUser;
    global $session;
    global $ally;

    if ( method() === "POST" )
    {
        print_r ( $_POST );
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script><br />
<a href="index.php?page=allianzen&session=<?=$session;?>&a=5">Назад к обзору</a>
<table width="519">
 <tr>
  <td class="c" colspan="11">Сформировать права</td>
 </tr>
 <form action="index.php?page=allianzen&session=<?=$session;?>&a=15" method="POST">
 <tr>
  <th></th>
  <th>Название ранга</th>
  <th>
   <img src=img/r1.png>
  </th>
  <th>
   <img src=img/r2.png>
  </th>
  <th>
   <img src=img/r3.png>
  </th>
  <th>
   <img src=img/r4.png>
  </th>
  <th>
   <img src=img/r5.png>
  </th>
  <th>
   <img src=img/r6.png>
  </th>
  <th>
   <img src=img/r7.png>
  </th>
  <th>
   <img src=img/r8.png>
  </th>
  <th>
   <img src=img/r9.png>
  </th>
 </tr>
<?php

    $result = EnumRanks ( $ally['ally_id'] );
    $rows = dbrows ($result);
    while ($rows--)
    {
        $rank = dbarray ($result);
        echo " <tr>\n";
        echo "  <th><a href=\"index.php?page=allianzen&session=$session&a=15&d=".$rank['rank_id']."\"><img src=\"".UserSkin()."pic/abort.gif\" alt=\"Удалить ранг\" border=\"0\"></a></th>\n";
        echo "  <th>&nbsp;".$rank['name']."&nbsp;</th>\n";
        for ($r=0; $r<9; $r++) {
            if ($rank['rights'] & (1 << $r))  echo "<th><input type=checkbox name=\"u".$rank['rank_id']."r$r\" checked></th>";
            else echo "<th><input type=checkbox name=\"u".$rank['rank_id']."r$r\"></th>";
        }
        echo " </tr>\n";
    }
?>
 <tr>
  <th colspan="11"><input type="submit" value="Сохранить"></th>
 </tr>
</form>
</table>
<br /><form action="index.php?page=allianzen&session=<?=$session;?>&a=15" method=POST>
<table width=519>
<tr><td class=c colspan=2>Назначить новый ранг</td></tr>
<tr><th>Название ранга</th><th><input type=text name="newrangname" size=20 maxlength=30></th></tr>
<tr><th colspan=2><input type=submit value="Назначить"></th></tr>
</form></table>

<br/><form action="index.php?page=allianzen&session=<?=$session;?>&a=15" method=POST>
<table width=519>
<tr><td class=c colspan=2>Пояснение прав</td></tr>
<tr><th><img src=img/r1.png></th><th>Распустить альянс</th></tr>
<tr><th><img src=img/r2.png></th><th>Выгнать игрока</th></tr>
<tr><th><img src=img/r3.png></th><th>Посмотреть заявления</th></tr>
<tr><th><img src=img/r4.png></th><th>Посмотреть список членов</th></tr>
<tr><th><img src=img/r5.png></th><th>Редактировать заявления</th></tr>
<tr><th><img src=img/r6.png></th><th>Управление альянсом</th></tr>
<tr><th><img src=img/r7.png></th><th>Посмотреть статус "он-лайн" в списке членов</th></tr>
<tr><th><img src=img/r8.png></th><th>Составить общее сообщение</th></tr>
<tr><th><img src=img/r9.png></th><th>'Правая рука' (необходимо для передачи статуса основателя)</th></tr>
</form></table>
<?php
}

?>