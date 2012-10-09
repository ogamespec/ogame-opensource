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
  } else {
    e.childNodes[0].data = window.document.forms[0].text.value.length;
  }
}

function fenster(target_url,win_name) {
  var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar=no,top=0,left=0,toolbar=no,width=550,height=280,resizable=yes');
  new_win.focus();
}

function fenstered(target_url,win_name) {
  var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar=no,top=0,left=0,toolbar=no,width=550,height=280,resizable=yes');
  new_win.focus();
}

function haha(z1) {
  eval("location='"+z1.options[z1.selectedIndex].value+"'");
}

function link_to_gamepay() {
  document.getElementsByName("lang");
  document.getElementsByName("name");
  document.getElementsByName("playerid");
  document.getElementsByName("checksum");
  document.getElementsByName("session");
}

function showGalaxy(galaxy, system, planet) {
    if(typeof window.opener == "undefined" || window.opener == null) {
        document.location.href="index.php?page=galaxy&no_header=1&galaxy=" + galaxy + "&system=" + system + "&planet=" + planet + "&session=" + session;
    } else {
        window.opener.document.location.href="index.php?page=galaxy&no_header=1&galaxy=" + galaxy + "&system=" + system + "&planet=" + planet + "&session=" + session;
    } 
}

function showRenamePlanet(planetID) {
    if(typeof window.opener == "undefined" || window.opener == null) {
        document.location.href="index.php?page=renameplanet&session=" + session + "&pl=" + planetID;
    } else {
        window.opener.document.location.href="index.php?page=renameplanet&session=" + session + "&pl=" + planetID;
    } 
}

function showFleetMenu(galaxy, system, planet, planettype, missiontype) {
    if(typeof window.opener == "undefined" || window.opener == null) {
        document.location.href="index.php?page=flotten1&session=" + session + "&galaxy=" + galaxy + "&system=" + system + "&planet=" + planet + "&planettype=" + planettype + "&target_mission=" + missiontype;
    } else {
        window.opener.document.location.href="index.php?page=flotten1&session=" + session + "&galaxy=" + galaxy + "&system=" + system + "&planet=" + planet + "&planettype=" + planettype + "&target_mission=" + missiontype;
    } 
}

function showMessageMenu(targetID) {
    if(typeof window.opener == "undefined" || window.opener == null) {
        document.location.href="index.php?page=writemessages&session=" + session + "&messageziel=" + targetID;
    } else {
        window.opener.document.location.href="index.php?page=writemessages&session=" + session + "&messageziel=" + targetID;
    } 
}