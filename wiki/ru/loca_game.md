Original game load all loca's at every page access (they had 'english.php', 'deutch.php' and so on).
But actually you don't need it all at same time, only some of them (except very important ones)

If game engine cannot find some LOCA, it just echo "LOCA\_NAME", instead its value.
So you can easily spot untranslated parts around the game.

# Install script #

**ogamelang** cookie used as install page language.

![![](http://oldogame.ru/images/install_sm.jpg)](http://oldogame.ru/images/install.jpg)

Location : /game/loca/x\_x/install.php (_Same language as Start Page_)

# Common #

Resources and tech names are used widley in game, so they are always loaded.

## Resources ##

**METAL**<br>
<b>CRYSTAL</b><br>
<b>DEUTERIUM</b><br>
<b>ENERGY</b><br>
<b>DM</b><br>
<b>MOON</b><br>

Location : /game/loca/common.php (<i>All languages are loaded at same time!</i>)<br>
<br>
<h2>Tech names</h2>

<b>NAME_xxx</b>, where xxx is tech id.<br>
<br>
Location : /game/loca/technames.php (<i>All languages are loaded at same time!</i>)<br>
<br>
<h1>Pages</h1>

<h2>Left Menu</h2>

Loaded only for some pages, which has it.<br>
<br>
<img src='http://oldogame.ru/images/leftmenu.jpg' />

Location : /game/loca/x_x/menu.php<br>
<br>
<h2>Overview</h2>

<img src='http://oldogame.ru/images/overview.jpg' />

Location : /game/loca/x_x/overview.php<br>
<br>
<h2>Resources</h2>

<img src='http://oldogame.ru/images/resources.jpg' />

Location : /game/loca/x_x/resources.php<br>
<br>
<h2>Tech tree / details</h2>

<a href='http://oldogame.ru/images/techtree.jpg'><img src='http://oldogame.ru/images/techtree_sm.jpg' /></a>

Location : /game/loca/x_x/techtree.php<br>
<br>
<h2>Admin Tool</h2>

Location : /game/loca/x_x/admin.php<br>
<br>
Main menu:<br>
<br>
<a href='http://oldogame.ru/images/admin_main.jpg'><img src='http://oldogame.ru/images/admin_main_sm.jpg' /></a>