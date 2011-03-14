<?php

// Настройки.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("changelog");
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
 <table width="519">

 <form action="index.php?page=options&session=149c500e570c&mode=change" method="POST" >
  

     <tr><td class="c" colspan ="2">Данные пользователя</td></tr>
<tr>
      <th><a title="Имя можно изменять только раз в 7 дней.">Имя</a></th>
   <th>Example-</th>
    </tr>
  <tr>
  <th>Старый пароль</th>

   <th><input type="password" name="db_password" size ="20" value="" /></th>
  </tr>
  <tr>
  <th>Новый пароль (мин. 8 символов)</th>
   <th><input type="password" name="newpass1" size="20" maxlength="40" /></th>
  </tr>
  <tr>
  <th>Новый пароль (подтверждение)</th>

   <th><input type="password" name="newpass2" size="20" maxlength="40" /></th>
  </tr>
  <tr>
  <th><a title="Этот адрес можно в любое время изменить. Через 7 дней без изменений он станет постоянным.">Адрес</a></th>
  <th><input type="text" name="db_email" maxlength="100" size="20" value="ogamespec@gmail.com" /></th>
  </tr>
  <tr>
  <th>Постоянный адрес</th>

   <th>ogamespec@gmail.com</th>
  </tr>
   <tr><th colspan="2">
  </tr>
  <tr>
  <td class="c" colspan="2">Общие настройки</td>
  </tr>
  <tr>

   <th>Сортировка планет по:</th>
   <th>
   <select name="settings_sort">
    <option value="0" selected >порядку колонизации</option>
    <option value="1" >координатам</option>
    <option value="2" >алфавиту</option>
   </select>

   </th>
  </tr>
  <tr>
   <th>Порядок сортировки:</th>
   <th>
   <select name="settings_order">
     <option value="0" selected>по возрастанию</option>
     <option value="1" >по убыванию</option>

   </select>
   </th>
 </tr>

  <th>Путь для скинов (напр. C:/ogame/kartinki/)<br /> <a href="http://graphics.ogame-cluster.net/download/" target="_blank">Скачать</a></th>
   <th><input type=text name="dpath" maxlength="80" size="40" value="http://graphics.ogame-cluster.net/download/use/evolution/" /> <br />
  </select>

   </th>
  </tr>
  <tr>
  <th>Показать скин</th>
   <th>
    <input type="checkbox" name="design"
    checked=checked />
   </th>
  </tr>

  <tr>
    <th><a title="Проверка IP означает, что автоматически последует выгрузка, если меняется IP или двое людей с разными IP зашли под одним аккаунтом. Отключение проверки IP может быть небезопасным!">Деактивировать проверку IP</a></th>
   <th><input type="checkbox" name="noipcheck"  /></th>
  </tr>
  <tr>
   <td class="c" colspan="2">Настройки просмотра галактики</td>
  </tr>
  <tr>

   <th><a title="Кол-во шпионских зондов, которые при каждом сканировании посылаются из меню Галактика.">Кол-во шпионских зондов</a></th>
   <th><input type="text" name="spio_anz" maxlength="2" size="2" value="1" /></th>
  </tr>
  <!--<tr>
   <th>Просмотреть название</th>
   <th><input type="text" name="settings_tooltiptime" maxlength="2" size="2" value="0" /> сек.</th>
  </tr>-->
  <tr>
   <th>Максимальные сообщения о флоте</th>
   <th><input type="text" name="settings_fleetactions" maxlength="2" size="2" value="3" /></th>
  </tr>

      
  <tr>
     <td class="c" colspan="2">Режим отпуска / Удалить аккаунт</td>
  </tr>
  <tr>
     <th><a title="Режим отпуска предназначен для того, чтобы оберегать Вас во время длительного отсутствия. Его можно активировать только тогда, когда ничего не строится (флоты, постройки или оборона) и не исследуется, а также если Вы никуда не посылали свои флоты. Когда он активирован, он защищает Вас от атак, однако уже начатые атаки продолжаются. Во время режима отпуска производство снижается до нуля, и после окончания этого режима надо его вручную выставлять на 100%. Режим отпуска длится минимум 2 дня, деактивировать его возможно только после этого срока.">Активировать режим отпуска</a></th>
   <th>
    <input type="checkbox" name="urlaubs_modus"
     />
   </th>

  </tr>
  <tr>
   <th><a title="Если поставить здесь галочку, то через 7 дней аккаунт автоматически полностью удалится.">Удалить аккаунт</a></th>
   <th><input type="checkbox" name="db_deaktjava"  />
      </th>
  </tr>
  <tr>
   <th colspan=2><input type="submit" value="Сохранить изменения" /></th>

  </tr>
   
 </form>
 </table>

<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>