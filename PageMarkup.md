# Introduction #

Для исторических целей перед вводом Редизайна была проведена обширная работа по сохранению HTML-кода всех оригинальных страниц. Результаты этой работы будут использованы для воссоздания оригинального облика игры.

Разметка всех игровых страниц однотипна и состоит из следующих частей:
  * Заголовок (header): мета-теги, теги head/title, подгрузка стилей и общих скриптов. Начало тега body.
  * Панель списка планет, ресурсов и офицеров (на некоторых страницах может отсутствовать)
  * "Левое меню": называется так, потому что находится в левой части экрана (на некоторых страницах может отсутствовать)
  * Содержимое (content): тут находятся непосредственно элементы игровой страницы
  * Хвост (footer): закрывается тег body/html и прочая мелочь.

В HTML коде **{вот так}** будут показываться места, где PHP-код генерирует контент, например путь к скину или какие-нибудь параметры.

Заранее извиняюсь, что часть текста на немецком, но я не успел сохранить разные незначительные страницы или их детали, пришлось потом дособирать инфу в единственной оставшейся вселенной без редизайна -- немецкой 20й вселенной. Не страшно, всё равно перевод немецких текстов у меня есть в русской локализации, главное чтобы HTML код соответствовал оригиналу.

# Заголовок #

```
 
<html> 
 <head> 
  <link rel='stylesheet' type='text/css' href='css/default.css' /> 
  <link rel='stylesheet' type='text/css' href='css/formate.css' /> 
 
  <script language="JavaScript">var session="1f855da316dd";</script> 
  <meta http-equiv="content-type" content="text/html; charset=koi8-r" /> 
 
<link rel="stylesheet" type="text/css" href="css/combox.css"> 
<link rel="stylesheet" type="text/css" href="{USER_SKIN_PATH}formate.css" /> 
 
<title>Вселенная 15 ОГейм</title> 
 
  <script src="js/utilities.js" type="text/javascript"></script> 
  <script language="JavaScript"> 
  </script> 
<script type="text/javascript" src="js/overLib/overlib.js"></script> 
<!-- HEADER --> 
 
<script language="JavaScript"> 
function onBodyLoad() {
    window.setTimeout("reloadImages()", 100);
}
 
function reloadImages() {
    for (var i = 0; i < document.images.length; ++i) {
      if ((document.images[i].className == 'reloadimage') && (document.images[i].title != "")) {
        document.images[i].src = document.images[i].title;
      }
    }
}
</script> 
 
</head> 
<body style="overflow: hidden;" onload="onBodyLoad();" onunload="" > 
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div> 
```

Краткое описание компонентов заголовка:
  * Подгружаются дефолтные стили `css/default.css` и `css/formate.css`, которые затем перегружаются стилем установленного скина `{USER_SKIN_PATH}formate.css`. Небольшая особенность: в пути к скину в конце всегда должна стоять `/` иначе format.css слепится с путём к скину и итоговый путь будет неверным. Путь к скину берется из настроек пользователя:<br><img src='http://ogamespec.com/imgstore/whc4d651e5551511.jpg'>
<ul><li>Установка кодировки через META-тег. Как видно тут ещё используется кодировка koi8-r, потому что сохранение страниц началось до повсевместного ввода кодировки utf-8 в игру.<br>
</li><li>Подгружается скрипт оверлеев <code>overlib.js</code>. Оверлеи - это всплывающие окна, которые выплывают к примеру в Галактике при наведении мышки на картинку планеты:<br><img src='http://ogamespec.com/imgstore/whc4d651ccc14f0a.jpg'><br>Скрипт <code>overlib.js</code> кстати OpenSource.<br>
</li><li>устанавливается таймер на вызов функции <code>reloadImages()</code> каждые 100ms. Эта функция принудительно перегружает все картинки <code>img</code>, у которых <code>class=reloadimage</code>. Я таких в игре не встречал.<br>
</li><li>Подгружается скрипт <code>js/utilities.js</code>
</li><li>Устанавливается заголовок страницы, например <b><code>Вселенная 15 ОГейм</code></b>
</li><li>Создается div <code>id=overDiv</code>, который представляет собой контейнер для оверлея.</li></ul>

<h1>Хвост</h1>

<pre><code>&lt;script&gt; <br>
messageboxHeight=0;<br>
errorboxHeight=0;<br>
contentbox = document.getElementById('content');<br>
&lt;/script&gt; <br>
 <br>
&lt;div id='messagebox'&gt; <br>
&lt;center&gt; <br>
{MESSAGE_TEXT}&lt;/center&gt; <br>
&lt;/div&gt; <br>
&lt;div id='errorbox'&gt; <br>
&lt;center&gt; <br>
{ERROR_TEXT}&lt;/center&gt; <br>
&lt;/div&gt; <br>
 <br>
&lt;script&gt; <br>
headerHeight = 81;<br>
errorbox.style.top=parseInt(headerHeight+messagebox.offsetHeight+5)+'px';<br>
contentbox.style.top=parseInt(headerHeight+errorbox.offsetHeight+messagebox.offsetHeight+10)+'px';<br>
if (navigator.appName=='Netscape'){if (window.innerWidth&lt;1020){document.body.scroll='no';}   contentbox.style.height=parseInt(window.innerHeight)-messagebox.offsetHeight-errorbox.offsetHeight-headerHeight-20;<br>
if(document.getElementById('resources')) {   document.getElementById('resources').style.width=(window.innerWidth*0.4);}}<br>
 else {<br>
if (document.body.offsetWidth&lt;1020){document.body.scroll='no';}   contentbox.style.height=parseInt(document.body.offsetHeight)-messagebox.offsetHeight-headerHeight-errorbox.offsetHeight-20;document.getElementById('resources').style.width=(document.body.offsetWidth*0.4);<br>
}for (var i = 0; i &lt; document.links.length; ++i) {<br>
  if (document.links[i].href.search(/.*redir\.php\?url=.*/) != -1) {<br>
    document.links[i].target = "_blank";<br>
  }<br>
}<br>
 <br>
&lt;/script&gt; <br>
&lt;style&gt; <br>
.layer {<br>
    z-index:999999999;<br>
    position:absolute;<br>
    left: 0;<br>
    right: 0;<br>
    top: 100px;<br>
    margin-left: auto;<br>
    margin-right: auto;<br>
    width: 757px; <br>
    height: 475px; <br>
    background-color: #040e1e;<br>
    border: 3px double orange;<br>
    padding: 0;<br>
    opacity: .90;<br>
}<br>
&lt;/style&gt; <br>
 &lt;/body&gt; <br>
&lt;/html&gt;<br>
</code></pre>

В хвосте находятся следующие элементы:<br>
<ul><li>Контейнеры для сообщения и ошибки. Сообщение - это небольшой текст наверху страницы, заключенный в зеленую рамочку, например:<br><img src='http://ogamespec.com/imgstore/whc4d6523a207802.jpg'><br>Ошибка - аналогично, но цвет рамки красный:<br><img src='http://ogamespec.com/imgstore/whc4d6524bb241af.jpg'>
</li><li>Небеольшой скрипт, для расчёта Y-координат контента, при наличии сообщения и/или ошибки.<br>
</li><li>Какой-то непонятный стиль <code>layer</code>, где используется неясно. Возможно просто артефакт от старых версий.</li></ul>

Для всплывающих окон наподобии <code>Заметок</code> хвост имеет немного другой вид:<br>
<br>
<pre><code>...<br>
headerHeight = 81;<br>
...<br>
</code></pre>

заменяется на :<br>
<br>
<pre><code>...<br>
messagebox.style.top='0px';<br>
headerHeight = 0;<br>
contentbox.style.left='0px';<br>
contentbox.style.width='100%';<br>
...<br>
</code></pre>

чтобы сдвинуть контент страницы в левый верхний угол.<br>
<br>
В меню <code>Галактика</code> :<br>
<br>
<pre><code>...<br>
headerHeight = 81;<br>
...<br>
</code></pre>

заменяется на :<br>
<br>
<pre><code>...<br>
messagebox.style.top='0px';<br>
headerHeight = 0;<br>
...<br>
</code></pre>

Так как в Галактике нет панели ресурсов.<br>
<br>
Также если {MESSAGE_TEXT} или {ERROR_TEXT} не пустые, добавляется две инструкции для создания видимости этих контейнеров:<br>
<br>
<pre><code>...<br>
headerHeight = 81;<br>
messagebox.style.display='block';<br>
errorbox.style.display='block';<br>
...<br>
</code></pre>

Контейнер сообщений и ошибки могут одновременно находиться на странице.<br>
<br>
<h1>Панель ресурсов</h1>

<img src='http://ogamespec.com/imgstore/oldogame_respane.jpg'>

<h1>Левое меню</h1>

Левое меню - статичный HTML-код. У некоторых страниц его нет (<code>Заметки</code>)<br>
<br>
<img src='http://ogamespec.com/imgstore/oldogame_leftmenu.jpg'>

<pre><code><br>
&lt;!-- LEFTMENU --&gt;<br>
<br>
    &lt;div id='leftmenu'&gt;<br>
    <br>
&lt;script language="JavaScript"&gt;<br>
function fenster(target_url,win_name) {<br>
  var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar=no,top=0,left=0,toolbar=no,width=550,height=280,resizable=yes');<br>
  new_win.focus();<br>
}<br>
&lt;/script&gt;<br>
<br>
&lt;script language="JavaScript"&gt;<br>
function popUp(URL) {<br>
  day = new Date();<br>
  id = day.getTime();<br>
  eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=1,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1,width=120,height=60,alwaysLowered=Yes');");<br>
}<br>
<br>
&lt;/script&gt;<br>
<br>
<br>
&lt;center&gt;<br>
<br>
&lt;div id='menu'&gt;<br>
&lt;a href="mailto:barrierefrei@ogame.de" title="Проблемы, касающиеся игроков со слабым зрением, отправляйте на barrierefrei@ogame.de." style="width:1px;"&gt;&lt;/a&gt;<br>
&lt;p style="width:110px;"&gt;&lt;NOBR&gt;Вселенная 5 (&lt;a href="index.php?page=changelog&amp;session=f79788caa724"&gt;v 0.83&lt;/a&gt;)&lt;/NOBR&gt;&lt;/p&gt;<br>
<br>
&lt;table width="110" cellspacing="0" cellpadding="0"&gt;<br>
 &lt;tr&gt;<br>
  &lt;td&gt;&lt;img src="http://uni5.ogame.ru/evolution/gfx/ogame-produktion.jpg" width="110" height="40" /&gt;&lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
    <br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=overview&amp;session=f79788caa724' accesskey="o"&gt;Обзор&lt;/a&gt;<br>
<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 <br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=b_building&amp;session=f79788caa724' accesskey="z"&gt;Постройки&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=resources&amp;session=f79788caa724' accesskey="s"&gt;Сырьё&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
<br>
 &lt;/tr&gt;<br>
<br>
  &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center" &gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=trader&amp;session=f79788caa724' accesskey=""&gt;&lt;font color='FF8900'&gt;Скупщик&lt;/font&gt;&lt;/a&gt; &lt;!-- TODO Loca Keys --&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
<br>
 &lt;/tr&gt;<br>
 <br>
  <br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=buildings&amp;session=f79788caa724&amp;mode=Forschung' accesskey="i"&gt;Исследования&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=buildings&amp;session=f79788caa724&amp;mode=Flotte' accesskey="v"&gt;Верфь&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=flotten1&amp;session=f79788caa724&amp;mode=Flotte' accesskey="f"&gt;Флот&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=techtree&amp;session=f79788caa724' accesskey="t"&gt;Технологии&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=galaxy&amp;session=f79788caa724&amp;no_header=1' accesskey="g"&gt;Галактика&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
<br>
     &lt;a href='index.php?page=buildings&amp;session=f79788caa724&amp;mode=Verteidigung' accesskey="x"&gt;Оборона&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;&lt;img src="http://uni5.ogame.ru/evolution/gfx/info-help.jpg" width="110" height="19"&gt;&lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=allianzen&amp;session=f79788caa724' accesskey="a"&gt;Мой альянс&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
  &lt;tr&gt;<br>
<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
    &lt;a href="http://board.ogame.ru/" target="_blank" accesskey="m" &gt;Форум&lt;/a&gt;&lt;!-- external link to board --&gt;<br>
   &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
 <br>
<br>
    &lt;tr&gt;<br>
       &lt;td align=center&gt;<br>
<br>
       &lt;a id='darkmatter2' style='cursor:pointer; width:110px;'<br>
         href='index.php?page=micropayment&amp;session=f79788caa724' accesskey="o"&gt;&lt;b&gt;Офицерское казино&lt;/a&gt;&lt;/b&gt;<br>
       &lt;/div&gt;<br>
      &lt;/td&gt;<br>
     &lt;/tr&gt;<br>
 <br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
  &lt;a href='index.php?page=statistics&amp;session=f79788caa724' accesskey="k"&gt;Статистика&lt;/a&gt;<br>
<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=suche&amp;session=f79788caa724' accesskey="p"&gt;Поиск&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
    &lt;a href="http://tutorial.ogame.ru" target="_blank" accesskey="2" &gt;Туториал&lt;/a&gt;&lt;!-- external link to ogame tutorial --&gt;<br>
   &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
<br>
 &lt;/tr&gt;<br>
 <br>
 &lt;tr&gt;<br>
  &lt;td&gt;&lt;img src="http://uni5.ogame.ru/evolution/gfx/user-menu.jpg" width="110" height="35"&gt;&lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=messages&amp;dsp=1&amp;session=f79788caa724' accesskey="b"&gt;Сообщения&lt;/a&gt;<br>
<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='#' onclick='fenster("index.php?page=notizen&amp;session=f79788caa724&amp;no_header=1", "Notizen");' accesskey="e"&gt;Заметки&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=buddy&amp;session=f79788caa724' accesskey="d"&gt;Друзья&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=options&amp;session=f79788caa724' accesskey="n"&gt;Настройки&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 <br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href='index.php?page=logout&amp;session=f79788caa724' accesskey="q"&gt;Выход&lt;/a&gt;<br>
    &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 <br>
 <br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
     &lt;a href="http://ogame.ru/regeln.html" target="_blank"&gt;Правила&lt;/a&gt; &lt;!-- external link to rules --&gt;<br>
   &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;tr&gt;<br>
  &lt;td&gt;<br>
   &lt;div align="center"&gt;&lt;font color="#FFFFFF"&gt;<br>
    &lt;a href="http://ogame.ru/portal/?go=contact&amp;lang=ru" target="_blank"&gt;О нас&lt;/a&gt; &lt;!-- external link to impressum --&gt;<br>
   &lt;/font&gt;&lt;/div&gt;<br>
  &lt;/td&gt;<br>
 &lt;/tr&gt;<br>
<br>
 &lt;/table&gt;<br>
 &lt;/center&gt;<br>
<br>
&lt;!-- GFAnalytics --&gt;<br>
 &lt;img src="http://analytics.gameforge.de/cp.php?game=ogame&amp;lang=ru&amp;gr=5&amp;action=login&amp;uid=167658" style="display:none" /&gt;<br>
 &lt;img src="http://adsm.gameforge.de/login.gif?game=ogame&amp;lang=ru&amp;gr=5&amp;action=login&amp;uid={USER_ID}&amp;email={USER_EMAIL}&amp;nicname={USER_LOGIN}" style="display:none" /&gt;<br>
 &lt;!-- /GFAnalytics --&gt;<br>
 &lt;/div&gt;<br>
<br>
    &lt;/div&gt;<br>
<br>
&lt;!-- END LEFTMENU --&gt;<br>
</code></pre>

Блок:<br>
<pre><code>&lt;!-- GFAnalytics --&gt;<br>
...<br>
 &lt;!-- /GFAnalytics --&gt;<br>
 &lt;/div&gt;<br>
</code></pre>
вставляется только один раз, после входа пользователя в игру (в Обзоре). Тут кстати синтаксическая ошибка - лишний раз закрывается тег <code>&lt;/div&gt;</code>.<br>
<br>
<h1>Содержимое игровых страниц</h1>

<h2>Обзор (page=overview)</h2>
<h2>Постройки (page=b_building)</h2>
<h2>Сырьё (page=resources)</h2>
<h2>Исследования (page=buildings&mode=Forschung)</h2>
Если на планете/луне нет лабы, выводится сообщение : "Для этого необходимо построить исследовательскую лабораторию!"<br>
<pre><code>&lt;!-- CONTENT AREA --&gt;<br>
&lt;div id='content'&gt;<br>
&lt;center&gt;<br>
&lt;title&gt;<br>
Постройки#Gebaeude<br>
&lt;/title&gt;<br>
&lt;script type="text/javascript"&gt;<br>
<br>
function setMax(key, number){<br>
    document.getElementsByName('fmenge['+key+']')[0].value=number;<br>
}<br>
&lt;/script&gt;<br>
&lt;table align=top&gt;&lt;tr&gt;&lt;td style='background-color:transparent;'&gt;  &lt;table width=530&gt;          &lt;tr&gt;<br>
          &lt;td class=l colspan="2"&gt;Описание&lt;/td&gt;<br>
          &lt;td class=l&gt;&lt;b&gt;Кол-во&lt;/b&gt;&lt;/td&gt;<br>
          &lt;/tr&gt;<br>
<br>
          <br>
&lt;table&gt;&lt;tr&gt;&lt;td class=c&gt;Для этого необходимо построить исследовательскую лабораторию!&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;&lt;/table&gt;&lt;/table&gt;<br>
&lt;br&gt;&lt;br&gt;&lt;br&gt;&lt;br&gt;<br>
&lt;/center&gt;<br>
&lt;/div&gt;<br>
&lt;!-- END CONTENT AREA --&gt;<br>
</code></pre>
<img src='http://ogamespec.com/imgstore/whc4e64abb8e89f9.jpg'>

Если на планете есть лаборатория, то выводится таблица исследований.<br>
Каждая строка таблицы -- одно исследование.<br>
В таблице перечислены только те исследования, которые можно исследовать в данной лаборатории. Если исследование можно запустить (ресурсов хватает), оно выделяется зеленым цветом, если нельзя (ресурсов не хватает) - красным. Если исследование уже запущено на одной из планет, то все ячейки пустые, за исключение текущего исследования, которое помечается ссылкой "отменить". Ссылка "отменить" показывается только на той планете, где было запущено исследование, на остальных планетах показываются пустые ячейки, а кнопка отмены неактивна.<br>
В Режиме Отпуска все исследования помечены красным, и вверху страницы показывается надпись : "Режим отпуска минимум до  2009-01-02 10:26:43".<br>
Если одна из Исследовательский лабораторий усовершенствуется, выводится соотв. надпись и все доступные исследования становятся "красными".<br>
<br>
Шаблон таблицы :<br>
<pre><code>&lt;!-- CONTENT AREA --&gt;<br>
&lt;div id='content'&gt;<br>
&lt;center&gt;<br>
&lt;title&gt;<br>
Постройки#Gebaeude<br>
&lt;/title&gt;<br>
&lt;script type="text/javascript"&gt;<br>
<br>
function setMax(key, number){<br>
    document.getElementsByName('fmenge['+key+']')[0].value=number;<br>
}<br>
&lt;/script&gt;<br>
&lt;table align=top&gt;&lt;tr&gt;&lt;td style='background-color:transparent;'&gt;  &lt;table width=530&gt;          &lt;tr&gt;<br>
          &lt;td class=l colspan="2"&gt;Описание&lt;/td&gt;<br>
          &lt;td class=l&gt;&lt;b&gt;Кол-во&lt;/b&gt;&lt;/td&gt;<br>
          &lt;/tr&gt;<br>
<br>
{ИССЛЕДОВАНИЕ 1}          <br>
<br>
{ИССЛЕДОВАНИЕ 2}<br>
<br>
{ИССЛЕДОВАНИЕ 3}<br>
<br>
....<br>
<br>
&lt;/table&gt;&lt;/table&gt;<br>
<br>
&lt;br&gt;&lt;br&gt;&lt;br&gt;&lt;br&gt;<br>
&lt;/center&gt;<br>
&lt;/div&gt;<br>
&lt;!-- END CONTENT AREA --&gt;<br>
</code></pre>

Для режима отпуска вначале таблицы такой текст:<br>
<br>
<pre><code>&lt;!-- CONTENT AREA --&gt;<br>
&lt;div id='content'&gt;<br>
&lt;center&gt;<br>
&lt;title&gt;<br>
Постройки#Gebaeude<br>
&lt;/title&gt;<br>
&lt;script type="text/javascript"&gt;<br>
<br>
function setMax(key, number){<br>
    document.getElementsByName('fmenge['+key+']')[0].value=number;<br>
}<br>
&lt;/script&gt;<br>
<br>
&lt;font color=#FF0000&gt;&lt;center&gt;Режим отпуска минимум до  2009-01-02 10:26:43&lt;/center&gt;&lt;/font&gt;&lt;table align=top&gt;&lt;tr&gt;&lt;td style='background-color:transparent;'&gt; ........ дальше всё тоже самое<br>
</code></pre>

<img src='http://ogamespec.com/imgstore/whc4e64abc08bd72.jpg'>

Если одна из лабораторий усовершенствуется :<br>
<br>
<pre><code>&lt;!-- CONTENT AREA --&gt;<br>
&lt;div id='content'&gt;<br>
&lt;center&gt;<br>
&lt;title&gt;<br>
Постройки#Gebaeude<br>
&lt;/title&gt;<br>
&lt;script type="text/javascript"&gt;<br>
<br>
function setMax(key, number){<br>
    document.getElementsByName('fmenge['+key+']')[0].value=number;<br>
}<br>
&lt;/script&gt;<br>
<br>
&lt;br&gt;&lt;br&gt;&lt;font color=#FF0000&gt;Проведение исследований невозможно, так как исследовательская лаборатория усовершенствуется.&lt;/font&gt;&lt;br /&gt;&lt;br /&gt;&lt;table align=top&gt;&lt;tr&gt;&lt;td style='background-color:transparent;'&gt; .... дальше тоже самое, но все исследования красные.<br>
</code></pre>

<img src='http://ogamespec.com/imgstore/whc4e64abc938c7a.jpg'>

Эти красные надписи (РО и лаба занята) выводятся даже если лаборатории на планете нет (см. первый снимок).<br>
<br>
Ячейки таблицы (исследования) :<br>
<br>
Исследование можно запустить :<br>
<br>
<pre><code>&lt;tr&gt;             &lt;td class=l&gt;<br>
                &lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=117&gt;<br>
                &lt;img border='0' src="{SKIN}gebaeude/117.gif" align='top' width='120' height='120'&gt;<br>
                &lt;/a&gt;<br>
                &lt;/td&gt;<br>
        &lt;td class=l&gt;&lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=117&gt;Impulstriebwerk&lt;/a&gt;&lt;/a&gt; (Stufe 4)&lt;br&gt;Das Impulstriebwerk basiert auf dem Rucksto?prinzip. Die Weiterentwicklung dieser Triebwerke macht einige Schiffe schneller, allerdings steigert jede Stufe die Geschwindigkeit nur um 20% des Grundwertes.&lt;br&gt;Benotigt: Metall: &lt;b&gt;32.000&lt;/b&gt; Kristall: &lt;b&gt;64.000&lt;/b&gt; Deuterium: &lt;b&gt;9.600&lt;/b&gt;&lt;br&gt;Produktionsdauer: 12h 0m &lt;br&gt;&lt;/th&gt;&lt;td class=k&gt; &lt;a href=index.php?page=buildings&amp;session={SESSION}&amp;mode=Forschung&amp;bau=117&gt;&lt;font color=#00FF00&gt;Erforschen&lt;br&gt; von Stufe  5&lt;/font&gt;&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;<br>
</code></pre>

Исследование нельзя запустить :<br>
<br>
<pre><code>&lt;tr&gt;                &lt;td class=l&gt;<br>
                &lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=106&gt;<br>
                &lt;img border='0' src="{SKIN}gebaeude/106.gif" align='top' width='120' height='120'&gt;<br>
                &lt;/a&gt;<br>
                &lt;/td&gt;<br>
        &lt;td class=l&gt;&lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=106&gt;Шпионаж&lt;/a&gt;&lt;/a&gt; (уровень 13)&lt;br&gt;С помощью этой технологии добываются данные о других планетах.&lt;br&gt;Стоимость: Металл: &lt;b&gt;1.638.400&lt;/b&gt; Кристалл: &lt;b&gt;8.192.000&lt;/b&gt; Дейтерий: &lt;b&gt;1.638.400&lt;/b&gt;&lt;br&gt;Длительность: 8дн. 8ч 37мин 13сек&lt;br&gt;&lt;/th&gt;&lt;td class=k&gt;&lt;font color=#FF0000&gt;Исследовать&lt;br&gt; уровень  14&lt;/font&gt;&lt;/td&gt;&lt;/tr&gt;<br>
</code></pre>

<img src='http://ogamespec.com/imgstore/whc4e64abd720600.jpg'>

Исследоование запущено (пустая ячейка) :<br>
<br>
<pre><code>&lt;tr&gt;                &lt;td class=l&gt;<br>
                &lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=106&gt;<br>
                &lt;img border='0' src="{SKIN}gebaeude/106.gif" align='top' width='120' height='120'&gt;<br>
                &lt;/a&gt;<br>
                &lt;/td&gt;<br>
        &lt;td class=l&gt;&lt;a href=index.php?page=infos&amp;session={SESSION}x&amp;gid=106&gt;Spionagetechnik&lt;/a&gt;&lt;/a&gt; (Stufe 6)&lt;br&gt;Mit Hilfe dieser Technik lassen sich Informationen uber andere Planeten und Monde gewinnen.&lt;br&gt;Benotigt: Metall: &lt;b&gt;12.800&lt;/b&gt; Kristall: &lt;b&gt;64.000&lt;/b&gt; Deuterium: &lt;b&gt;12.800&lt;/b&gt;&lt;br&gt;Produktionsdauer: 10h 58m 17s&lt;br&gt;&lt;/th&gt;&lt;td class=k&gt; - &lt;/td&gt;&lt;/tr&gt;<br>
</code></pre>

<img src='http://ogamespec.com/imgstore/whc4e64abdcaaa6b.jpg'>

Отменить исследование (только на планете откуда оно было запущено) :<br>
<br>
<pre><code>&lt;tr&gt;                &lt;td class=l&gt;<br>
                &lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=113&gt;<br>
                &lt;img border='0' src="{SKIN}gebaeude/113.gif" align='top' width='120' height='120'&gt;<br>
                &lt;/a&gt;<br>
                &lt;/td&gt;<br>
        &lt;td class=l&gt;&lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=113&gt;Energietechnik&lt;/a&gt;&lt;/a&gt; (Stufe 6)&lt;br&gt;Die Beherrschung der unterschiedlichen Arten von Energie ist fur viele neue Technologien notwendig.&lt;br&gt;Benotigt: Kristall: &lt;b&gt;51.200&lt;/b&gt; Deuterium: &lt;b&gt;25.600&lt;/b&gt;&lt;br&gt;Produktionsdauer: 6h 24m &lt;br&gt;&lt;/th&gt;&lt;td class=k&gt;                &lt;div id="bxx" class="z"&gt;&lt;/div&gt;<br>
                &lt;script   type="text/javascript"&gt;<br>
                v=new Date();<br>
                var bxx=document.getElementById('bxx');<br>
                function t(){<br>
                    n=new Date();<br>
                    ss=21551;<br>
                    s=ss-Math.round((n.getTime()-v.getTime())/1000.);<br>
                    m=0;h=0;<br>
                    if(s&lt;0){<br>
    <br>
                        bxx.innerHTML='Abgeschlossen&lt;br&gt;&lt;a href=index.php?page=buildings&amp;session={SESSION}&amp;mode=Forschung&amp;cp=1135279 &gt;weiter&lt;/a&gt;';<br>
                    }else{<br>
                        if(s&gt;59){<br>
                            m=Math.floor(s/60);<br>
                            s=s-m*60<br>
                        }<br>
                        if(m&gt;59){<br>
                            h=Math.floor(m/60);<br>
                            m=m-h*60<br>
                        }<br>
                        if(s&lt;10){<br>
                            s="0"+s<br>
                        }<br>
                        if(m&lt;10){<br>
                            m="0"+m<br>
                        }<br>
                        bxx.innerHTML=h+":"+m+":"+s+"&lt;br&gt;&lt;a href=index.php?page=buildings&amp;session={SESSION}&amp;unbau=113&amp;mode=Forschung&amp;cp=1135279"+<br>
                        "&gt;Abbrechen&lt;/a&gt;"                    }<br>
                    ;<br>
                    window.setTimeout("t();",999);<br>
                }<br>
                window.onload=t;<br>
                &lt;/script&gt;<br>
    &lt;/td&gt;&lt;/tr&gt;<br>
</code></pre>

Отменить исследование нельзя (на другой планете) :<br>
<br>
<pre><code>&lt;tr&gt;                &lt;td class=l&gt;<br>
                &lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=113&gt;<br>
                &lt;img border='0' src="{SKIN}gebaeude/113.gif" align='top' width='120' height='120'&gt;<br>
                &lt;/a&gt;<br>
                &lt;/td&gt;<br>
        &lt;td class=l&gt;&lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=113&gt;Energietechnik&lt;/a&gt;&lt;/a&gt; (Stufe 6)&lt;br&gt;Die Beherrschung der unterschiedlichen Arten von Energie ist fur viele neue Technologien notwendig.&lt;br&gt;Benotigt: Kristall: &lt;b&gt;51.200&lt;/b&gt; Deuterium: &lt;b&gt;25.600&lt;/b&gt;&lt;br&gt;Produktionsdauer: 7h 18m 51s&lt;br&gt;&lt;/th&gt;&lt;td class=k&gt;                &lt;div id="bxx" class="z"&gt;&lt;/div&gt;<br>
                &lt;script   type="text/javascript"&gt;<br>
                v=new Date();<br>
                var bxx=document.getElementById('bxx');<br>
                function t(){<br>
                    n=new Date();<br>
                    ss=21643;<br>
                    s=ss-Math.round((n.getTime()-v.getTime())/1000.);<br>
                    m=0;h=0;<br>
                    if(s&lt;0){<br>
    <br>
                        bxx.innerHTML='Abgeschlossen&lt;br&gt;&lt;a href=index.php?page=buildings&amp;session={SESSION}&amp;mode=Forschung&amp;cp=1143901 &gt;weiter&lt;/a&gt;';<br>
                    }else{<br>
                        if(s&gt;59){<br>
                            m=Math.floor(s/60);<br>
                            s=s-m*60<br>
                        }<br>
                        if(m&gt;59){<br>
                            h=Math.floor(m/60);<br>
                            m=m-h*60<br>
                        }<br>
                        if(s&lt;10){<br>
                            s="0"+s<br>
                        }<br>
                        if(m&lt;10){<br>
                            m="0"+m<br>
                        }<br>
                        bxx.innerHTML=h+":"+m+":"+s+"&lt;br&gt;&lt;a href=index.php?page=buildings&amp;session={SESSION}&amp;unbau=113&amp;mode=Forschung&amp;cp=1135279"+<br>
                                            }<br>
                    ;<br>
                    window.setTimeout("t();",999);<br>
                }<br>
                window.onload=t;<br>
                &lt;/script&gt;<br>
    &lt;/td&gt;&lt;/tr&gt;<br>
</code></pre>

<img src='http://ogamespec.com/imgstore/whc4e64abe3b58c1.jpg'>

Особый случай -- исследование 0го уровня, немного отличается :<br>
<br>
<pre><code>&lt;tr&gt;                &lt;td class=l&gt;<br>
                &lt;a href=index.php?page=infos&amp;session=55851c46e726&amp;gid=124&gt;<br>
                &lt;img border='0' src="{SKIN}gebaeude/124.gif" align='top' width='120' height='120'&gt;<br>
                &lt;/a&gt;<br>
                &lt;/td&gt;<br>
        &lt;td class=l&gt;&lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=124&gt;Expeditionstechnik&lt;/a&gt;&lt;br&gt;Schiffe konnen mit einem Forschungsmodul ausgerustet werden, welches bei Forschungsreisen eine wissenschaftliche Aufarbeitung der gesammelten Daten ermoglicht.&lt;br&gt;Benotigt: Metall: &lt;b&gt;4.000&lt;/b&gt; Kristall: &lt;b&gt;8.000&lt;/b&gt; Deuterium: &lt;b&gt;4.000&lt;/b&gt;&lt;br&gt;Produktionsdauer: 1h 30m &lt;br&gt;&lt;/th&gt;&lt;td class=k&gt; &lt;a href=index.php?page=buildings&amp;session={SESSION}&amp;mode=Forschung&amp;bau=124&gt;&lt;font color=#00FF00&gt; erforschen &lt;/font&gt;&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;<br>
<br>
&lt;tr&gt;               &lt;td class=l&gt;<br>
<br>
                &lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=199&gt;<br>
                &lt;img border='0' src="{SKIN}gebaeude/199.gif" align='top' width='120' height='120'&gt;<br>
                &lt;/a&gt;<br>
                &lt;/td&gt;<br>
        &lt;td class=l&gt;&lt;a href=index.php?page=infos&amp;session={SESSION}&amp;gid=199&gt;Гравитационная технология&lt;/a&gt;&lt;br&gt;Путём запуска концентрированного заряда частиц гравитона можно создавать искусственное гравитационное поле, благодаря которому можно уничтожать корабли или даже луны.&lt;br&gt;Стоимость: Энергия: &lt;b&gt;300.000&lt;/b&gt;&lt;br&gt;Длительность: 1сек&lt;br&gt;&lt;/th&gt;&lt;td class=k&gt;&lt;font color=#FF0000&gt; исследовать &lt;/font&gt;&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;<br>
</code></pre>

<img src='http://ogamespec.com/imgstore/whc4e64abe9123e2.jpg'>

нет пометки <b>(уровень X)</b> и вместо надписи "Исследовать уровень 4", просто "исследовать".<br>
<br>
Ещё одна деталь : если для исследования не нужно Металла/Кристалла/Дейтерия/Энергии, то этот ресурс не указывается в стоимости исследования. Порядок вывода необходимых ресурсов : Металл -> Кристалл -> Дейтерий -> Энергия.<br>
<br>
Ну и естественно описание каждого исследования разное :-)<br>
<br>
<h2>Верфь (page=buildings&mode=Flotte)</h2>
<h2>Флот (page=flotten1)</h2>
<h2>Технологии (page=techtree)</h2>
<h2>Галактика (page=galaxy)</h2>
<h2>Оборона (page=buildings&mode=Verteidigung)</h2>
<h2>Мой альянс (page=allianzen)</h2>
<h2>Статистика (page=statistics)</h2>
<h2>Поиск (page=suche)</h2>
<h2>Сообщения (page=messages)</h2>
<h2>Заметки (page=notizen)</h2>
<h2>Друзья (page=buddy)</h2>
<h2>Настройки (page=options)</h2>

<h2>Выход (page=logout)</h2>

Страничка выхода из игры отличается от стандартной схемы, ниже приведен полный HTML этой странички:<br>
<pre><code>&lt;html&gt;<br>
 &lt;head&gt;<br>
 &lt;meta http-equiv="content-type" content="text/html; charset=UTF-8" /&gt;<br>
 &lt;link rel="stylesheet" type="text/css" href="{USER_SKIN_PATH}formate.css" /&gt;<br>
  &lt;meta http-equiv="refresh" content="3;URL=http://ogame.ru?redirect=1" /&gt;<br>
  &lt;title&gt;Logout&lt;/title&gt;<br>
<br>
 &lt;script language="JavaScript"&gt;<br>
 function popUp(URL) {<br>
   day = new Date();<br>
   id = day.getTime();<br>
   eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=1,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1,width=800,height=600');");<br>
}<br>
 &lt;/script&gt;<br>
&lt;/head&gt;<br>
<br>
&lt;body class='style' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' &gt;<br>
&lt;div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"&gt;&lt;/div&gt;<br>
&lt;center&gt;<br>
До скорого!!&lt;br /&gt;<br>
&lt;p&gt;<br>
             &lt;/p&gt;<br>
&lt;/center&gt;<br>
&lt;/body&gt;<br>
&lt;/html&gt;<br>
</code></pre>

После сброса сессии пользователя показывается короткий текст <b><code>До скорого!!</code></b> и через 3 секунды происходит редирект на главную страницу.<br>
<br>
<h2>Релогин</h2>

HTML-код, который генерируется при попытке открытия игровой страницы, после релогина:<br>
<br>
<pre><code>&lt;script&gt;document.location.href='http://ogame.ru';&lt;/script&gt;Вы долго отсутствовали 0. (Войдите снова)&lt;br&gt;<br>
</code></pre>