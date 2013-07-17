<?php

// Графический редактор интеллекта ботов.

function Admin_Botedit ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser, $GlobalUni;

    // Обработка GET-запроса.
    if ( method () === "GET" )
    {
        if ( $_GET['action'] === "preview" ) {      // Предпросмотр
            $id = intval ( $_GET['strat'] );
            $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = $id LIMIT 1";
            $result = dbquery ($query);
            $row = dbarray ($result);

            ob_clean ();
?>

<html>

 <head>
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel='stylesheet' type='text/css' href='css/formate.css' />
  <script language="JavaScript">var session="<?=$session;?>";</script>
  <meta http-equiv='content-type' content='text/html; charset=UTF-8' />
<link rel='stylesheet' type='text/css' href='css/combox.css'>
<link rel='stylesheet' type='text/css' href='<?=UserSkin();?>formate.css' />
<title><?=$row['name'];?></title>
  <script src='js/utilities.js' type='text/javascript'></script>
  <script language='JavaScript'>
  </script>
</head>

<body>

<script type="text/javascript" src="js/tw-sack.js"></script>
<script type="text/javascript" src="js/go.js"></script>
<script type="text/javascript" src="js/go-game.js"></script>

<div id="sample">
  <div style="width:100%; white-space:nowrap; display:none;">
    <span style="display: inline-block; vertical-align: top; padding: 5px; width:100px">
      <div id="myPalette" style="background-color: #344566; border: solid 1px black; height: 500px"></div>
    </span>
    <span style="display: inline-block; vertical-align: top; padding: 5px; width:88%">
      <div id="myDiagram" style="background-color: #344566; border: solid 1px black; height: 500px"></div>
    </span>
  </div>

<input type="text" size="50" id="strategyName" style="display:none;">
<select id="strategyId" style="display:none;">
<option value="<?=$row['id'];?>" selected><?=$row['id'];?></option>
</select>

  <textarea id="mySavedModel" style="width:100%;height:300px; display:none;">
<?=$row['source'];?>
  </textarea>
</div>

<img src="" id="preview_img">

<script type="text/javascript">
    init ();
</script>

</body>

</html>

<?php
            die ();
        }
    }

    // Обработка POST-запроса.
    if ( method () === "POST" )
    {
        if ( $_POST['action'] === "load" ) {        // Загрузить
            $id = intval ( $_POST['strat'] );
            $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = $id LIMIT 1";
            $result = dbquery ($query);
            $row = dbarray ($result);
            ob_clean ();
            setcookie ( "uni".$GlobalUni['num']."_".$GlobalUser['name']."_strategy", $id, 9999 );
            die ($row['source']);
        }
        else if ( $_POST['action'] === "save" ) {    // Сохранить
            $id = intval ( $_POST['strat'] );

            // Сохранить текущий исходник в бекап
            $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = $id LIMIT 1";
            $result = dbquery ($query);
            $row = dbarray ($result);
            $query = "UPDATE ".$db_prefix."botstrat SET source = '".$row['source']."' WHERE id = 1;";
            dbquery ( $query );

            $source = urldecode ( $_POST['source'] );
            $source = addslashes ( $source );
            $query = "UPDATE ".$db_prefix."botstrat SET source = '".$source."' WHERE id = $id;";
            dbquery ( $query );
            ob_clean ();
            die ();
        }
        else if ( $_POST['action'] === "new" ) {    // Новая стратегия
            $name = $_POST['name'];
            $name = addslashes ( $name );
            $source = "{ \"class\": \"go.GraphLinksModel\",
                         \"linkFromPortIdProperty\": \"fromPort\",
                         \"linkToPortIdProperty\": \"toPort\",
                         \"nodeDataArray\": [ ],
                         \"linkDataArray\": [ ]}";
            $strat = array ( '', $name, $source );
            AddDBRow ($strat, 'botstrat');
            ob_clean ();
            die ( );
        }
        else if ( $_POST['action'] === "rename" ) {    // Переименовать
            $id = intval ( $_POST['strat'] );
            $name = $_POST['name'];
            $name = addslashes ( $name );
            $query = "UPDATE ".$db_prefix."botstrat SET name = '".$name."' WHERE id = $id;";
            dbquery ( $query );
            ob_clean ();
            $query = "SELECT * FROM ".$db_prefix."botstrat ORDER BY id ASC";
            $result = dbquery ($query);
            echo "<option value=\"0\">-- Выберите стратегию --</option>\n";
            while ($row = dbarray ($result) ) {
                echo "<option value=\"".$row['id']."\"  ";
                if ( $row['id'] == $id ) echo "selected";
                echo ">".stripslashes($row['name'])."</option>\n";
            }
            die ( );
        }
        else {
            ob_clean ();
            die ();
        }
    }

?>

<script type="text/javascript" src="js/tw-sack.js"></script>
<script type="text/javascript" src="js/go.js"></script>
<script type="text/javascript" src="js/go-game.js"></script>

<?=AdminPanel();?>

<div id="sample">
  <div style="width:100%; white-space:nowrap;">
    <span style="display: inline-block; vertical-align: top; padding: 5px; width:100px">
      <div id="myPalette" style="background-color: #344566; border: solid 1px black; height: 500px"></div>
    </span>
    <span style="display: inline-block; vertical-align: top; padding: 5px; width:88%">
      <div id="myDiagram" style="background-color: #344566; border: solid 1px black; height: 500px"></div>
    </span>
  </div>

<span style="float:left;">
 <input type="text" size="50" id="strategyName">
 <button onclick="newstrat()">Новая</button>
 <button onclick="rename()">Переименовать</button>
 <button onclick="showimg()">Показать</button>
</span>

<span style="float:right;">
  <button onclick="save()">Сохранить</button>
<select id="strategyId">
<option value="0">-- Выберите стратегию --</option>
<?php
    $query = "SELECT * FROM ".$db_prefix."botstrat ORDER BY id ASC";
    $result = dbquery ($query);
    while ($row = dbarray ($result) ) {
        echo "<option value=\"".$row['id']."\">".stripslashes($row['name'])."</option>\n";
    }
?>
</select>
  <button onclick="load()">Загрузить</button>
</span>
  <textarea id="mySavedModel" style="width:100%;height:300px; display:none;">
{ "class": "go.GraphLinksModel",
  "linkFromPortIdProperty": "fromPort",
  "linkToPortIdProperty": "toPort",
  "nodeDataArray": [ ],
  "linkDataArray": [ ]}
  </textarea>
</div>

<script type="text/javascript">
init ();
</script>

<img src="" id="preview_img" style="display:none;">

<?php
}
?>