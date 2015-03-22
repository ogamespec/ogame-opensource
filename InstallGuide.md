В данной теме освещается вопрос установки игры на свой сайт или на локальный компьютер для тестирования и разработки.

Установка игры на локальный компьютер рассматривается для среды Windows.

# Установка локального Web-сервера #

Рекомендую AppServ - легковесную сборку Apache + PHP + MySQL :
[URL](URL.md)http://www.appservnetwork.com/[/URL]

После несложной установки можно сразу приступать к разработке. Ваш localhost будет находиться по умолчанию в папке C:\AppServ\www

На этапе установки вам необходимо будет запомнить или записать пароль для root на MySQL. Я обычно делаю qwerty :)

Другие популярные сборки :<br>
- <code>Denwer</code><br>
- <code>VertrigoServ</code><br>
- <code>LAMP</code><br>

<h1>Скачивание исходного кода</h1>

Исходный код проекта находится на сайте Google Code, по адресу:<br>
<a href='http://code.google.com/p/ogame-opensource/'>http://code.google.com/p/ogame-opensource/</a>

Для скачивания всех исходников и прочих файлов потребуется SVN-клиент, такой например как TortoiseSVN:<br>
<a href='http://tortoisesvn.net/'>http://tortoisesvn.net/</a><br>
Скачиваете его себе и устанавливаете.<br>
<br>
Затем нужно сделать SVN Checkout, используя данный адрес :<br>
<a href='http://ogame-opensource.googlecode.com/svn/trunk/'>http://ogame-opensource.googlecode.com/svn/trunk/</a>

<img src='http://ogamespec.com/imgstore/whc4f204aab88086.jpg' />

В данном примере исходный код скачивается сразу в директорию www, и будет доступен по локальному адресу :<br>
<a href='http://localhost/ogame-opensource'>http://localhost/ogame-opensource</a>

<h1>Настройка PHP.INI</h1>

Немаловажная часть - это правильная настройка PHP.INI. Ниже перечислены важные параметры и значения, которые рекомендуется им установить.<br>
<br>
<b>short_open_tag = On</b><br>
Разрешает использование коротких включений PHP вида <? ... ?><br>
Этот параметр обязательно должен быть включен, потому что короткие включения используются повсеместно в игровой движке.<br>
<br>
<b>max_execution_time = 200</b><br>
Время выполнения скриптов. 200 секунд предостаточно для большинства задач. Бои уровня 20ккк потерь обрабатываются порядка 10-15 секунд, в зависимости от сервера.<br>
<br>
<b>display_errors = On</b><br>
Показывать ошибки выполнения скриптов.<br>
<br>
<b>variables_order = "EGPCS"</b><br>
Порядок обработки глобальных переменных.<br>
<br>
<b>register_globals = On</b><br>
Какая-то херня, у меня включена обычно )<br>
<br>
<b>magic_quotes_gpc = On</b><br>
Экранирование строк с кавычками. Все игровые скрипты предполагают, что строки экранированы по умолчанию.<br>
<br>
<b>Необходимые расширения</b><br>
extension=php_gd2.dll<br>
extension=php_mbstring.dll<br>
extension=php_mysql.dll<br>