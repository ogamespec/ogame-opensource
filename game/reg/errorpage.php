<?php

// Проверить, если файл конфигурации отсутствует - редирект на страницу установки игры.
if ( !file_exists ("../config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=../install.php' /></head><body></body></html>";
    exit ();
}

include "../config.php";

function hostname () {
    $host = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/game/reg/errorpage.php" );
    return substr ( $host, 0, $pos+1 );
}
?>

<html>
 <head>
 <center>
 <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <link rel='stylesheet' type='text/css' href='../css/default.css' />
  <link rel='stylesheet' type='text/css' href='../css/formate.css' />
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel='stylesheet' type='text/css' href='css/formate.css' />
 <link rel="stylesheet" type="text/css" href="<?=hostname();?>evolution/formate.css" />
 <title>Ошибка</title>
</head>
<body class='style' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' >
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

 <br><br>
 <table width="519">
 <tr>
   <td class="c" align="center" ><font color="red">Ошибка</font></td>
  </tr>
  <tr>
  <th class="errormessage">Вы пытались войти во вселенную <?=$_GET['arg1'];?> под ником <?=$_GET['arg2'];?>.</th>
  </tr>
  <tr>
<?php

    switch ( $_GET['errorcode'] )
    {
        case '2': 
?>
   <th class='errormessage'>Такого аккаунта не существует либо Вы неправильно ввели пароль. <br>Введите <a href='<?=$StartPage;?>'>правильный пароль</a> либо воспользуйтесь <a href='mail.php'>восстановлением пароля</a>.<br>Также Вы можете создать <a href='new.php'>новый аккаунт</a>.</th>   
<?php
        break;

        case '3':
?>
   <th class='errormessage'>Этот аккаунт заблокирован до <?=$_GET['arg3'];?>, более подробная информация приведена <a href=../pranger.php>здесь</a>.<br> Если у Вас возникли вопросы, обратитесь к заблокировавшему Вас <a href='#'>оператору</a>.<br><br>ВНИМАНИЕ: статус командира при блоке не прекращается, прекращение делается отдельно!</th>   
<?php
        break;
    }

?>
  </tr>
 </table>
 </center>
</body>
</html>