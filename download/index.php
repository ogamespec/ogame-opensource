
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"> 
<html> 
<!-- Created on: 14.12.2004 --> 
<head> 
<style type="text/css"> 
 
 table { border:groove white 2px;  }
</style> 
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
<title>Скины ОГейм</title> 
<meta name="author" content="Ringkeeper"> 
<meta name="generator" content="2 Hï¿½nde, eine Tastatur, einen Kopf, nen Rechner, 20 Kippen, 3 Liter Kaffee,nen lieber Hend, ne sï¿½ï¿½e Icecoldbaby"> 
</head> 
</head> 
<body text="#FFFFFF" bgcolor=" #001746" link="#FFFFFF" alink="#FF0000" vlink="#FF0000" style="background-image:url(pic/background.gif);font-family:verdana;font-size:10px;"> 
<br><font face="verdana" size="18" color="#FFFFFF"> <div align="center"> <h1>Скины для OGame</h1></font></div><br> 
<br><font face="verdana" size="02" color="#FFFFFF"> <div align="center"> Большое спасибо NEFFE за программу предпросмотра скинов.</font></div><br> 
<br><font face="verdana" size="02" color="#FFFFFF"> <div align="center"> Список всех скинов можно найти в Настройках игры. Для этого нужно удалить путь к скину, тогда появится выпадающий список.</font></div><br> 
<br><font face="verdana" size="02" color="#FFFFFF"> <div align="center"> Авторские права на скины принадлежат их создателям.</font></div><br> 
 <table border="0" align="center" summary=""width=800px> 
<?php

$skins = array (
    // shortname, longname, author, email

    array ( "reloaded", "Reloaded", "g3ck0", "g3ck0@cnp-online.de" ) ,
    array ( "allycpb", "Ally-CPB", "Poll@", "bla@blubb.de" ) ,
    array ( "asgard", "Asgard", "Der Lapper", "bla@blubb.de" ) ,
    array ( "aurora", "Aurora", "Diamond", "bla@blubb.de" ) ,
    array ( "vampir", "Vampir", "Meistervampir", "Meistervampir@ogame-team.de" ) ,
    array ( "allesnurgeklaut", "Allesnurgeklaut", "GaLAxY", "bla@blubb.de" ) ,
    array ( "bluedream", "Bluedream", "eSpGhost", "bla@blubb.de" ) ,
    array ( "bluegalaxy", "Bluegalaxy", "BigMuffl", "bla@blubb.de" ) ,
    array ( "blue-mx", "Blue-MX", "Steryc", "bla@blubb.de" ) ,
    array ( "brotstyle", "Brotstyle", "BrotUser", "bla@blubb.de" ) ,
    array ( "dd", "DD", "DarkDragon", "bla@blubb.de" ) ,
    array ( "eclipse", "Eclipse", "Dracon", "bla@blubb.de" ) ,
    array ( "empire", "Empire", "Medhiv", "bla@blubb.de" ) ,
    array ( "g3cko", "G3ck0", "g3ck0", "g3ck0@cnp-online.de" ) ,
    array ( "gruen", "Gruen", "eSpGhost", "bla@blubb.de" ) ,
    array ( "infraos", "Infraos", "oldi", "bla@blubb.de" ) ,
    array ( "lambda", "Lambda", "Eseno", "bla@blubb.de" ) ,
    array ( "lego", "Lego", "Nolte", "bla@blubb.de" ) ,
    array ( "militaryskin", "Military", "Warhorse", "bla@blubb.de" ) ,
    array ( "okno", "Okno", "oknoeno", "bla@blubb.de" ) ,
    array ( "ovisiofarbig", "Ovisiofarbig", "TheMaze/Spyme", "bla@blubb.de" ) ,
    array ( "ovisio", "Ovisio", "Spyme", "bla@blubb.de" ) ,
    array ( "paint", "Paint", "Daggoth", "bla@blubb.de" ) ,
    array ( "redfuturistisch", "Redfuturistisch", ".:Diamond:.", "bla@blubb.de" ) ,
    array ( "redvision", "Redvision", "SyRuS", "bla@blubb.de" ) ,
    array ( "shadowpato", "Shadowpato", "ShadowPato", "bla@blubb.de" ) ,
    array ( "simpel", "Simpel", "janKG", "bla@blubb.de" ) ,
    array ( "starwars", "Starwars", "Conan", "bla@blubb.de" ) ,
    array ( "w4wooden4ce", "W4wooden4ce", "[W4]hoLogramm", "bla@blubb.de" ) ,
    array ( "xonic", "Xonic", "xonic", "bla@blubb.de" ) ,
    array ( "skin1", "1 Skin", "Piratentunte", "bla@blubb.de" ) ,
    array ( "brace", "Brace", "BraCe", "bla@blubb.de" ) ,
    array ( "bluechaos", "Bluechaos", "002", "bla@blubb.de" ) ,
    array ( "epicblue", "Epicblue", "", "bla@blubb.de" ) ,
    array ( "quadratorstyle", "Quadrator Style", "Quadrator", "Quadrator@gmx.net" ) ,
    array ( "real", "Real", "Thanos", "tobi@tobiweb.de" ) ,
    array ( "blueplanet", "BluePlanet", "Mic2003", "mic2003-skin@lycos.de" ) ,
    array ( "", "", "", "bla@blubb.de" ) ,

);

$index = 0;

foreach ( $skins as $i=>$skin )
{
    if ( $index == 0 )
    {
        echo "	<tr> \n";
    }
?>
		<td > 
			<table border="0" align="center" summary="" width=100%> 
				<tr> 
					<td align="center"><a href="index2.php?i=<?=$skin[0];?>" target="_blank"><img src="pic/<?=$skin[0];?>.jpg" width="100" height="100" alt="" align="middle"></a></td> 
				</tr> 
				<tr> 
					<td align="center"><a href="zip/<?=$skin[0];?>.zip">Скачать</a></td> 
				</tr> 
				<tr> 
					<td align="center">Скин <?=$skin[1];?>. Автор: <a href="mailto:<?=$skin[3];?>"><?=$skin[2];?></a></td> 
				</tr> 
			</table> 
		</td> 
<?php

    if ($index == 1) echo "    </tr> \n";
    $index ^= 1;
}

?>

</table> 
</body> 
</html> 