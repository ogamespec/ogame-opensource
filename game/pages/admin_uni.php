<?php

// ========================================================================================
// Настройки Вселенной.

function UniIsSelected ($option, $value)
{
    if ( $option == $value ) return "selected";
    else return "";
}

function UniIsChecked ($option)
{
    if ( $option ) return "checked";
    else return "";
}

function Admin_Uni ()
{
    global $db_prefix;
    global $GlobalUser;
    global $session;
    $now = time ();

    //$info = "[i]";
    $info = "<img src='img/r5.png' />";

    if ( method () === "POST" && $GlobalUser['admin'] >= 2 )
    {
        if ( key_exists ('news_upd', $_POST) )        // Обновить новости
        {
            if ( $_POST['news_upd'] > 0 ) UpdateNews ( $_POST['news1'], $_POST['news2'], $_POST['news_upd'] );
        }
        if ( $_POST['news_off'] === "on" )    // Убрать новости
        {
            DisableNews ();
        }

        $rapid = ($_POST['rapid'] === "on") ? 1 : 0;
        $moons = ($_POST['moons'] === "on") ? 1 : 0;
        $freeze = ($_POST['freeze'] === "on") ? 1 : 0;

        SetUniParam ( $_POST['speed'], $_POST['fspeed'], $_POST['acs'], $_POST['fid'], $_POST['did'], $_POST['defrepair'], $_POST['defrepair_delta'], $_POST['galaxies'], $_POST['systems'], $rapid, $moons, $freeze, $_POST['lang'] );

        // Включить принудительное РО активным игрокам, если вселенная ставится на паузу.
        if ( $freeze ) {
            $days7 = $now - 7*24*60*60;
            $query = "UPDATE ".$db_prefix."users SET vacation = 1, vacation_until = ".$now." WHERE lastclick >= $days7";
            dbquery ( $query );
        }

        //print_r ( $_POST );
    }

    $unitab = LoadUniverse ();
?>

<?php echo AdminPanel();?>

<table >
<form action="index.php?page=admin&session=<?php echo $session;?>&mode=Uni" method="POST" >
<tr><td class=c colspan=2>Настройки Вселенной <?php echo $unitab['num'];?></td></tr>
<tr><th>Дата открытия</th><th><?php echo date ("Y-m-d H:i:s", $unitab['startdate']);?></th></tr>
<tr><th>Счётчик попыток взлома <a title="Очищается после релогина"><?php echo $info;?></a></th><th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Debug&filter=HACKING"><?php echo $unitab['hacks'];?> (Проверить)</a></th></tr>
<tr><th>Количество игроков</th><th><?php echo $unitab['usercount'];?></th></tr>
<tr><th>Максимальное количество игроков</th><th><input type="text" name="maxusers" maxlength="10" size="10" value="<?php echo $unitab['maxusers'];?>" /></th></tr>
<tr><th>Количество галактик</th><th><input type="text" name="galaxies" maxlength="3" size="3" value="<?php echo $unitab['galaxies'];?>" /></th></tr>
<tr><th>Количество систем в галактике</th><th><input type="text" name="systems" maxlength="3" size="3" value="<?php echo $unitab['systems'];?>" /></th></tr>

  <tr>
   <th>Ускорение игры</th>
   <th>
   <select name="speed">
     <option value="1" <?php echo UniIsSelected($unitab['speed'], 1);?>>1x</option>
     <option value="2" <?php echo UniIsSelected($unitab['speed'], 2);?>>2x</option>
     <option value="3" <?php echo UniIsSelected($unitab['speed'], 3);?>>3x</option>
     <option value="4" <?php echo UniIsSelected($unitab['speed'], 4);?>>4x</option>
     <option value="5" <?php echo UniIsSelected($unitab['speed'], 5);?>>5x</option>
     <option value="6" <?php echo UniIsSelected($unitab['speed'], 6);?>>6x</option>
     <option value="7" <?php echo UniIsSelected($unitab['speed'], 7);?>>7x</option>
     <option value="8" <?php echo UniIsSelected($unitab['speed'], 8);?>>8x</option>
     <option value="9" <?php echo UniIsSelected($unitab['speed'], 9);?>>9x</option>
     <option value="10" <?php echo UniIsSelected($unitab['speed'], 10);?>>10x</option>
   </select>
   </th>
 </tr>

  <tr>
   <th>Ускорение флота</th>
   <th>
   <select name="fspeed">
     <option value="1" <?php echo UniIsSelected($unitab['fspeed'], 1);?>>1x</option>
     <option value="2" <?php echo UniIsSelected($unitab['fspeed'], 2);?>>2x</option>
     <option value="3" <?php echo UniIsSelected($unitab['fspeed'], 3);?>>3x</option>
     <option value="4" <?php echo UniIsSelected($unitab['fspeed'], 4);?>>4x</option>
     <option value="5" <?php echo UniIsSelected($unitab['fspeed'], 5);?>>5x</option>
     <option value="6" <?php echo UniIsSelected($unitab['fspeed'], 6);?>>6x</option>
     <option value="7" <?php echo UniIsSelected($unitab['fspeed'], 7);?>>7x</option>
     <option value="8" <?php echo UniIsSelected($unitab['fspeed'], 8);?>>8x</option>
     <option value="9" <?php echo UniIsSelected($unitab['fspeed'], 9);?>>9x</option>
     <option value="10" <?php echo UniIsSelected($unitab['fspeed'], 10);?>>10x</option>
   </select>
   </th>
 </tr>

  <tr>
   <th>Флот в обломки</th>
   <th>
   <select name="fid">
     <option value="0" <?php echo UniIsSelected($unitab['fid'], 0);?>>0%</option>
     <option value="10" <?php echo UniIsSelected($unitab['fid'], 10);?>>10%</option>
     <option value="20" <?php echo UniIsSelected($unitab['fid'], 20);?>>20%</option>
     <option value="30" <?php echo UniIsSelected($unitab['fid'], 30);?>>30%</option>
     <option value="40" <?php echo UniIsSelected($unitab['fid'], 40);?>>40%</option>
     <option value="50" <?php echo UniIsSelected($unitab['fid'], 50);?>>50%</option>
     <option value="60" <?php echo UniIsSelected($unitab['fid'], 60);?>>60%</option>
     <option value="70" <?php echo UniIsSelected($unitab['fid'], 70);?>>70%</option>
     <option value="80" <?php echo UniIsSelected($unitab['fid'], 80);?>>80%</option>
     <option value="90" <?php echo UniIsSelected($unitab['fid'], 90);?>>90%</option>
     <option value="100" <?php echo UniIsSelected($unitab['fid'], 100);?>>100%</option>
   </select>
   </th>
 </tr>

  <tr>
   <th>Оборона в обломки</th>
   <th>
   <select name="did">
     <option value="0" <?php echo UniIsSelected($unitab['did'], 0);?>>0%</option>
     <option value="10" <?php echo UniIsSelected($unitab['did'], 10);?>>10%</option>
     <option value="20" <?php echo UniIsSelected($unitab['did'], 20);?>>20%</option>
     <option value="30" <?php echo UniIsSelected($unitab['did'], 30);?>>30%</option>
     <option value="40" <?php echo UniIsSelected($unitab['did'], 40);?>>40%</option>
     <option value="50" <?php echo UniIsSelected($unitab['did'], 50);?>>50%</option>
     <option value="60" <?php echo UniIsSelected($unitab['did'], 60);?>>60%</option>
     <option value="70" <?php echo UniIsSelected($unitab['did'], 70);?>>70%</option>
     <option value="80" <?php echo UniIsSelected($unitab['did'], 80);?>>80%</option>
     <option value="90" <?php echo UniIsSelected($unitab['did'], 90);?>>90%</option>
     <option value="100" <?php echo UniIsSelected($unitab['did'], 100);?>>100%</option>
   </select>
   </th>
 </tr>

<tr><th>Восстановление обороны</th><th>
<input type="text" name="defrepair" maxlength="3" size="3" value="<?php echo $unitab['defrepair'];?>" /> +/-
<input type="text" name="defrepair_delta" maxlength="3" size="3" value="<?php echo $unitab['defrepair_delta'];?>" /> %
</th></tr>

<tr><th>Приглашенных игроков в САБ</th><th><input type="text" name="acs" maxlength="3" size="3" value="<?php echo $unitab['acs'];?>" /> (макс. <?php echo ($unitab['acs']*$unitab['acs']);?> флотов)</th></tr>

<tr><th>Скорострел</th><th><input type="checkbox" name="rapid"  <?php echo UniIsChecked($unitab['rapid']);?> /></th></tr>
<tr><th>Луны и Звёзды Смерти</th><th><input type="checkbox" name="moons" <?php echo UniIsChecked($unitab['moons']);?> /></th></tr>
<tr><th>Новость 1</th><th><input type="text" name="news1" maxlength="99" size="20" value="<?php echo $unitab['news1'];?>" /></th></tr>
<tr><th>Новость 2</th><th><input type="text" name="news2" maxlength="99" size="20" value="<?php echo $unitab['news2'];?>" /></th></tr>
<?php
    if ( $now > $unitab['news_until'] ) echo "<tr><th>Продлить новость</th><th><input type=\"text\" name=\"news_upd\" maxlength=\"3\" size=\"3\" value=\"0\" /> дн.</th></tr>\n";
    else echo "<tr><th>Показывать новость до</th><th>".date ("Y-m-d H:i:s", $unitab['news_until'])." <input type=\"checkbox\" name=\"news_off\"  /> убрать</th></tr>\n";
?>
<tr><th>Язык интерфейса</th><th>
   <select name="lang">
<?php
    global $Languages;
    foreach ( $Languages as $lang_id=>$lang_name ) {
        echo "    <option value=\"".$lang_id."\" " . UniIsSelected($unitab['lang'], $lang_id)." >$lang_name</option>\n";
    }
?>
   </select>
</th></tr>
<tr><th>Поставить вселенную на паузу <a title="При постановке вселенной на паузу не будет срабатывать ни одно событие (очередь будет остановлена). После снятия паузы все завершенные события будут выполнены в порядке очереди"><?php echo $info;?></a>
</th><th><input type="checkbox" name="freeze"  <?php echo UniIsChecked($unitab['freeze']);?> /></th></tr>
<tr><th colspan=2><input type="submit" value="Сохранить" /></th></tr>

</form>
</table>

<?php
}
?>