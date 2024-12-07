# HTML исходники всех игровых страниц с картинками

Для исторических целей перед вводом Редизайна была проведена обширная работа по сохранению HTML-кода всех оригинальных страниц. Результаты этой работы будут использованы для воссоздания оригинального облика игры.

Разметка всех игровых страниц однотипна и состоит из следующих частей:
- Заголовок (header): мета-теги, теги head/title, подгрузка стилей и общих скриптов. Начало тега body.
- Панель списка планет, ресурсов и офицеров (на некоторых страницах может отсутствовать)
- "Левое меню": называется так, потому что находится в левой части экрана (на некоторых страницах может отсутствовать)
- Содержимое (content): тут находятся непосредственно элементы игровой страницы
- Хвост (footer): закрывается тег body/html и прочая мелочь.

В HTML коде *{вот так}* будут показываться места, где PHP-код генерирует контент, например путь к скину или какие-нибудь параметры.

Заранее извиняюсь, что часть текста на немецком, но я не успел сохранить разные незначительные страницы или их детали, пришлось потом дособирать инфу в единственной оставшейся вселенной без редизайна -- немецкой 20й вселенной. Не страшно, всё равно перевод немецких текстов у меня есть в русской локализации, главное чтобы HTML код соответствовал оригиналу.

## Заголовок

```html
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
- Подгружаются дефолтные стили `css/default.css` и `css/formate.css`, которые затем перегружаются стилем установленного скина `{USER_SKIN_PATH}formate.css`. Небольшая особенность: в пути к скину в конце всегда должна стоять `/` иначе format.css слепится с путём к скину и итоговый путь будет неверным. Путь к скину берется из настроек пользователя:<br/><img src="/imgstore/whc4d651e5551511.jpg">
- Установка кодировки через META-тег. Как видно тут ещё используется кодировка koi8-r, потому что сохранение страниц началось до повсевместного ввода кодировки utf-8 в игру.
- Подгружается скрипт оверлеев `overlib.js`. Оверлеи - это всплывающие окна, которые выплывают к примеру в Галактике при наведении мышки на картинку планеты:<br/><img src="/imgstore/whc4d651ccc14f0a.jpg"><br/>Скрипт `overlib.js` кстати OpenSource.
- устанавливается таймер на вызов функции `reloadImages()` каждые 100ms. Эта функция принудительно перегружает все картинки `img`, у которых `class=reloadimage`. Я таких в игре не встречал.
- Подгружается скрипт `js/utilities.js` 
- Устанавливается заголовок страницы, например *`Вселенная 15 ОГейм`*
- Создается div `id=overDiv`, который представляет собой контейнер для оверлея.

## Хвост

```html
<script> 
messageboxHeight=0;
errorboxHeight=0;
contentbox = document.getElementById('content');
</script> 
 
<div id='messagebox'> 
<center> 
{MESSAGE_TEXT}</center> 
</div> 
<div id='errorbox'> 
<center> 
{ERROR_TEXT}</center> 
</div> 
 
<script> 
headerHeight = 81;
errorbox.style.top=parseInt(headerHeight+messagebox.offsetHeight+5)+'px';
contentbox.style.top=parseInt(headerHeight+errorbox.offsetHeight+messagebox.offsetHeight+10)+'px';
if (navigator.appName=='Netscape'){if (window.innerWidth<1020){document.body.scroll='no';}   contentbox.style.height=parseInt(window.innerHeight)-messagebox.offsetHeight-errorbox.offsetHeight-headerHeight-20;
if(document.getElementById('resources')) {   document.getElementById('resources').style.width=(window.innerWidth*0.4);}}
 else {
if (document.body.offsetWidth<1020){document.body.scroll='no';}   contentbox.style.height=parseInt(document.body.offsetHeight)-messagebox.offsetHeight-headerHeight-errorbox.offsetHeight-20;document.getElementById('resources').style.width=(document.body.offsetWidth*0.4);
}for (var i = 0; i < document.links.length; ++i) {
  if (document.links[i].href.search(/.*redir\.php\?url=.*/) != -1) {
    document.links[i].target = "_blank";
  }
}
 
</script> 
<style> 
.layer {
    z-index:999999999;
    position:absolute;
    left: 0;
    right: 0;
    top: 100px;
    margin-left: auto;
    margin-right: auto;
    width: 757px; 
    height: 475px; 
    background-color: #040e1e;
    border: 3px double orange;
    padding: 0;
    opacity: .90;
}
</style> 
 </body> 
</html>
```

В хвосте находятся следующие элементы:
- Контейнеры для сообщения и ошибки. Сообщение - это небольшой текст наверху страницы, заключенный в зеленую рамочку, например:<br><img src="/imgstore/whc4d6523a207802.jpg"><br>Ошибка - аналогично, но цвет рамки красный:<br><img src="/imgstore/whc4d6524bb241af.jpg">
- Небольшой скрипт, для расчёта Y-координат контента, при наличии сообщения и/или ошибки.
- Какой-то непонятный стиль `layer`, где используется неясно. Возможно просто артефакт от старых версий.

Для всплывающих окон наподобии `Заметок` хвост имеет немного другой вид:

```
...
headerHeight = 81;
...
```

заменяется на :

```
...
messagebox.style.top='0px';
headerHeight = 0;
contentbox.style.left='0px';
contentbox.style.width='100%';
...
```

чтобы сдвинуть контент страницы в левый верхний угол.

В меню `Галактика` :

```
...
headerHeight = 81;
...
```

заменяется на :

```
...
messagebox.style.top='0px';
headerHeight = 0;
...
```

Так как в Галактике нет панели ресурсов.

Также если {MESSAGE_TEXT} или {ERROR_TEXT} не пустые, добавляется две инструкции для создания видимости этих контейнеров:

```
...
headerHeight = 81;
messagebox.style.display='block';
errorbox.style.display='block';
...
```

Контейнер сообщений и ошибки могут одновременно находиться на странице.

## Панель ресурсов

![oldogame_respane](/imgstore/oldogame_respane.jpg)

## Левое меню

Левое меню - статичный HTML-код. У некоторых страниц его нет (`Заметки`)

![oldogame_leftmenu](/imgstore/oldogame_leftmenu.jpg)

```html

<!-- LEFTMENU -->

    <div id='leftmenu'>
    
<script language="JavaScript">
function fenster(target_url,win_name) {
  var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar=no,top=0,left=0,toolbar=no,width=550,height=280,resizable=yes');
  new_win.focus();
}
</script>

<script language="JavaScript">
function popUp(URL) {
  day = new Date();
  id = day.getTime();
  eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=1,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1,width=120,height=60,alwaysLowered=Yes');");
}

</script>


<center>

<div id='menu'>
<a href="mailto:barrierefrei@ogame.de" title="Проблемы, касающиеся игроков со слабым зрением, отправляйте на barrierefrei@ogame.de." style="width:1px;"></a>
<p style="width:110px;"><NOBR>Вселенная 5 (<a href="index.php?page=changelog&session=f79788caa724">v 0.83</a>)</NOBR></p>

<table width="110" cellspacing="0" cellpadding="0">
 <tr>
  <td><img src="http://uni5.ogame.ru/evolution/gfx/ogame-produktion.jpg" width="110" height="40" /></td>
 </tr>

    
 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=overview&session=f79788caa724' accesskey="o">Обзор</a>

    </font></div>
  </td>
 </tr>

 
 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=b_building&session=f79788caa724' accesskey="z">Постройки</a>
    </font></div>

  </td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=resources&session=f79788caa724' accesskey="s">Сырьё</a>
    </font></div>
  </td>

 </tr>

  <tr>
  <td>
   <div align="center" ><font color="#FFFFFF">
     <a href='index.php?page=trader&session=f79788caa724' accesskey=""><font color='FF8900'>Скупщик</font></a> <!-- TODO Loca Keys -->
    </font></div>
  </td>

 </tr>
 
  
 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=buildings&session=f79788caa724&mode=Forschung' accesskey="i">Исследования</a>
    </font></div>
  </td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=buildings&session=f79788caa724&mode=Flotte' accesskey="v">Верфь</a>
    </font></div>
  </td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=flotten1&session=f79788caa724&mode=Flotte' accesskey="f">Флот</a>
    </font></div>
  </td>
 </tr>

 <tr>

  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=techtree&session=f79788caa724' accesskey="t">Технологии</a>
    </font></div>
  </td>
 </tr>

 <tr>
  <td>

   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=galaxy&session=f79788caa724&no_header=1' accesskey="g">Галактика</a>
    </font></div>
  </td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">

     <a href='index.php?page=buildings&session=f79788caa724&mode=Verteidigung' accesskey="x">Оборона</a>
    </font></div>
  </td>
 </tr>

 <tr>
  <td><img src="http://uni5.ogame.ru/evolution/gfx/info-help.jpg" width="110" height="19"></td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=allianzen&session=f79788caa724' accesskey="a">Мой альянс</a>
    </font></div>
  </td>
 </tr>

  <tr>

  <td>
   <div align="center"><font color="#FFFFFF">
    <a href="http://board.ogame.ru/" target="_blank" accesskey="m" >Форум</a><!-- external link to board -->
   </font></div>
  </td>
 </tr>
 

    <tr>
       <td align=center>

       <a id='darkmatter2' style='cursor:pointer; width:110px;'
         href='index.php?page=micropayment&session=f79788caa724' accesskey="o"><b>Офицерское казино</a></b>
       </div>
      </td>
     </tr>
 
 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
  <a href='index.php?page=statistics&session=f79788caa724' accesskey="k">Статистика</a>

    </font></div>
  </td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=suche&session=f79788caa724' accesskey="p">Поиск</a>
    </font></div>

  </td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
    <a href="http://tutorial.ogame.ru" target="_blank" accesskey="2" >Туториал</a><!-- external link to ogame tutorial -->
   </font></div>
  </td>

 </tr>
 
 <tr>
  <td><img src="http://uni5.ogame.ru/evolution/gfx/user-menu.jpg" width="110" height="35"></td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=messages&dsp=1&session=f79788caa724' accesskey="b">Сообщения</a>

    </font></div>
  </td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='#' onclick='fenster("index.php?page=notizen&session=f79788caa724&no_header=1", "Notizen");' accesskey="e">Заметки</a>
    </font></div>

  </td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=buddy&session=f79788caa724' accesskey="d">Друзья</a>
    </font></div>
  </td>

 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=options&session=f79788caa724' accesskey="n">Настройки</a>
    </font></div>
  </td>
 </tr>

 
 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href='index.php?page=logout&session=f79788caa724' accesskey="q">Выход</a>
    </font></div>
  </td>
 </tr>

 
 

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
     <a href="http://ogame.ru/regeln.html" target="_blank">Правила</a> <!-- external link to rules -->
   </font></div>
  </td>
 </tr>

 <tr>
  <td>
   <div align="center"><font color="#FFFFFF">
    <a href="http://ogame.ru/portal/?go=contact&lang=ru" target="_blank">О нас</a> <!-- external link to impressum -->
   </font></div>
  </td>
 </tr>

 </table>
 </center>

<!-- GFAnalytics -->
 <img src="http://analytics.gameforge.de/cp.php?game=ogame&lang=ru&gr=5&action=login&uid=167658" style="display:none" />
 <img src="http://adsm.gameforge.de/login.gif?game=ogame&lang=ru&gr=5&action=login&uid={USER_ID}&email={USER_EMAIL}&nicname={USER_LOGIN}" style="display:none" />
 <!-- /GFAnalytics -->
 </div>

    </div>

<!-- END LEFTMENU -->
```

Блок: 
```
<!-- GFAnalytics -->
...
 <!-- /GFAnalytics -->
 </div>
```
вставляется только один раз, после входа пользователя в игру (в Обзоре). Тут кстати синтаксическая ошибка - лишний раз закрывается тег `</div>`.

## Содержимое игровых страниц

### Обзор (page=overview)

### Постройки (page=b_building)

### Сырьё (page=resources)

### Исследования (page=buildings&mode=Forschung)

Если на планете/луне нет лабы, выводится сообщение: "Для этого необходимо построить исследовательскую лабораторию!"

```html
<!-- CONTENT AREA -->
<div id='content'>
<center>
<title>
Постройки#Gebaeude
</title>
<script type="text/javascript">

function setMax(key, number){
    document.getElementsByName('fmenge['+key+']')[0].value=number;
}
</script>
<table align=top><tr><td style='background-color:transparent;'>  <table width=530>          <tr>
          <td class=l colspan="2">Описание</td>
          <td class=l><b>Кол-во</b></td>
          </tr>

          
<table><tr><td class=c>Для этого необходимо построить исследовательскую лабораторию!</td></tr></table></table></table>
<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->
```

Если на планете есть лаборатория, то выводится таблица исследований.
Каждая строка таблицы -- одно исследование.
В таблице перечислены только те исследование, которые можно исследовать в данной лаборатории. Если исследование можно запустить (ресурсов хватает), оно выделяется зеленым цветом, если нельзя (ресурсов не хватает) - красным. Если исследование уже запущено на одной из планет, то все ячейки пустые, за исключение текущего исследования, которое помечается ссылкой "отменить". Ссылка "отменить" показывается только на той планете, где было запущено исследование, на остальных планетах показываются пустые ячейки, а кнопка отмены неактивна.
В Режиме Отпуска все исследования помечены красным, и вверху страницы показывается надпись : "Режим отпуска минимум до  2009-01-02 10:26:43".

Шаблон таблицы:

```html
<!-- CONTENT AREA -->
<div id='content'>
<center>
<title>
Постройки#Gebaeude
</title>
<script type="text/javascript">

function setMax(key, number){
    document.getElementsByName('fmenge['+key+']')[0].value=number;
}
</script>
<table align=top><tr><td style='background-color:transparent;'>  <table width=530>          <tr>
          <td class=l colspan="2">Описание</td>
          <td class=l><b>Кол-во</b></td>
          </tr>

{ИССЛЕДОВАНИЕ 1}          

{ИССЛЕДОВАНИЕ 2}

{ИССЛЕДОВАНИЕ 3}

....

</table></table>

<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->
```

Для режима отпуска вначале таблицы такой текст:

```html
<!-- CONTENT AREA -->
<div id='content'>
<center>
<title>
Постройки#Gebaeude
</title>
<script type="text/javascript">

function setMax(key, number){
    document.getElementsByName('fmenge['+key+']')[0].value=number;
}
</script>

<font color=#FF0000><center>Режим отпуска минимум до  2009-01-02 10:26:43</center></font><table align=top><tr><td style='background-color:transparent;'> ........ дальше всё тоже самое
```

Ячейки таблицы (исследования) :

Исследование можно запустить :

```html
<tr>             <td class=l>
                <a href=index.php?page=infos&session={SESSION}&gid=117>
                <img border='0' src="{SKIN}gebaeude/117.gif" align='top' width='120' height='120'>
                </a>
                </td>
        <td class=l><a href=index.php?page=infos&session={SESSION}&gid=117>Impulstriebwerk</a></a> (Stufe 4)<br>Das Impulstriebwerk basiert auf dem Rucksto?prinzip. Die Weiterentwicklung dieser Triebwerke macht einige Schiffe schneller, allerdings steigert jede Stufe die Geschwindigkeit nur um 20% des Grundwertes.<br>Benotigt: Metall: <b>32.000</b> Kristall: <b>64.000</b> Deuterium: <b>9.600</b><br>Produktionsdauer: 12h 0m <br></th><td class=k> <a href=index.php?page=buildings&session={SESSION}&mode=Forschung&bau=117><font color=#00FF00>Erforschen<br> von Stufe  5</font></a></td></tr>
```

Исследование нельзя запустить :

```html
<tr>                <td class=l>
                <a href=index.php?page=infos&session={SESSION}&gid=106>
                <img border='0' src="{SKIN}gebaeude/106.gif" align='top' width='120' height='120'>
                </a>
                </td>
        <td class=l><a href=index.php?page=infos&session={SESSION}&gid=106>Шпионаж</a></a> (уровень 13)<br>С помощью этой технологии добываются данные о других планетах.<br>Стоимость: Металл: <b>1.638.400</b> Кристалл: <b>8.192.000</b> Дейтерий: <b>1.638.400</b><br>Длительность: 8дн. 8ч 37мин 13сек<br></th><td class=k><font color=#FF0000>Исследовать<br> уровень  14</font></td></tr>
```

Исследоование запущено (пустая ячейка):

```html
<tr>                <td class=l>
                <a href=index.php?page=infos&session={SESSION}&gid=106>
                <img border='0' src="{SKIN}gebaeude/106.gif" align='top' width='120' height='120'>
                </a>
                </td>
        <td class=l><a href=index.php?page=infos&session={SESSION}x&gid=106>Spionagetechnik</a></a> (Stufe 6)<br>Mit Hilfe dieser Technik lassen sich Informationen uber andere Planeten und Monde gewinnen.<br>Benotigt: Metall: <b>12.800</b> Kristall: <b>64.000</b> Deuterium: <b>12.800</b><br>Produktionsdauer: 10h 58m 17s<br></th><td class=k> - </td></tr>
```

Отменить исследование (только на планете откуда оно было запущено):

```html
<tr>                <td class=l>
                <a href=index.php?page=infos&session={SESSION}&gid=113>
                <img border='0' src="{SKIN}gebaeude/113.gif" align='top' width='120' height='120'>
                </a>
                </td>
        <td class=l><a href=index.php?page=infos&session={SESSION}&gid=113>Energietechnik</a></a> (Stufe 6)<br>Die Beherrschung der unterschiedlichen Arten von Energie ist fur viele neue Technologien notwendig.<br>Benotigt: Kristall: <b>51.200</b> Deuterium: <b>25.600</b><br>Produktionsdauer: 6h 24m <br></th><td class=k>                <div id="bxx" class="z"></div>
                <script   type="text/javascript">
                v=new Date();
                var bxx=document.getElementById('bxx');
                function t(){
                    n=new Date();
                    ss=21551;
                    s=ss-Math.round((n.getTime()-v.getTime())/1000.);
                    m=0;h=0;
                    if(s<0){
    
                        bxx.innerHTML='Abgeschlossen<br><a href=index.php?page=buildings&session={SESSION}&mode=Forschung&cp=1135279 >weiter</a>';
                    }else{
                        if(s>59){
                            m=Math.floor(s/60);
                            s=s-m*60
                        }
                        if(m>59){
                            h=Math.floor(m/60);
                            m=m-h*60
                        }
                        if(s<10){
                            s="0"+s
                        }
                        if(m<10){
                            m="0"+m
                        }
                        bxx.innerHTML=h+":"+m+":"+s+"<br><a href=index.php?page=buildings&session={SESSION}&unbau=113&mode=Forschung&cp=1135279"+
                        ">Abbrechen</a>"                    }
                    ;
                    window.setTimeout("t();",999);
                }
                window.onload=t;
                </script>
    </td></tr>
```

Отменить исследование нельзя (на другой планете) :

```html
<tr>                <td class=l>
                <a href=index.php?page=infos&session={SESSION}&gid=113>
                <img border='0' src="{SKIN}gebaeude/113.gif" align='top' width='120' height='120'>
                </a>
                </td>
        <td class=l><a href=index.php?page=infos&session={SESSION}&gid=113>Energietechnik</a></a> (Stufe 6)<br>Die Beherrschung der unterschiedlichen Arten von Energie ist fur viele neue Technologien notwendig.<br>Benotigt: Kristall: <b>51.200</b> Deuterium: <b>25.600</b><br>Produktionsdauer: 7h 18m 51s<br></th><td class=k>                <div id="bxx" class="z"></div>
                <script   type="text/javascript">
                v=new Date();
                var bxx=document.getElementById('bxx');
                function t(){
                    n=new Date();
                    ss=21643;
                    s=ss-Math.round((n.getTime()-v.getTime())/1000.);
                    m=0;h=0;
                    if(s<0){
    
                        bxx.innerHTML='Abgeschlossen<br><a href=index.php?page=buildings&session={SESSION}&mode=Forschung&cp=1143901 >weiter</a>';
                    }else{
                        if(s>59){
                            m=Math.floor(s/60);
                            s=s-m*60
                        }
                        if(m>59){
                            h=Math.floor(m/60);
                            m=m-h*60
                        }
                        if(s<10){
                            s="0"+s
                        }
                        if(m<10){
                            m="0"+m
                        }
                        bxx.innerHTML=h+":"+m+":"+s+"<br><a href=index.php?page=buildings&session={SESSION}&unbau=113&mode=Forschung&cp=1135279"+
                                            }
                    ;
                    window.setTimeout("t();",999);
                }
                window.onload=t;
                </script>
    </td></tr>
```

Особый случай -- исследование 0го уровня, немного отличается :

```html
<tr>                <td class=l>
                <a href=index.php?page=infos&session=55851c46e726&gid=124>
                <img border='0' src="{SKIN}gebaeude/124.gif" align='top' width='120' height='120'>
                </a>
                </td>
        <td class=l><a href=index.php?page=infos&session={SESSION}&gid=124>Expeditionstechnik</a><br>Schiffe konnen mit einem Forschungsmodul ausgerustet werden, welches bei Forschungsreisen eine wissenschaftliche Aufarbeitung der gesammelten Daten ermoglicht.<br>Benotigt: Metall: <b>4.000</b> Kristall: <b>8.000</b> Deuterium: <b>4.000</b><br>Produktionsdauer: 1h 30m <br></th><td class=k> <a href=index.php?page=buildings&session={SESSION}&mode=Forschung&bau=124><font color=#00FF00> erforschen </font></a></td></tr>

<tr>               <td class=l>

                <a href=index.php?page=infos&session={SESSION}&gid=199>
                <img border='0' src="{SKIN}gebaeude/199.gif" align='top' width='120' height='120'>
                </a>
                </td>
        <td class=l><a href=index.php?page=infos&session={SESSION}&gid=199>Гравитационная технология</a><br>Путём запуска концентрированного заряда частиц гравитона можно создавать искусственное гравитационное поле, благодаря которому можно уничтожать корабли или даже луны.<br>Стоимость: Энергия: <b>300.000</b><br>Длительность: 1сек<br></th><td class=k><font color=#FF0000> исследовать </font></a></td></tr>
```

нет пометки *(уровень X)* и вместо надписи "Исследовать уровень 4", просто "исследовать".

Ещё одна деталь : если для исследования не нужно Металла/Кристалла/Дейтерия/Энергии, то этот ресурс не указывается в стоимости исследования. Порядок вывода необходимых ресурсов : Металл -> Кристалл -> Дейтерий -> Энергия.

Ну и естественно описание каждого исследования разное :-)

### Верфь (page=buildings&mode=Flotte)

### Флот (page=flotten1)

### Технологии (page=techtree)

### Галактика (page=galaxy)

### Оборона (page=buildings&mode=Verteidigung)

### Мой альянс (page=allianzen)

### Статистика (page=statistics)

### Поиск (page=suche)

### Сообщения (page=messages)

![oldogame_messages](/imgstore/oldogame_messages.jpg)

### Заметки (page=notizen)

### Друзья (page=buddy)

### Настройки (page=options)

### Выход (page=logout)

Страничка выхода из игры отличается от стандартной схемы, ниже приведен полный HTML этой странички:

```html
<html>
 <head>
 <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
 <link rel="stylesheet" type="text/css" href="{USER_SKIN_PATH}formate.css" />
  <meta http-equiv="refresh" content="3;URL=http://ogame.ru?redirect=1" />
  <title>Logout</title>

 <script language="JavaScript">
 function popUp(URL) {
   day = new Date();
   id = day.getTime();
   eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=1,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1,width=800,height=600');");
}
 </script>
</head>

<body class='style' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' >
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<center>
До скорого!!<br />
<p>
             </p>
</center>
</body>
</html>
```

После сброса сессии пользователя показывается короткий текст *`До скорого!!`* и через 3 секунды происходит редирект на главную страницу.

### Релогин

HTML-код, который генерируется при попытке открытия игровой страницы, после релогина:

```
<script>document.location.href='http://ogame.ru';</script>Вы долго отсутствовали 0. (Войдите снова)<br>
```
