<script type="text/javascript">

function createCookie(name,value,days) {
    var expires;
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        expires = "; expires="+date.toGMTString();
    }
    else expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function setlang(lang)
{
    createCookie ( "ogamelang", lang, 9999);
    location.reload ();
}

</script>

<div class="products" align="right">

<a href="#" onclick="javascript:setlang('de');"><img src="img/flags/de.gif" alt="Deutschland" title="Deutschland"></a>
<a href="#" onclick="javascript:setlang('en');"><img src="img/flags/gb.gif" alt="English" title="English"></a>
<a href="#" onclick="javascript:setlang('ru');"><img src="img/flags/ru.gif" alt="Russia" title="Russia"></a>

<!--
<a href="#" onclick="javascript:setlang('br');"><img src="img/flags/br.gif" alt="Brazil" title="Brazil"></a>
<a href="#" onclick="javascript:setlang('bg');"><img src="img/flags/bg.gif" alt="Bulgaria" title="Bulgaria"></a>
<a href="#" onclick="javascript:setlang('cn');"><img src="img/flags/cn.gif" alt="China" title="China"></a>
<a href="#" onclick="javascript:setlang('cz');"><img src="img/flags/cz.gif" alt="Czech Republic" title="Czech Republic"></a>
<a href="#" onclick="javascript:setlang('de');"><img src="img/flags/de.gif" alt="Deutschland" title="Deutschland"></a>
<a href="#" onclick="javascript:setlang('dk');"><img src="img/flags/dk.gif" alt="Denmark" title="Denmark"></a>
<a href="#" onclick="javascript:setlang('en');"><img src="img/flags/gb.gif" alt="English" title="English"></a>
<a href="#" onclick="javascript:setlang('es');"><img src="img/flags/es.gif" alt="Spain" title="Spain"></a>
<a href="#" onclick="javascript:setlang('fr');"><img src="img/flags/fr.gif" alt="France" title="France"></a>
<a href="#" onclick="javascript:setlang('gr');"><img src="img/flags/gr.gif" alt="Greece" title="Greece"></a>
<a href="#" onclick="javascript:setlang('hu');"><img src="img/flags/hu.gif" alt="Hungary" title="Hungary"></a>
<a href="#" onclick="javascript:setlang('it');"><img src="img/flags/it.gif" alt="Italy" title="Italy"></a>
<a href="#" onclick="javascript:setlang('jp');"><img src="img/flags/jp.gif" alt="Japan" title="Japan"></a>
<a href="#" onclick="javascript:setlang('kr');"><img src="img/flags/kr.gif" alt="Korea" title="Korea"></a>
<a href="#" onclick="javascript:setlang('nl');"><img src="img/flags/nl.gif" alt="Netherlands" title="Netherlands"></a>
<a href="#" onclick="javascript:setlang('no');"><img src="img/flags/no.gif" alt="Norway" title="Norway"></a>
<a href="#" onclick="javascript:setlang('pl');"><img src="img/flags/pl.gif" alt="Poland" title="Poland"></a>
<a href="#" onclick="javascript:setlang('pt');"><img src="img/flags/pt.gif" alt="Portugal" title="Portugal"></a>
<a href="#" onclick="javascript:setlang('ro');"><img src="img/flags/ro.gif" alt="Romania" title="Romania"></a>
<a href="#" onclick="javascript:setlang('ru');"><img src="img/flags/ru.gif" alt="Russia" title="Russia"></a>
<a href="#" onclick="javascript:setlang('sk');"><img src="img/flags/sk.gif" alt="Slovakia" title="Slovakia"></a>
<a href="#" onclick="javascript:setlang('se');"><img src="img/flags/se.gif" alt="Sweden" title="Sweden"></a>
<a href="#" onclick="javascript:setlang('tr');"><img src="img/flags/tr.gif" alt="Turkey" title="Turkey"></a>
<a href="#" onclick="javascript:setlang('tw');"><img src="img/flags/tw.gif" alt="Taiwan" title="Taiwan"></a>
-->

<a href="#"><?php echo loca('CHOOSELANG');?></a>
</div>