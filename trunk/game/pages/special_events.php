<?php

// Вставка кода и обработчика POST-запроса в Обзор, для управления специальными событиями.

// Доступные глобальные переменные:
// now : текущее время сервера
// uni : объект вселенной
// session : сессия игрока

# Обработка запросов
###################################################################

if ( method () === "POST" ) 
{

### Специальная Вселенная
    if ( $uni['special'] ) {

        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'GlobalAttackBan' ";
        $result = dbquery ($query);
        $attack_ban = dbrows($result) > 0;

        $TimeLimit = GetVar ( $GlobalUser['player_id'], "TimeLimit" );

        if ( $attack_ban && $TimeLimit > 0 ) {        // Ускорять задания можно только при глобальном бане атак и доступном времени.
            $speed_h = abs(intval($_POST['speedup_h']));
            $speed_m = abs(intval($_POST['speedup_m']));
            $speed_s = abs(intval($_POST['speedup_s']));

            $speed_h = min ( max (0, $speed_h), 23 );
            $speed_m = min ( max (0, $speed_m), 59 );
            $speed_s = min ( max (0, $speed_s), 59 );

            $seconds = min ($speed_h * 3600 + $speed_m * 60 + $speed_s, $TimeLimit);

            // Выбрать все задания, связанные со строительством и флотом
            $query = "SELECT * FROM ".$db_prefix."queue WHERE (type = 'Build' OR type = 'Demolish' OR type = 'Research' OR type = 'Fleet' OR type = 'Shipyard') AND owner_id = " . $GlobalUser['player_id'];
            $result = dbquery ( $query );
            $rows = dbrows ($result);
            while ($rows--) {
                $queue = dbarray ($result);
                $query = "UPDATE ".$db_prefix."queue SET start = start - $seconds, end = end - $seconds WHERE task_id = " . $queue['task_id'];
                dbquery ( $query );
            }

            // Отодвинуть выработку планет "в прошлое"
            $query = "UPDATE ".$db_prefix."planets SET lastpeek = lastpeek - $seconds WHERE owner_id = " . $GlobalUser['player_id'];
            dbquery ( $query );

            SetVar ( $GlobalUser['player_id'], "TimeLimit", $TimeLimit - $seconds );
            echo "<script type=\"text/javascript\">document.location.href=\"index.php?page=overview&session=$session\"</script>";
        }
    }

}

# Генерация дополнительного контента
###################################################################

### Специальная Вселенная

if ( $uni['special'] ) {
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'WipeUniverse' ";
    $result = dbquery ($query);
    if ( dbrows($result) ) {
        $queue = dbarray ($result);

        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'GlobalAttackBan' ";
        $result = dbquery ($query);
        $attack_ban = dbrows($result) > 0;
?>

<script type="text/javascript"> 
<!--
function t_wipe() {
    v = new Date();
    var wxx = document.getElementById('wxx');
    var timeout = 1;
    n=new Date();
    ss=wpp;
    aa=Math.round((n.getTime()-v.getTime())/1000.);
    s=ss-aa;
    m=0;
    h=0;
    
    if (s < 0) {
        wxx.innerHTML='--';
        
        if ((ss + 6) >= aa) {
            window.setTimeout('document.location.href="index.php?page=overview&session='+ps+'";', 1500);
        }
    } else {
        if(s>59){
            m=Math.floor(s/60);
            s=s-m*60;
        }
        if(m>59){
            h=Math.floor(m/60);
            m=m-h*60;
        }
        if(s<10){
            s="0"+s;
        }
        if(m<10){
            m="0"+m;
        }
        wxx.innerHTML="<font color=red>"+h+":"+m+":"+s+"</font>";
    }    
    wpp=wpp-1;
    window.setTimeout("t_wipe();", 999);
}
//--> 
</script> 


<center>
<table width='519'>
<tr><td class='c' colspan='4'>Специальная Вселенная</td></tr>
<tr><th>    Перезапуск через  </th> <th colspan=3>
<div id="wxx" title="<?=$queue['end'];?>" class="z"></div><SCRIPT language=JavaScript>
wpp="<?=($queue['end']-$now);?>"; ps="<?=$session;?>"; t_wipe();
</script>
</th></tr>
<tr><th>    Глобальный бан атак  </th> <th colspan=3>
<?php
    if ( $attack_ban ) echo "<font color=red>Вкл.</font>";
    else echo "<font color=lime>Выкл.</font>";
?>
</th></tr>
<tr><th>    Лимит времени  </th> <th colspan=3>
<?php
    $TimeLimit = GetVar ( $GlobalUser['player_id'], "TimeLimit" );
    if ( $TimeLimit > 0 ) echo BuildDurationFormat ($TimeLimit);
    else echo "<font color=red>Вы исчерпали доступный запас времени</font>";
?>
</th></tr>
<tr><th>    Затраченное время  </th> <th colspan=3>
<?php
    $acctime = ($now - $GlobalUser['regdate']) + ( 3*365*24*60*60 - $TimeLimit );
    echo BuildDurationFormat ($acctime);
?>
</th></tr>
<?php
    if ( $attack_ban && $TimeLimit > 0 ) {
?>
<tr><th>    Ускорить время   </th> <th colspan=3>
<form action="?page=overview&session=<?=$session;?>" method="POST">
<table>
<tr><td>Часов <input type=text size=2 name="speedup_h" value="<?=$speed_h;?>"></td>
<td>Минут <input type=text size=2 name="speedup_m" value="<?=$speed_m;?>"></td>
<td>Секунд <input type=text size=2 name="speedup_s" value="<?=$speed_s;?>"></td>
<td><input type=submit value="Отправить"></td></tr>
</table>
</form>
</th></tr>
<?php
    }    // $attack_ban
?>
</table>
</center>
<?php
    }
}

?>