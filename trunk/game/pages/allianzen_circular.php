<?php

// Общее сообщение.

function AllyPage_CircularMessage ()
{
    global $session;

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=17&sendmail=1" method=POST>
<tr><td class=c colspan=2>Отправить общее сообщение</td></tr>
<tr><th>Получатель</th><th>
<select name=r>
    <option value=0>Все игроки</option>
    <option value=2>Только определённому рангу: 1111</option>
    <option value=3>Только определённому рангу: 2222</option>
</select></th></tr>
<tr><th>Текст сообщения (<span id="cntChars">0</span> / 2000 Симв.)</th><th><textarea name=text cols=60 rows=10 onkeyup="javascript:cntchar(2000)"></textarea></th></tr>
<tr><th colspan=2><input type=submit value="Отправить"></th></tr></table></center></form>
<?php
}

?>