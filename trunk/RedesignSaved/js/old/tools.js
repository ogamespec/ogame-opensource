function tsdpkt(f) 
{
    r = ""; 
    vz = ""; 
    if (f < 0) 
    { 
        vz = "-"; 
    }
    f = Math.abs(f);
    r = f % 1000;
    while (f >= 1000)
    { 
        k1 = "";
        if ((f % 1000) < 100) 
        {
            k1 = "0"; 
        } 
        if ((f % 1000) < 10) 
        { 
            k1 = "00"; 
        } 
        if ((f % 1000) == 0) 
        { 
            k1 = "00"; 
        } 
        f = Math.abs((f-(f % 1000)) / 1000); 
        r = f % 1000 + LocalizationStrings['thousandSeperator'] + k1 + r;
    } 
    r = vz + r;
    return r; 
}

function formatTime(seconds)
{
    var hours = Math.floor(seconds / 3600);
    seconds -= hours * 3600;

    var minutes = Math.floor(seconds / 60);
    seconds -= minutes * 60;

    if (minutes < 10) minutes = "0" + minutes;
    if (seconds < 10) seconds = "0" + seconds;

    return hours + ":" + minutes  + ":" + seconds;
}

function trimInteger(value)
{
    value = value.replace(/^\s+|\s+$/g,'');

    withoutZero =  value.replace(/^0+/g,"");

    if(withoutZero==""&&value!="")
    {
        return 0;
    }
    else
    {
        return withoutZero;
    }
}

function round(x, n) {
    if (n < 1 || n > 14) return false;
    var e = Math.pow(10, n);
    var k = (Math.round(x * e) / e).toString();
    if (k.indexOf('.') == -1) k += '.';
    k += e.toString().substring(1);
    return k.substring(0, k.indexOf('.') + n+1);
}

function show_hide_menus(element)    
{
    if(document.getElementById(element).style.display == "block")
    {
        document.getElementById(element).style.display = "none";
    } 
    else    
    {
        document.getElementById(element).style.display = "block";
    }
} 

function change_class(ele)
{
    if (document.getElementById(ele).className == "closed")
    {
        document.getElementById(ele).className = "opened";
    }
    else
    {
        document.getElementById(ele).className = "closed";
    }
}

function show_hide_tbl(id)
{
    var el= document.getElementById(id);
    try
    {
        if(el) el.style.display= (el.style.display == "none" ? "table-row" : "none");
    }
    catch(e)
    { 
        // Der IE bis V7 kann kein table-row, deshalb Fallback auf 'Block'
        el.style.display= "block";
    }
}