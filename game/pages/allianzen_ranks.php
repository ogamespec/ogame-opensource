<?php

// Управление рангами пользователей.

function PageAlly_Ranks ()
{
    global $session;

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
 <tr>
<?php
    echo "  <th><a href=\"index.php?page=allianzen&session=$session&a=15&d=2\"><img src=\"http://uni20.ogame.de/evolution/pic/abort.gif\" alt=\"Удалить ранг\" border=\"0\"></a></th>\n";
    echo "  <th>&nbsp;1111&nbsp;</th>\n";
    echo "  <th><input type=checkbox name=\"u2r0\"></th><th><input type=checkbox name=\"u2r1\"></th><th><input type=checkbox name=\"u2r2\"></th><th><input type=checkbox name=\"u2r3\"></th><th><input type=checkbox name=\"u2r4\"></th><th><input type=checkbox name=\"u2r5\"></th><th><input type=checkbox name=\"u2r6\"></th><th><input type=checkbox name=\"u2r7\"></th><th><input type=checkbox name=\"u2r8\"></th> </tr>\n";
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