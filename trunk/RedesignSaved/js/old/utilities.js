function t(){
    v = new Date();
    n = new Date();
    o = new Date();
    for (cn = 1; cn <= anz; cn++) {
        bxx = document.getElementById('bxx' + cn);
        ss = bxx.title;
        s = ss - Math.round((n.getTime() - v.getTime()) / 1000.);
        m = 0;
        h = 0;
        if (s < 0) {
            bxx.innerHTML = "-";
        } else {
            if (s > 59) {
                m = Math.floor(s/60);
                s = s - m * 60;
            }
            if (m > 59) {
                h = Math.floor(m / 60);
                m = m - h * 60;
            }
            if (s < 10) {
                s = "0" + s;
            }
            if (m < 10) {
                m = "0" + m;
            }
            bxx.innerHTML = h + ":" + m + ":" + s + "";
        }
        bxx.title = bxx.title - 1;
    }
    window.setTimeout("t();", 999);
}

var x = "";
var e = null;

function cntchar(m) {
    if (window.document.forms[0].text.value.length > m) {
        window.document.forms[0].text.value = x;
    } else {
        x = window.document.forms[0].text.value;
    }

    if (e == null) {
        e = document.getElementById('cntChars');
        e.childNodes[0].data = window.document.forms[0].text.value.length;
    } else {
        e.childNodes[0].data = window.document.forms[0].text.value.length;
    }
}

function popupWindow(target_url,win_name,scrollbars,menubar,top,left,toolbar,width,height,resizable) {
    var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar='+menubar+',top='+top+',left='+left+',toolbar='+toolbar+',width='+width+',height='+height+',resizable='+resizable+'');
    new_win.focus();
}

function fenster(target_url,win_name) {
    var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar=no,top=0,left=0,toolbar=no,width=550,height=280,resizable=yes');
    new_win.focus();
}

function fenstered(target_url,win_name) {
    var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar=no,top=0,left=0,toolbar=no,width=550,height=280,resizable=yes');
    new_win.focus();
}

function tb_open(url)
{
    tb_show(null,url,false);
}

function tb_remove_openNew(url)
{
    tb_remove();
    setTimeout("tb_open_new('"+url+"')", 500);
}

function tb_initialize()
{
    tb_init('a.thickbox, area.thickbox, input.thickbox, a.ajax_thickbox');//pass where to apply thickbox
    imgLoader = new Image();// preload image
    imgLoader.src = tb_pathToImage;
}

function tb_is_open()
{
    return TB_open;
}

function tb_remove_iframe(e){
    e=!e?event:e;
    tastenCode=e.keyCode?e.keyCode:e.which;
    if(tastenCode==27)self.parent.tb_remove();
}

function link_to_gamepay() {
    document.getElementsByName("lang");
    document.getElementsByName("name");
    document.getElementsByName("playerid");
    document.getElementsByName("checksum");
    document.getElementsByName("session");
}

function getSession() {
    return $('meta[name="ogame-session"]').attr('content');
}

function showGalaxy(galaxy, system, planet) {
    openParentLocation("index.php?page=galaxy&no_header=1&galaxy=" + galaxy + "&system=" + system + "&planet=" + planet + "&session=" + getSession());
}

function showRenamePlanet(planetID) {
    openParentLocation("index.php?page=renameplanet&session=" + getSession() + "&pl=" + planetID);
}

function showFleetMenu(galaxy, system, planet, planettype, missiontype) {
    openParentLocation("index.php?page=flotten1&session=" + getSession() + "&galaxy=" + galaxy + "&system=" + system + "&planet=" + planet + "&planettype=" + planettype + "&target_mission=" + missiontype);
}

function showMessageMenu(targetID) {
    openParentLocation("index.php?page=writemessages&session=" + getSession() + "&messageziel=" + targetID);
}

function openParentLocation(url) {
    try {
        window.opener.document.location.href=url;
    } catch (error) {
        try {
            window.parent.document.location.href=url;
        } catch (error) {
            document.location.href=url;
        }
    }
}

function submitOnEnter(ev)
{
    var keyCode;

    if(window.event)
    {
        keyCode = window.event.keyCode;
    }
    else if(ev)
    {
        keyCode = ev.which;
    }
    else
    {
        return true;
    }

    if(keyCode == 13)
    {
        trySubmit();
        return false;
    }
    else
    {
        return true;
    }
}

function formSubmitOnEnter(form, ev)
{
    var keyCode;

    if (window.event) {
        keyCode = window.event.keyCode;
    } else if (ev) {
        keyCode = ev.which;
    } else {
        return true;
    }
    
    if (keyCode == 13) {
        document.forms[form].submit();
        return false;
    } else {
        return true;
    }
}

function show_hide_tr(ele)
{
    if (document.getElementById(ele).style.display == "none")
    {
        document.getElementById(ele).style.display = "block";
    }
    else 
    {
        document.getElementById(ele).style.display = "none";
    }
} 
 
function setMaxIntInput(data)
{
    for(var techID in data) {
        if (!$("#ship_"+techID).attr("disabled")) {
            $("#ship_"+techID).val(data[techID]); 
            checkIntInput("ship_"+techID, 0, data[techID]);
        }
    }
}

function clearInput(id)
{
    $("#"+id).val(""); 
}

function setTemplate(dataTpl, dataMax)
{
    for(var techID in dataTpl) {
    
        if (!$("#ship_"+techID).attr("disabled")) {
            $("#ship_"+techID).val(dataTpl[techID]);
        }

        if (typeof dataMax[techID] == "undefined") {
            dataMax[techID] = 0;
        }
        
        checkIntInput("ship_"+techID, 0, dataMax[techID]);
    }
}

function checkIntInput(id, minVal, maxVal)
{
    value = $("#"+id).val();

    if (typeof value != "undefined" && value != "") {
        intVal = trimInteger(value);
        intVal = parseInt(value);
        intVal = (isNaN(intVal) || intVal == 0) ? minVal : intVal;
        intVal = Math.abs(intVal);
        
        if (maxVal != null) {
            intVal = Math.min(intVal, maxVal); 
        }   
        
        $("#"+id).val(intVal);
    }
}

function handlerToSubmitAjaxForm(form)
{
    var submitFunction = "submit_" + String(form);

    if ($.isFunction(window[submitFunction])) {
        window[submitFunction]();
    }

    return false;
}

function ajaxCall(link, targetHTMLObj)
{
    $("#" + targetHTMLObj).html('<p id=\"ajaxLoad\"></p>');
    $.post(link, function(data){
        $("#" + targetHTMLObj).html(data);
    });
}

function ajaxSumbit(url, form, targetHTMLObj)
{
    $("#" + targetHTMLObj).html("<p id=\"ajaxLoad\"><?=LOCA_ALL_AJAXLOAD ?></p>");
    $.post(url, $('#' + form).serialize(),
        function(data) {
            $('#' + targetHTMLObj).html(data);
        }
        )
}

jQuery.fn.selectText = function() {
    var obj = this[0];
    if ($.browser.msie) {
        var range = obj.offsetParent.createTextRange();
        range.moveToElementText(obj);
        range.select();
    } else if ($.browser.mozilla || $.browser.opera) {
        var selection = obj.ownerDocument.defaultView.getSelection();
        var range = obj.ownerDocument.createRange();
        range.selectNodeContents(obj);
        selection.removeAllRanges();
        selection.addRange(range);
    } else if ($.browser.safari) {
        var selection = obj.ownerDocument.defaultView.getSelection();
        selection.setBaseAndExtent(obj, 0, obj, 1);
    }
    return this;
}