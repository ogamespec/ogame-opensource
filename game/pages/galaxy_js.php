
  <script  language="JavaScript">
  function galaxy_submit(value) {
      document.getElementById('auto').name = value;
      document.getElementById('galaxy_form').submit();
  }

  function fenster(target_url,win_name) {
  var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar=no,top=0,left=0,toolbar=no,width=550,height=280,resizable=yes');
  new_win.focus();
  }


  var IE = document.all?true:false;

  function mouseX(e){
    if (IE) { // grab the x-y pos.s if browser is IE
        return event.clientX + document.body.scrollLeft;
    } else {
        return e.pageX
    }
  }
  function mouseY(e) {
    if (IE) { // grab the x-y pos.s if browser is IE
        return event.clientY + document.body.scrollTop;
    }else {
        return e.pageY;
    }
  }

  </script>
  <script language="JavaScript" src="js/tw-sack.js"></script>
  <script type="text/javascript">
  var ajax = new sack();
  var strInfo = "";

  function whenLoading(){
      //var e = document.getElementById('fleetstatus');
      //e.innerHTML = "<?php echo loca("GALAXY_AJAX_LOADING");?>";
  }

  function whenLoaded(){
      //    var e = document.getElementById('fleetstatus');
      // e.innerHTML = "<?php echo loca("GALAXY_AJAX_LOADED");?>";
  }

  function whenInteractive(){
      //var e = document.getElementById('fleetstatus');
      // e.innerHTML = "<?php echo loca("GALAXY_AJAX_INTERACT");?>";
  }

  /*
  We can overwrite functions of the sack object easily. :-)
  This function will replace the sack internal function runResponse(),
  which normally evaluates the xml return value via eval(this.response).
  */
  function whenResponse(){

    //alert (this.response);

      /*
      *
      *  600   OK
      *  601   no planet exists there
      *  602   no moon exists there
      *  603   player is in noob protection
      *  604   player is too strong
      *  605   player is in u-mode
      *  610   not enough espionage probes, sending x (parameter is the second return value)
      *  611   no espionage probes, nothing send
      *  612   no fleet slots free, nothing send
      *  613   not enough deuterium to send a probe
      *
      */
      // the first three digit long return value
      retVals = this.response.split(" ");
      // and the other content of the response
      // but since we only got it if we can send some but not all probes
      // theres no need to complicate things with better parsing

      // each case gets a different table entry, no language file used :P
      switch(parseInt(retVals[0])) {
          case 600:
          addToTable("done", "success");
                    changeSlots(retVals[1]);
          setShips("probes", retVals[2]);
          setShips("recyclers", retVals[3]);
          setShips("missiles", retVals[4]);
                    break;
          case 601:
          addToTable("<?php echo loca("GALAXY_ERROR_601");?>", "error");
          break;
          case 602:
          addToTable("<?php echo loca("GALAXY_ERROR_602");?>", "error");
          break;
          case 603:
          addToTable("<?php echo loca("GALAXY_ERROR_603");?>", "error");
          break;
          case 604:
          addToTable("<?php echo loca("GALAXY_ERROR_604");?>", "error");
          break;
          case 605:
          addToTable("<?php echo loca("GALAXY_ERROR_605");?>", "vacation");
          break;
          case 610:
          addToTable("<?php echo va(loca("GALAXY_ERROR_610"), "\"+retVals[1]+\"");?>", "notice");
          break;
          case 611:
          addToTable("<?php echo loca("GALAXY_ERROR_611");?>", "error");
          break;
          case 612:
          addToTable("<?php echo loca("GALAXY_ERROR_612");?>", "error");
          break;
          case 613:
          addToTable("<?php echo loca("GALAXY_ERROR_613");?>", "error");
          break;
          case 614:
          addToTable("<?php echo loca("GALAXY_ERROR_614");?>", "error");
          break;
          case 615:
          addToTable("<?php echo loca("GALAXY_ERROR_615");?>", "error");
          break;
          case 616:
          addToTable("<?php echo loca("GALAXY_ERROR_616");?>", "error");
          break;
      }
  }

  function doit(order, galaxy, system, planet, planettype, shipcount){
      strInfo = "<?=loca("GALAXY_DOIT_SEND");?>"+shipcount+"<?=loca("GALAXY_DOIT_SHIPS");?>"+(shipcount>1?"<?=loca("GALAXY_DOIT_MANY");?>":"<?=loca("GALAXY_DOIT_ONE");?>")+"<?=loca("GALAXY_DOIT_TO");?>"+galaxy+":"+system+":"+planet+"<?=loca("GALAXY_DOIT_END");?>";
      ajax.requestFile = "index.php?ajax=1&page=flottenversand&session=<?=$session;?>";

      // no longer needed, since we don't want to write the cryptic
      // response somewhere into the output html
      //ajax.element = 'fleetstatus';
      //ajax.onLoading = whenLoading;
      //ajax.onLoaded = whenLoaded;
      //ajax.onInteractive = whenInteractive;

      // added, overwrite the function runResponse with our own and
      // turn on its execute flag
      ajax.runResponse = whenResponse;
      ajax.execute = true;

      ajax.setVar("session", "<?=$session;?>");
      ajax.setVar("order", order);
      ajax.setVar("galaxy", galaxy);
      ajax.setVar("system", system);
      ajax.setVar("planet", planet);
      ajax.setVar("planettype", planettype);
      ajax.setVar("shipcount", shipcount);
      ajax.setVar("speed", 10);
      ajax.setVar("reply", "short");
      ajax.runAJAX();
  }

  /*
  * This function will manage the table we use to output up to three lines of
  * actions the user did. If there is no action, the tr with id 'fleetstatusrow'
  * will be hidden (display: none;) - if we want to output a line, its display
  * value is cleaned and therefore its visible. If there are more than 2 lines
  * we want to remove the first row to restrict the history to not more than
  * 3 entries. After using the object function of the table we fill the newly
  * created row with text. Let the browser do the parsing work. :D
  */
  function addToTable(strDataResult, strClass) {
      var e = document.getElementById('fleetstatusrow');
      var e2 = document.getElementById('fleetstatustable');
      // make the table row visible
      e.style.display = '';
      if(e2.rows.length > <?=($GlobalUser['maxfleetmsg'] - 1);?>) {
          e2.deleteRow(<?=($GlobalUser['maxfleetmsg'] - 1);?>);
      }
      var row = e2.insertRow('test');
      var td1 = document.createElement("td");
      var td1text = document.createTextNode(strInfo);
      td1.appendChild(td1text);
      var td2 = document.createElement("td");
      var span = document.createElement("span");
      var spantext = document.createTextNode(strDataResult);
      var spanclass = document.createAttribute("class");
      spanclass.nodeValue = strClass;
      span.setAttributeNode(spanclass);
      span.appendChild(spantext);
      td2.appendChild(span);
      row.appendChild(td1);
      row.appendChild(td2);

  }

  function changeSlots(slotsInUse) {
      var e = document.getElementById('slots');
      e.innerHTML = slotsInUse;
  }

  function setShips(ship, count) {
      var e = document.getElementById(ship);
      e.innerHTML = count;
  }

  function cursorevent(evt) {
      evt = (evt) ? evt : ((event) ? event : null);
      if(evt.keyCode == 37) {
          galaxy_submit('systemLeft');
      }

      if(evt.keyCode == 39) {
          galaxy_submit('systemRight');
      }

      if(evt.keyCode == 38) {
          galaxy_submit('galaxyRight');
      }

      if(evt.keyCode == 40) {
          galaxy_submit('galaxyLeft');
      }

  }
  document.onkeyup = cursorevent;
</script>

