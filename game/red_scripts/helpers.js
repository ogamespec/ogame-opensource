/**
 * getElementById-Funktion mit Cache
 */
var DOM_GET_ELEMENT_BY_ID_CACHE = new Array();
function getElementByIdWithCache(uid) {
    if (!DOM_GET_ELEMENT_BY_ID_CACHE[uid]) {
        DOM_GET_ELEMENT_BY_ID_CACHE[uid] = document.getElementById(uid);
    }
    return DOM_GET_ELEMENT_BY_ID_CACHE[uid];
}

/**
 * Event-Handler zufuegen
 * Beispiel: addListener(window, "load", function() { alert('document loaded'); });
 */
function addListener( obj, type, fn )
{
    if (obj.addEventListener) {
        if (type=='mousewheel') {
            type = 'DOMMouseScroll';
        } 
        obj.addEventListener( type, fn, false );
    } else if (obj.attachEvent) {
        obj["e"+type+fn] = fn;
        obj[type+fn] = function() { 
            obj["e"+type+fn]( window.event ); 
        };
        obj.attachEvent('on'+type, obj[type+fn] );
    }
}

/**
 * Event-Handler entfernen
 */
function removeListener( obj, type, fn ) {
    if (obj.removeEventListener) {
        obj.removeEventListener( type, fn, false );
    } else if (obj.detachEvent) {
        obj.detachEvent( "on"+type, obj[type+fn] );
        obj[type+fn] = null;
        obj["e"+type+fn] = null;
    }
}

function addClass(obj, cName) {
    if (obj && cName && cName != 'undefined') {
        removeClass(obj, cName);
        obj.className+= ' '+ cName;
    }
}

function removeClass(obj, cName) {
    if (obj && obj.className) {
        obj.className = obj.className.replace(cName, '');
    }
}


// alle, die einen beliebigen Klassennamen definiert haben
function  getAllChildNodesWithClassName(obj, childNodesWithClassName) {
    if (!childNodesWithClassName) {
        var childNodesWithClassName = new Array();
    }
    var i = 0;
    if (obj.childNodes) {
        for(i in obj.childNodes) {
            if (obj.childNodes[i].className) {
                childNodesWithClassName.push(obj.childNodes[i]);
            }
            if (obj.childNodes[i].firstChild) {
                childNodesWithClassName.concat(getAllChildNodesWithClassName(obj.childNodes[i], childNodesWithClassName)); 
            }
        }
    }
    return childNodesWithClassName;
}

function hasClassName(obj, needle) {
    if (obj.className && needle) {
        var haystack = obj.className;
        return (haystack==needle
            || haystack.indexOf(" "+ needle +" ")>=0
            || haystack.indexOf(needle + " ")==0
            || ( haystack.indexOf(" " + needle) >0 && haystack.indexOf(" " + needle) ==  haystack.length - ((" " + needle).length)) 
            );
    } else {
        return false;
    }
} 

// alle mit einem bestimmten Klassenname
function  getChildNodesWithClassName(obj, className, childNodesWithClassName) {
    if (obj.getElementsByClassName) {
        // using html5 function
        return obj.getElementsByClassName(className);
    }
    
    if (!childNodesWithClassName) {
        var childNodesWithClassName = new Array();
    }
    var i = 0;
    if (obj.childNodes) {
        for(i in obj.childNodes) {
            if (obj.childNodes[i] && obj.childNodes[i].className && hasClassName(obj.childNodes[i], className)) {
                childNodesWithClassName.push(obj.childNodes[i]);
            }
            if (obj.childNodes[i] && obj.childNodes[i].firstChild) {
                childNodesWithClassName.concat(getChildNodesWithClassName(obj.childNodes[i], className, childNodesWithClassName)); 
            }
        }
    }
    return childNodesWithClassName;
}
// erstes Objekt mit Klassennamen
function  getChildNodeWithClassName(obj, className) {
    if (obj.childNodes) {
        for(i in obj.childNodes) {
            if (hasClassName(obj.childNodes[i], className)) {
                return obj.childNodes[i];
            } else if (obj.childNodes[i].firstChild) {
                var node = getChildNodeWithClassName(obj.childNodes[i], className);
                if (node) {
                    return node;
                } 
            }
        }
    }
    return false;
}

// alle mit einem bestimmten Klassenname
function  getChildNodesWithTagName(obj, tagName, childNodesWithTagName) {
    if (!childNodesWithTagName) {
        var childNodesWithTagName = new Array();
    }
    var i = 0;
    if (obj.childNodes) {
        for(i in obj.childNodes) {
            //alert(obj.childNodes[i].tagName);
            if (obj.childNodes[i].tagName && obj.childNodes[i].tagName==tagName.toUpperCase()) {
                childNodesWithTagName.push(obj.childNodes[i]);
            }
            if (obj.childNodes[i].firstChild) {
                childNodesWithTagName.concat(getChildNodesWithTagName(obj.childNodes[i], tagName, childNodesWithTagName)); 
            }
        }
    }
    return childNodesWithTagName;
}

// macht aus "min=1 max=99" zB. aus dem className einen array('min'=>1, 'max'=>99)
function splitParameterStringToArray(str) {
    var arr = new Object();
    var vars = str.split(" ");
    for (var i in vars) { 
        var pair = vars[i].split("=");
        if (pair[0]) {
            arr[pair[0]] = pair[1]; 
        }
    }
    return arr;
}


/**
 *  returns a formated number like php number_format()
 *
 * @see http://de3.php.net/number_format
 */
function number_format (number, decimals, dec_point, thousands_sep)
{
    dec_point      = LocalizationStrings['decimalPoint'];
    thousands_sep  = LocalizationStrings['thousandSeperator'];

    var exponent = "";
    var numberstr = number.toString ();
    var eindex = numberstr.indexOf ("e");
    if (eindex > -1)
    {
        exponent = numberstr.substring (eindex);
        number = parseFloat (numberstr.substring (0, eindex));
    }

    if (decimals != null)
    {
        var temp = Math.pow (10, decimals);
        number = Math.round (number * temp) / temp;
    }
    var sign = number < 0 ? "-" : "";
    var integer = (number > 0 ?
        Math.floor (number) : Math.abs (Math.ceil (number))).toString ();

    var fractional = number.toString ().substring (integer.length + sign.length);
    dec_point = dec_point != null ? dec_point : ".";
    fractional = decimals != null && decimals > 0 || fractional.length > 1 ?
    (dec_point + fractional.substring (1)) : "";
    if (decimals != null && decimals > 0)
    {
        for (i = fractional.length - 1, z = decimals; i < z; ++i)
            fractional += "0";
    }

    thousands_sep = (thousands_sep != dec_point || fractional.length == 0) ?
    thousands_sep : null;
    if (thousands_sep != null && thousands_sep != "")
    {
        for (i = integer.length - 3; i > 0; i -= 3)
            integer = integer.substring (0 , i) + thousands_sep + integer.substring (i);
    }

    return sign + integer + fractional + exponent;
}

function gfNumberGetHumanReadable(value)
{
    value = Math.floor(value);
    var unit = '';
    var precision = 3;
    
    //if (value >= 1000000000) {
    //    unit = LocalizationStrings['unitMilliard'];
    //    value = value / 1000000000;
    //}
    
    //if (value >= 1000000) {
    //    unit = LocalizationStrings['unitMega'];
    //    value = value / 1000000;
    //}
    
    //if (value >= 1000) {
    //    unit = LocalizationStrings['unitKilo'];
    //    value = value / 1000;
    //}
    
    floorWithPrecision = function(value, precision) {
        return Math.floor(value * Math.pow(10, precision)) / Math.pow(10, precision);
    }
    
    value = floorWithPrecision(value, precision);
    
    while (precision >= 0) {
        if (floorWithPrecision(value, precision - 1) != value) {
            break;
        }
        precision = precision - 1;
    }
    
    return number_format(
        value, 
        precision, 
        LocalizationStrings['decimalPoint'], 
        LocalizationStrings['thousandSeperator']
        ) + unit;
}

/**
 * adds prefix digits to a number ('2'->'02')
 *
 * @param int   number
 * @param int   digits
 * @param str   prefix, default is '0'
 */
function dezInt(num,size,prefix) {
    prefix=(prefix)?prefix:"0";
    var minus=(num<0)?"-":"",
    result=(prefix=="0")?minus:"";
    num=Math.abs(parseInt(num,10));
    size-=(""+num).length;
    for(var i=1;i<=size;i++) {
        result+=""+prefix;
    }
    result+=((prefix!="0")?minus:"")+num;
    return result;
}

/**
*   Sends an ajax post request, dont waits for answer
**/
function ajaxSendUrl(aUrl) {
    var xmlHttp = null;
    // Mozilla, Opera, Safari, IE7
    if (typeof XMLHttpRequest != 'undefined') {
        xmlHttp = new XMLHttpRequest();
    }
    if (!xmlHttp) {
        // IE < 7
        try {
            xmlHttp  = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(e) {
            try {
                xmlHttp  = new ActiveXObject("Microsoft.XMLHTTP");
            } catch(e) {
                xmlHttp  = null;
            }
        }
    }
    if (xmlHttp) {
        xmlHttp.open('POST', aUrl, true);
        xmlHttp.onreadystatechange = function () {
            if (xmlHttp.readyState == 4) {
        //alert(xmlHttp.responseText);
        }
        };
        xmlHttp.send(null);
    }
}

/**
* Ajax Anfrage
*
* @param: string    URL incl. Parametern wie GET-URLs
* @param: function  diese Funktion wird bei Erfolg ausgef�hrt (mit new aFunction(xmlHttp.responseText))
**/
function ajaxRequest(aUrl, aFunction) {
    var xmlHttp = null;
    // Mozilla, Opera, Safari, IE7
    if (typeof XMLHttpRequest != 'undefined') {
        xmlHttp = new XMLHttpRequest();
    }
    if (!xmlHttp) {
        // IE < 7
        try {
            xmlHttp  = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(e) {
            try {
                xmlHttp  = new ActiveXObject("Microsoft.XMLHTTP");
            } catch(e) {
                xmlHttp  = null;
            }
        }
    }
    if (xmlHttp) {
        xmlHttp.open('POST', aUrl, true);
        xmlHttp.onreadystatechange = function () {
            if (xmlHttp.readyState == 4) {
                var tmp = new aFunction(xmlHttp.responseText);
            }
        };
        xmlHttp.send(null);
    }
}
