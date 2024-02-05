// ==UserScript==
// @include http://*ogame*/game/*
// @name SuperOgame - Core file
// @author Mladen Pejaković
// @namespace http://superogame.sourceforge.net/
// @version 1.7.0
// @description SuperOgame is an user javascript for Opera which improves experience of playing OGame
// ==/UserScript==

/* (C) Mladen Pejaković <operasog@gmail.com>, 2008, 2009, 2010.
 * (C) cedricpc, 2007, 2008.
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

var RegEx  = /http:\/\/(.*?ogame.*?)\/game\/(.*?)$/i;
var Result = RegEx.exec(document.location);
var soGen_LTimeS = new Date();
var Server = Result[1];
var Page = Result[2].match(/page=(.*?)(?=&|$)/i) ? RegExp.$1 : '';
var Mode = Result[2].match(/mode=(.*?)(?=&|$)/i) ? RegExp.$1 : '';
var soVer_Curr = '1.7.0';

// ### TIMING ###

function sofGen_LTime(){
var soGen_LTime = new Date() - soGen_LTimeS;
window.status = i18n('Loadtime',46)+': '+soGen_LTime+' [ms]';}

//### STORING ###

function sofGet(set){
if (typeof(localStorage) != 'undefined')
var item = ((localStorage.getItem(set) != null) ? localStorage.getItem(set) : soSetDef[set]);
return item;}

function sofSet(set,val){
if (typeof(localStorage) != 'undefined')
localStorage.setItem(set,val);}

function sofClr(set){
if (typeof(localStorage) != 'undefined')
localStorage.removeItem(set);}

//### LANGUAGE ###

function sofGen_GetLang(){
var gServArr = new Array('ar','ba','br','bg','cn','cz','de','dk','es','fi','fr','gr','hu','it','jp','kr','lt','lv','mx','nl','no','pl','pt','ro','rs','ru','sk','se','si','tr','tw');
for (var i = gServArr.length-1; i>=0; i--){
if(Server.match(gServArr[i])){gServer = gServArr[i];gLang = (sofGet('soLan_Ovr') == '' ? gServArr[i].toUpperCase() : sofGet('soLan_Ovr'));break;}
else{gServer = 'en';gLang = (sofGet('soLan_Ovr') == '' ? 'EN' : sofGet('soLan_Ovr'));}}
switch (gServArr){
case 'br':
gLang = 'PT';
case 'ar':
gLang = 'ES';
case 'mx':
gLang = 'ES';
case 'rs':
gLang = 'SR';}
var gServLan = gServer + ';' + gLang;
return gServLan.split(';');}

soLan_LngList = new Array();
soLan_LngList['EN'] = 'English';

function addLng(tagLng, nameLng){
soLan_LngList[tagLng.toUpperCase()] = nameLng;}

function i18n(w,n){
var lang = sofGen_GetLang();
var langa = (typeof(window['i18n_'+lang[1]]) == 'undefined' ? new Array() : window['i18n_'+lang[1]]);
return (lang != '' ? ((typeof(langa[n]) == 'undefined' || langa[n] == '') ? w : langa[n]) : w);}

//### REFRESH ###

function sofGen_AddRefr(functionName, interval){
var cpu_refr_fact = sofGet('soRfr_Fact')
if (typeof(soGen_Refr) != 'object')
soGen_Refr = new Array();
soGen_Refr[functionName] = (isNaN(parseInt(interval)) ? 100 : parseInt(interval));
if (typeof(soGen_RefrTmr) == 'undefined')
soGen_RefrTmr = window.setInterval('soGen_RefrFunc()', 100);}

function sofGen_DelRefr(functionName){
if (soGen_Refr[functionName]) soGen_Refr[functionName] = 0;}

function soGen_RefrFunc(){
if (typeof(soGen_Refr) != 'object') return;
for (var functionName in soGen_Refr) {
var functionInterval = String(soGen_Refr[functionName]).split(';');
var interval = parseInt(functionInterval[0]);
var nextInterval = (functionInterval.length > 1 ? parseInt(functionInterval[1]) : 0);
if ((interval != 0) && (nextInterval <= 100))
eval(functionName + '()');
if (interval != 0)
soGen_Refr[functionName] = interval + ';' + (nextInterval <= 100 ? interval : nextInterval - 100);}}

//### SYSTEM ###

function xpath(path){
var xpathR = document.evaluate(path,document,null,XPathResult.FIRST_ORDERED_NODE_TYPE,null);
return xpathR.singleNodeValue;}

function xpaths(path){
var xpathsR = document.evaluate(path,document,null,XPathResult.UNORDERED_NODE_SNAPSHOT_TYPE,null);
return xpathsR;}

function AddCommas(number){
var number = String(parseInt(number));
return number.replace(/(\d{1,2}(\.\d{3})*)(?=(\d{3})+$)/g, '$1.');}

function DelCommas(number){
var number = parseInt(number.replace(/(\.|\s)/g, ''));
return (isNaN(number) ? 0 : number);}

function makeEvent(func){
document.addEventListener('DOMContentLoaded', func, false);}

function parseDate(oDate, format, local){
var defaultFormat = sofGet('soGen_TimeF');
if ((typeof(format) == 'undefined') || (format == ''))
var format = defaultFormat;
if (typeof(oDate.getSeconds()) == 'undefined')
return '';
if ((sofGet('soGen_TimeDiff')) != '' && (local != 1)) oDate = new Date(oDate.getTime() + (parseInt(sofGet('soGen_TimeDiff')*3600*1000)));
var today = new Date();
var yearf = String(oDate.getFullYear());
var years = yearf.substr(yearf.length - 2, 2);
var month = (oDate.getMonth() < 9 ? '0' : '') + (oDate.getMonth() + 1);
var day = (oDate.getDate() <= 9 ? '0' : '') + oDate.getDate();
var hours = (oDate.getHours() <= 9 ? '0' : '') + oDate.getHours();
var minutes = (oDate.getMinutes() <= 9 ? '0' : '') + oDate.getMinutes();
var seconds = (oDate.getSeconds() <= 9 ? '0' : '') + oDate.getSeconds();
format = format.replace(/(^|[^%])%(h|m)(\+|-)[0-9]+(.*?)/i, '$1$4');
format = format.replace(/(^|[^%])%\((.*?)\)/g, (oDate.getDate() == today.getDate() ? '$1' : '$1$2'));
format = format.replace(/(^|[^%])%Y/g, '$1' + yearf).replace(/(^|[^%])%y/g, '$1' + years);
format = format.replace(/(^|[^%])%m/gi, '$1' + month).replace(/(^|[^%])%d/gi, '$1' + day);
format = format.replace(/(^|[^%])%h/gi, '$1' + hours).replace(/(^|[^%])%n/gi, '$1' + minutes);
format = format.replace(/(^|[^%])%s/gi, '$1' + seconds).replace(/%%/g, '%');
return format;}

function sofSys_GetRes(){
var Metal = parseInt(xpath('//td[@align="center"][@class="header"][@width="90"][1]/font').innerHTML.replace(/\./g, ''));
var Crystal = parseInt(xpath('//td[@align="center"][@class="header"][@width="90"][2]/font').innerHTML.replace(/\./g, ''));
var Deuterium = parseInt(xpath('//td[@align="center"][@class="header"][@width="90"][3]/font').innerHTML.replace(/\./g, ''));
var Energy = xpath('//td[@align="center"][@class="header"][@width="90"][5]').innerHTML.split('/');
var Energy2 = parseInt(Energy[0].replace(/<.*?>/g, '').replace(/\./g, ''));
var Energy1 = parseInt(Energy[2].replace(/\./g, ''));
var Result = Metal + ';' + Crystal + ';' + Deuterium + ';' + Energy1 + ';' + Energy2;
return ((Result != 'undefined') ? Result.split(';') : -1);}

function sofSys_ResN(){
var mn = (Page == 'galaxy' ? sofGet('metal') : xpath('//td[@align="center"][@class="header"][@width="85"][1]/i/b/font').innerHTML);
var cn = (Page == 'galaxy' ? sofGet('kristal') : xpath('//td[@align="center"][@class="header"][@width="85"][2]/i/b/font').innerHTML);
var dn = (Page == 'galaxy' ? sofGet('deut') : xpath('//td[@align="center"][@class="header"][@width="85"][3]/i/b/font').innerHTML);
var en = (Page == 'galaxy' ? null : xpath('//td[@align="center"][@class="header"][@width="85"][5]/i/b/font').innerHTML);
if(Page != 'galaxy'){sofSet('metal',mn);sofSet('kristal',cn);sofSet('deut',dn);}
var Result = mn+';'+cn+';'+dn+';'+en;
return Result.split(';');}

function sofSys_GetSkinURL(){
if (typeof(soGen_SkinURL) != 'undefined') return soGen_SkinURL;
var skin = xpath('//head/link[4]');
soGen_SkinURL = skin.getAttribute('href').substr(0, skin.getAttribute('href').lastIndexOf('/'));
return soGen_SkinURL;}

function sofSys_GetTime(){
var thTime = xpath('//body/div[starts-with(@id,"content")]/center/table[@width="519"]/tbody/tr/th[@colspan="3"][1]');
// ### TODO
var d = new Date();
var godina = d.getFullYear();
var brojka = thTime.innerHTML.split(' ');
var niz = brojka[1]+' '+brojka[2]+', '+godina+' '+brojka[3];
var ServerTime = new Date(niz);
// ###
var LocalTime  = new Date();
var ServerSeconds = (ServerTime - LocalTime);
sofSet('soSet_ServSecs',ServerSeconds);
soSet_ServSecs = ServerSeconds;}

function sofSys_ServSecs(){
if (typeof(soSet_ServSecs) == 'undefined'){
soSet_ServSecs = sofGet('soSet_ServSecs');}
return parseInt(((isNaN(soSet_ServSecs)) || (soSet_ServSecs == '') ? 0 : soSet_ServSecs));}

function sofSys_PlntCrds(){
var planetsList = new Array();
var options = xpath('//select[starts-with(@onchange,"haha(this)")]');
for (var i = 0, option = ''; option = options[i]; i++){
var planetID = option.value.match(/cp=(.*?)(?=&|$)/i) ? RegExp.$1 : '';
var planetName   = option.text;
var planetSelect = option.selected ? 1 : 0;
planetsList.push(planetID + '==' + planetName + '==' + planetSelect);}
planet_sets = planetsList.join('||');
sofSet('soSet_Planets',planet_sets);}

function sofUpd_PlntCrds(cPID){
var planets = sofGet('soSet_Planets');
if (planets == '') return;
var newPlanetsList = new Array();
var planetsList = planets.split('||');
for (var i = 0, planet = ''; planet = planetsList[i]; i++) {
planet = planet.split('==');
planetID = planet[0];
planetName = planet[1];
planetSelect = (planetID == cPID ? 1 : 0);
newPlanetsList.push(planetID + '==' + planetName + '==' + planetSelect);
new_planet_sets = newPlanetsList.join('||');}
sofSet('soSet_Planets',new_planet_sets);}

function sofSys_toggleDisp(id){
var elmnt = document.getElementById(id);
if(elmnt.style.display == 'none'){elmnt.style.display = 'block';}
else{elmnt.style.display = 'none';}}

function sofSys_AMI(mlnkn, mlnkh, mlnkc, mlnkt){
var tble = xpath('//table[@width="110"]/tbody/tr[@id="soLnks"]');
mtr = document.createElement('tr');
mtr.innerHTML = '<td align="center"><a href="'+mlnkh+'" title="'+mlnkn+'" target="'+mlnkt+'"><span style="color:'+mlnkc+'">'+mlnkn+'</span></a></td>';
tble.parentNode.insertBefore(mtr,tble.nextSibling);}

function soSys_Checker(){
document.getElementById('soUpd1').innerHTML = i18n('Checking',41)+'...';
var updscript = document.createElement('script');
updscript.type = 'text/javascript';
updscript.src = 'http://superogame.sourceforge.net/superogame/update.js';
document.body.appendChild(updscript);}

function sofSys_Menu(){
var sets = xpath('//tr/td/div/font/a[contains(@href,"page=options")]');
var sosets = document.createElement('tr');
sosets.innerHTML = '<td><div align="center"><a href="index.php?page=options&session='+session+'&so=setts" style="color:#21A3E0;" title="SuperOgame">SO '+sets.innerHTML+'</a></div></td>';
sets.parentNode.parentNode.parentNode.parentNode.parentNode.insertBefore(sosets,sets.parentNode.parentNode.parentNode.parentNode.nextSibling);}

function sofSys_Sys(){
tbl = xpath('//div[@id="content"]/center/table[@width="519"]');
var ntbl = document.createElement('table');
ntbl.setAttribute('width','519');
var lang = sofGen_GetLang();
var langs = Array('UA','TW','TR','SR','SK','SI','SE','RU','RO','PT','PL','NO','NL','LV','LT','KR','JP','IT','HU','GR','FR','FI','ES','DK','DE','CZ','CN','BG','BA','EN');
var opts;
for (var i = langs.length-1; i >= 0; i--){var option = '<option value="'+langs[i]+'">'+soLan_LngList[langs[i]]+'</option>';
if(typeof(soLan_LngList[langs[i]]) == 'undefined') option = '';opts += option;}
ntbl.innerHTML = '<tr><td class="c" colspan="2">Opera</td></tr><tr><th>'+i18n('Version',34)+'</th><th>'+(opera.version() >= 10.50 ? '<span style="color:lime;font-weight:bold">'+opera.version()+'</span>' : '<span style="color:red;font-weight:bold;cursor:help" title="This version of SuperOgame requires Opera 10.50 and newer">'+opera.version()+'</span>')+'</b></th></tr><tr><td class="c" colspan="2">SuperOgame</td></tr><tr><th width="30%">'+i18n('Status',27)+'</th><th>'+(sofGet('soSet_OnOff') > 0 ? '<a style="cursor:hand;color:lime" title="'+i18n('Deactivate',33)+' SuperOgame" onclick="sofSet(\'soSet_OnOff\',0);location.reload()"><b>'+i18n('Activated',28)+'</b></a>' : '<a style="cursor:hand;color:red" title="'+i18n('Activate',32)+' SuperOgame" onclick="sofSet(\'soSet_OnOff\',1);location.reload()"><b>'+i18n('Deactivated',29)+'</b></a>')+'</th></tr><tr><th rowspan="2">'+i18n('Version',34)+'</th><th><span style="font-weight:bold;">'+soVer_Curr+'</span></th></tr><tr><th><span id="soUpd1" style="font-weight:bold;color:yellow"><a href="javascript: soSys_Checker();" style="color:yellow">'+i18n('Check for updates',40)+'</a></span><span id="soUpd2" style="display:none;font-weight:bold;"><a onclick="sofSys_toggleDisp(\'vinfo\')" title="+/-" target="blank" style="color:red;cursor:pointer">'+i18n('New version available',43)+': <span id="soNV"></span></a><ul id="vinfo" style="text-align:left;display:none"><li><a id="updlnk0" href="#" target="_blank">'+i18n('What\'s new',47)+'</a></li><li><a id="updlnk1" href="#" target="_blank">'+i18n('Visit download page',48)+'</a></li></ul></span><span id="soUpd3" style="display:none;font-weight:bold;color:lime">'+i18n('You are using the latest version!',42)+'</span></th></tr><tr><th>'+i18n('Links',31)+'</th><th style="text-align:left;"><ul><li><a href="http://superogame.sourceforge.net" target="_blank">'+i18n('Website',35)+'</a></li><li><a href="http://sourceforge.net/projects/superogame/forums" target="_blank">'+xpath('//tr/td/div/font/a[contains(@href,"board")][@target]').innerHTML+'</a></li><li><a href="http://sourceforge.net/projects/superogame/support" target="_blank">'+xpath('//tr/td/div/font/a[contains(@href,"tutorial")][@target]').innerHTML+'</a></li></ul></th></tr><tr><th>'+i18n('Language',30)+'</th><th><select onchange="sofSet(\'soLan_Ovr\',this.options[this.selectedIndex].value);location.reload()"><option value="" style="color:orange">'+(typeof(soLan_LngList[lang[1]]) == 'undefined' ? 'English' : soLan_LngList[lang[1]])+'</option><option value="">Default</option>'+opts+'</select></th></tr>';
tbl.parentNode.insertBefore(ntbl,tbl);
(sofGet('soGen_VerCh') ? soSys_Checker() : '');}

function addOption(typ,set,txt,set2,set3){
var line;
switch (typ){
case 'cb':
line = '<tr><th style="text-align:left;">'+txt+'</th><th><input type="checkbox" '+(sofGet(set) > 0 ? 'checked="checked"' : '')+'" onchange="sofSet(\''+set+'\',(this.checked ? 1 : 0));" /></th></tr>';break;
case 'cb2':
line = '<tr><th style="text-align:left;">'+txt+'</th><th><input type="checkbox" '+(sofGet(set) > 0 ? 'checked="checked"' : '')+'" onchange="sofSet(\''+set+'\',(this.checked ? 1 : 0));'+(set2 || set3 ? 'sofSys_toggleDisp(\''+set+'\');' : '')+'" /><div id="'+set+'" style="display:'+(sofGet(set) > 0 && (set2 || set3) ? 'block' : 'none')+'">'+(set2 ? '<input style="text-align:center;color:'+sofGet(set2)+'" value="'+sofGet(set2)+'" size="19" maxlength="20" onchange="sofSet(\''+set2+'\',this.value);this.style.color = this.value;" />' : '')+(set3 ? '<input style="text-align:center" value="'+sofGet(set3)+'" size="19" maxlength="20" onchange="sofSet(\''+set3+'\',this.value)" />' : '')+'</div></th></tr>';break;
case 'color':
line = '<tr><th style="text-align:left">'+txt+'</th><th><input style="text-align:center;color:'+sofGet(set)+'" value="'+sofGet(set)+'" size="19" maxlength="20" onchange="sofSet(\''+set+'\',this.value);this.style.color = this.value;" /></th></tr>';break;
case 'txts':
line = '<tr><th style="text-align:left">'+txt+'</th><th><input style="text-align:center" value="'+sofGet(set)+'" maxlength="3" size="4" onchange="sofSet(\''+set+'\',this.value)" /></th></tr>';break;}
return line;}

function sofSys_Opt(){
tbl = xpath('//div[@id="content"]/center/table[@width="519"]');
var ststbl = document.createElement('table');
ststbl.setAttribute('width','519');
ststbl.innerHTML = '<tr><td class="c" colspan="2" style="font-weight:bold">SuperOgame '+xpath('//tr/td/div/font/a[contains(@href,"page=options")]').innerHTML+'</td></tr><tr><td colspan="2" class="c" style="text-align:center">General</td></tr>'
+'<tr><th style="text-align:left" width=70%>'+i18n('Global time format',101)+'</th><th><input style="text-align:center" value="'+sofGet('soGen_TimeF')+'" size="19" maxlength="20" onchange="sofSet(\'soGen_TimeF\',this.value)" /></th></tr>'
+addOption('txts','soGen_TimeDiff',i18n('Time Zone',102))
+addOption('color','soGen_notEnC',i18n('Not enough resources color',103))
+addOption('cb2','soGen_Clock',i18n('Display server time clock above left menu',104),'soGen_ClockC','soGen_ClockF')
+addOption('cb','soGen_ClockI',i18n('Switch local/server time for clocks',105))
+addOption('cb2','soGen_Moons',i18n('Highlight moons in planets list',106),'soGen_MoonsC')
+addOption('cb','soGen_ChgButts',i18n('Add planet change buttons',108))
+addOption('cb','soGen_JumpGate',i18n('Display Jump Gate shortcut in left menu',108))
+addOption('cb','soGen_MLst',i18n('Display Member List shortcut in left menu',109))
+'<tr><th style="text-align:left">'+i18n('Member list link sort order',110)+'</th><th><select style="text-align:center" onchange="sofSet(\'soGen_MLstSort1\',this.options[this.selectedIndex].value)"><option '+(sofGet('soGen_MLstSort1') == 0 ? 'selected="selected" style="color:orange"' : '')+' value="0">Coords</option><option '+(sofGet('soGen_MLstSort1') == 1 ? 'selected="selected" style="color:orange"' : '')+' value="1">Name</option><option '+(sofGet('soGen_MLstSort1') == 2 ? 'selected="selected" style="color:orange"' : '')+' value="2">Rank</option><option '+(sofGet('soGen_MLstSort1') == 3 ? 'selected="selected" style="color:orange"' : '')+' value="3">Points</option><option '+(sofGet('soGen_MLstSort1') == 4 ? 'selected="selected" style="color:orange"' : '')+' value="4">Date</option><option '+(sofGet('soGen_MLstSort1') == 5 ? 'selected="selected" style="color:orange"' : '')+' value="5">Status</option></th></tr>'
+'<tr><th style="text-align:left">'+i18n('Member list link sort (ascending/descending)',113)+'</th><th><input type="radio" id="opt0" name="soGen_MLstSort2" value="0" '+(sofGet('soGen_MLstSort2') == '0' ? 'checked="true"' : '')+' onchange="sofSet(\'soGen_MLstSort2\',this.value)" /><label for="opt0" style="cursor:pointer"> ⇧</label> <input type="radio" id="opt1" name="soGen_MLstSort2" value="1" '+(sofGet('soGen_MLstSort2') == '1' ? 'checked="true"' : '')+' onchange="sofSet(\'soGen_MLstSort2\',this.value)" /><label for="opt1" style="cursor:pointer"> ⇩</label></th></tr>'
+addOption('cb','soGen_Banned',i18n('Display Banned shortcut in left menu',111))
+addOption('cb','soGen_CircMes',i18n('Display circular message shortcut in left menu',112))
+'<tr><th style="text-align:left;">'+i18n('Enable trade calculator',113)+'</th><th><input type="checkbox" '+(sofGet('soGen_TrdCal') > 0 ? 'checked="checked"' : '')+'" onchange="sofSet(\'soGen_TrdCal\',(this.checked ? 1 : 0));sofSys_toggleDisp(\'soGen_TrdCal\')" /><div id="soGen_TrdCal" style="display:'+(sofGet('soGen_TrdCal') > 0 ? 'block' : 'none')+'">⇧<input style="text-align:center" title="'+i18n('Vertical position (px)',114)+'" value="'+sofGet('soGen_TrdCalVPos')+'" maxlength="3" size="4" onchange="sofSet(\'soGen_TrdCalVPos\',this.value)" />  ⬄<input style="text-align:center" title="'+i18n('Horizontal position (px)',115)+'" value="'+sofGet('soGen_TrdCalHPos')+'" maxlength="3" size="4" onchange="sofSet(\'soGen_TrdCalHPos\',this.value)" /><br /><label for="optl" style="cursor:pointer">⇦ </label><input type="radio" id="optl" name="soGen_TrdCalSwPos" value="L" '+(sofGet('soGen_TrdCalSwPos') == 'L' ? 'checked="true"' : '')+' onchange="sofSet(\'soGen_TrdCalSwPos\',this.value)" />  <input type="radio" id="optr" name="soGen_TrdCalSwPos" value="R" '+(sofGet('soGen_TrdCalSwPos') != 'L' ? 'checked="true"' : '')+' onchange="sofSet(\'soGen_TrdCalSwPos\',this.value)" /><label for="optr" style="cursor:pointer"> ⇨</label></div></th></tr>'
+'<tr><td colspan="2" class="c" style="text-align:center">'+xpath('//tr/td/div/font/a[contains(@href,"page=overview")]').innerHTML+'</td></tr>'
+addOption('cb2','soMen_Clock',i18n('Display local time clock in overview menu',116),'soMen_ClockC')
+addOption('cb','soMen_Fields',i18n('Display original size of a planet or moon',117))
+addOption('cb','soMen_ResOver',i18n('Display moving resources overview',118))
+addOption('cb','soMen_Enh',i18n('Overview menu enhancements',119))
+'<tr><td colspan="2" class="c" style="text-align:center">'+xpath('//tr/td/div/font/a[contains(@href,"page=resources")]').innerHTML+'</td></tr>'
+addOption('cb','soRes_ProdCalc',i18n('Display resources production calculator',120))
+addOption('cb','soRes_StStatus',i18n('Display status of storage capacities',121))
+addOption('cb','soRes_ProdFact',i18n('Display graphical status of the production factor',122))
+addOption('cb','soRes_SatEnerg',i18n('Display solar satelite energy production',123))
+'<tr><td colspan="2" class="c" style="text-align:center">'+xpath('//tr/td/div/font/a[contains(@href,"page=galaxy")]').innerHTML+'</td></tr>'
+addOption('cb','soGal_PlntLst',i18n('Display planets list in the galaxy',124))
+addOption('cb2','soGal_HlMoons',i18n('Highlight moons (set minimum size & color)',125),'soGal_HlMoonsC','soGal_HlMoonsS')
+addOption('cb2','soGal_HlDebr',i18n('Highlight debris (set minimum size & color)',126),'soGal_HlDebrC','soGal_HlDebrS')
+addOption('cb','soGal_DebrRemI',i18n('Display metal and crystal amount instead of debris image',127))
+addOption('cb','soGal_DebrRecN',i18n('Display number of recyclers needed to harvest the debris',128))
+addOption('cb2','soGal_Ranks',i18n('Display rank positions in galaxy view',129),'soGal_RanksC')
+'<tr><th style="text-align:left">'+i18n('Colorize player ranks by number of points',130)+'<div id="prct"style="line-height:1.9;text-align:right;display:'+(sofGet('soGal_PrankC') > 0 ? 'block' : 'none')+'">< 11<br />< 51<br />< 101<br />< 201<br />< 801<br />< 1501<br />> 1501<br />= 0</div></th><th><input type="checkbox" '+(sofGet('soGal_PrankC') > 0 ? 'checked="checked"' : '')+'" onchange="sofSet(\'soGal_PrankC\',(this.checked ? 1 : 0));sofSys_toggleDisp(\'prcif\');sofSys_toggleDisp(\'prct\');" /><div id="prcif" style="display:'+(sofGet('soGal_PrankC') > 0 ? 'block' : 'none')+'"><input style="text-align:center;color:'+sofGet('soGal_PrankC1')+'" value="'+sofGet('soGal_PrankC1')+'" size="15" maxlength="20" onchange="sofSet(\'soGal_PrankC1\',this.value);this.style.color = this.value;" /><br /><input style="text-align:center;color:'+sofGet('soGal_PrankC2')+'" value="'+sofGet('soGal_PrankC2')+'" size="15" maxlength="20" onchange="sofSet(\'soGal_PrankC2\',this.value);this.style.color = this.value;" /><br /><input style="text-align:center;color:'+sofGet('soGal_PrankC3')+'" value="'+sofGet('soGal_PrankC3')+'" size="15" maxlength="20" onchange="sofSet(\'soGal_PrankC3\',this.value);this.style.color = this.value;" /><br /><input style="text-align:center;color:'+sofGet('soGal_PrankC4')+'" value="'+sofGet('soGal_PrankC4')+'" size="15" maxlength="20" onchange="sofSet(\'soGal_PrankC4\',this.value);this.style.color = this.value;" /><br /><input style="text-align:center;color:'+sofGet('soGal_PrankC5')+'" value="'+sofGet('soGal_PrankC5')+'" size="15" maxlength="20" onchange="sofSet(\'soGal_PrankC5\',this.value);this.style.color = this.value;" /><br /><input style="text-align:center;color:'+sofGet('soGal_PrankC6')+'" value="'+sofGet('soGal_PrankC6')+'" size="15" maxlength="20" onchange="sofSet(\'soGal_PrankC6\',this.value);this.style.color = this.value;" /><br /><input style="text-align:center;color:'+sofGet('soGal_PrankC7')+'" value="'+sofGet('soGal_PrankC7')+'" size="15" maxlength="20" onchange="sofSet(\'soGal_PrankC7\',this.value);this.style.color = this.value;" /><br /><input style="text-align:center;color:'+sofGet('soGal_PrankC8')+'" value="'+sofGet('soGal_PrankC8')+'" size="15" maxlength="20" onchange="sofSet(\'soGal_PrankC8\',this.value);this.style.color = this.value;" /></div></th></tr>'
+addOption('cb','soGal_PhalStat',i18n('Display phalanx status indicator in galaxy view',131))
+addOption('cb','soGal_MissAtt',i18n('Missile launcher in galaxy page all missiles link',132))
+addOption('cb','soGal_Compact',i18n('Compact Galaxy view (no images)',133))
+'<tr><td colspan="2" class="c" style="text-align:center">'+xpath('//tr/td/div/font/a[contains(@href,"page=b_building")]').innerHTML+'</td></tr>'
+addOption('cb2','soBld_ResRem',i18n('Display needed resources',134),'soBld_ResRemC')
+addOption('cb2','soBld_CargRem',i18n('Display cargos needed',135),'soBld_CargRemC')
+addOption('cb','soBld_RemDesc',i18n('Remove building descriptions',136))
+addOption('txts','soBld_ResImg',i18n('Resize building images (pixels, -1, 0-150)',137))
+addOption('cb','soBld_ImpMsg',i18n('Display "impossible" message when something can\'t be built',138))
+addOption('cb2','soBld_EndTime',i18n('Display build end time',139),'soBld_EndTimeC','soBld_EndTimeF')
+addOption('cb','soBld_Range',i18n('Display IPM and phalanx range info',140))
+addOption('cb','soBld_Energ',i18n('Display mines and solar powerplant energy status',141))
+addOption('cb','soBld_InfDiff',i18n('Display differences of phalanx reach',142))
+addOption('cb2','soBld_JGRt',i18n('Display Jump Gate recharge time',143),'soBld_JGRtC','soBld_JGRtF')
+addOption('cb','soBld_ResPts',i18n('Display total research points in research page',144))
+'<tr><td colspan="2" class="c" style="text-align:center">'+xpath('//tr/td/div/font/a[contains(@href,"page=flotten1")]').innerHTML+'</td></tr>'
+addOption('cb2','soFlt_Cap',i18n('Display fleet cargo capacity',145),'soFlt_CapC')
+addOption('cb2','soFlt_TransCalc',i18n('Display transport calculator in the Fleets page',146),'soFlt_CargNeedC')
+addOption('cb2','soFlt_ArrTime',i18n('Display arrival time in the Overview and Phalanges',147),'soFlt_ArrTimeC','soFlt_ArrTimeF')
+addOption('cb2','soFlt_CmBckTime',i18n('Display comeback time in the Fleets page',148),'soFlt_CmBckTimeC','soFlt_CmBckTimeF')
+addOption('cb2','soFlt_SndTime',i18n('Display arrival and comeback time when sending a fleet',149),'soFlt_SndTimeC','soFlt_SndTimeF')
+addOption('color','soFlt_ResC',i18n('Color of all resources in the Fleets page',150))
+addOption('cb','soFlt_ExpShrt',i18n('Display a shortcut for Outer Space when sending fleet',151))
+addOption('cb2','soFlt_DefMis',i18n('Define default mission types<br />1-Attack; 2-Attack (ACS); 3-Transport; 4-Deploy; 5-Hold position; 6-Spy; 7-Colonize; 9-Destroy;',152),'','soFlt_DefMisO')
+addOption('cb','soFlt_HoldCons',i18n('Display holding deuterium cost for expedition or holding missions',153))
+addOption('cb','soFlt_Impr',i18n('Display UI enhancements in flotten menus',154))
+'<tr><th style="text-align:left">'+i18n('Position of the fleet speed selector',155)+'</th><th><select style="text-align:center" onchange="sofSet(\'soFlt_SpdSel\',this.options[this.selectedIndex].value)"><option'+(sofGet('soFlt_SpdSel') == 0 ? ' style="color:orange" selected="selected" ' : '')+' value="0">⇨⇦</option><option '+(sofGet('soFlt_SpdSel') == 1 ? 'selected="selected" style="color:orange"' : '')+' value="1">⇦⇦</option><option'+(sofGet('soFlt_SpdSel') == 2 ? ' style="color:orange" selected="selected"' : '')+' value="2">⇨⇨</option><option'+(sofGet('soFlt_SpdSel') == 3 ? ' style="color:orange" selected="selected" ' : '')+' value="3">⇦⇨</option></th></tr>'
+'<tr><th style="text-align:left">'+i18n('Default coordinates',156)+'</th><th><input name="dcgalaxy" style="text-align:center" value="'+sofGet('soFlt_DefCrds1')+'" size="2" maxlength="2" onchange="sofSet(\'soFlt_DefCrds1\',this.value);" />:<input name="dcsystem" style="text-align:center" value="'+sofGet('soFlt_DefCrds2')+'" size="3" maxlength="3" onchange="sofSet(\'soFlt_DefCrds2\',this.value);" />:<input name="dcplanet" style="text-align:center" value="'+sofGet('soFlt_DefCrds3')+'" size="2" maxlength="2" onchange="sofSet(\'soFlt_DefCrds3\',this.value);" /></th></tr>'
+addOption('cb','soFlt_SubmFocus',i18n('Automatically set focus on submit button in fleet pages',157))
+addOption('cb','soFlt_Moons',i18n('Highlight moons in shortcuts list',158))
+'<tr><td colspan="2" class="c" style="text-align:center">'+xpath('//tr/td/div/font/a[contains(@href,"page=messages")]').innerHTML+'</td></tr>'
+'<tr><th style="text-align:left;">'+i18n('Display a second delete form before messages',159)+'</th><th><input type="checkbox" '+(sofGet('soMsg_DelForm') > 0 ? 'checked="checked"' : '')+'" onchange="sofSet(\'soMsg_DelForm\',(this.checked ? 1 : 0));sofSys_toggleDisp(\'soMsg_DelForm\');" /><div id="soMsg_DelForm" style="display:'+(sofGet('soMsg_DelForm') > 0 ? 'block' : 'none')+'">'+i18n('Min. messages')+' # <input style="text-align:center" value="'+sofGet('soMsg_DelFormMM')+'" size="2" maxlength="3" onchange="sofSet(\'soMsg_DelFormMM\',this.value)" /></div></th></tr>'
+addOption('cb','soMsg_AllyReply',i18n('Display reply link in alliance message',160))
+addOption('cb','soMsg_Clear',i18n('Remove the background behind messages',161))
+addOption('cb','soMsg_RepRes',i18n('Display number of needed cargos to raid in spy report',162))
+'<tr><th style="text-align:left">'+i18n('Colorize prefered cargos',163)+'</th><th><select style="text-align:center" onchange="sofSet(\'soMsg_RepResC\',this.options[this.selectedIndex].value)"><option'+(sofGet('soMsg_RepResC') == '0' ? ' style="color:orange" selected="selected" ' : '')+' value="0">0</option><option '+(sofGet('soMsg_RepResC') == '1' ? 'selected="selected" style="color:orange"' : '')+' value="1">'+i18n('LC',12)+'</option><option'+(sofGet('soMsg_RepResC') == '2' ? ' style="color:orange" selected="selected"' : '')+' value="2">'+i18n('SC',26)+'</option></th></tr>'
+addOption('color','soMsg_RepResCC',i18n('Color of the prefered cargo ship',164))
+addOption('cb','soMsg_RepEsp',i18n('Display number of needed espionage probes in spy report',165))
+addOption('cb','soMsg_RepLnk',i18n('Display links to SpeedSim and DragoSim in spy report',166))
+addOption('cb','soMsg_SpyRepC',i18n('Colorization for spy report',167))
+addOption('cb','soMsg_MissRepC',i18n('Colorization for missile attack report',168))
+addOption('color','soMsg_PrivC',i18n('Color of private messages',169))
+addOption('color','soMsg_PrivBgC',i18n('Background color of private messages',170))
+addOption('color','soMsg_AllyC',i18n('Color of alliance messages',171))
+addOption('color','soMsg_AllyBgC',i18n('Background color of alliance messages',172))
+addOption('color','soMsg_ActiveC',i18n('Color of activity messages - spying, missile attacks',173))
+addOption('color','soMsg_ActiveBgC',i18n('Background color of activity messages, etc.',174))
+addOption('color','soMsg_PassiveC',i18n('Color of passive messages - comeback of fleet, resources delivery, etc.',175))
+addOption('color','soMsg_PassiveBgC',i18n('Background color of passive messages',176))
+addOption('color','soMsg_RecC',i18n('Color of recycler messages',177))
+addOption('color','soMsg_RecBgC',i18n('Background color of recycler messages',178))
+addOption('color','soMsg_ExpC',i18n('Color of expedition messages',179))
+addOption('color','soMsg_ExpBgC',i18n('Background color of expedition messages',180))
+addOption('cb','soMsg_Sml',i18n('Enable graphical smileys in messages',181))
+'<tr><th style="text-align:left">'+i18n('Signature for outgoing personal messages',182)+'</th><th><input type="checkbox" '+(sofGet('soMsg_Sgnt') != '' ? 'checked="checked"' : '')+'" onchange="sofSys_toggleDisp(\'sgta\');" /><textarea id="sgta" cols="35" rows="4" onchange="sofSet(\'soMsg_Sgnt\',this.value)" style="display:'+(sofGet('soMsg_Sgnt') != '' ? 'block' : 'none')+'">'+sofGet('soMsg_Sgnt')+'</textarea></th></tr>'
+addOption('cb','soMsg_SgntAll',i18n('Include signature in circular messages, too',183))
+'<tr><td colspan="2" class="c" style="text-align:center">'+xpath('//tr/td/div/font/a[contains(@href,"page=statistics")]').innerHTML+'</td></tr>'
+addOption('cb','soSts_Diff',i18n('Points difference',184))
+'<tr><th colspan="2"><input type="button" value=" ↻ " onclick="location.reload()" /> <input type="button" title="'+i18n('Reset',44)+'" value=" ✘ "'+((typeof(localStorage) != 'undefined') ? ' onclick="localStorage.clear();location.reload()"' : '')+' /></th></tr>';
tbl.parentNode.insertBefore(ststbl,tbl);
tbl.style.display = 'none';}

function sofSys_overlibFix(){window.opera.defineMagicVariable('olOp',function(){return true;},null);}

//### GENERAL ###

function sofGen_Men(){
var tblsoc = xpath('//div[@id="menu"]/p');
tblsoc.innerHTML += '<br /><nobr><span id="GlobalClock" title="'+(sofGet('soGen_ClockI') > 0 ? '' : sofSys_ServSecs()/1000+' [s]')+'" style="color:'+(sofGet('soGen_ClockI') > 0 ? sofGet('soMen_ClockC') : sofGet('soGen_ClockC'))+';font-weight:bold;cursor:help"></span></nobr>';sofUpd_GlobClock();
sofGen_AddRefr('sofUpd_GlobClock',500);}

function sofUpd_GlobClock(){
var globTime = (sofGet('soGen_ClockI') > 0 ? new Date(new Date().getTime()) : new Date(new Date().getTime() + sofSys_ServSecs()));
if (globT = document.getElementById('GlobalClock'))
globT.innerHTML = parseDate(globTime, sofGet('soGen_ClockF'), (sofGet('soGen_ClockI') > 0 ? 1 : 0));}

function sofGen_Gen(){
var fimg = xpath('//div[@id="menu"]/table[@width="110"]/tbody/tr/td/img[contains(@src,"gfx/info-help.jpg")]');
var simg = xpath('//div[@id="menu"]/table[@width="110"]/tbody/tr/td/img[contains(@src,"gfx/user-menu.jpg")]');
if (sofGet('soGen_JumpGate') > 0){
var telp = document.createElement('tr');
telp.innerHTML = '<td><div align="center"><a href="index.php?page=infos&session='+session+'&gid=43">'+i18n('Jump Gate',38)+'</a></div></td>';
fimg.parentNode.parentNode.parentNode.insertBefore(telp,fimg.parentNode.parentNode);}
if (sofGet('soGen_MLst') > 0){
var mlst = document.createElement('tr');
mlst.innerHTML = '<td><div align="center"><a style="color:lime" href="index.php?page=allianzen&session='+session+'&a=4&sort1='+sofGet('soGen_MLstSort1')+'&sort2='+sofGet('soGen_MLstSort2')+'">'+i18n('Member List',16)+'</a></div></td>';
fimg.parentNode.parentNode.parentNode.insertBefore(mlst,fimg.parentNode.parentNode.nextSibling);}
if (sofGet('soGen_Banned') > 0){
var bann = document.createElement('tr');
bann.innerHTML = '<td><div align="center"><a href="index.php?page=pranger&session='+session+'">'+i18n('Banned',21)+'</a></div></td>';
simg.parentNode.parentNode.parentNode.insertBefore(bann,simg.parentNode.parentNode);}
if(sofGet('soGen_CircMes') > 0){
var msgl = xpath('//div[@id="menu"]/table[@width="110"]/tbody/tr/td/div/font/a[@href="#"][contains(@onclick,"fenster")]');
var circ = document.createElement('tr');
circ.innerHTML = '<td><div align="center"><a style="color:lime" href="index.php?page=allianzen&session='+session+'&a=17">'+i18n('Circular Message',4)+'</a></div></td>';
msgl.parentNode.parentNode.parentNode.parentNode.parentNode.insertBefore(circ,msgl.parentNode.parentNode.parentNode.parentNode);}
if ((sofGet('soGen_ChgButts') > 0) && (Page != 'galaxy')){
var slct = xpath('//select[starts-with(@onchange,"haha(this)")]');
if(slct){
var slcti1 = (slct.selectedIndex-1 == -1 ? slct.selectedIndex-1+slct.length : slct.selectedIndex-1);
var slcti2 = (slct.selectedIndex+1 > slct.length-1 ? slct.selectedIndex+1-slct.length : slct.selectedIndex+1);
slct1 = slct.options[slcti1].value.match(/cp=(.*?)(?=&|$)/i) ? RegExp.$1 : '';
slct2 = slct.options[slcti2].value.match(/cp=(.*?)(?=&|$)/i) ? RegExp.$1 : '';
slct0 = this.location.href.replace(/&cp=(.*?)(?=&|$)/i,'')+'&cp=';
var butt = document.createElement('div');
butt.innerHTML = '<center><input type="button" style="cursor:pointer" value="⋘" onclick="document.location.href=\''+slct0+slct1+'\'" />&nbsp;&nbsp;<input type="button" style="cursor:pointer" value="⋙" onclick="document.location.href=\''+slct0+slct2+'\'" /></center>';
slct.parentNode.insertBefore(butt,slct.nextSibling);}}
if (sofGet('soGen_TrdCal') > 0){
var trader = xpath('//div[@id="menu"]/table[@width="110"]/tbody/tr/td/div/font/a[contains(@href,"index.php?page=trader")]');
var trcl = document.createElement('tr');
trcl.innerHTML = '<td><div align="center"><a href="#" title="+/-" style="cursor:pointer" onclick="javascript:sofSys_toggleDisp(\'tradcalc\');"><span style="display:none" id="tradcalcind"><font color=lime>+</font></span>'+i18n('Trade Calculator',50)+'</a></div></td>';
trader.parentNode.parentNode.parentNode.parentNode.parentNode.insertBefore(trcl,trader.parentNode.parentNode.parentNode.parentNode.nextSibling);}
if ((sofGet('soGen_Moons') > 0) && (Page != 'galaxy')){
var opts = xpaths('//select[starts-with(@onchange,"haha(this)")]/option');
for (var i = opts.snapshotLength - 1; i >= 0; i--){
var opt = opts.snapshotItem(i);
if (opt.innerHTML.match(/\(./)){opt.setAttribute('style','background-color:'+sofGet('soGen_MoonsC'));}}}
if (soGen_MenuL.length > 0){
var tbleg = xpath('//div[@id="menu"]/table[@width="110"]/tbody/tr[last()]');
var mtrg = document.createElement('tr');
mtrg.id = 'soLnks';
mtrg.innerHTML = '<td><span align="center" style="color:#21A3E0;font-weight:bold;text-shadow:1 1 black;"><hr></span></td>';
tbleg.parentNode.insertBefore(mtrg,tbleg.nextSibling);
var n = soGen_MenuL.length;
for (var i = n - 1; i >= 1 ; i--){
sofSys_AMI(soGen_MenuL[i][0],soGen_MenuL[i][1],soGen_MenuL[i][2],soGen_MenuL[i][3]);}}}

function sofGen_TrdCal(){
var ResN = sofSys_ResN();
var trt = document.createElement('div');
trt.setAttribute('style','position:absolute;bottom:'+sofGet('soGen_TrdCalVPos')+'px;'+(sofGet('soGen_TrdCalSwPos') == 'L' ? 'left' : 'right')+':'+sofGet('soGen_TrdCalHPos')+'px;z-index:1;display:none');
trt.setAttribute('id','tradcalc');
trt.innerHTML = '<table><tr><td class="c" colspan="6"><span style="float:right;cursor:pointer;" onclick="javascript:sofSys_toggleDisp(\'tradcalc\');" title="-">✖</span>'+i18n('Trade Calculator',51)+'</td></tr><tr><th>'+i18n('Ratio',51)+'</th><th colspan="4"><input style="text-align:center;color:orange;font-weight:bold" id="kurs1" type="text" size="2" value="3" onkeyup="javascript:TCtRc();" />:<input style="text-align:center;color:orange;font-weight:bold" id="kurs2" type="text" size="2" value="2" onkeyup="javascript:TCtRc();" />:<input style="text-align:center;color:orange;font-weight:bold" id="kurs3" type="text" size="2" value="1" onkeyup="javascript:TCtRc();" />&nbsp;&nbsp;('+ResN[0]+':'+ResN[1]+':'+ResN[2]+')</th><th>'+i18n('Cargos',3)+'</th></tr><tr><th>'+i18n('Sell',52)+'</th><th><input style="text-align:center;color:skyblue;font-weight:bold" id="prod0" type="text" size="10" value="0" onkeyup="javascript:TCtRc()" /></th><th><input name="prod" id="prod1" type="radio" onchange="javascript:TCtRc();" />'+ResN[0]+'</th><th><input name="prod" id="prod2" type="radio" onchange="javascript:TCtRc();" />'+ResN[1]+'</th><th><input name="prod" id="prod3" type="radio" onchange="javascript:TCtRc();" checked="true" />'+ResN[2]+'</th><th><span id="mptrans" style="color:skyblue">0</span> <span style="color:skyblue">'+i18n('SC',26)+'</span> '+i18n('or',18)+' <span id="vptrans" style="color:skyblue">0</span> <span style="color:skyblue">'+i18n('LC',12)+'</span></th></tr><tr><th>'+i18n('Buy',53)+'</th><th><input style="text-align:center;color:lime;font-weight:bold" id="kup0" type="text" size="10" value="0" onkeyup="javascript:TCtRc(1)" /></th><th><input name="kup" id="kup1" type="radio" onchange="javascript:TCtRc();" checked="true" />'+ResN[0]+'</th><th><input name="kup" id="kup2" type="radio" onchange="javascript:TCtRc();" />'+ResN[1]+'</th><th><input name="kup" id="kup3" type="radio" onchange="javascript:TCtRc();" />'+ResN[2]+'</th><th><span id="mktrans" style="color:lime">0</span><span style="color:lime"> '+i18n('SC',26)+'</span> '+i18n('or',18)+' <span id="vktrans" style="color:lime">0</span><span style="color:lime"> '+i18n('LC',12)+'</span></th></tr></table>';
document.body.appendChild(trt);}

function TCtRc(ob){
var prod0v = document.getElementById((ob ? 'kup0' : 'prod0')).value;
var tpres = (isNaN(parseInt(DelCommas(prod0v))) ? 0 : DelCommas(prod0v));
for (var i=3;i>0;i--){if (document.getElementById('prod'+i).checked){var resp=i;}}
for (var j=3;j>0;j--){if (document.getElementById('kup'+j).checked){var resk=j;}}
for (var k=3;k>0;k--){if (resp==k){var rpt = document.getElementById('kurs'+k).value}}
for (var l=3;l>0;l--){if (isNaN(parseInt(document.getElementById('kurs'+l).value))){document.getElementById('kurs'+l).value=l;}
if (resk==l){var rkt = parseInt(document.getElementById('kurs'+l).value);}}
var tkres = (ob ? AddCommas(tpres*(rpt/rkt)) : AddCommas(tpres*(rkt/rpt)));
document.getElementById((ob ? 'prod0' : 'kup0')).value = tkres;
document.getElementById((ob ? 'mktrans' : 'mptrans')).innerHTML = Math.floor((tpres+4999)/5000);
document.getElementById((ob ? 'vktrans' : 'vptrans')).innerHTML = Math.floor((tpres+24999)/25000);
document.getElementById((ob ? 'mptrans' : 'mktrans')).innerHTML = Math.floor((DelCommas(tkres)+4999)/5000);
document.getElementById((ob ? 'vptrans' : 'vktrans')).innerHTML = Math.floor((DelCommas(tkres)+24999)/25000);}

//### OVERVIEW ###

function sofMen_Clock(){
var th = xpath('//body/div[starts-with(@id,"content")]/center/table[@width="519"]/tbody/tr/th[@colspan="3"][1]');
(sofGet('soGen_ClockI') > 0 ? '' : th.previousSibling.previousSibling.innerHTML = i18n('Local Time',13));
th.innerHTML = '<span id="OverviewClock" style="color:'+(sofGet('soGen_ClockI') > 0 ? sofGet('soGen_ClockC') : sofGet('soMen_ClockC'))+';font-weight:bold" title="'+(sofGet('soGen_ClockI') > 0 ? sofSys_ServSecs()/1000+' [s]' : '')+'"></span>';
var tr = th.parentNode;
sofGen_AddRefr('sofUpd_MenuClock',500);}

function sofUpd_MenuClock(){
var ovrvmTime = (sofGet('soGen_ClockI') > 0 ? new Date(new Date().getTime() + sofSys_ServSecs()) : new Date(new Date().getTime()));
if (ovrvmT = document.getElementById('OverviewClock'))
ovrvmT.innerHTML = parseDate(ovrvmTime, sofGet('soGen_ClockF'), (sofGet('soGen_ClockI') > 0 ? 0 : 1));}

function sofMen_Fields(){
var astat = xpath('//body/div[starts-with(@id,"content")]/center/table/tbody/tr/th[@colspan="3"]/a[contains(@href,"index.php?page=statistics")]');
var th = astat.parentNode.parentNode.previousSibling.previousSibling.previousSibling.previousSibling.previousSibling.previousSibling.childNodes[2];
var fields = th.innerHTML.split('(');
var size = parseInt(fields[0].match(/[0-9]+/g).join(''));
var rlsz = fields[1].match(/[0-9]+/g);
var fieldsize = Math.ceil(size*size/1000000-1);
(fieldsize == rlsz[1] ? '' : th.innerHTML += ' ['+fieldsize+']')}

function sofMen_ResOver(){
var as = xpaths('//table[@width="519"]/tbody/tr/th/span/a[contains(@onmouseover,"return overlib")][2]');
if (as.snapshotLength == 0) return;
var ResN = sofSys_ResN();
var metal1 = crystal1 = deuter1 = metal2 = crystal2 = deuter2 = metal3 = crystal3 = deuter3 = metal4 = crystal4 = deuter4 = metal5 = crystal5 = deuter5 = metal6 = crystal6 = deuter6 = 0;
for (var i = as.snapshotLength - 1; i >= 0; i--){
var a = as.snapshotItem(i);
var tmpa = a.getAttribute('onmouseover').replace(/[.]/g, '').match(/\d+/g);
var acl = a.getAttribute('class');
var metal = parseInt(tmpa[0]);
var crystal = parseInt(tmpa[1]);
var deuter = parseInt(tmpa[2]);
switch (acl){
case 'ownattack':
metal1 += metal; crystal1 += crystal; deuter1 += deuter;
var t1 = a.innerHTML; break;
case 'ownfederation':
metal2 += metal; crystal2 += crystal; deuter2 += deuter;
var t2 = a.innerHTML; break;
case 'owntransport':
metal3 += metal; crystal3 += crystal; deuter3 += deuter;
var t3 = a.innerHTML; break;
case 'owndeploy':
metal4 += metal; crystal4 += crystal; deuter4 += deuter;
var t4 = a.innerHTML; break;
case 'ownharvest':
metal5 += metal; crystal5 += crystal; deuter5 += deuter;
var t5 = a.innerHTML; break;
case 'owncolony':
metal6 += metal; crystal6 += crystal; deuter6 += deuter;
var t6 = a.innerHTML; }}
var totmet = metal1+metal2+metal3+metal4+metal5+metal6;
var totcrys = crystal1+crystal2+crystal3+crystal4+crystal5+crystal6;
var totdeut = deuter1+deuter2+deuter3+deuter4+deuter5+deuter6;
var newt = xpath('//table[@width="519"]');
var newtb = document.createElement('table');
newtb.setAttribute('width','519');
newtb.setAttribute('id','resOverview');
newtb.setAttribute('style','text-align:center;'+((totmet+totcrys+totdeut) == 0 ? 'display:none' : ''));
newtb.innerHTML = '<tr><th colspan="4" style="color:lime;text-align:left">'+xpath('//tr/td/div/font/a[contains(@href,"page=resources")]').innerHTML+'</th></tr><tr><td class="c">'+i18n('Missions',17)+'</td><td class="c">'+ResN[0]+'</td><td class="c">'+ResN[1]+'</td><td class="c">'+ResN[2]+'</td></tr>'+((metal1+crystal1+deuter1) != 0 ? '<tr><th>'+t1+'</th><th>' + AddCommas(metal1) + '</th><th>' + AddCommas(crystal1) + '</th><th>' + AddCommas(deuter1) + '</th></tr>' : '')+((metal2+crystal2+deuter2) != 0 ? '<tr><th>'+t2+'</th><th>' + AddCommas(metal2) + '</th><th>' + AddCommas(crystal2) + '</th><th>' + AddCommas(deuter2) + '</th></tr>' : '')+((metal3+crystal3+deuter3) != 0 ? '<tr><th>'+t3+'</th><th>' + AddCommas(metal3) + '</th><th>' + AddCommas(crystal3) + '</th><th>' + AddCommas(deuter3) + '</th></tr>' : '')+((metal4+crystal4+deuter4) != 0 ? '<tr><th>'+t4+'</th><th>' + AddCommas(metal4) + '</th><th>' + AddCommas(crystal4) + '</th><th>' + AddCommas(deuter4) + '</th></tr>' : '')+((metal5+crystal5+deuter5) != 0 ? '<tr><th>'+t5+'</th><th>' + AddCommas(metal5) + '</th><th>' + AddCommas(crystal5) + '</th><th>' + AddCommas(deuter5) + '</th></tr>' : '')+((metal6+crystal6+deuter6) != 0 ? '<tr><th>'+t6+'</th><th>' + AddCommas(metal6) + '</th><th>' + AddCommas(crystal6) + '</th><th>' + AddCommas(deuter6) + '</th></tr>' : '')+'<tr><th style="color:lime">'+i18n('Total',39)+'</th><th style="color:lime">' + AddCommas(totmet) + '</th><th style="color:lime">' + AddCommas(totcrys) + '</th><th style="color:lime">' + AddCommas(totdeut) + '</th></tr></table>';
newt.parentNode.insertBefore(newtb, newt.nextSibling);}

function sofMen_Enh(){
var img = xpath('//div[@id="content"]/center/table[@width="519"]/tbody/tr/th/img[@width="200"][@height="200"]');
img.setAttribute('width','150');
img.setAttribute('height','150');
var as = xpaths('//th[@class="s"]/table[@class="s"]/tbody/tr/th/a[@title]');
var opts = xpaths('//select[starts-with(@onchange,"haha(this)")]/option');
var planete = [];
for (var i = 0; i < as.snapshotLength; i++){
var a = as.snapshotItem(i);
var titl = a.getAttribute('title').match(/\[(\d*):(\d*):(\d*)\]/);
var koord = titl[0].replace('[','').replace(']','');
var work = a.parentNode.getElementsByTagName('center')[0].innerHTML.replace(/<.*?>/g, '');
var nme = a.parentNode.innerHTML.replace(/<.*?>/g, '').replace(work, '');
var lnk = a.href.match(/cp=(.*?)(?=&|$)/i)[1];
var img = a.firstChild.src;
planete[koord] = titl[0];planete[koord] += ':|:'+titl[1];planete[koord] += ':|:'+titl[2];planete[koord] += ':|:'+work;planete[koord] += ':|:'+nme;planete[koord] += ':|:'+lnk;planete[koord] += ':|:'+img;}
for (var j = opts.snapshotLength - 1; j >= 0; j--){
var opt = opts.snapshotItem(j);
var coordm = opt.text.match(/\[(\d*):(\d*):(\d*)\]/);
var koordm = coordm[0].replace('[','').replace(']','');
if (opt.innerHTML.match(/\(./)){
var moonID = opt.value.match(/cp=(.*?)(?=&|$)/i) ? RegExp.$1 : '';
var moonnme = opt.text.split(' [');}
else{var moonID = 'undefined';
var moonnme = 'undefined';}
planete[koordm] += ':|:'+moonID;
planete[koordm] += ':|:'+moonnme[0];}
var ntt = xpath('//th[@class="s"]/table[@class="s"]');
var nt = ntt.parentNode;
ntt.style.display = 'none';
var newo = '';
var planet = [];
for (var p in planete){
planet[p] = planete[p].split(':|:');
if (planet[p][0] != 'undefined'){
newo += '<tr><th rowspan="2"><a href="index.php?page=overview&session='+session+'&cp='+planet[p][5]+'" title="'+planet[p][4]+'"><img src="'+planet[p][6]+'" style="width:30px;height:30px" /></a></th><th rowspan="2">'+(planet[p][7] != 'undefined' ? '<a href="index.php?page=overview&session='+session+'&cp='+planet[p][7]+'" title="'+planet[p][8]+'"><img src="'+sofSys_GetSkinURL()+'/planeten/small/s_mond.jpg" style="width:30px;height:30px" /></a>' : ' - ')+'</th><th><span style="float:left">'+planet[p][4]+'&nbsp;</span><a href="/game/index.php?page=galaxy&session='+session+'&galaxy='+planet[p][1]+'&system='+planet[p][2]+'">'+planet[p][0]+'</a></th></tr><tr><th><a href="/game/index.php?page=b_building&session='+session+'&cp='+planet[p][5]+'">'+ planet[p][3]+'</a></th></tr>';}}
nt.innerHTML = '<table>'+newo+'</table>';}

//### RESOURCES ###

function calcProd_plus(id){
document.getElementById(id).value ++;
calcProd();}

function calcProd(){
var Resources = sofSys_GetRes();
var p_th = xpath('//table[@width="550"]/tbody/tr/th[@colspan=6]');
var p_tr = p_th.parentNode.nextSibling.nextSibling.childNodes;
var days = document.getElementById('pDays').value;
var hours = document.getElementById('pHours').value;
var toth = parseInt(days)*24+parseInt(hours);
var pMetal = toth*DelCommas(p_tr[3].lastChild.innerHTML);
var pKristal = toth*DelCommas(p_tr[5].lastChild.innerHTML);
var pDeut = toth*DelCommas(p_tr[7].lastChild.innerHTML);
var pUkup = pMetal+pKristal+pDeut;
var uMetal = pMetal+parseInt(Resources[0]);
var uKristal = pKristal+parseInt(Resources[1]);
var uDeut = pDeut+parseInt(Resources[2]);
var uUkup = uMetal+uKristal+uDeut;
document.getElementById('pMetl').innerHTML = AddCommas(pMetal);
document.getElementById('pKrist').innerHTML = AddCommas(pKristal);
document.getElementById('pDeut').innerHTML = AddCommas(pDeut);
document.getElementById('pTot').innerHTML = AddCommas(pUkup);
document.getElementById('pSc').innerHTML = Math.ceil(pUkup/5000);
document.getElementById('pLc').innerHTML = Math.ceil(pUkup/25000);
document.getElementById('uMetl').innerHTML = AddCommas(uMetal);
document.getElementById('uKrist').innerHTML = AddCommas(uKristal);
document.getElementById('uDeut').innerHTML = AddCommas(uDeut);
document.getElementById('uTot').innerHTML = AddCommas(uMetal+uKristal+uDeut);
document.getElementById('uSc').innerHTML = Math.ceil(uUkup/5000);
document.getElementById('uLc').innerHTML = Math.ceil(uUkup/25000);}

function sofRes_Gen(){
var Resources = sofSys_GetRes();
var sMetal = parseInt(Resources[0]);
var sKristal = parseInt(Resources[1]);
var sDeut = parseInt(Resources[2]);
var sUkupno = sMetal+sKristal+sDeut;
if (sofGet('soRes_ProdCalc') > 0){
var p_tb = xpath('//table[@width="550"]');
var op = xpath('//table[@width="550"]/tbody/tr[3]/td[@class="k"][1]');
if (op.innerHTML != 0){
var ResN = sofSys_ResN();
var p_ntb = document.createElement('table');
p_ntb.setAttribute('width','550');
p_ntb.innerHTML = '<tr><td class="c" colspan="7">'+xpath('//table[@width="550"]/tbody/tr[1]/td[@class="c"][@colspan="6"]').innerHTML+'</td></tr><tr><th width="36%">'+i18n('Calculate production per',2)+':</th><th width="13%">'+ResN[0]+'</th><th width="13%">'+ResN[1]+'</th><th width="13%">'+ResN[2]+'</th><th>'+i18n('Total',39)+'</th><th title="'+i18n('Cargos',3)+'">'+i18n('SC',26)+'</th><th title="'+i18n('Cargos',3)+'">'+i18n('LC',12)+'</th></tr><tr><th><input style="float:right" type="button" value="✖" title="'+i18n('Reset',44)+'" onclick="document.getElementById(\'pDays\').value = \'0\';document.getElementById(\'pHours\').value = \'0\';calcProd()" /> <input style="text-align:center" size="3" maxlength="4" id="pDays" value="0" onkeyup="calcProd()" /> <a title="+1" href="javascript:calcProd_plus(\'pDays\')">'+ i18n('day(s)',6)+'</a>&nbsp;&nbsp;&nbsp;<input style="text-align:center" size="3" maxlength="4" id="pHours" value="0" onkeyup="calcProd()" /> <a title="+1" href="javascript:calcProd_plus(\'pHours\')">'+ i18n('hour(s)',9)+'</a></th><td class="k" style="color:#00ff00" id="pMetl">0</td><td class="k" style="color:#00ff00" id="pKrist">0</td><td class="k" style="color:#00ff00" id="pDeut">0</td><td class="k" style="color:'+sofGet('soFlt_ResC')+'" id="pTot">0</td><td class="k" id="pSc">0</td><td class="k" id="pLc">0</td></tr><tr><th>'+i18n('Total',39)+':</th><td class="k" style="color:#00ff00" id="uMetl">'+AddCommas(sMetal)+'</td><td class="k" style="color:#00ff00" id="uKrist">'+AddCommas(sKristal)+'</td><td class="k" style="color:#00ff00" id="uDeut">'+AddCommas(sDeut)+'</td><td class="k" style="color:'+sofGet('soFlt_ResC')+'" id="uTot">'+AddCommas(sUkupno)+'</td><td class="k" id="uSc">'+Math.ceil(sUkupno/5000)+'</td><td class="k" id="uLc">'+Math.ceil(sUkupno/25000)+'</td></tr>'
p_tb.parentNode.insertBefore(p_ntb, p_tb.nextSibling);}}
if (sofGet('soRes_StStatus') > 0){
var st_th = xpath('//table[@width="550"]/tbody/tr/th[@colspan=6]');
var st_tr = st_th.parentNode.previousSibling.previousSibling;
var stMetal = DelCommas(st_tr.childNodes[3].lastChild.innerHTML);
var stKristal = DelCommas(st_tr.childNodes[5].lastChild.innerHTML);
var stDeut = DelCommas(st_tr.childNodes[7].lastChild.innerHTML);
var strMetal = Math.round((parseInt(Resources[0]))/(stMetal*10));
var strKristal = Math.round((parseInt(Resources[1]))/(stKristal*10));
var strDeut = Math.round((parseInt(Resources[2]))/(stDeut*10));
stMColr = stKColr = stDColr = 'lime';
if (strMetal > 90 && strMetal < 100){stMColr = 'orange';}
else if (strMetal >= 100){stMColr = 'red';}
if (strKristal > 90 && strKristal < 100){stKColr = 'orange';}
else if (strKristal >= 100){stKColr = 'red';}
if (strDeut > 90 && strDeut < 100){stDColr = 'orange';}
else if (strDeut >= 100){stDColr = 'red';}
var st_ntr = document.createElement('tr');
st_ntr.innerHTML = '<th colspan="2">'+i18n('Storage Status',36)+'</th><td class="k"><font color="'+stMColr+'">'+strMetal+'%</font></td><td class="k"><font color="'+stKColr+'">'+strKristal+'%</font></td><td class="k"><font color="'+stDColr+'">'+strDeut+'%</font></td><td class="k"><font color="#00ff00">-</font></td>'
st_tr.parentNode.insertBefore(st_ntr, st_tr.nextSibling);}
if (sofGet('soRes_ProdFact') > 0){
var faktp = xpath('//div[@id="content"]/center/center/br[2]');
var faktt = faktp.nextSibling.nodeValue;
faktor = faktt.split(':');
faktor = parseFloat(faktor[1])*100;
var graf = '<table width="550"><tr><th width="200" align="center">'+faktt+'</th><th align="center"><div style="text-align:left;border:1px solid white;width:350px;"><div id="faktr" style="background-color:'+(faktor < 100 ? '#C00000' : '#00C000' )+';text-align:center;color:white">'+faktor+'%</div></div></th></tr></table><br />'
var content = xpath('//div[@id="content"]');
content.innerHTML = content.innerHTML.replace(faktt,graf);
for (var i = 0; i <= faktor; i++){setTimeout('document.getElementById("faktr").style.width="'+i+'%"', 16*i);}}
if (sofGet('soRes_SatEnerg') > 0){
var selc = xpath('//table[@width="550"]/tbody/tr/th/select[@name="last212"]');
if (selc){
var totenrg = DelCommas(selc.parentNode.previousSibling.previousSibling.childNodes[1].childNodes[1].innerHTML);
var satt = selc.parentNode.parentNode.childNodes[1];
var sat = satt.innerHTML.match(/\d+/);
satt.innerHTML += '<br /> <font size="smaller" color="lime">'+xpath('//td[@align="center"][@class="header"][@width="85"][5]/i/b/font').innerHTML+': '+Math.round(totenrg/sat)+'</font>';}}}

//### GALAXY ###

function sofGal_PlntLst() {
if (document.getElementById('PlanetsList') || (typeof(soUpd_PlntCrds) != 'undefined')) return;
var table = document.getElementById('t1');
if (! table) return;
var Planets = sofGet('soSet_Planets');
if (Planets != '') {
var tr = document.createElement('tr');
tr.setAttribute('class', 'header');
tr.innerHTML = '<td class="header" colSpan="2" align="center" style="background-color: transparent; border: 0px;"></td>'
var select = tr.childNodes[0].appendChild(document.createElement('select'));
select.id  = 'PlanetsList';
select.setAttribute('onchange', 'sofUpd_PlntCrds(this.options[this.selectedIndex].value);document.location.href = \'/game/index.php?page=' + Page + '&session=' + session + '&cp=\' + this.options[this.selectedIndex].value; + \'&mode=&gid=&messageziel=&re=0\';');
var PlanetsList = Planets.split('||');
for (var i = 0, Planet = ''; Planet = PlanetsList[i]; i++) {
var option  = select.appendChild(document.createElement('option'));
Planet = Planet.split('==');
option.value = Planet[0];
option.text  = Planet[1];
if (sofGet('soGen_Moons') > 0){if (option.text.match(/\(./)) option.setAttribute('style','background-color:'+sofGet('soGen_MoonsC'));}
if (Planet[2] == 1)
option.selected = 'selected';}
table.parentNode.parentNode.insertBefore(tr, table.parentNode);}}

function sofGal_Hl(){
if (sofGet('soGal_HlMoons') > 0){
var imgs = xpaths('//img[contains(@src,"small/s_mond.jpg")]');
for (var i = imgs.snapshotLength - 1; i >= 0; i--){
var img = imgs.snapshotItem(i);
var moon = img.getAttribute('alt').split(':');
var moonsize = parseInt(moon[1]);
(moonsize >= sofGet('soGal_HlMoonsS') ? img.parentNode.parentNode.setAttribute('style', 'background-color:' + sofGet('soGal_HlMoonsC')) : '');}}
if((sofGet('soGal_HlDebrS') > 0) || (sofGet('soGal_DebrRemI') > 0) || (sofGet('soGal_DebrRecN') > 0)){
var as = xpaths('//a[contains(@onmouseover,"debris.jpg")]');
for (var i = as.snapshotLength - 1; i >= 0; i--){
var a = as.snapshotItem(i);
var ress = a.getAttribute('onmouseover').replace(/[\s.]/g, '').match(/<th>(\d*)<\/th>.*<th>(\d*)<\/th>/i);
var metal = parseInt(ress[1]);
var krist = parseInt(ress[2]);
var dbrsz = metal+krist;
(sofGet('soGal_DebrRemI') > 0 ? a.innerHTML = '<span style="font-size:3">'+metal+'<br />'+krist+'</span>' : '');
((sofGet('soGal_HlDebr') > 0 && dbrsz >= sofGet('soGal_HlDebrS')) ? a.parentNode.setAttribute('style', 'background-color:' + sofGet('soGal_HlDebrC')) : '');
((sofGet('soGal_DebrRecN') > 0) ? a.setAttribute('onmouseover',a.getAttribute('onmouseover').replace('</tr></table></th></tr></table>\', STICKY, MOUSEOFF,',' ('+Math.floor((dbrsz+20000)/20000)+')</tr></table></th></tr></table>\', STICKY, MOUSEOFF,')) : '');}}}

function sofGal_AllC(){
var content = document.getElementById('content');
if (! content) return;
var as = content.getElementsByTagName ('a');
for (var i = as.length - 1; i >= 0; i--){
alianza = as[i].innerHTML;
alianzac = alianza + '#';
var n1 = soGal_AllC.length;
for (var k = n1 - 1; k >= 1; k--){
var n2 = soGal_AllC[k].length;
for (var j = n2 - 1; j > 1; j--){
if(alianzac.search(soGal_AllC[k][j]+' #') != -1 ){
if (soGal_AllC[k][0]!=''){
as[i].innerHTML = alianza+'('+soGal_AllC[k][0]+')';}
as[i].style.color = soGal_AllC[k][1];}}}}}

function sofGal_Ranks(){
var aps = xpaths('//th[@width="150"]/a[@onmouseover]');
for (var i = aps.snapshotLength - 1; i >= 0; i--){
var ap = aps.snapshotItem(i);
var appn = ap.parentNode;
var rp = parseInt(ap.getAttribute('onmouseover').match(/[^\d](\d*)[^\d]*<\/td>/i)[1]);
var color = sofGet('soGal_RanksC');
if(sofGet('soGal_PrankC') > 0){
if(rp<11){color = sofGet('soGal_PrankC1');}
else if(rp<51){color = sofGet('soGal_PrankC2');}
else if(rp<101){color = sofGet('soGal_PrankC3');}
else if(rp<201){color = sofGet('soGal_PrankC4');}
else if(rp<801){color = sofGet('soGal_PrankC5');}
else if(rp<1501){color = sofGet('soGal_PrankC6');}
else if(rp>=1501){color = sofGet('soGal_PrankC7');}
else if(rp==0){color = sofGet('soGal_PrankC8');}}
appn.innerHTML+='<span style="color:'+color+';font-size:smaller">#'+rp+'</span>';}
var aas = xpaths('//th[@width="80"]/a[@onmouseover]');
for (var i = aas.snapshotLength - 1; i >= 0; i--){
var aa = aas.snapshotItem(i);
var onmsovr = aa.getAttribute('onmouseover')
var ranka = onmsovr.match(/[^\d](\d*)[^\d]*\d*[^\d]*<\/td>/i)[1];
var nmbmb = onmsovr.match(/[^\d](\d*)[^\d]*[^\d]*<\/td>/i)[1];
aa.parentNode.innerHTML+='<span style="color:'+sofGet('soGal_RanksC')+';font-size:smaller;white-space:nowrap">#'+ranka+'/'+nmbmb+'</span>';}}

function sofGal_PhalStat(){
var td = xpath('//tr[@id="fleetstatusrow"]').previousSibling.previousSibling.childNodes[1];
var phalanx = xpath('//table[@width="569"]/descendant::tr/th[3]/a[starts-with(@onclick,"fenster(")]');
if ((td.getAttribute('colspan') != 8) || (! phalanx)) return;
var deut = parseInt(td.innerHTML.replace(/[.]/g, '').match(/\d+/));
var img = '<img title="'+i18n('Phalanx Range',20)+'" src="' + (((deut > 5000) && (phalanx))? 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIBAMAAAA2IaO4AAAAAXNSR0IArs4c6QAAACpQTFRF1NDIDngAEZQAEpoAFKwAGM4AGuAAHPAAKP8LVv8/gICAgv9x0v/L////LLCNPgAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH2AgJDhEJX1qGFwAAAC9JREFUCNdjYFgoxcDAeHqqAsOaO9u8GKRPT3NkkNzZ5siwvLzUioExLUSAAaQEAPTTC25bZYTUAAAAAElFTkSuQmCC" alt="on" />' : 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIBAMAAAA2IaO4AAAAAXNSR0IArs4c6QAAAC1QTFRF1NDIeAAJgICAkwAKmgALqwAMzgAP3wAQ8AAR8Rgi9ElK9aOI93x4+IZ3////uQ7F8QAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH2AgJDhAbtfjGHgAAAC9JREFUCNdjYFAUYmBg3LvMgEH79fEgBtk9ywMZpE5ND2TQ6GhzYmAsTxVgACkBANgQCizwvXt6AAAAAElFTkSuQmCC" alt="off" />');
td.innerHTML = img + '&nbsp;' + td.innerHTML;}

function sofGal_MissAtt(){
var ipnma = xpath('//input[@type="text"][@name="anz"]');
var textma = ipnma.parentNode;
var textbr = textma.innerHTML.match(/\((\d*) /)[1];
textma.innerHTML = textma.innerHTML.replace(textbr,'<a href="#" style="cursor:pointer;color:lime" onclick="document.getElementsByName(\'anz\')[0].value=\''+textbr+'\'">'+textbr+'</a>');}

function sofGal_Compact(){
var plsls = xpaths('//tr/th[@width="30"]/a/img[@height="30"][@width="30"]');
for (var i = plsls.snapshotLength - 1; i >= 0; i--){
var plsl = plsls.snapshotItem(i);
var plsla = plsl.parentNode;
plsla.parentNode.setAttribute('style','white-space:nowrap');
plsla.innerHTML = plsla.parentNode.nextSibling.nextSibling.innerHTML;}
var plimeg = xpath('//tr/td[@colspan="8"]');
plimeg.setAttribute('colspan','7');
plimeg.parentNode.nextSibling.nextSibling.childNodes[3].style.display = 'none';
var plimes = xpaths('//tr/th[@width="130"][@style="white-space: nowrap;"]');
for (var i = plimes.snapshotLength - 1; i >= 0; i--){
var plime = plimes.snapshotItem(i);
plime.style.display='none';}
var imgs = xpaths('//img[contains(@src,"small/s_mond.jpg")]');
for (var i = imgs.snapshotLength - 1; i >= 0; i--){
var img = imgs.snapshotItem(i);
img.parentNode.innerHTML = xpath('//table[@width="569"]/tbody/tr[2]/td[@class="c"][4]').innerHTML;}
if (! sofGet('soGal_DebrRemI') > 0){
var as = xpaths('//a[contains(@onmouseover,"debris.jpg")]');
for (var i = as.snapshotLength - 1; i >= 0; i--){
var a = as.snapshotItem(i);
a.innerHTML = xpath('//table[@width="569"]/tbody/tr[2]/td[@class="c"][5]').innerHTML;}}}

function sofGal_galaxyFix(){
var skripta = document.getElementById('content').getElementsByTagName('script')[2];
skripta.text = skripta.text.replace(/test/g, '0');}

//### BUILDINGS ###

function sofBld_EndTime(){
var bx = document.getElementById('bx');
var atr= document.getElementsByName('Atr');
var bxx= document.getElementById('bxx');
var bxy= document.getElementById('bxy');
var bxxend = document.getElementById('bxxend');
var bxyend = document.getElementById('bxyend');
if ((bxx) && (!bxxend)){
var buildingTd = bxx.parentNode;
var scripts = buildingTd.getElementsByTagName('script');
if (scripts.length > 0){
if ((scripts[0].text.search(/ss=(\d*?);/i) != -1) || (scripts[0].text.search(/pp=(?:"|')(\d*?)(?:"|');/i) != -1)) {
var endTime = new Date(new Date().getTime() + sofSys_ServSecs() + parseInt(RegExp.$1) * 1000);
var newDiv = buildingTd.appendChild(document.createElement('div'));
newDiv.innerHTML = parseDate(endTime, sofGet('soBld_EndTimeF'));
newDiv.id= 'bxxend';
newDiv.setAttribute('class', 'z');
newDiv.setAttribute('style', 'text-align:center;white-space:nowrap;color:'+sofGet('soBld_EndTimeC')+';');}}}
if ((bxy) && (!bxyend)){
if (bxy.getAttribute('title')){
var endTime = new Date(new Date().getTime() + sofSys_ServSecs() + parseInt(bxy.getAttribute('title')) * 1000);
var newDiv   = bxy.parentNode.appendChild(document.createElement('div'));
newDiv.innerHTML = parseDate(endTime, sofGet('soBld_EndTimeF'));
newDiv.id= 'bxyend';
newDiv.setAttribute('style','text-align:center;whity-space:nowrap;color:'+sofGet('soBld_EndTimeC')+';');}}
if ((bx) && (atr.length > 0) && (! bxxend)){
var seconds = 0;
var timeList = bx.parentNode.innerHTML.substr(-100).match(/\D\d+\D/g);
for (var i = 0, time = ''; time = parseInt(timeList[i]); i++) {
var j = i + 4 - timeList.length;
if (j == 0) seconds += time * 86400;
if (j == 1) seconds += time * 3600;
if (j == 2) seconds += time * 60;
if (j == 3) seconds += time;}
if (seconds > 0){
var endTime = new Date(new Date().getTime() + sofSys_ServSecs() + parseInt(seconds) * 1000);
var newDiv = bx.parentNode.insertBefore(document.createElement('div'), bx.parentNode.childNodes[bx.parentNode.childNodes.length - 4]);
newDiv.innerHTML = i18n('End time',7) + ': ' + parseDate(endTime, sofGet('soBld_EndTimeF'));
newDiv.id= 'bxxend';
newDiv.setAttribute('style', 'text-align:center;white-space:nowrap;color:'+sofGet('soBld_EndTimeC')+';');}}}

function sofBld_Bld(){
var reslvls = new Array();
var Resources = sofSys_GetRes();
if (Resources == -1) return;
var ResN = sofSys_ResN();
var Bimgs = xpaths('//td[@class="l"]/a[contains(@href,"gid")]/img[contains(@src,"gebaeude")]');
if (Bimgs.snapshotLength == 0) return;
for (var i = Bimgs.snapshotLength - 1; i >= 0; i--){
var Bimg = Bimgs.snapshotItem(i);
var Building = (Page == 'b_building' ? Bimg.parentNode.parentNode.nextSibling : Bimg.parentNode.parentNode.nextSibling.nextSibling);
if ((sofGet('soBld_ResRem') > 0) && (Building.innerHTML.search(/<b>/i))){
var j = 0;
var ResourcesRemained = 0;
var bResources = Building.getElementsByTagName('b');
if (Building.innerHTML.search(new RegExp(ResN[0] + '[^(<br>)]*<b>', 'i')) != -1){
var Metal = parseInt(bResources[j].innerHTML.replace(/\./g, ''));
if (Metal > Resources[0]){
bResources[j].innerHTML += ' <span style="color: ' + sofGet('soBld_ResRemC') + ';">(-' + AddCommas(Metal - Resources[0]) + ')</span>';
ResourcesRemained   += parseInt(Metal - Resources[0]);}
j++;}
if (Building.innerHTML.search(new RegExp(ResN[1] + '[^(<br>)]*<b>', 'i')) != -1){
var Crystal   = parseInt(bResources[j].innerHTML.replace(/\./g, ''));
if (Crystal > Resources[1]){
bResources[j].innerHTML += ' <span style="color: ' + sofGet('soBld_ResRemC') + ';">(-' + AddCommas(Crystal - Resources[1]) + ')</span>';
ResourcesRemained   += parseInt(Crystal - Resources[1]);}
j++;}
if (Building.innerHTML.search(new RegExp(ResN[2] + '[^(<br>)]*<b>', 'i')) != -1){
var Deuterium = parseInt(bResources[j].innerHTML.replace(/\./g, ''));
if (Deuterium > Resources[2]){
bResources[j].innerHTML += ' <span style="color: ' + sofGet('soBld_ResRemC') + ';">(-' + AddCommas(Deuterium - Resources[2]) + ')</span>';
ResourcesRemained   += parseInt(Deuterium - Resources[2]);}
j++;}
if (Building.innerHTML.search(new RegExp(ResN[3] + '[^(<br>)]*<b>', 'i')) != -1) {
var Energy = parseInt(bResources[j].innerHTML.replace(/\./g, ''));
if (Energy > Resources[3]){
bResources[j].innerHTML += ' <span style="color: ' + sofGet('soBld_ResRemC') + ';">(-' + AddCommas(Energy - Resources[3]) + ')</span>';}
j++;}
if ((sofGet('soBld_CargRem') > 0) && (ResourcesRemained > 0)){
Building.innerHTML  += i18n('Cargos',3) + ': <span style="color: ' + sofGet('soBld_CargRemC') + ';">' + Math.ceil(ResourcesRemained / 5000) + ' ' + i18n('SC',26) + '</span> ' + i18n('or',18);
Building.innerHTML  += ' <span style="color: ' + sofGet('soBld_CargRemC') + ';">' + Math.ceil(ResourcesRemained / 25000) + ' ' + i18n('LC',12) + '</span><br>';}}
if ((sofGet('soBld_RemDesc') > 0) || (sofGet('soBld_ResImg') >= 0)){
Bimg.parentNode.style.display = (sofGet('soBld_ResImg') == 0 ? 'none' : '');
Bimg.width = ((sofGet('soBld_ResImg') <= 150) && (sofGet('soBld_ResImg') > 0) ? sofGet('soBld_ResImg') : 120);
Bimg.height = ((sofGet('soBld_ResImg') <= 150) && (sofGet('soBld_ResImg') > 0) ? sofGet('soBld_ResImg') : 120);
Building.innerHTML = (sofGet('soBld_RemDesc') > 0 ? Building.innerHTML.replace(/^(.*?<br>)(.*<br>)(.*?<b>)/i, '$1$3') : Building.innerHTML);
if((sofGet('soBld_ImpMsg') > 0) && (Mode != 'Flotte') && (Building.nextSibling.childNodes.length == 1) && (Building.nextSibling.innerHTML != ' - ') && (Building.nextSibling.childNodes[0].tagName.toLowerCase() == 'font')) Building.nextSibling.firstChild.innerHTML = i18n('Impossible',10);}
if (sofGet('soBld_Range') > 0){
var option = xpath('//select[starts-with(@onchange,"haha(this)")]');
var coord = option.options[option.selectedIndex].text.match(/\[(\d*):(\d*):(\d*)\]/);
var coordG = parseInt(coord[1]);
var coordS = parseInt(coord[2]);
if (((Page == 'buildings') && (Mode == 'Forschung') && (Bimg.src.match('gebaeude/117.gif'))) || ((Page == 'b_building') && (Bimg.src.match('gebaeude/42.gif')))){
var level = (isNaN(parseInt(Building.innerHTML.match(/<\/a>[^\d]*(\d*)[^\d]*<br/i)[1])) ? 0 : parseInt(Building.innerHTML.match(/<\/a>[^\d]*(\d*)[^\d]*<br/i)[1]));
if (level != 0){
var range = (Bimg.src.match('gebaeude/117.gif') ? (level*5 - 1) : (level*level - 1));
var range1 = (coordS-range < 1 ? 1 : coordS-range);
var range2 = (coordS+range > 499 ? 499 : coordS+range);
range1 = coordG+':'+range1;
range2 = coordG+':'+range2;
Building.innerHTML += '<br /><span style="color: orange;">'+ (Bimg.src.match('gebaeude/117.gif') ? i18n('IPM Range',11) : i18n('Phalanx Range',20)) +': <b>'+range+'</b> '+i18n('systems',37)+' ('+range1+' - '+range2+')</span>';}}}
if ((Page == 'b_building') && (sofGet('soBld_Energ') > 0)){
var level = (isNaN(parseInt(Building.innerHTML.match(/<\/a>[^\d]*(\d*)[^\d]*<br/i)[1])) ? 0 : parseInt(Building.innerHTML.match(/<\/a>[^\d]*(\d*)[^\d]*<br/i)[1]));
for (var z = 1; z < 5; z++){
if (Bimg.src.match('gebaeude/'+[z]+'.gif')){
var factor = ((z == 3) || (z == 4) ? 20 : 10);
var ener1 = Math.ceil(factor*level*Math.pow(1.1,level));
var ener2 = Math.ceil(factor*(level+1)*Math.pow(1.1,(level+1)));
var energy = (z == 4 ? -(-(ener2-ener1) - Resources[4]) : (Resources[4] - (ener2-ener1)));
Building.innerHTML += xpath('//td[@align="center"][@class="header"][@width="85"][5]/i/b/font').innerHTML+': <span style="color:'+(energy<0 ? 'red' : 'lime')+'">'+energy+'</span>';}}}
if ((sofGet('soBld_ResPts') > 0) && (Page == 'buildings') && (Mode == 'Forschung')){
var reslvl = (isNaN(parseInt(Building.innerHTML.match(/<\/a>[^\d]*(\d*)[^\d]*<br/i)[1])) ? 0 : parseInt(Building.innerHTML.match(/<\/a>[^\d]*(\d*)[^\d]*<br/i)[1]));
reslvls.push(reslvl);}}
if ((Page == 'buildings') && (Mode == 'Forschung') && (sofGet('soBld_ResPts') > 0)){
var respts = 0;
for (var g=0;g<reslvls.length;g++){respts += parseInt(reslvls[g]);}
if (respts == 0) return;
var topl = xpath('//tr/td[@class="l"][@colspan="2"]');
var topl2 = document.createElement('tr');
topl2.innerHTML = '<td colspan="2" class="l">'+i18n('Research points',45)+'</td><th style="text-align:center">'+respts+'</th>';
topl.parentNode.parentNode.insertBefore(topl2,topl.parentNode);}}

function sofBld_InfDiff(){
var cfnt = xpath('//table[@border="1"]/tbody/tr/th/font[@color="FF0000"]');
if (!cfnt) return;
var reach = DelCommas(cfnt.parentNode.nextSibling.innerHTML);
var rows = cfnt.parentNode.parentNode.parentNode.childNodes;
for (var i = rows.length - 1; i >= 1; i--){
row = rows[i];
if (document.getElementById('InfoDiff' + i)) continue;
var reachDiff = DelCommas(row.childNodes[1].innerHTML) - reach;
if (reachDiff != 0) {
var newSpan = row.childNodes[1].appendChild(document.createElement('span'));
newSpan.innerHTML = ' (' + AddCommas(reachDiff) + ')';
newSpan.id= 'InfoDiff' + i;
newSpan.setAttribute('style', 'text-align: center; color: #' + (reachDiff > 0 ? '00FF00' : 'FF0000') + ';');}}}

function soBld_JmpGtR(){
var ovv = xpath('//center/center/font');
if (!ovv) return;
var vre = ovv.innerHTML.match(/\d{1,2}/gi);
var sek = ((vre[0] * 60) + (vre[1]*1)) * 1000;
var rdyTime = new Date(new Date().getTime() + sofSys_ServSecs() + sek);
var rdyTsp = ovv.parentNode.appendChild(document.createElement('div'));
rdyTsp.innerHTML = parseDate(rdyTime, sofGet('soBld_JGRtF'));
rdyTsp.setAttribute('style', 'text-align:center;white-space:nowrap;color:'+sofGet('soBld_JGRtC')+';');}

//### FLEET ###

function sofFlt_ArrTime(){
var fleetDivs = xpaths('//tr/th/div[starts-with(@id,"bxx")]');
if (fleetDivs.snapshotLength == 0) return;
for (var i = fleetDivs.snapshotLength - 1; i >= 0; i--){
var fleetDiv = fleetDivs.snapshotItem(i);
if (fleetDiv.getAttribute('title'))
var arrivalTime = new Date(new Date().getTime() + sofSys_ServSecs() + parseInt(fleetDiv.getAttribute('title')) * 1000);
else continue;
var newDiv = fleetDiv.parentNode.appendChild(document.createElement('div'));
newDiv.innerHTML = parseDate(arrivalTime, sofGet('soFlt_ArrTimeF'));
newDiv.id = fleetDiv.id + 'end';
newDiv.setAttribute('style', 'text-align: center; white-space: nowrap; color: ' + sofGet('soFlt_ArrTimeC') + ';');}}

function sofFlt_CmBckTime(){
var returnFleets = xpaths('//input[@name="order_return"]');
if (returnFleets.snapshotLength == 0) return;
for (var i = returnFleets.snapshotLength - 1; i >= 0; i--){
var returnFleet = returnFleets.snapshotItem(i);
var newDiv= returnFleet.parentNode.getElementsByTagName('div');
if (newDiv.length < 1) {
var newInput   = returnFleet.parentNode.appendChild(document.createElement('input'));
newInput.setAttribute('name', 'SendTime');
newInput.setAttribute('type', 'hidden');
// ### TODO
var dt2 = new Date();
var godinat2 = dt2.getFullYear();
var brojkat2 = returnFleet.parentNode.parentNode.parentNode.childNodes[9].innerHTML.split(' ');
var nizt2 = brojkat2[1]+' '+brojkat2[2]+', '+godinat2+' '+brojkat2[3];
// ###
newInput.value = new Date(nizt2).getTime();
var newDiv = returnFleet.parentNode.appendChild(document.createElement('div'));
newDiv.setAttribute('style', 'text-align: center; white-space: nowrap; color: ' + sofGet('soFlt_CmBckTimeC') + ';');}}
sofGen_AddRefr('sofUpd_CmBckTime',500);}

function sofUpd_CmBckTime(){
var returnFleets = document.getElementsByName('SendTime');
if (returnFleets.length < 1) return;
for (var i = 0, returnFleet = ''; returnFleet = returnFleets[i]; i++) {
var comeBackTime = new Date(2*(new Date().getTime() + sofSys_ServSecs()) - parseInt(returnFleet.value));
returnFleet.parentNode.getElementsByTagName('div')[0].innerHTML = parseDate(comeBackTime, sofGet('soFlt_CmBckTimeF'));}}

function sofFlt_Cap(){
var content = document.getElementById('content');
if (! content) return;
var divCapacity = document.getElementById('FleetsCapacity');
var capacity = 0;
for (var i = 0; (inpt = content.getElementsByTagName('input')[i]); i++) {
if ((inpt.getAttribute('name')) && (inpt.getAttribute('name').substr(0,4) == 'ship') && (inpt.getAttribute('name').substr(4) != '210') && (! isNaN(parseInt(inpt.value)))) {
capacity += parseInt(document.getElementsByName('capacity' + inpt.getAttribute('name').substr(4))[0].value) * parseInt(inpt.value);
if ((! divCapacity) && (! thCapacity)){
var thCapacity = inpt.parentNode.parentNode.parentNode.childNodes[2].childNodes[9];
// thCapacity = (thCapacity.childNodes[0].lastChild.tagName.toLowerCase() != 'th' ? thCapacity.childNodes[1].lastChild : thCapacity.childNodes[2].lastChild);
}}}
if (thCapacity){
thCapacity.innerHTML = '<div id="FleetsCapacity" style="text-align: center; color: ' + sofGet('soFlt_CapC') + ';"></div>';
var divCapacity  = thCapacity.firstChild;
sofGen_AddRefr('sofFlt_Cap',250);}
if (divCapacity)
divCapacity.innerHTML = AddCommas(capacity);}

function sofFlt_SndTime(){
var consumption = document.getElementById('consumption');
var resources = document.getElementById('remainingresources');
var arrivalTime = document.getElementById('ArrivalTime');
var comeBackTime = document.getElementById('ComeBackTime');
if (consumption){
if (! arrivalTime){
var newTr = consumption.parentNode.parentNode.parentNode.insertBefore(document.createElement('tr'), consumption.parentNode.parentNode);
newTr.setAttribute('height', '20');
newTr.innerHTML  = '<th>' + i18n('Arrival time',1) + ':</th><th><div id="ArrivalTime" style="text-align: center; white-space: nowrap; color: ' + sofGet('soFlt_SndTimeC') + ';">-</div></th>';
var arrivalTime = newTr.lastChild.lastChild;}
if (! comeBackTime){
var newTr= consumption.parentNode.parentNode.parentNode.insertBefore(document.createElement('tr'), consumption.parentNode.parentNode);
newTr.setAttribute('height', '20');
newTr.innerHTML  = '<th>' + i18n('Comeback time',5) + ':</th><th><div id="ComeBackTime" style="text-align: center; white-space: nowrap; color: ' + sofGet('soFlt_SndTimeC') + ';">-</div></th>';
var comeBackTime = newTr.lastChild.lastChild;}}
else if (resources){
var td = resources.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.getElementsByTagName('tr')[0].firstChild.nextSibling;
if (! arrivalTime)
td.innerHTML += ' - <span id="ArrivalTime" style="color: '+sofGet('soFlt_SndTimeC')+';">-</span> /';
if (! comeBackTime)
td.innerHTML += ' <span id="ComeBackTime" style="color: '+sofGet('soFlt_SndTimeC')+';">-</span>';}
sofGen_AddRefr('sofUpd_SndTime',250);}

function sofUpd_SndTime(){
var divArrivalTime  = document.getElementById('ArrivalTime');
var divComeBackTime = document.getElementById('ComeBackTime');
if ((! divArrivalTime) || (! divComeBackTime)) return;
var expTime = document.getElementsByName('expeditiontime');
var holdTime = document.getElementsByName('holdingtime');
var stopTime = (expTime.length == 0 ? holdTime : expTime);
var parkingTime= (stopTime.length > 0 ? stopTime[0].value * 3600000 : 0);
var arrivalTime  = new Date(new Date().getTime() + sofSys_ServSecs() + parseInt(duration()) * 1000);
var comeBackTime = new Date(new Date().getTime() + sofSys_ServSecs() + parseInt(duration()) * 2000 + parkingTime);
divArrivalTime.innerHTML  = parseDate(arrivalTime, sofGet('soFlt_SndTimeF'));
divComeBackTime.innerHTML = parseDate(comeBackTime, sofGet('soFlt_SndTimeF'));}

function sofFlt_TransCalc(){
var Resources = sofSys_GetRes();
var ResN = sofSys_ResN()
var ctMet = parseInt(Resources[0]);
var ctCry = parseInt(Resources[1]);
var ctDeut = parseInt(Resources[2]);
var TotRess = ctMet+ctCry+ctDeut;
var tc = xpath('//form[contains(@action,"page=flotten2")]/table[@width="519"]');
tc_ntb = document.createElement('table');
tc_ntb.setAttribute('width','519');
tc_ntb.innerHTML = '<tr><td class="c" colspan="3">'+i18n('Transport calculator',15)+'</td></tr><tr><td class="k" width="25%">'+ResN[0]+':</td><td width="35%" class="k"><input size="12" id="tcMetal" value="'+AddCommas(ctMet)+'" onchange="javascript:CalcRes()" onkeyup="javascript:CalcRes()" /></td><td class="k" rowspan="3" style="line-height:175%;"><a style="cursor:pointer;color: '+sofGet('soFlt_ResC')+';" onclick="javascript:Reset_CalcRes('+ctMet+','+ctCry+','+ctDeut+');" title="'+i18n('Reset',44)+'">'+xpath('//tr/td/div/font/a[contains(@href,"page=resources")]').innerHTML+': '+AddCommas(TotRess)+'</a><br /><input type="text" size="12" id="resTotl" value="'+AddCommas(TotRess)+'" /><div style="text-align: center;" id="CargosNeeded"></div></td></tr><tr><td class="k">'+ResN[1]+':</td><td class="k"><input size="12" id="tcCrystal" value="'+AddCommas(ctCry)+'" onchange="javascript:CalcRes()" onkeyup="javascript:CalcRes()" /></td></tr><tr><td class="k">'+ResN[2]+':</td><td class="k"><input size="12" id="tcDeut" value="'+AddCommas(ctDeut)+'"; onchange="javascript:CalcRes()" onkeyup="javascript:CalcRes()" /></td></tr>';
tc.parentNode.insertBefore(tc_ntb, tc.nextSibling);
var as = xpaths('//a[contains(@href,"javascript:maxShip(")]');
for (var i = as.snapshotLength - 1; i >= 0; i--){
var a = as.snapshotItem(i);
if (a.parentNode.parentNode.getElementsByTagName('input')[3].value >= 500){
inp = a.parentNode.parentNode.getElementsByTagName('input');
inpnr = parseInt(inp[0].value);
inpcp = parseInt(inp[3].value);
inpin = inp[4].name;
var astar = a.parentNode.appendChild(document.createElement('span'));
astar.innerHTML = '(<a onclick="javascript:NeedShip('+inpnr+','+inpcp+',\''+inpin+'\');" style="cursor:pointer;" title="'+Math.ceil(DelCommas(document.getElementById('resTotl').value) / inpcp)+'">*</a>)';}}
sofGen_AddRefr('sofUpd_CargNeed', 300);}

function CalcRes(){
var metal = DelCommas(document.getElementById('tcMetal').value);
var kristal = DelCommas(document.getElementById('tcCrystal').value);
var deut = DelCommas(document.getElementById('tcDeut').value);
document.getElementById('resTotl').value = AddCommas(metal+kristal+deut);}

function Reset_CalcRes(ctMet,ctCry,ctDeut){
document.getElementById('tcMetal').value = AddCommas(ctMet);
document.getElementById('tcCrystal').value = AddCommas(ctCry);
document.getElementById('tcDeut').value = AddCommas(ctDeut);
document.getElementById('resTotl').value = AddCommas(ctMet+ctCry+ctDeut);}

function NeedShip(inpnr,inpcp,inpin){
var tempRess = DelCommas(document.getElementById('resTotl').value);
neededShips = Math.ceil(tempRess / inpcp);
if (neededShips > inpnr) neededShips = inpnr;
document.getElementsByName(inpin)[0].value = neededShips;
document.getElementsByName(inpin)[0].focus();}

function sofUpd_CargNeed(){
var cargosNeeded = document.getElementById('CargosNeeded');
var resursi = DelCommas(document.getElementById('resTotl').value);
if (! cargosNeeded) return;
if (resursi < 1){
cargosNeeded.innerHTML = '<span style="color:red;">' + i18n('Impossible',10) + ', ' + i18n('there is no resources.',8) + '</span>';
return;}
var capacity = 0;
var inputs = xpaths('//th/input[starts-with(@name,"ship")]');
for (var i = inputs.snapshotLength - 1; i >= 0; i--){
var inpt = inputs.snapshotItem(i);
if ((inpt.name != 'ship210') && (! isNaN(parseInt(inpt.value))))
capacity += parseInt(inpt.parentNode.previousSibling.previousSibling.previousSibling.previousSibling.value) * parseInt(inpt.value);}
var ship = document.getElementsByName('maxship202');
var MaxSmallCargo    = parseInt((ship.length > 0 ? ship[0].value - document.getElementsByName('ship202')[0].value : 0));
var ship = document.getElementsByName('maxship203');
var MaxLargeCargo    = parseInt((ship.length > 0 ? ship[0].value - document.getElementsByName('ship203')[0].value : 0));
var SmallCargoNeeded = Math.ceil((resursi - capacity) / 5000);
var LargeCargoNeeded = Math.ceil((resursi - capacity) / 25000);
if ((SmallCargoNeeded > 0) && (MaxSmallCargo < 1))
var SmallCargoNeededLink = '<span style="color:red;">' + AddCommas(SmallCargoNeeded) + ' ' + i18n('SC',26) + '</span>';
else if (SmallCargoNeeded > 0)
var SmallCargoNeededLink = '<a style="cursor:pointer;color: ' + (MaxSmallCargo >= SmallCargoNeeded ? sofGet('soFlt_CargNeedC') : sofGet('soGen_notEnC')) + ';" onclick="javascript: var input = document.getElementsByName(\'ship202\')[0]; input.value = \'' + Math.min(MaxSmallCargo, SmallCargoNeeded) + '\'; input.focus(); return undefined;">' + AddCommas(SmallCargoNeeded) + ' ' + i18n('SC',26) + '</a>';
if ((LargeCargoNeeded > 0) && (MaxLargeCargo < 1))
var LargeCargoNeededLink = '<span style="color:red">' + AddCommas(LargeCargoNeeded) + ' ' + i18n('LC',12) + '</span>';
else if (LargeCargoNeeded > 0)
var LargeCargoNeededLink = '<a style="cursor:pointer;color: ' + (MaxLargeCargo >= LargeCargoNeeded ? sofGet('soFlt_CargNeedC') : sofGet('soGen_notEnC')) + ';" onclick="javascript: var input = document.getElementsByName(\'ship203\')[0]; input.value = \'' + Math.min(MaxLargeCargo, LargeCargoNeeded) + '\'; input.focus(); return undefined;">' + AddCommas(LargeCargoNeeded) + ' ' + i18n('LC',12) + '</a>';
cargosNeeded.innerHTML = '';
if (SmallCargoNeeded > 0)
cargosNeeded.innerHTML += SmallCargoNeededLink + '&nbsp;&nbsp;' + i18n('or',18) + '&nbsp;&nbsp;' + LargeCargoNeededLink;}

function sofFlt_Fl1(){
var FI1 = xpath('//table[@width="519"]/tbody/tr/td/table/tbody/tr[1]/td');
var flote = FI1.innerHTML.match(/\d+/g);
var diff = flote[1]-flote[0];
var color = 'lime';
switch (diff){
case 0: color = 'red'; break;
case 1: color = 'orange'; break;
case 2: color = '#FDDA00'; break;
case 3: color = '#CCFF00';}
FI1.setAttribute('style','color:'+color+';background-color:transparent;font-weight:bold');}

function sofFlt_SpdSel(indx){
var speedsel = xpath('//tr[@height="20"]/th/select[@name="speed"][1]');
speedsel.value = (1+indx);
for (var i = 9; i >= 0; i--){
if (indx != i) document.getElementById('spdsel'+i).style.color = 'inherit';
else document.getElementById('spdsel'+i).style.color = 'red';}
shortInfo();}

function sofFlt_Fl2(){
if(sofGet('soFlt_SpdSel') > 0){
var spdsel = xpath('//select[@name="speed"][1]');
switch (sofGet('soFlt_SpdSel')){
case '1': spdsel.parentNode.setAttribute('style', 'text-align: left'); break;
case '2': spdsel.parentNode.setAttribute('style', 'text-align: right'); break;
case '3': for (var i = 9; i >= 0; i--){
var a = document.createElement('span');
a.innerHTML = '<a id="spdsel'+i+'" href="#" style="cursor:pointer;color:'+(i == 9 ? 'red' : 'inherit')+'" onclick="sofFlt_SpdSel('+i+')">'+(i*10+10) + '</a>  ';
spdsel.parentNode.insertBefore(a, spdsel);}
spdsel.style.display = 'none';}}
if(sofGet('soFlt_ExpShrt') > 0){
var table = xpath('//div[@id="content"]/center/center/table[@width="519"]/tbody');
var nwTr = table.insertBefore(document.createElement('tr'), table.lastChild);
nwTr.height= '20';
nwTr.innerHTML = '<th colspan="2"><a style="cursor:pointer" onclick="var planet = document.getElementsByName(\'planet\'); if (planet.length > 0) { planet[0].value = \'16\'; shortInfo(); }">'+i18n('Outer Space',19)+'</a></th>';}
if(soFlt_Scs.length > 0){
try{var table = xpath('//div[@id="content"]/center/center/table[@width="519"]/tbody');
var debrt = xpath('//select[@name="planettype"]/option[@value="2"]').innerHTML.replace(' ','');
var moont = xpath('//select[@name="planettype"]/option[@value="3"]').innerHTML.replace(' ','');
for (var j = 1; j < soFlt_Scs.length; j++){
var newTr = table.insertBefore(document.createElement('tr'), table.lastChild);
newTr.style.display = (j%2 == 0 ? 'none' : '');
newTr.height= '20';
newTr.innerHTML = '<th><a href="javascript:setTarget('+soFlt_Scs[j][0]+','+soFlt_Scs[j][1]+','+soFlt_Scs[j][2]+','+soFlt_Scs[j][3]+'); shortInfo()">'+soFlt_Scs[j][4]+(soFlt_Scs[j][3] == 3 ? ' ('+moont+')' : '')+(soFlt_Scs[j][3] == 2 ? ' ('+debrt+')' : '')+' '+soFlt_Scs[j][0]+':'+soFlt_Scs[j][1]+':'+soFlt_Scs[j][2]+'</a></th><th>'+(soFlt_Scs[j+1] ? '<a href="javascript:setTarget('+soFlt_Scs[j+1][0]+','+soFlt_Scs[j+1][1]+','+soFlt_Scs[j+1][2]+','+soFlt_Scs[j+1][3]+'); shortInfo()">'+soFlt_Scs[j+1][4]+(soFlt_Scs[j+1][3] == 3 ? ' ('+moont+')' : '')+(soFlt_Scs[j+1][3] == 2 ? ' ('+debrt+')' : '')+' '+soFlt_Scs[j+1][0]+':'+soFlt_Scs[j+1][1]+':'+soFlt_Scs[j+1][2]+'</a>' : '')+'</th>';}}
catch(err){}}
if(sofGet('soFlt_DefCrds1') != '' && sofGet('soFlt_DefCrds2') != '' && sofGet('soFlt_DefCrds3') != ''){
var crd1 = sofGet('soFlt_DefCrds1');
var crd2 = sofGet('soFlt_DefCrds2');
var crd3 = sofGet('soFlt_DefCrds3');
var sbmt = xpath('//tr[@height="20"]/th[@colspan="2"]/input[@type="submit"]');
var plant = xpath('//select[@name="planettype"]/option[@value="1"]').innerHTML.replace(' ','');
var debrt = xpath('//select[@name="planettype"]/option[@value="2"]').innerHTML.replace(' ','');
var moont = xpath('//select[@name="planettype"]/option[@value="3"]').innerHTML.replace(' ','');
var sbmtnw = document.createElement('span');
sbmtnw.innerHTML = '<input type="button" title="['+crd1+':'+crd2+':'+crd3+'] ('+plant+')" onclick="javascript:setTarget('+crd1+','+crd2+','+crd3+',1); shortInfo(); document.forms[0].submit()" value="'+plant+'" /> <input type="button" title="['+crd1+':'+crd2+':'+crd3+'] ('+moont+')" onclick="javascript:setTarget('+crd1+','+crd2+','+crd3+',3); shortInfo(); document.forms[0].submit()" value="'+moont+'" /> <input type="button" title="['+crd1+':'+crd2+':'+crd3+'] ('+debrt+')" onclick="javascript:setTarget('+crd1+','+crd2+','+crd3+',2); shortInfo(); document.forms[0].submit()" value="'+debrt+'" /> ';
sbmt.parentNode.insertBefore(sbmtnw,sbmt);}
if (sofGet('soFlt_SubmFocus') > 0){
var submf = xpath('//div[@id="content"]/center/center/table[@width="519"]/tbody/tr/th/input[@type="submit"]');
submf.setAttribute('accessKey','n');
submf.focus();}
if (sofGet('soFlt_Moons') > 0){
var mnlnks = xpaths('//tr[@height="20"]/th/a[contains(@href,"javascript:setTarget")][contains(@href,",3); shortInfo()")]');
if (!mnlnks) return;
for (var m = mnlnks.snapshotLength - 1; m >= 0; m--){
var mnlnk = mnlnks.snapshotItem(m);
(sofGet('soGen_MoonsC') ? mnlnk.parentNode.style.backgroundColor = sofGet('soGen_MoonsC') : '')}}}

function sofFlt_CalcCons(time){
var deutCost = 0;
for (var i = 220; i >= 200; i--){
var shipcount = document.getElementsByName('ship' + i)[0];
if (shipcount){
var sCons = document.getElementsByName('consumption' + i)[0];
deutCost += shipcount.value*sCons.value/10;}}
var hCons = Math.ceil(time*deutCost);
if (hCons == 0) hCons = 1;
document.getElementById('ExpHoldingTime').innerHTML = tsdpkt(consumption()+hCons);}

function nullResources(){
for (i = 3; i >= 1; i--){
document.getElementsByName('resource'+[i])[0].value = 0;}
calculateTransportCapacity();}

function maxResources2(){
nullResources();
maxResource('3');
maxResource('2');
maxResource('1');}

function sofFlt_Fl3(){
if(sofGet('soFlt_Impr') > 0){
var inpt = xpath('//input[@name="order"]');
if (inpt != null){
var inplab = inpt.parentNode.parentNode.parentNode;
inputs = inplab.getElementsByTagName('input');
for (var i = inputs.length-1; i >= 0; i--){
label = document.createElement('label');
label.appendChild(inputs[i].parentNode.removeChild(inputs[i].nextSibling));
inputs[i].parentNode.insertBefore(label, inputs[i].nextSibling);
inputs[i].setAttribute('id','InpId'+i);
label.setAttribute('for','InpId'+i);
label.setAttribute('style','cursor:pointer');}}
var ResN = sofSys_ResN();
var FI3 = xpath('//table[@width="259"]/tbody/tr[@height="20"]/th[@colspan="3"]/a[@href]');
var sviresi = FI3.innerHTML;
FI3.parentNode.parentNode.innerHTML = '<th>'+xpath('//table[@width="259"]/tbody/tr[@height="20"]/td[@colspan="3"][@class="c"]').innerHTML+'</th><th colspan="2"><a href="javascript:nullResources()">'+i18n('Reset',44)+'</a>&nbsp;-&nbsp;<a href="javascript:maxResources()" title="'+ResN[0]+' - '+ResN[1]+' - '+ResN[2]+'">'+sviresi+'</a>&nbsp;-&nbsp;<a href="javascript:maxResources2()" title="'+ResN[2]+' - '+ResN[1]+' - '+ResN[0]+'">'+sviresi+' 2</a></th>';}
if (sofGet('soFlt_HoldCons') > 0){
var selct = xpath('//th[@colspan="3"]/select[contains(@name,"time")]');
if (selct){
selct.setAttribute('onChange','sofFlt_CalcCons(this.value);');
selct.setAttribute('onFocus','sofFlt_CalcCons(this.value);');
selct.parentNode.setAttribute('colSpan','2');
var th = document.createElement('th');
th.innerHTML = xpath('//td[@align="center"][@class="header"][@width="85"][3]/i/b/font').innerHTML+': <span id="ExpHoldingTime" style="color:lime">0</span>'
selct.parentNode.parentNode.appendChild(th);}}
if (sofGet('soFlt_SubmFocus') > 0){
var submf = xpath('//div[@id="content"]/center/center/table[@width="519"]/tbody/tr/th/input[@type="submit"]');
submf.setAttribute('accessKey','n');
submf.focus();}
if (sofGet('soFlt_DefMis') > 0){
var orders = xpaths('//input[@type="radio"][@name="order"]');
// if (orders.snapshotLength != 0)
if (orders.snapshotLength == 1){orders.snapshotItem(0).checked = 'checked';return;}
var opt = sofGet('soFlt_DefMisO')[0];
sofGet('soFlt_DefMisO')[sofGet('soFlt_DefMisO').length] = -1;
if (opt == -1) return;
var order1 = xpath('//input[@type="radio"][@name="order"][@checked="checked"]');
if (order1) return;
i = 0;
while(opt != -1){
order = xpath('//input[@name="order"][@type="radio"][@value="'+opt+'"]');
if (order){
order.checked = 'checked';
return;}i++;
opt = sofGet('soFlt_DefMisO')[i];}return;}}

//### MESSAGES ###

function sofMsg_MsgsC(){
var deleteSelect = document.getElementsByName('deletemessages');
if (deleteSelect.length < 1) return;
if (sofGet('soMsg_Clear') > 0){
deleteSelect[0].parentNode.parentNode.parentNode.parentNode.parentNode.style.backgroundColor = 'transparent';}
var msgbs = xpaths('//th/input[@type="checkbox"][starts-with(@name,"delmes")]');
if (!msgbs) return;
for (var i = msgbs.snapshotLength - 1; i >= 0; i--){
var msgb = msgbs.snapshotItem(i);
var rprt = msgb.parentNode.parentNode.lastChild.previousSibling;
var proz = msgb.parentNode.parentNode.nextSibling.nextSibling.lastChild.previousSibling;
if ((((rprt.innerHTML.search(/espionagereport/i) != -1) && (sofGet('soMsg_SpyRepC') > 0)) || ((rprt.innerHTML.search(/combatreport/i) != -1) && (sofGet('soMsg_MissRepC') > 0))) && (rprt.innerHTML.search(/onclick="fenster/i) == -1)){
proz.style.color = sofGet('soMsg_ActiveC');
proz.style.backgroundColor = sofGet('soMsg_ActiveBgC');}
else if ((rprt.innerHTML.search(/(<span|<font)/i) == -1) && (rprt.innerHTML.search(/espionagereport/i) == -1) && (proz.innerHTML.search(/:\s*1?[0-9]{1,2}\s*%.?\s*$/i) != -1) && (msgb.parentNode.parentNode.nextSibling.nextSibling.nextSibling.nextSibling.innerHTML.search(/sneak/i) == -1)){
rprt.innerHTML = '<span class="espionagereport">' + rprt.innerHTML + '</span>';
proz.style.color = sofGet('soMsg_ActiveC');
proz.style.backgroundColor = sofGet('soMsg_ActiveBgC');}
if (msgb.parentNode.parentNode.nextSibling.nextSibling.nextSibling.nextSibling.innerHTML.search(/sneak/i) != -1){
if (rprt.innerHTML.search(/<img alt/i) == -1){
proz.style.color = sofGet('soMsg_PrivC');
proz.style.backgroundColor = sofGet('soMsg_PrivBgC');}
if (rprt.innerHTML.search(/<img/i) == -1){
if (sofGet('soMsg_AllyReply') > 0){
rprt.innerHTML += ' <a href="index.php?page=allianzen&session='+session+'&a=17"><img alt="'+i18n('Reply',25)+'" title="'+i18n('Reply',25)+'" src="'+sofSys_GetSkinURL()+'/img/m.gif" /></a>';}
proz.style.color = sofGet('soMsg_AllyC');
proz.style.backgroundColor = sofGet('soMsg_AllyBgC');}}
else if (rprt.innerHTML.search(/\[[0-9]+:[0-9]+:16\]/) != -1 && rprt.innerHTML.search(/onclick="fenster/i) == -1){
proz.style.color = sofGet('soMsg_ExpC');
proz.style.backgroundColor = sofGet('soMsg_ExpBgC');}
else if ((rprt.innerHTML.search(/\[[0-9]+:[0-9]+:[0-9]+\]/) != -1) && (rprt.innerHTML.search(/espionagereport/i) == -1) && (rprt.innerHTML.search(/showGalaxy/i) != -1)){
proz.style.color = sofGet('soMsg_RecC');
proz.style.backgroundColor = sofGet('soMsg_RecBgC');}
else if (((rprt.innerHTML.search(/espionagereport/i) == -1) && (rprt.innerHTML.search(/onclick="fenster/i) == -1)) || ((rprt.innerHTML.search(/combatreport/i) != -1) && (rprt.innerHTML.search(/onclick="fenster/i) == -1))){
proz.style.color = sofGet('soMsg_PassiveC');
proz.style.backgroundColor = sofGet('soMsg_PassiveBgC');}}}

function sofMsg_DelForm(){
var deleteSelect = xpath('//select[@name="deletemessages"]');
var reportsInput = xpath('//input[@name="fullreports"]');
var messageDel = xpaths('//tr/td[@colspan="3"][@class="b"]');
if ((deleteSelect.length < 1) || (reportsInput.length < 1) || (messageDel.snapshotLength < sofGet('soMsg_DelFormMM'))) return;
var reportsTr = reportsInput.parentNode.parentNode;
var deleteTr = deleteSelect.parentNode.parentNode;
reportsInput.setAttribute('onchange', 'document.getElementsByName("fullreports")[1].checked = this.checked;');
deleteSelect.setAttribute('onchange', 'document.getElementsByName("deletemessages")[1].selectedIndex = this.selectedIndex;');
deleteSelect.parentNode.childNodes[3].setAttribute('onclick', 'document.forms[0].submit();');
var newReportsTr = reportsTr.cloneNode(true);
var newDeleteTr  = deleteTr.cloneNode(true);
newDeleteTr.id   = 'deleteFormTop';
reportsInput.setAttribute('onchange', 'document.getElementsByName("fullreports")[0].checked = this.checked;');
deleteSelect.setAttribute('onchange', 'document.getElementsByName("deletemessages")[0].selectedIndex = this.selectedIndex;');
deleteTr.parentNode.insertBefore(document.createElement('tr'), deleteTr.parentNode.childNodes[1]);
deleteTr.parentNode.insertBefore(newReportsTr, deleteTr.parentNode.childNodes[1]);
deleteTr.parentNode.insertBefore(newDeleteTr, deleteTr.parentNode.childNodes[1]);}

function sofMsg_SpyRep(){
var sprs = xpaths('//span[@class="espionagereport"]');
for (var i = sprs.snapshotLength - 1; i >= 0; i--){
var spr = sprs.snapshotItem(i);
var formn = spr.parentNode.parentNode.firstChild.nextSibling.firstChild.getAttribute('name');
var tables = spr.parentNode.parentNode.nextSibling.nextSibling.getElementsByTagName('td')[1];
if(sofGet('soMsg_RepRes') > 0){
var restd = tables.getElementsByTagName('td');
var metal = parseInt(restd[2].innerHTML.replace(/\./g ,''));
var crystal = parseInt(restd[4].innerHTML.replace(/\./g ,''));
var deut = parseInt(restd[6].innerHTML.replace(/\./g ,''));
var totmet = Math.round(metal/2);
var totcry = Math.round(crystal/2);
var totdeu = Math.round(deut/2);
if (totmet <= (totcry + totdeu)){
var LCn = Math.floor((totmet + totcry + totdeu + 24999) / 25000);
var SCn = Math.floor((totmet + totcry + totdeu + 4999) / 5000);}
else{
LCn = Math.floor(((totcry + totdeu) * 3) / 50000);
  tempm = totmet - Math.floor(LCn * (25000 / 3));
  tempm -= Math.min(16667 - Math.floor((totcry + totdeu - (LCn * (50000 / 3))) / 2), Math.floor(50000 / 3));
LCn += Math.floor((tempm + 33333) / (50000 / 3));
SCn = Math.floor(((totcry + totdeu) * 3) / 10000);
  tempm = totmet - Math.floor(SCn * (5000 / 3));
  tempm -= Math.min(3333 - Math.floor(totcry + totdeu - (SCn * (10000 / 3))), Math.floor(10000 / 3));
SCn += Math.floor((tempm + 6666) / (10000 / 3));}
var RepRes = '<table width="400" cellpadding="2"><tr><td class="c" colspan="4">'+xpath('//tr/td/div/font/a[contains(@href,"page=resources")]').innerHTML+'</td></tr><tr><td>'+i18n('Total',39)+'</td><td>'+AddCommas(metal+crystal+deut)+'</td><td>'+i18n('Plunder',24)+'</td><td>'+AddCommas(Math.floor(totmet+totcry+totdeu))+'</td></tr><tr><td colspan="2">'+i18n('Cargos',3)+'</td><td>'+i18n('LC',12)+':&nbsp;&nbsp;&nbsp;<span style="font-weight:bold;'+(sofGet('soMsg_RepResC') == 1 ? 'color:'+sofGet('soMsg_RepResCC')+';' : '')+'">'+LCn+'</span></td><td>'+i18n('SC',26)+':&nbsp;&nbsp;&nbsp;<span style="font-weight:bold;'+(sofGet('soMsg_RepResC') == 2 ? 'color:'+sofGet('soMsg_RepResCC')+';' : '')+'">'+SCn+'</span></td></tr></table>';
tables.innerHTML += RepRes;}
if(sofGet('soMsg_RepEsp') > 0){
var tblssrch = tables.innerHTML.match(/<TABLE width="400"><TBODY><TR><TD class="c" colspan="4">/g);
var proben = parseInt(tblssrch.length-1);
var probenum = (proben == 4 ? 5 : proben);
if (proben > 0 && proben < 5){
var RepEsp = '<table width="400" cellpadding="2"><tr><td class="c" colspan="4">'+i18n('Espionage',14)+'</td></tr><tr><td>'+i18n('Probes used',22)+':</td><td align="center"><input type="text" onkeyup="if (this.value != ' + "''" + ') document.getElementById(' + "'prbsn" + formn + "'" + ').innerHTML = Math.max(7 + ( ((parseInt(this.value) - ' + probenum + ' + 0.1) / Math.abs(parseInt(this.value) - ' + probenum + ' + 0.1)) * Math.pow(Math.ceil(Math.sqrt(Math.abs(parseInt(this.value) - ' + probenum + '))), 2)), 1)' + '" size="3" maxlength="2" style="text-align:center"></td><td>'+i18n('Probes needed',23)+':</td><td align="center" id="prbsn' + formn + '"></td></tr></table>';
tables.innerHTML += RepEsp;}}
if(sofGet('soMsg_RepLnk') > 0){
var gServLan = sofGen_GetLang();
var ServLang = gServLan[0];
switch (gServLan[0]){
case 'es':
ServLang = 'sp'; break;
case 'se':
ServLang = 'sv';}
var RedRepRes = tables.innerHTML.replace(/<td\sclass\=\"c\".*?>/ig, '\n').replace(/<\/tr>/ig, '\n').replace(/<\/td>.*?<td>/ig, '\t').replace(/<.*?>/g, '');
RedRepRes = RedRepRes.replace(/\n\n/g, "\n").replace(/\n\n/g, '\n');

var dsServ = new Array('ba','bg','br','cz','de','dk','es','fr','gr','hu','it','kr','nl','pl','pt','ro','ru','sk','se','tr','tw','us');
var dsLang = new Array('bosnian','bulgarian','portuguese','czech','german','danish','spanish','french','greek','hungarian','italian','korean','dutch','polish','portuguese','romanian','russian','slovak','swedish','turkish','taiwanese','english');
var dslan = 'english';
for (var l = 21; l >= 0; l--){
if(Server.match(dsServ[l])){dslan = dsLang[l];}}

var el = document.createElement('form');
el.innerHTML = '<input type="hidden" name="report" value="'+encodeURI(RedRepRes)+'" />';
el.method = 'post';
el.target = '_speedsim';
el.id = el.name = 'speedsim_form'+formn;
el.action = 'http://websim.speedsim.net/index.php?lang='+ServLang;
document.body.appendChild(el);
var linkdosim = '<br /><center><a style="cursor:pointer;padding:2px;font-weight:bold" title="SpeedSim" onclick="document.getElementById(\'speedsim_form'+formn+'\').submit();">SpeedSim</a> <a style="padding:2px;font-weight:bold" title="DragoSim" href="http://drago-sim.com/index.php?lang='+dslan+'&referrer=drago-script&scan='+encodeURI(RedRepRes)+'" target="blank">DragoSim</a></center><br />'
tables.innerHTML += linkdosim;}}}

function sofMsg_InsSml(smilie){
var inpf = document.getElementsByName('text')[0];
inpf.focus();
inpf.value = inpf.value.substr(0, inpf.selectionStart) + smilie + inpf.value.slice(inpf.selectionEnd);}

function sofMsg_Sgnt(){
xpath('//textarea[@name="text"]').innerHTML += ((sofGet('soMsg_Sgnt') != '') ? '\n\n\n'+sofGet('soMsg_Sgnt') : '');}

function sofMsg_Sml(){

var smilt = new Array('^_^',':S',':lol:',':|',':rotfl:',':P','O.O','O.o','&gt;_&lt;',':&lt;',':&gt;','8-)',';(',':(','xD',':D',';)',':)');
var smilg = new Array('data:image/gif;base64,R0lGODlhFAAUAIQdAP/mIFVACP/iHPrWDEA0EPbOAP/aEOqqAPbKAPK2AOaZAG1QBPK+APrSBP/eFKVtBPLCAO6yAFlACNquALKJAP/eGKp9BMqNAJllAM6FALp1APbOBM6ZAP///////////yH5BAEKAB8ALAAAAAAUABQAAAW74CeOZGmaQaqeZbBQEwJN1hKwX2Btg2EMDQQDczNJOAOHYClwDAqMi6RFGVQEgGxW8ExYiqJFQ0kgaMuVBuOwGAWqAjPALCcYEJFHMTAxYLWAAAYFCRd7CH6BgA6ECnsQA3+KgniOInwNFZMAXBAHGXsUCJGKAoMJB3ojCwwFAwR/AhWDawptbhatDWUOP1ARCqokEhcJEAgFBTKoChpTKBgHEQkJEQcHCkQ4Lg8XCgoZDzY4biop5OgmIQA7',
'data:image/gif;base64,R0lGODlhFAAUAKUxAAAAAHhkPGBIFGxQBKx8ANisAPTAAHBcMJh0BPTMAPjUDP/YEPjQCPTIAOy4AGhQHNCkAP/cFP/cGP/gHP/gGPjYDPjMAPC4ALyEAAAACP/kIPC0AKRwBHBQBOyoAGxIBPjYEOysAHBgNNCMAPDEAP/YFOCQAGRMHPjUCHRkOOigAKRoAP/kHOSYAPDAAJRkBFxIFP///////////////////////////////////////////////////////////yH5BAEAAD8ALAEAAQATABIAAAbDwJ9wmEoJBMWhUpkaEAoWQ4HwSS2HB4QFFbF0LQ7E4SoocCOTSWQNxgiYmPUkA9Bo1KgowSr8eCMZdoEaAF0OHh9CKXGAGhmDgl0eK1YHBWuCkI8AYCYBPwIOaHakmZImbwJbEaWlagkeLTA/lhYTAHWtESiHI58pBAlyuXavDiovfB8OwmgAaWyHJomKBMx/uBEJhyqUSgIYHswJCWAeKioEJ1cHLx7vh+8mL2NXPykfLyMtKhgvVfaYpIAB4wCfJUEAADs%3D',
'data:image/gif;base64,R0lGODlhFAAUAKU2AEA0EFVACP/mIG1MBI1dAJ0AAPraEP8AAKVxBP/iHFlACPrWDPbKAPK2APLGAOadAN6VAHFVBPK+AKVpBPbOBLKNAM6NAMKNAP/eGPrSCG1QBO66ALp1AOKVAJVhBMqlAJ19BOqqAMqFANauAOalAK6FAPbCAK59APbGAKVtAJlxBN6uAOaZAJlpBNalAKp1BO6yAM6FAK6JBK6BBN62AP/eFP///////////////////////////////////////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQJDQA/ACwAAAAAFAAUAAAGv8CfcEgo/ghH5HDJFBaVyuYyQK1KmYGIbIRyuGaawPUXAFEWBsMiw9ioxE3FB0BP2OkAyUWBrQAwCQIAggIGDAANL3BCERSAApCRhRkmIQNDARULgZKRaQwwCHABNAYIA3ipAAMIDRajDgiqswAIELCotKkDt0IBK7m6dAMxoyWywnQIKYsREngFBwDRBXgdl5gnzwAH3d50DxOLQgoXDdMF6ekADxx8TQEtJMGrEB7jWKwWDywiEwP4pFSx0iQIACH5BAEKAD8ALAEAAAASABQAAAa8wJ9w+CMUiwQjcSlMKpnLgHQKHQYishHK4ZppAtAAiLIwGBYZxkYFJio+ixomQccYGJKLwlpZAP4CgX8ADA0vbREUAAmCAgCBBgAmIQM/AX2MgZqQdzAIUjQGmZuchRZSDgYIA4OtAAMIDRCoCK62AAizASust60DMVIltb6DCClgERK9BQcAzQV/Ax2VlicSfwfa238PE20/ChcNzwXm5gAPHHtEAS0kvdIQHuDtsBYPLCITA/VMU1SWBAEAOw%3D%3D',
'data:image/gif;base64,R0lGODlhFAAUANUHAFVACP/mIEA0EP/iHP/aEPbKAP/ykf////K2APbGAPrWDOqqAG1QBOaZAKVxBK6FAO6yAPK+AP/eGPrSBP/eFP/ylVlACMqNAM6FANaqAPbOBN6yAJltBJllALp1AM6ZAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH+HUJ1aWx0IHdpdGggR0lGIE1vdmllIEdlYXIgNC4wACH5BAkAACAALAAAAAAUABQAAAbSQJBwSCwajYCk8lgEMB6ZRCLzYACYIIBDoyAQFJMCgnM1Wj4EymA9EAgKkYul+SBI3IKAvi12lIUME2oBBgaEhhITCQsMQwAPCgMBB24BbgcDBAUQfkIAGwQDBpR5lwaaCBdlAAmhowcHk7AGFGINqwmRhxUBFb4BmhC3nhsTEnrIyAMKixirDwW6yQGZYgudgBHREpLUEgQaEQsNjY4P2hNeFF9wwtiOFwgJBQVuCQjjHnNIHAsQCAggLBjX4Q8SBg4wNGiAwYEVLI6UJIFI0UgQADs%3D',
'data:image/gif;base64,R0lGODlhFAAUAKUEAEA0EP/mINLu///iHP///1VACPraEPbKAPrWDP/eGPK2AP/aFPK+AO6yAFlACHFVBG1MBPbGAPrOBKVpBPbCAK6BBLKNAPrSBOaZAO6qAOqhAMqlAPLGAN6VAPLCAOadAJ19BOqqAKVxBK6JBMKNAM6NANauALp1AO66AMqFAO6uAN6uAJVhBNalAOKVAOalAKp1BJlxBM6FAN62AJlpBP///////////////////////////////////////////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFCgA/ACwAAAAAFAAUAAAGysCfcEgsGo2FJKAAOBYLD4IU0Ko8Cs5fQcDlAi4HVAxrdHS7gIQBwCA5noC4PD5YHxQwsvAhAUj/AAEDAAgUIRBDBRYIg2eBAQAGBw0iZAUzBgONXpAGkgollhyZAZBypQYJdx2ijIKvpQFqk6xCBSsXCQOxvAuFGTKWFQeZu7x1BwwaE3oPDMS6ggO6CwwhGIiJFc8InmoIBx4qH8xFDiQKzwfrFAoZGCdvSDQvDQoKDRkaLix6SBAiSnzAkGICBH9OkihEmKXhkCAAIfkEBRQAPwAsAwABAAsAEAAABkXAn/BXKBqHwpEpEkH+QBKAFIDcIACErBBwsCB+Wa1TQCYjp2jqNhwWDqRs8bZcVv8AdDL1nZ5u73FqgnSCQ3h6d05oQ0EAIfkEBQoAPwAsAwABAAsAEAAABlXAn3D4AxSJP4LSSBQ4ncjnUzgAWK9XpHJLRAx+UqJhUJ0WDeNAADv0BgZvtfp3ScDl8sVhfJf/DD97dm9fPwtCBwhoCYA/HkMMB5IHPwpIDQoKDUNBACH5BAEUAD8ALAMAAQALAAkAAAY4wJ/wVygah8KRKRJB/kAShMGAQG4Qi8RgkEggDhaENrANCA8/7YAcaP8Wv/J63YYL23j3PY//BQEAOw%3D%3D',
'data:image/gif;base64,R0lGODlhFAAUAKU4AEAUEJEEBEA0EPoEBFVACFlACG1MBG1QBHFVBI1dAJVhBJlpBKVpBKVtAJlxBKVxBKp1BJ19BLp1AK6BBK6FAMqFALKNAM6FAMKJAM6JAMKNAM6NAN6VAOKVAOaZAMqlAOadAOqhAOalAOqqAN6uAO6qAO6uAN62AO6yAPK2AO66APK+APLCAPLGAPbGAPbKAPbOBPrSCPrWDPraEP/aFP/eGP/iHP/mIP///////////////////////////////yH5BAEKAD8ALAAAAAAUABQAAAbNwJ9w+EsMjcSk0shEKpOEqPQJRQAAruvkQKD+CBEADicmqxxdZeEjvrrJK00BapG13e1XCpIWImA1ZWRjZDEsIwZDBHU2N26OVzQzLygPaQQnM403nJ03NDR6G5ctmp6eoHoclywyNgKnnAKTKKtCBCQxNTcCeAACNDIsJReXFC+mv8o2oSkhDX0IK8g1AQPWATYvKyUdiYoT0zHY19smIAx9QgUYKSzWA9cpJSASc0oECyIoAf0lIRwUqINi4EEGEB4qMDAw8ImUKUqCAAA7',
'data:image/gif;base64,R0lGODlhFAAUAKUAAP///wAAAAgICP/mIFVACP/iHPraEPbKAPrWDP/eGCAgIPK+AFlACPK2AHFQBPbCAG1MBOqqAPrSBKVpBOadAO6yAN6VAPrOBP/aFNaqAOaZAPLGAMKNAKVxBN6yAPbGALKNAK59AM6NAOqhAJltBLp1AO66AK6FAO6uAMqlAPLCAJ19BOalAJVhBM6FAMqFAK6JBKp1BP///////////////////////////////////////////////////////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFyAA/ACwAAAAAFAAUAAAGs8CfcEgsGo2EpPJYJDhgmc8nE3IQmD/CKsA1ICTcwNXISAUA6EDhnGY0QWw0IJ4eCx10uT49JMDleXFiQgQeeXuAYwQbBgJ7jnoBFoobCGuAA5BzFZOEHhIJBQIKAQOmCgIBDxEuiicHBgUFprQFBgcLIxN2DguwoaYFoQkLERoQRAQhvhIGBgleuCgUu0UMHA0qB2EBxRQlbkgkLBXcIxYtdkgQHSIUGi8TEOpMSktY+EdBACH5BAUPAD8ALAwACQAEAAMAAAYMwN9PQBQYDgMD4hcEACH5BAUPAD8ALAwACQAEAAMAAAYKQIBwCAgoBIFfEAAh+QQFDwA/ACwMAAkABAADAAAGDMDfT0AUGA4DA+IXBAAh+QQFyAA/ACwMAAkABAADAAAGCkCAcAgIKASBXxAAIfkEBQ8APwAsBQAJAAsAAwAABhBAgeBHLBYHA6GROEQ6k8ogACH5BAVkAD8ALAUACQALAAMAAAYSQIBQ+CsaA0Pj8SdQBIyK3zMIACH5BAUPAD8ALAUACQALAAcAAAYYQIHgRywWBwOhkThEOpPKpXS6TFCLBmoQACH5BAVkAD8ALAUACQALAAcAAAYbQIBQ+CsaA0Pj8SdQBIyK31NJrVp/kqvxcA0CACH5BAUKAD8ALAMAAwAPAAkAAAZDwJ/wgjAYhAcTSchECJ6FQiJA5TQFTAGVGYj9LljmLyAeQ5zldKBjCHPLAtGv4H6ShYEGc/AUDP5UAQdlf4V/BghMQQAh+QQFDwA/ACwEAAQADQAHAAAGMsDfD5MoGH+Iw0L4Kw6MT8Ph0GhCC4NsAYN4RAJgcDYMrkCz6IEhQf2l0wmpUPs2GIRBACH5BAEKAD8ALAQABAANAAcAAAYpwN9PQBT+AkijAMAUIJmAwBDKDFCjS8DvusVerVTBtOmESoVEsRB5DgIAOw%3D%3D',
'data:image/gif;base64,R0lGODlhFAAUAKUCAEA0EP/mIP/////iHFVACPraEPbKAP/eGP/aFPrWDPK2APLCAPK+AFlACG1MBO6yAJltBKVpBO6qAPrSBOaZAPbGAK59APrOBMKNAPLGAHFQBKVxBOqhAOadAG1QBN6VAOqqALKNAN6yAM6NAOKVAO66AO6uAMqlAOalAK6JBNauAM6FAK6FAKp1BLp1AMqFAJ19BNalAP///////////////////////////////////////////////////////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFLAE/ACwAAAAAFAAUAAAGzMCfcEgsGo2EpPJYJHhSqkolZvEQmD8C7JIoFBITQwlyNTZOCcRhMDgcEgYGptEMJQ6A/CAwKBgMCi1lQhoXeAKIAAF5AAkLIA5DBHYAiJaViQYPG2UEIgWYiQKYAIAjnRmglqKZCh+oCQOhiox+D69CnhNrjAG+AV8LEiudLAYFbL++AwhxHBGDGgzHa8trCAwgFJGSFtMTXnilCyYd0EUNGAoLf38LChIULnRIECgPCgoPEhwkZFgEHGwY0YHCiwgOBgFUkgSLQyZBAAAh+QQFCgA/ACwEAAUACwAHAAAGKMDfbxAYDITIIZEYKCKUxWizUBgAroAAFoBgNr+BwuEX9QbGQvBXGAQAIfkEBWQAPwAsBAAFAAsABwAABiDA3w9AFBqFAIESMCwmlUsoESpI/pZPqXS4JTKRxeMvCAAh+QQFCgA/ACwEAAUACwAHAAAGKMDfbxAYDITIIZEYKCKUxWizUBgAroAAFoBgNr+BwuEX9QbGQvBXGAQAIfkEBQoAPwAsBAAFAAsABwAABiDA3w9AFBqFAIESMCwmlUsoESpI/pZPqXS4JTKRxeMvCAAh+QQBCgA/ACwEAAUACwAHAAAGKMDfbxAYDITIIZEYKCKUxWizUBgAroAAFoBgNr+BwuEX9QbGQvBXGAQAOw%3D%3D',
'data:image/gif;base64,R0lGODlhFAAUAPQfANGRBv/iHJRtDNitDumoAOjCBfS4AG1UCP3dF//qIVdACAAAAP/mH//yKP/uJvrXDPPNA5qKC/3RAvzCAv7xBN6XAv/fCv3oCeayAPfTCvbFAP7NAf/yHMGrD/fUBv///yH5BAEAAB8ALAAAAAAUABQAAAW14CeOZGmaSqqepXJEhSQNwqGwn3tdScJYkontpOhQGg1HjxF4aAA3kiJyRCp9AYTEIIiKDsfF4spgiJ2Gw2hadSyWi+wDQuiKFAVOMiE2xxFOGFB3D0lKfWJZGQUEFVEKHglXPmVMCBCMjncFF2SVAU0FGBWDOREWS5UMCJeMAHZfGxZlYrWYgqV3EbJ/gBALo69edx0TEg8PGbcVFbAtBxMG0RiNFUMsLgID1MLD2CopOOIsIQA7',
'data:image/gif;base64,R0lGODlhFAAUAKUzAAAAAPf3797fxnhkPGBIFM7PpWxQBKx8ANisAPTAAK2ue/TMAPjUDP/YEPjQCPTIAGNhOf/cFP/cGP/gHP/gGPjYDP/kIHBQBPjMAOyoAGxIBLyEAP8AAPjYEOysAKRwBHBgNPC0ANCMAPDEAP/kHP/YFOCQAGRMHPjUCHRkOPC4AOigAKRoAOSYAPDAAGhQHHBcMJRkBFxIFP///////////////////////////////////////////////////yH5BAEAAD8ALAEAAAASABQAAAbXQIDwRywaf0IAMqA8HgFMpGD4S6UIBCsSMEUWCkLDAYFJIA7CrxLwVQAwqAhGjgEo1FsFBBCJTCZ9EQAQbk1JQn8WFoKHKUQXcyRCihZCESgJGRpVG3EAHJNCoHIZLFYIfZ+glRygKBgqIikyKhGKk7cAJKQrWAsYE5TCixELGSYnMKh9w4qXKhmyKQcLKInCfQsqKzGOGipzjEnF0CabVQcqC3yXqdAtpkUyGyrqC/f1GSsHBEcwMSsyZICmz0QMGE6qaIghokWLDTE0OEpIxIoMGVqOBAEAOw%3D%3D',
'data:image/gif;base64,R0lGODlhFAAUAKUqAAAAAPf3797fxnhkPGBIFM7PpWxQBKx8ANisAPTAAK2ue/TMAPjUDP/YEPjQCPTIAGNhOf/cFP/cGP/gHP/gGPjYDP/kIHBQBPjMAOyoAGxIBLyEAP8AAPjYEOysAKRwBHBgNPC0ANCMAPDEAP/kHP/YFOCQAGRMHDkgAHRkOP//////7/C4AOigAKRoAOSYAPDAAGhQHHBcMJRkBFxIFP///////////////////////////////////////////yH5BAEAAD8ALAEAAAASABQAAAbXQIDwRywaf0IAcqU8HgFMpGD4S6UIBCsSMEUWCkLDAYFJIA7CrxLwVQAwlQhGjgEo1FsFBBCJTCZ9EQAQbk1JQn8WFoKHKUQXcyRCihZCERUJGRpVG3EAHJNCoBUYGS4pMgh9n6CVHKByLCIpNAkRJJUAlJZyGS1YCxgTlMQWgAsZJiepdSjFikIsGbMpB3Uqh0nYLC2nPxosACrj5OQAyZtVBwlC5djnLTOORAQbLOGH0i8bBEcyMxky3AvYwsQMGU6qaHAhokWLDS40zEtYhRYNGROLBAEAOw%3D%3D',
'data:image/gif;base64,R0lGODlhFAAUAKU+AFdACK+KBNeuAvXHAKyBBJ99BPTMBvzZEfvXDvrSB+25AJpwBFtDCMukAv3cFv7fGv3dF/jMAPK9AMKMAltCCAAAAN+0AfC2AP/mI9+XAPbKAO+yAN6uAf/kIfrUCvTDAPfMAOmiAHFTB/G8AP7hHf7iHvO/AOOVAG9NB/zbFPvaEUMXEO2uAOecAMKLAuyqALt3AphrBOemAOCTAJViBKZxBMyLAuaZAMqEAqRrBGxLB4GysoCAgPPCAP///////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFlgA/ACwAAAAAFAAUAAAGxsCfcEgsGo2ipPJYFC0agoNBEIiJmD9RweCApA4IhIJwRVpKpEep83AgDCZbeSiaOB6dvF7qGbnmPysVg4SFhnSGiYZlIhaJGIkmGQxCDB6PiRctjFyGkIUGG5OVHAkkGKipqF4fL3JCIgEGB3gdqiUOAxchNTp0IxEIanklDyQOJiwnNEQiBBIGCQcHDioeKxIsLTmURBQuGx8DK+QrFy8tMIB0CzIbFxcbLyEzNBRYADo1Njc3ODko1h0RQQGAQQBYEjIJAgAh+QQFCgA/ACwIAA0ABgADAAAGCsCfULgaDjUaYxAAIfkEBQoAPwAsBAANAAoAAwAABhPAn3A4dAhXxJ/mt+P9VtBVTxgEACH5BAkKAD8ALAEADwAKAAQAAAYRwJ/QtRH+QAOj0rjbLZlPYxAAIfkEAQoAPwAsAQABABIAEgAABrzAn3AoKhqHSOKiITgYBIGYKCkUFQwOSOqAQCgIU6TIUiI9Sp2HA2Ew2cI/0cTx6NjvTs/IFV5V/oCBgnGChYJFFoUYhSYZDAweioUXLUVYgouBBhuODBwJJBiio6JaHy9vIgEGB3UdpCUOAxchNTpxIxEIZ3YlDyQOJiwnNFUEEgYJBwcOKh4aEiwtOQxDFC4bHwMr3CsjLy0wcFULMhsXFxsvITM0FFQ/ADo1Njc3ODko41QiFAD/AKgEAQA7',
'data:image/gif;base64,R0lGODlhFAAUAIQfAPTDAKt0BBud5tesAvnQBeupAJ13BPvaEq+HA5ZmBOedAMyFAm54SuCWAM6MAt/ZOUWluaRrA8KMA4KwePK9AO+xAPC2APzZEf3eGPvXDvbKAEolAP7iHv/mImJMF////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFyAAfACwAAAAAFAAUAAAFsuAnjmRpml6qnmWKDBowICn7eQaRHVdGaBWD5+QZ7DAcDgbjo0iGJA8ig0x2OsmMxhKAijyEKnaszAAKXimVc21fOdpK9zu4sO9t9mXrgHo0dmxubRcEFg1+ABlWGxtXjRwHQIh0YRyOgxtmBQt+CIBJgm+SFAoRaRSAYkqFFAWUI1KpBBeNjQQUFaZeXxIWABsawsAFCnMoCQUVFhYVxQ0JvC0eEQ4KCgun0kQqNTbfJyEAIfkEBQoAHwAsBQAMAAMAAwAABQigIDzQ+GVfCAAh+QQFCgAfACwEAAwABAADAAAFCeDBjZOAcSXxhQAh+QQFCgAfACwDAAwAAwAFAAAFDOB3fZ92GRD0CerHhAAh+QQJCgAfACwDAA4ABAAEAAAFDCBCad+HWOUnKGoZAgAh+QQFZAAfACwDAA4ABAAEAAAFDSBCaYQnWcDnJcWneiEAIfkEBQoAHwAsBAAJAAsACQAABSbgJ27bWIrf1nXien4dh4ockbVzbpL6m+aGD0EU0RlRgU9DlPyEAAAh+QQFCgAfACwEAAkACwAJAAAFJ+Andt1Yil+3beLKodsJZ5f4ojjKdRyWfzUUIcfStH5IVOSjEC0/IQAh+QQFCgAfACwEAAkACwAJAAAFJuAnbttYit/WdeJ6fh2HihyRtXNukvqb5oYPQRTRGVGBT0OU/IQAACH5BAUKAB8ALAQACQALAAkAAAUn4Cd23ViKX7dt4sqh2wlnl/iiOMp1HJZ/NRQhx9K0fkhU5KMQLT8hACH5BAUKAB8ALAQACQALAAkAAAUm4Cdu21iK39Z14np+HYeKHJG1c26S+pvmhg9BFNEZUYFPQ5T8hAAAIfkEBQoAHwAsBAAJAAsACQAABSfgJ3bdWIpft23iyqHbCWeX+KI4ynUcln81FCHH0rR+SFTkoxAtPyEAIfkEAQoAHwAsBAAJAAsACQAABSbgJ27bWIrf1nXien4dh4ockbVzbpL6m+aGD0EU0RlRgU9DlPyEAAA7',
'data:image/gif;base64,R0lGODlhFAAUAMQAAP/mIFVACPraECwkCPK6AP/iHPbOAPLCAEA0EOqqAG1QBOaZAPbKAKVtBP/aFO6yAFlACPrWDPrSBLKJAKp9BP/eGMqNAJllAN6yANaqAM6FALp1APbOBM6ZAAAAAAAAACH+HUJ1aWx0IHdpdGggR0lGIE1vdmllIEdlYXIgNC4wACH5BAkAAB4ALAAAAAAUABQAAAW1oCeOZGmaQaqeZaBMGXNklBKwXkBxkeBLDMLlZoJ0BI5BYTkQGA4WSGsiqAwAWGzTQKAQRQqJNUseSA4JxSgwiRS02WtBwHg0iAGM4A24wgEOXBZ4B3t/fViBBAuEhmRkdA+MInlij1kFEWgaeBMMjmRzXAl3IwoEBlUIq6xPCQtqaxSoCEgOAhEGCJKlJBAWBAcMBgYyBK8bUigXCQ8EBA8Jr0M4Lg0WCwsaDTY4ayop3uImIQA7',
'data:image/gif;base64,R0lGODlhFAAUAIQbAAAAAHRkOGBIFGxQBKx8ANisAPjMAPTAAGxIBHBcMJh0BPjYDP/cFOy4AP/gHLyEAPjUDP/kIOyoAKRoAPTMAOSYAHhkPOCQAGRMHDkgANCMAP///+igAJRkBFxIFP///yH5BAEAAB8ALAEAAQASABIAAAWx4CeOQSAI5aiSA1EYRkEgwSomirEwBm80isTKU9gxjsjfw0N67AAORyTCACwMB0Ltg+gxIoAp+PiTID6BB3IaDlN5kkkgUThK3eApnJPwNI55YmMMFA0XJzpfgmJHDRKHdAYAGYtsAAYSGiUEkhsAn6AAng0cclwNohuqq6oAj2doBAefrJ6uHB1bHx4PB6ihjhxLKwkdHBIHEsocFx1CNgEIExocwhM0NiolHh4JuiMhADs%3D',
'data:image/gif;base64,R0lGODlhFAAUAKUEAEA0EFVACP/iKNLu//////rWDPraJFlACG1MBP/iHPLGAHFVBLKNAO62AM6FAN6VAP/mIPbOBPLGHP/aEMKJAOKREO66HPbGAKVpAPrOAKVxBOaVEO6yGK6BBN6yAP/eGOaZAOalFKp1BJVhBNauAP/eFMqFAPbOINalAJlxBPK6ALp1AG1QBK6FAOqqAMqZAKVtAJlpBJ19BP///////////////////////////////////////////////////yH5BAEKAD8ALAAAAAAUABQAAAaxwJ9wSCwajYGk8lgMLBikiwLVYQWYv4AsUphMCpFLI3U1Hl6F0ifB/hQyKsqhySgI7vi74dQQlYULEQIAhHmEABIuCEMBDAYAeAAQEJACjw0aZQEeBnmeliccDpoKh6anhA+kAAQEA6yur7EAqkKbsLKzs6O2Lbi4sgOvMH8LFqy5wsgAFYuMHcfJsgAbGH9CBxQchK3K1CtzSDEhqBUj100IGg4gICYYCOhHSktY9kdBADs%3D',
'data:image/gif;base64,R0lGODlhFAAUAKUCAEA0EP/mIP/////iHFVACPraEPbKAEAUEFlACPK2AP/eFP/eGPrWCPK+APrWDPLCAG1MBHFVBO6yAN6VAO6qAKVxBOadAP/aFPbGAOqhAPrOBPLGAOqqAMKNAKVpBM6NAK6BBLKNALp1AKp1BMqlAJlpBO6uAOaZAJVhBJ19BK6JBN6uAOalAK6FAOKVAM6FANalAMqFAJlxBN62ANauAP///////////////////////////////////////////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFRgA/ACwAAAAAFAAUAAAG0cCfcEgsGo0EAiKJOBYRERUNs4GBIgTnj5DSFL4OhiEhaxoRJMdlMRgsFg5Do2MeIkIOdmAAAAT6YyN1PxEabAN/AgIAigAPHBBDBHhtiY2KixIVZggzBZV9jKGLCR9ZWxufiKOsCROnBA8OiHuhtgYSr0IEKwx6AcDBCgwYHC+nCC0GqsHAAwVjGRWnhA3Lh3tsCg8ULpGSINYMBQBfYg0AFh7UQggdCQ8GBrbpIoN2JSwSCQkSFBkTULArQgBChQ8WTsTwAGHgkSQQHWqZKCQIACH5BAUKAD8ALAcABQAJAAYAAAYiwN8vEBgUDAbhgFhUMH7LJbFwZDILCgNgCwhwAUNpgPoLAgAh+QQFHgA/ACwHAAUACQAGAAAGG8DfDwAYFoUAgSC5HCqXTyJxOT3+qEmkVPoLAgAh+QQFCgA/ACwGAAUACgALAAAGLcDfDwAIEA0GIUAgWDYZQ2ZTCjAQl9emldjkVofXa0FILpvPaHNhPR6a0hRhEAAh+QQFMgA/ACwEAAUACwALAAAGOMDf73AYBAKDgvAHEAiOx4GC6XwaA4dCoUnEdgsK7gF6LFyGXfJ0yR6224f3Mv4m0htsg97wwP+CACH5BAEKAD8ALAQABQALAAsAAAY1wN9vMAAAfkbhsCgQAJrHQeAJbTqJxmf2OjU6vQBiN5sUBs7ogHKtHLDbRHahoCgIj2yyMAgAOw%3D%3D',
'data:image/gif;base64,R0lGODlhFAAUAMQAAP/mIFVACEA0EPbKAP/iHPK2AP/aEOqqAG1QBOaZAKVxBPLGAK6FAPraDPrSCPK+AO6yAP/eFPrWDP/eGFlACMqNAM6FAN6yANaqAJltBLp1APbOBJllAM6ZAAAAAAAAACH+HUJ1aWx0IHdpdGggR0lGIE1vdmllIEdlYXIgNC4wACH5BAkAAB4ALAAAAAAUABQAAAW6oCeOZGmaQaqeZYAw2LJgDBKwXqBskmFIjkEhczNROo3IhMCMNAaPCqXFMEwA2CzhWVAURQhHhAAQZM0Tx+KAGAUYEnL5DNgOIF5R4GKQm7F/BkIVRQELfVmJWBFCCYULEn+KWAYCEI56Fw6SigSbBxaFDAMNnFgElQUHeWAPpAJyBBOVDwcJbW4MrpsCvb0Pl6xuFQULA8cDC6oJGlMoGQcQBQUQB7YcXygIChYJCRYKNjhuKinj5yYhADs%3D');

if (Page == 'messages'){
var tds = xpaths('//table[@width="519"]/tbody/tr/td[@colspan="3"][@class="b"]');
for (var i = tds.snapshotLength - 1; i >= 0; i--){
var td = tds.snapshotItem(i);
for (var j = 17; j >= 0; j--){
while (td.innerHTML.indexOf(smilt[j]) != -1){
td.innerHTML = td.innerHTML.replace(smilt[j],'<img src="'+smilg[j]+'" alt="'+[j]+'" />');}}}}
if (((Page == 'allianzen') && (document.location.href.search(/a=17/) != -1)) || (Page == 'writemessages') || (Page == 'buddy')){
var smiltbl = document.getElementById('cntChars').parentNode;
smiltbl.innerHTML += '<br /><br />';
var smilhld = smiltbl.insertBefore(document.createElement('span'), ((Page == 'writemessages') || (Page == 'buddy') ? smiltbl.nextSibling.nextSibling.nextSibling.nextSibling : smiltbl.nextSibling.nextSibling));
smilhld.setAttribute('style','white-space:nowrap');
for (var j = 17; j >= 0; j--){
smilhld.innerHTML += '<img src="'+smilg[j]+'" title="'+smilt[j]+'" alt="'+smilt[j]+'" onclick="sofMsg_InsSml(\' '+smilt[j]+' \')" style="cursor:pointer" /> ';
if ((j == 6) || (j == 12)) smilhld.innerHTML += '<br />';}}}

// ### OTHER ###

function sofSts_Diff(){
var self = xpath('//a[@href="#"][@style="color:lime;"]');
if (! self) return;
var poenitr = self.parentNode.parentNode.lastChild.previousSibling;
var poeni = DelCommas(poenitr.innerHTML);
if (poeni != 0){
var as = xpaths('//div[@id="content"]/center/table[@width="525"]/tbody/tr/th/a[contains(@onmouseover,"return overlib")]');
for (var i = as.snapshotLength - 1; i >= 0; i--){
var a = as.snapshotItem(i);
var tr = a.parentNode.parentNode;
var points = tr.childNodes[19].innerHTML;
var diff = AddCommas(DelCommas(points)-poeni);
tr.childNodes[7].innerHTML += '<br /><span style="color:orange;font-size:smaller">'+(diff > 0 ? '+'+diff : diff)+'</span>';}}}

//### DEFAULT SETTINGS ###

var soSetDef = new Array();
var soGen_MenuL = (typeof(soGen_MenuL) == 'undefined' ? new Array() : soGen_MenuL);
var soGal_AllC = (typeof(soGal_AllC) == 'undefined' ? new Array() : soGal_AllC);
var soFlt_Scs = (typeof(soFlt_Scs) == 'undefined' ? new Array() : soFlt_Scs);

//### GENERAL SETTINGS ###
soSetDef['soSet_OnOff'] = 1;
soSetDef['soLan_Ovr'] = '';
soSetDef['soGen_VerCh'] = 1;
soSetDef['soGen_TimeF'] = '%(%d/%m )%h:%n:%s';
soSetDef['soGen_TimeDiff'] = '';
soSetDef['soGen_notEnC'] = '#FFCC00';
soSetDef['soGen_Clock'] = 1;
soSetDef['soGen_ClockC'] = '#FFCC00';
soSetDef['soGen_ClockF'] = '%h:%n:%s';
soSetDef['soGen_ClockI'] = 0;
soSetDef['soGen_Moons'] = 1;
soSetDef['soGen_MoonsC'] = '#993322';
soSetDef['soGen_ChgButts'] = 1;
soSetDef['soGen_JumpGate'] = 1;
soSetDef['soGen_MLst'] = 1;
soSetDef['soGen_MLstSort1'] = 5;
soSetDef['soGen_MLstSort2'] = 0;
soSetDef['soGen_Banned'] = 1;
soSetDef['soGen_CircMes'] = 1;
soSetDef['soGen_TrdCal'] = 0;
soSetDef['soGen_TrdCalVPos'] = 15;
soSetDef['soGen_TrdCalHPos'] = 15;
soSetDef['soGen_TrdCalSwPos'] = 'L';

//### OVERVIEW MENU ###
soSetDef['soMen_Clock'] = 0;
soSetDef['soMen_ClockC'] = '#AAAA00';
soSetDef['soMen_Fields'] = 1;
soSetDef['soMen_ResOver'] = 1;
soSetDef['soMen_Enh'] = 1;

//### RESOURCES ###
soSetDef['soRes_ProdCalc'] = 1;
soSetDef['soRes_StStatus'] = 1;
soSetDef['soRes_ProdFact'] = 1;
soSetDef['soRes_SatEnerg'] = 1;

//### GALAXY ###
soSetDef['soGal_PlntLst'] = 1;
soSetDef['soGal_HlMoons'] = 1;
soSetDef['soGal_HlMoonsS'] = 8000;
soSetDef['soGal_HlMoonsC'] = '#993322';
soSetDef['soGal_DebrRemI'] = 0;
soSetDef['soGal_HlDebr'] = 1;
soSetDef['soGal_HlDebrS'] = 20000;
soSetDef['soGal_HlDebrC'] = '#229922';
soSetDef['soGal_DebrRecN'] = 1;
soSetDef['soGal_Ranks'] = 1;
soSetDef['soGal_RanksC'] = '#FFA500';
soSetDef['soGal_PrankC'] = 0;
soSetDef['soGal_PrankC1'] = '#FF4500';
soSetDef['soGal_PrankC2'] = '#FF7A00';
soSetDef['soGal_PrankC3'] = '#FFA500';
soSetDef['soGal_PrankC4'] = '#E3BA00';
soSetDef['soGal_PrankC5'] = '#CCCC00';
soSetDef['soGal_PrankC6'] = '#94B000';
soSetDef['soGal_PrankC7'] = '#669900';
soSetDef['soGal_PrankC8'] = '#386B00';
soSetDef['soGal_PhalStat'] = 1;
soSetDef['soGal_MissAtt'] = 1;
soSetDef['soGal_Compact'] = 0;

//### BUILD INFO ###
soSetDef['soBld_ResRem'] = 1;
soSetDef['soBld_ResRemC'] = '#FF0000';
soSetDef['soBld_CargRem'] = 1;
soSetDef['soBld_CargRemC'] = '#00FF00';
soSetDef['soBld_RemDesc'] = 1;
soSetDef['soBld_ResImg'] = 100;
soSetDef['soBld_ImpMsg'] = 1;
soSetDef['soBld_EndTime'] = 1;
soSetDef['soBld_EndTimeF'] = '%(%d/%m )%h:%n:%s';
soSetDef['soBld_EndTimeC'] = '#00FF00';
soSetDef['soBld_Range'] = 1;
soSetDef['soBld_Energ'] = 1;
soSetDef['soBld_InfDiff'] = 1;
soSetDef['soBld_JGRt'] = 1;
soSetDef['soBld_JGRtF'] = '%(%d/%m )%h:%n:%s';
soSetDef['soBld_JGRtC'] = '#00FF00';
soSetDef['soBld_ResPts'] = 1;

//### FLEET INFOS ###
soSetDef['soFlt_Cap'] = 1;
soSetDef['soFlt_CapC'] = '#FFD700';
soSetDef['soFlt_TransCalc'] = 1;
soSetDef['soFlt_CargNeedC'] = '#00FF00';
soSetDef['soFlt_ArrTime'] = 1;
soSetDef['soFlt_ArrTimeF'] = '%(%d/%m )%h:%n:%s';
soSetDef['soFlt_ArrTimeC'] = '#00FF00';
soSetDef['soFlt_CmBckTime'] = 1;
soSetDef['soFlt_CmBckTimeF'] = '%(%d/%m )%h:%n:%s';
soSetDef['soFlt_CmBckTimeC'] = '#00FF00';
soSetDef['soFlt_SndTime'] = 1;
soSetDef['soFlt_SndTimeF'] = '%(%d/%m )%h:%n:%s';
soSetDef['soFlt_SndTimeC'] = '#00FF00';
soSetDef['soFlt_ResC'] = '#FFD700';
soSetDef['soFlt_ExpShrt'] = 1;
soSetDef['soFlt_DefMis'] = 1;
soSetDef['soFlt_DefMisO'] = new Array(6,1,3,4,2,5,7);
soSetDef['soFlt_HoldCons'] = 1;
soSetDef['soFlt_Impr'] = 1;
soSetDef['soFlt_SpdSel'] = 3;
soSetDef['soFlt_DefCrds1'] = '';
soSetDef['soFlt_DefCrds2'] = '';
soSetDef['soFlt_DefCrds3'] = '';
soSetDef['soFlt_SubmFocus'] = 0;
soSetDef['soFlt_Moons'] = 1;

//### MESSAGES ###
soSetDef['soMsg_DelForm'] = 1;
soSetDef['soMsg_DelFormMM'] = 2;
soSetDef['soMsg_AllyReply'] = 1;
soSetDef['soMsg_Clear'] = 1;
soSetDef['soMsg_RepRes'] = 1;
soSetDef['soMsg_RepResC'] = 0;
soSetDef['soMsg_RepResCC'] = '#00FF00';
soSetDef['soMsg_RepEsp'] = 1;
soSetDef['soMsg_RepLnk'] = 1;
soSetDef['soMsg_SpyRepC'] = 0;
soSetDef['soMsg_MissRepC'] = 1;
soSetDef['soMsg_PrivC'] = '';
soSetDef['soMsg_PrivBgC'] = '#000080';
soSetDef['soMsg_AllyC'] = '';
soSetDef['soMsg_AllyBgC'] = '#006400';
soSetDef['soMsg_ActiveC'] = '';
soSetDef['soMsg_ActiveBgC'] = '#990000';
soSetDef['soMsg_PassiveC'] = '';
soSetDef['soMsg_PassiveBgC'] = '#808080';
soSetDef['soMsg_RecC'] = '';
soSetDef['soMsg_RecBgC'] = '#330066';
soSetDef['soMsg_ExpC'] = '';
soSetDef['soMsg_ExpBgC'] = '#550022';
soSetDef['soMsg_Sml'] = 1;
soSetDef['soMsg_Sgnt'] = '';
soSetDef['soMsg_SgntAll'] = 0;
soSetDef['soSet_OnOff'] = 1;
//### OTHER ###
soSetDef['soSts_Diff'] = 1;

//### END DEFAULT SETTINGS ###

// ### INIT ###

sofSys_overlibFix();
if ((Page == 'options') && (location.href.search(/so=setts/) == -1)) makeEvent(sofSys_Sys);
if ((sofGet('soSet_OnOff') > 0) && (opera.version() >= 10.50)){
if (Page == 'overview') makeEvent(sofSys_GetTime);
if ((Page == 'options') && (location.href.search(/so=setts/) != -1)) makeEvent(sofSys_Opt);
if ((Page != 'notizen') && (Page != 'phalanx') && (Page != 'bericht')) makeEvent(sofSys_Menu);
if ((Page != 'notizen') && (Page != 'phalanx') && (Page != 'bericht') && (sofGet('soGen_Clock') > 0)) makeEvent(sofGen_Men);
if ((Page != 'notizen') && (Page != 'phalanx') && (Page != 'bericht') && (sofGet('soGen_TrdCal') > 0)) makeEvent(sofGen_TrdCal);
if ((Page != 'notizen') && (Page != 'phalanx') && (Page != 'bericht')) makeEvent(sofGen_Gen);
if ((sofGet('soMen_Clock') > 0) && (Page == 'overview')) makeEvent(sofMen_Clock);
if ((sofGet('soMen_Fields') > 0) && (Page == 'overview')) makeEvent(sofMen_Fields);
if ((sofGet('soMen_ResOver') > 0) && (Page == 'overview')) makeEvent(sofMen_ResOver);
if ((sofGet('soMen_Enh') > 0) && (Page == 'overview')) makeEvent(sofMen_Enh);
if(((sofGet('soRes_ProdCalc') > 0) || (sofGet('soRes_StStatus') > 0) || (sofGet('soRes_ProdFact') > 0) || (sofGet('soRes_SatEnerg') > 0)) && (Page == 'resources')) makeEvent(sofRes_Gen);
if (sofGet('soGal_PlntLst') > 0){
if ((Page != 'galaxy') && (Page != 'phalanx') && (Page != 'notizen') && (Page != 'bericht')) makeEvent(sofSys_PlntCrds);
if (Page == 'galaxy') makeEvent(sofGal_PlntLst);}
if (((sofGet('soGal_HlMoons') > 0) || (sofGet('soGal_HlDebr') > 0) || (sofGet('soGal_DebrRemI') > 0) || (sofGet('soGal_DebrRecN') > 0)) && (Page == 'galaxy')) makeEvent(sofGal_Hl);
if ((soGal_AllC.length > 0) && (Page == 'galaxy')) makeEvent(sofGal_AllC);
if ((sofGet('soGal_Ranks') > 0) && (Page == 'galaxy')) makeEvent(sofGal_Ranks);
if ((sofGet('soGal_PhalStat') > 0) && (Page == 'galaxy')) makeEvent(sofGal_PhalStat);
if ((sofGet('soGal_MissAtt') > 0) && (Page == 'galaxy') && (Mode == '1')) makeEvent(sofGal_MissAtt);
if ((sofGet('soGal_Compact') > 0) && (Page == 'galaxy')) makeEvent(sofGal_Compact);
if (Page == 'galaxy') makeEvent(sofGal_galaxyFix);
if ((sofGet('soBld_EndTime') > 0) && ((Page.indexOf('building') != -1) || (Page == 'overview'))) makeEvent(sofBld_EndTime);
if ((Page.indexOf('building') != -1) && ((sofGet('soBld_ResRem') > 0) || (sofGet('soBld_Range') > 0) || (sofGet('soBld_RemDesc') > 0) || (sofGet('soBld_ResImg') >= 0) || ((sofGet('soBld_ResPts') > 0) && (Mode == 'Forschung')))) makeEvent(sofBld_Bld);
if ((sofGet('soBld_InfDiff') > 0) && (Page == 'infos') && (location.href.search(/gid=42/) != -1)) makeEvent(sofBld_InfDiff);
if ((sofGet('soBld_JGRt') > 0) && (Page == 'infos') && (location.href.search(/gid=43/) != -1)) makeEvent(soBld_JmpGtR);
if ((sofGet('soFlt_ArrTime') > 0) && ((Page == 'overview') || (Page == 'phalanx'))) makeEvent(sofFlt_ArrTime);
if ((sofGet('soFlt_CmBckTime') > 0) && (Page == 'flotten1')) makeEvent(sofFlt_CmBckTime);
if ((sofGet('soFlt_Cap') > 0) && (Page == 'flotten1')) makeEvent(sofFlt_Cap);
if ((sofGet('soFlt_SndTime') > 0) && ((Page == 'flotten2') || (Page == 'flotten3'))) makeEvent(sofFlt_SndTime);
if ((sofGet('soFlt_TransCalc') > 0) && (Page == 'flotten1')) makeEvent(sofFlt_TransCalc);
if ((sofGet('soFlt_Impr') > 0) && (Page == 'flotten1')) makeEvent(sofFlt_Fl1);
if (((sofGet('soFlt_ExpShrt') > 0) || (sofGet('soFlt_Impr') > 0) || (sofGet('soFlt_SpdSel') > 0) || (soFlt_Scs.length > 0) || (sofGet('soFlt_DefCrds1') != '' && sofGet('soFlt_DefCrds2') != '' && sofGet('soFlt_DefCrds3') != '') || (sofGet('soFlt_SubmFocus') > 0) || (sofGet('soFlt_Moons') > 0)) && (Page == 'flotten2')) makeEvent(sofFlt_Fl2);
if (((sofGet('soFlt_HoldCons') > 0) || (sofGet('sofFlt_Impr') > 0) || (sofGet('soFlt_DefMis') > 0) || (sofGet('soFlt_SubmFocus') > 0)) && (Page == 'flotten3')) makeEvent(sofFlt_Fl3);
if (((sofGet('soMsg_RepRes') > 0) || (sofGet('soMsg_RepEsp') > 0) || (sofGet('soMsg_RepLnk') > 0)) && (Page == 'messages')) makeEvent(sofMsg_SpyRep);
if (Page == 'messages') makeEvent(sofMsg_MsgsC);
if ((sofGet('soMsg_DelForm') > 0) && (Page == 'messages')) makeEvent(sofMsg_DelForm);
if ((sofGet('soMsg_Sml') > 0) && ((((Page == 'allianzen') && (location.href.search(/a=17/) != -1)) || (Page == 'writemessages') || ((Page == 'buddy') &&  (location.href.search(/action=7/) != -1))) || (Page == 'messages'))) makeEvent(sofMsg_Sml);
if (((sofGet('soMsg_Sgnt') != '') && (Page == 'writemessages')) || (((sofGet('soMsg_Sgnt') != '') && (sofGet('soMsg_SgntAll') > 0)) && ((Page == 'allianzen') && (location.href.search(/a=17/) != -1)))) makeEvent(sofMsg_Sgnt);
if((sofGet('soSts_Diff') > 0) && (Page == 'statistics')) makeEvent(sofSts_Diff);}
makeEvent(sofGen_LTime);