
<html>
<head>
<meta http-equiv='content-type' content='text/html; charset=utf-8' />
<TITLE>Установка OGame</TITLE>
</head>

<body style='background:#000000 url(img/space_background.jpg) no-repeat fixed right top; color: #fff;'>

<style>
td.c { background-color: #334445; }
.button { border: 1px solid; color: white; background-color: #334445; }
.text { border: 1px solid; color: white; background-color: #334445; }
#install_form { background: url(img/page_bg.png); }
</style>

<center>
<form action='install.php' method='POST'>
<input type=hidden name='install' value='1'>

<img src='img/install.png'><br>

<font color=red><?=$InstallError?></font>

<table id='install_form'>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'>Настройки базы данных</td></tr>
<tr><td>Хост</td><td><input type=text value='localhost' class='text' name='db_host'></td></tr>
<tr><td>Пользователь</td><td><input type=text class='text' name='db_user'></td></tr>
<tr><td>Пароль</td><td><input type=password class='text'  name='db_pass'></td></tr>
<tr><td>Название БД</td><td><input type=text class='text' name='db_name'></td></tr>
<tr><td><a title='Чтобы было легко найти все таблицы этой вселенной, задайте им общий префикс'>Префикс таблиц</a></td><td><input type=text value='uni1_' class='text' name='db_prefix'></td></tr>
<tr><td><a title='Используется при генерации паролей и сессий'>Секретное слово</a></td><td><input type=text type=password class='text' name='db_secret'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'>Настройки вселенной</td></tr>
<tr><td><a title='Название будет указано в заголовке окна и над главным меню в игре.'>Название вселенной</a></td><td><input type=text value='Вселенная 1' class='text' name='uni_name'></td></tr>
<tr><td><a title='Ускорение влияет на скорость добычи ресурсов, длительность построек и проведение исследований, скорость летящих флотов, минимальную длительность Режима Отпуска.'>Ускорение</a></td><td><input type=text value='1' class='text' name='uni_speed'></td></tr>
<tr><td>Количество галактик</td><td><input type=text value='9' class='text' name='uni_galaxies'></td></tr>
<tr><td>Количество систем</td><td><input type=text value='499' class='text' name='uni_systems'></td></tr>
<tr><td><a title='Максимальное количество аккаунтов. После достижения этого значения регистрация закрывается до тех пор, пока не освободится место.'>Максимум игроков</a></td><td><input type=text value='12500' class='text' name='uni_maxusers'></td></tr>
<tr><td><a title='Максимальное количество приглашенных игроков для Совместной атаки. Максимальное количство флотов в САБ вычисляется по формуле N*4, где N - количетсво участников. При N=0 САБ отключен.'>Участников САБ</a></td><td><input type=text value='4' class='text' name='uni_acs'></td></tr>
<tr><td><a title='Флот в Обломки. Указанное количество процентов флота выпадает в виде обломков. Если указано 0, то ФВО отключено.'>Обломки флота</a></td><td><input type=text value='30' class='text' name='uni_fid'></td></tr>
<tr><td><a title='Оборона в Обломки. Указанное количество процентов обороны выпадает в виде обломков. Если указано 0, то ОВО отключено.'>Обломки обороны</a></td><td><input type=text value='0' class='text' name='uni_did'></td></tr>
<tr><td><a title='Корабли получают возможность повторного выстрела'>Скорострел</a></td><td><input type=checkbox class='text' name='uni_rapid' CHECKED></td></tr>
<tr><td>Луны и Звезды Смерти</td><td><input type=checkbox class='text' name='uni_moons' CHECKED></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'>Аккаунт администратора игры (Legor)</td></tr>
<tr><td>E-Mail</td><td><input type=text class='text' name='admin_email'></td></tr>
<tr><td>Пароль</td><td><input type=password class='text' name='admin_pass'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2><center><input type=submit value='Инсталлировать' class='button'></center></td></tr>
</table>

</form>

</center>
</body>
</html>
