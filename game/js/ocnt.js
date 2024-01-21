function t(){
  n = new Date();
  for (cn = 1; cn <= anz; cn++) {
    bxx = document.getElementById('bxx'+cn);
    if (!('dpp' in bxx)) {
      bxx.dpp = n.getTime() + bxx.title * 1000;
    }
    s = Math.round((bxx.dpp - n.getTime()) / 1000.);
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
  }
  window.setTimeout("t();", 999);
}
