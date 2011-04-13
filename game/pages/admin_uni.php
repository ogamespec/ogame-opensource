<?php

// ========================================================================================
// Настройки Вселенной.

function UniIsSelected ($option, $value)
{
    if ( $option == $value ) return "selected";
    else return "";
}

function Admin_Uni ()
{
    global $session;
    $now = time ();

    if ( method () === "POST" )
    {
        if ( key_exists ('news_upd', $_POST) )        // Обновить новости
        {
            if ( $_POST['news_upd'] > 0 ) UpdateNews ( $_POST['news1'], $_POST['news2'], $_POST['news_upd'] );
        }
        if ( $_POST['news_off'] === "on" )    // Убрать новости
        {
            DisableNews ();
        }

        SetUniParam ( $_POST['speed'], $_POST['acs'], $_POST['fid'], $_POST['did'], $_POST['defrepair'], $_POST['defrepair_delta'], $_POST['galaxies'], $_POST['systems'] );

        //print_r ( $_POST );
    }

    $unitab = LoadUniverse ();
?>

<table >
<form action="index.php?page=admin&session=<?=$session;?>&mode=Uni" method="POST" >
<tr><td class=c colspan=2>Настройки Вселенной <?=$unitab['num'];?></td></tr>
<tr><th>Дата открытия</th><th><?=date ("Y-m-d H:i:s", $unitab['startdate']);?></th></tr>
<tr><th>Количество игроков</th><th><?=$unitab['usercount'];?></th></tr>
<tr><th>Максимальное количество игроков</th><th><input type="text" name="maxusers" maxlength="10" size="10" value="<?=$unitab['maxusers'];?>" /></th></tr>
<tr><th>Количество галактик</th><th><input type="text" name="galaxies" maxlength="3" size="3" value="<?=$unitab['galaxies'];?>" /></th></tr>
<tr><th>Количество систем в галактике</th><th><input type="text" name="systems" maxlength="3" size="3" value="<?=$unitab['systems'];?>" /></th></tr>

  <tr>
   <th>Ускорение</th>
   <th>
   <select name="speed">
     <option value="1" <?=UniIsSelected($unitab['speed'], 1);?>>1x</option>
     <option value="2" <?=UniIsSelected($unitab['speed'], 2);?>>2x</option>
     <option value="3" <?=UniIsSelected($unitab['speed'], 3);?>>3x</option>
     <option value="4" <?=UniIsSelected($unitab['speed'], 4);?>>4x</option>
     <option value="5" <?=UniIsSelected($unitab['speed'], 5);?>>5x</option>
     <option value="6" <?=UniIsSelected($unitab['speed'], 6);?>>6x</option>
     <option value="7" <?=UniIsSelected($unitab['speed'], 7);?>>7x</option>
     <option value="8" <?=UniIsSelected($unitab['speed'], 8);?>>8x</option>
     <option value="9" <?=UniIsSelected($unitab['speed'], 9);?>>9x</option>
     <option value="10" <?=UniIsSelected($unitab['speed'], 10);?>>10x</option>
   </select>
   </th>
 </tr>

  <tr>
   <th>Флот в обломки</th>
   <th>
   <select name="fid">
     <option value="0" <?=UniIsSelected($unitab['fid'], 0);?>>0%</option>
     <option value="10" <?=UniIsSelected($unitab['fid'], 10);?>>10%</option>
     <option value="20" <?=UniIsSelected($unitab['fid'], 20);?>>20%</option>
     <option value="30" <?=UniIsSelected($unitab['fid'], 30);?>>30%</option>
     <option value="40" <?=UniIsSelected($unitab['fid'], 40);?>>40%</option>
     <option value="50" <?=UniIsSelected($unitab['fid'], 50);?>>50%</option>
     <option value="60" <?=UniIsSelected($unitab['fid'], 60);?>>60%</option>
     <option value="70" <?=UniIsSelected($unitab['fid'], 70);?>>70%</option>
     <option value="80" <?=UniIsSelected($unitab['fid'], 80);?>>80%</option>
     <option value="90" <?=UniIsSelected($unitab['fid'], 90);?>>90%</option>
     <option value="100" <?=UniIsSelected($unitab['fid'], 100);?>>100%</option>
   </select>
   </th>
 </tr>

  <tr>
   <th>Оборона в обломки</th>
   <th>
   <select name="did">
     <option value="0" <?=UniIsSelected($unitab['did'], 0);?>>0%</option>
     <option value="10" <?=UniIsSelected($unitab['did'], 10);?>>10%</option>
     <option value="20" <?=UniIsSelected($unitab['did'], 20);?>>20%</option>
     <option value="30" <?=UniIsSelected($unitab['did'], 30);?>>30%</option>
     <option value="40" <?=UniIsSelected($unitab['did'], 40);?>>40%</option>
     <option value="50" <?=UniIsSelected($unitab['did'], 50);?>>50%</option>
     <option value="60" <?=UniIsSelected($unitab['did'], 60);?>>60%</option>
     <option value="70" <?=UniIsSelected($unitab['did'], 70);?>>70%</option>
     <option value="80" <?=UniIsSelected($unitab['did'], 80);?>>80%</option>
     <option value="90" <?=UniIsSelected($unitab['did'], 90);?>>90%</option>
     <option value="100" <?=UniIsSelected($unitab['did'], 100);?>>100%</option>
   </select>
   </th>
 </tr>

<tr><th>Восстановление обороны</th><th>
<input type="text" name="defrepair" maxlength="3" size="3" value="<?=$unitab['defrepair'];?>" /> +/-
<input type="text" name="defrepair_delta" maxlength="3" size="3" value="<?=$unitab['defrepair_delta'];?>" /> %
</th></tr>

<tr><th>Приглашенных игроков в САБ</th><th><input type="text" name="acs" maxlength="3" size="3" value="<?=$unitab['acs'];?>" /> (макс. <?=($unitab['acs']*$unitab['acs']);?> флотов)</th></tr>

<tr><th>Скорострел</th><th><input type="checkbox" name="rapid"  checked=checked /></th></tr>
<tr><th>Луны и Звёзды Смерти</th><th><input type="checkbox" name="moons"  checked=checked /></th></tr>
<tr><th>Новость 1</th><th><input type="text" name="news1" maxlength="99" size="20" value="<?=$unitab['news1'];?>" /></th></tr>
<tr><th>Новость 2</th><th><input type="text" name="news2" maxlength="99" size="20" value="<?=$unitab['news2'];?>" /></th></tr>
<?php
    if ( $now > $unitab['news_until'] ) echo "<tr><th>Продлить новость</th><th><input type=\"text\" name=\"news_upd\" maxlength=\"3\" size=\"3\" value=\"0\" /> дн.</th></tr>\n";
    else echo "<tr><th>Показывать новость до</th><th>".date ("Y-m-d H:i:s", $unitab['news_until'])." <input type=\"checkbox\" name=\"news_off\"  /> убрать</th></tr>\n";
?>
<tr><th colspan=2><input type="submit" value="Сохранить" /></th></tr>

</form>
</table>

<?php
}
?>