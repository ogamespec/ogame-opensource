/*
*	allgemeiner Countdown
*/

function countdown(leftoverTime,maxDigits) {

	if(maxDigits == null || maxDigits == "") {
		maxDigits = 2;
	}
    var thisObj = this;

	// config
    thisObj.timestamp = 0;
	thisObj.maxDigits = maxDigits; // bei 2 werden keine Sekunden gezeigt, wenn der Zeitraum > 1 h ist
	thisObj.delimiter = " "; // Trennzeichen
	thisObj.approx = ""; // wird vor Zeitstring angefuegt
    thisObj.showunits = true; // Einheiten zeigen
    thisObj.zerofill = false; // nullen auffuellen

	var localTime = new Date();
	thisObj.startTime = localTime.getTime(); // Script-Startzeit
	thisObj.startLeftoverTime = leftoverTime; // Sekunden Restzeit

    this.getCurrentTimestring = function() {
        return thisObj.formatTime(thisObj.getLeftoverTime());
    }

	this.getLeftoverTime = function() {
		var currTime = new Date();
		return Math.round((thisObj.startLeftoverTime - (currTime.getTime() - thisObj.startTime)/1000));
	}

	this.formatTime = function(timestamp) {
		maxDigits = thisObj.maxDigits;
	    var timeunits = new Array;
	    timeunits.day = 86400;
	    timeunits.hour = 3600;
	    timeunits.minute = 60;
	    timeunits.second = 1;
	    var loca = new Array;
	    loca.day = thisObj.showunits ? LocalizationStrings.timeunits['short'].day : "";
	    loca.hour = thisObj.showunits ? LocalizationStrings.timeunits['short'].hour : "";
	    loca.minute = thisObj.showunits ? LocalizationStrings.timeunits['short'].minute : "";
	    loca.second = thisObj.showunits ? LocalizationStrings.timeunits['short'].second : "";
	    var timestring = "";
		for (var k in timeunits) {
	        var nv = Math.floor(timestamp / timeunits[k]);
	        if (maxDigits > 0 && (nv > 0 || thisObj.zerofill && timestring != "")) {
	            timestamp = timestamp - nv * timeunits[k];
	            if (timestring != "") {
	                timestring += thisObj.delimiter;
	                if (nv < 10 && nv > 0 && thisObj.zerofill) {
	                    nv = "0" + nv;
	                }
	                if (nv == 0) {
	                    nv = "00";
	                }
	            }
				timestring += nv + loca[k];
	            maxDigits--;
	        }
	    }

	    if (timestamp > 0) {
	        timestring = thisObj.approx + timestring;
	    }
	    return timestring;
	}
}

/*
* Bau Countdown fuer Buttons (zaehlt einmal auf 0)
*/
function bauCountdown(htmlObj, leftoverTime, totalTime, reloadPage) {
    if (typeof(htmlObj) == 'object') {

        var thisObj = this;
        thisObj.totalTime = totalTime;

        // diese elemente werden veraendert
        thisObj.startHeight = htmlObj.offsetHeight;
        thisObj.htmlObj = htmlObj;
        //alert(thisObj.htmlObj.style);
        thisObj.timeHtmlObj = getChildNodeWithClassName(htmlObj, 'time');

        this.updateCountdown = function() {
            thisObj.countdown.getCurrentTimestring();
            timestamp = thisObj.countdown.getLeftoverTime();
            timestring = thisObj.countdown.getCurrentTimestring();
            thisObj.timeHtmlObj.innerHTML = timestring;

            var faktor = Math.max(0,timestamp)/thisObj.totalTime;
            if (faktor>0) {
                height = Math.round(thisObj.startHeight * (1 - (faktor)));
                thisObj.htmlObj.style.height =  height+'px';
                thisObj.htmlObj.style.marginBottom = '-'+ height +'px';

            } else {
                thisObj.timeHtmlObj.innerHTML =  LocalizationStrings.status.ready;
                height = thisObj.startHeight;
                thisObj.htmlObj.style.height =  height+'px';
                thisObj.htmlObj.style.marginBottom = '-'+ height +'px';

                if (timestamp <= -1 && timestamp > -10 && !tb_is_open()) {
                	reload_page(reloadPage);
                }
            }
        }

        if (thisObj.timeHtmlObj) {
            // countdown objekt
            thisObj.countdown = new countdown(leftoverTime);
            timerHandler.appendCallback(thisObj.updateCountdown);
            thisObj.updateCountdown();
        } else {
            window.status = 'kein timeHtmlObj';
        }
    }
}

/*
* Schiffbau Countdown fuer Buttons (zaehlt mehrmals auf 0)
*/
function schiffbauCountdown(htmlObj, shipCount, currentShips, leftoverTime, oneShipTime, reloadPage) {
    //alert(htmlObj +'#'+ shipCount +'#'+ leftoverTime +'#'+ oneShipTime);
    if (typeof(htmlObj) == 'object') {
        var thisObj = this;
        thisObj.totalTime = oneShipTime;
        thisObj.oneShipTime = oneShipTime;
        thisObj.shipCount = shipCount;
        thisObj.currentShips = currentShips;

        // diese elemente werden veraendert
        thisObj.startHeight = htmlObj.offsetHeight;
        thisObj.htmlObj = htmlObj;
        //alert(thisObj.htmlObj.style);
        thisObj.timeHtmlObj = getChildNodeWithClassName(htmlObj, 'time');
        thisObj.countHtmlObj = getChildNodeWithClassName(htmlObj.parentNode, 'count');
        thisObj.shipsHtmlObj = getChildNodeWithClassName(htmlObj.parentNode, 'level');

        this.updateCountdown = function() {
            thisObj.countdown.getCurrentTimestring();
            timestamp = thisObj.countdown.getLeftoverTime();
            timestring = thisObj.countdown.getCurrentTimestring();
            //thisObj.timeHtmlObj.innerHTML = timestring;

            thisObj.replaceInnerHTML(thisObj.timeHtmlObj, timestring);

            var faktor = Math.max(0,timestamp)/thisObj.totalTime;
            if (faktor>0) {
                height = Math.round(thisObj.startHeight * (1 - (faktor)));
                thisObj.htmlObj.style.height =  height+'px';
                thisObj.htmlObj.style.marginBottom = '-'+ height +'px';
            } else {
                
                if (thisObj.shipCount > 0) {
	                thisObj.shipCount--;
	                thisObj.currentShips++;
                }
				
                if(thisObj.shipCount >= 0) {
                    thisObj.replaceInnerHTML(thisObj.countHtmlObj, thisObj.shipCount);

                    if(typeof document.getElementById("shipcount") != "undefined") {
                        document.getElementById("shipcount").innerHTML = thisObj.shipCount;
                    }
                }
                
                thisObj.replaceInnerHTML(thisObj.shipsHtmlObj, gfNumberGetHumanReadable(thisObj.currentShips));

                if (thisObj.shipCount>0) {
                    thisObj.countdown = new countdown(oneShipTime);
                    thisObj.replaceInnerHTML(thisObj.timeHtmlObj, '-');
                } else {
                   timerHandler.removeCallback(thisObj.timer);
                   thisObj.replaceInnerHTML(thisObj.timeHtmlObj, LocalizationStrings.status.ready);
                }
            }
        }

        this.replaceInnerHTML = function(obj, val) {
            var htmlNode = document.createTextNode(val);
            if (obj.firstChild) {
                obj.firstChild.deleteData(0,20)
                obj.firstChild.appendData(htmlNode.nodeValue);
            }
        }

        if (thisObj.timeHtmlObj && thisObj.countHtmlObj && thisObj.shipsHtmlObj) {
            // countdown objekt
            totalTime = Math.floor(shipCount * oneShipTime);

            thisObj.countdown = new countdown(leftoverTime);
            thisObj.timer     = timerHandler.appendCallback(thisObj.updateCountdown);
            thisObj.updateCountdown();
        } else {
            window.status = 'kein: timeHtmlObj oder countHtmlObj oder shipsHtmlObj';
        }
    }
}

/*
* Countdown fuer alle 3 Baulisten (zaehlt einmal auf 0)
*/
function baulisteCountdown(htmlObj, leftoverTime, reloadPage) {

    if (typeof(htmlObj) == 'object') {
        var thisObj = this;

        // diese elemente werden veraendert
        thisObj.timeHtmlObj = htmlObj;

        this.updateCountdown = function() {
            thisObj.countdown.getCurrentTimestring();
            timestamp = thisObj.countdown.getLeftoverTime();
            timestring = thisObj.countdown.getCurrentTimestring();
            if (timestamp>0) {
                thisObj.timeHtmlObj.innerHTML = timestring;
            } else {
                thisObj.timeHtmlObj.innerHTML =  LocalizationStrings.status.ready;

                if (timestamp <= -1 && timestamp > -10) {
                    if (reloadPage != null && timestamp > -10 && !tb_is_open()) {
                	   reload_page(reloadPage);
                    }
                }
            }
        }
        if (thisObj.timeHtmlObj) {
            // countdown objekt
            thisObj.countdown = new countdown(leftoverTime,3);
            timerHandler.appendCallback(thisObj.updateCountdown);
            thisObj.updateCountdown();
        }
    }
}

/*
* Countdown fuer alle 3 Baulisten (zaehlt einmal auf 0)
*/
function eventboxCountdown(htmlObj, leftoverTime) {
    if (typeof(htmlObj) == 'object') {

        var thisObj = this;

        // diese elemente werden veraendert
        thisObj.timeHtmlObj = htmlObj;

        this.updateCountdown = function() {
            thisObj.countdown.getCurrentTimestring();
            timestamp = thisObj.countdown.getLeftoverTime();
            timestring = thisObj.countdown.getCurrentTimestring();
            if (timestamp>0) {
                thisObj.timeHtmlObj.innerHTML = timestring;
            } else {
                timerHandler.removeCallback(thisObj.timer);
                
                thisObj.timeHtmlObj.innerHTML =  LocalizationStrings.status.ready;
                setTimeout("checkEventList()", 2500);
            }
        }
        if (thisObj.timeHtmlObj) {
            // countdown objekt
            thisObj.countdown = new countdown(leftoverTime, 3);
            thisObj.timer     = timerHandler.appendCallback(thisObj.updateCountdown);
            thisObj.updateCountdown();
        }
    }
}

/*
* Einfacher Countdown mit Funktionsaufruf nach Ende des Countdowns
*/
function simpleCountdown(htmlObj, leftoverTime, countdownDoneFunction, countdownTickFunction) {
    if (typeof(htmlObj) == 'object') {

        var thisObj = this;

        // diese elemente werden veraendert
        thisObj.timeHtmlObj = htmlObj;

        this.updateCountdown = function() {
            thisObj.countdown.getCurrentTimestring();
            timestamp = thisObj.countdown.getLeftoverTime();
            timestring = thisObj.countdown.getCurrentTimestring();
            if (timestamp>0) {
                thisObj.timeHtmlObj.innerHTML = timestring;
                if ($.isFunction(countdownTickFunction)) {
                    countdownTickFunction(timestamp);
                }
            } else {
                timerHandler.removeCallback(thisObj.timer);
                
                thisObj.timeHtmlObj.innerHTML =  LocalizationStrings.status.ready;
                
		        if (typeof countdownDoneFunction == "string" && $.isFunction(window[countdownDoneFunction])) {
		            window[countdownDoneFunction]();
		        } else if($.isFunction(countdownDoneFunction)) {
		            countdownDoneFunction();
		        }
                
            }
        }
        if (thisObj.timeHtmlObj) {
            // countdown objekt
            thisObj.countdown = new countdown(leftoverTime, 3);
            thisObj.timer     = timerHandler.appendCallback(thisObj.updateCountdown);
            thisObj.updateCountdown();
        }
    }
}

/*
* Einfacher Countdown mit Funktionsaufruf nach Ende des Countdowns
*/
function reloadCountdown(htmlObj, leftoverTime, reloadPage) {
    if (typeof(htmlObj) == 'object') {

        var thisObj = this;

        // diese elemente werden veraendert
        thisObj.timeHtmlObj = htmlObj;

        this.updateCountdown = function() {
            thisObj.countdown.getCurrentTimestring();
            timestamp = thisObj.countdown.getLeftoverTime();
            timestring = thisObj.countdown.getCurrentTimestring();
            if (timestamp>0) {
                thisObj.timeHtmlObj.innerHTML = timestring;
            } else {
                thisObj.timeHtmlObj.innerHTML =  LocalizationStrings.status.ready;

                if (timestamp <=- 2 && timestamp > -10 && !tb_is_open()) {
                    reload_page(reloadPage);
                }
            }
        }
        if (thisObj.timeHtmlObj) {
            // countdown objekt
            thisObj.countdown = new countdown(leftoverTime, 3);
            timerHandler.appendCallback(thisObj.updateCountdown);
            thisObj.updateCountdown();
        }
    }
}

function movementImageCountdown(htmlObj, leftoverTime, duration, isReturn, isRTL) {
    if (typeof(htmlObj) == 'object') {

        var thisObj = this;

        // diese elemente werden veraendert
        thisObj.timeHtmlObj = htmlObj;

        this.updateCountdown = function() {
            thisObj.countdown.getCurrentTimestring();
            timestamp = thisObj.countdown.getLeftoverTime();
            timestring = thisObj.countdown.getCurrentTimestring();
            
            if (timestamp > 0) {
	            percent = 100 - Math.floor(timestamp / (duration / 100));
	            if (!isReturn) {
	                pixel = Math.abs((274 / 100) * percent);
	            } else {
	                pixel = 548 - Math.abs((548 / 100) * percent);
	            }

                if (isRTL) {
                    thisObj.timeHtmlObj.style["marginRight"] = pixel + "px";
                } else {
                    thisObj.timeHtmlObj.style["marginLeft"] = pixel + "px";
                }
            }
        }
        if (thisObj.timeHtmlObj) {
            // countdown objekt
            thisObj.countdown = new countdown(leftoverTime, 3);
            timerHandler.appendCallback(thisObj.updateCountdown);
            thisObj.updateCountdown();
        }
    }
}


// Countdown fuer Schiffe & Verteidigung
function shipCountdown(totalTimeObj, unitTimeObj, sumCountObj, unitTime, restTime, timeSum, unitCount, reloadPage) {
    if (
        (typeof(totalTimeObj) == 'object')
        &&
        (typeof(unitTimeObj) == 'object')
        &&
        (typeof(sumCountObj) == 'object')
        ) {

        var thisObj = this;
        
        thisObj.totalTimeHtmlObj = totalTimeObj;
        thisObj.unitTimeHtmlObj = unitTimeObj;
        thisObj.sumCountHtmlObj = sumCountObj;
				
		this.updateCountdown = function() {
		
            thisObj.unitCountdown.getCurrentTimestring();
            unitTimestamp = thisObj.unitCountdown.getLeftoverTime();
            unitTimestring = thisObj.unitCountdown.getCurrentTimestring();
            
            thisObj.totalCountdown.getCurrentTimestring();
            totalTimestamp = thisObj.totalCountdown.getLeftoverTime();
            totalTimestring = thisObj.totalCountdown.getCurrentTimestring();
	
            if (unitTimestamp > 0) {
                thisObj.unitTimeHtmlObj.innerHTML = unitTimestring;
            } else {
                unitCount --;
                thisObj.unitTimeHtmlObj.innerHTML =  LocalizationStrings.status.ready;
                if(unitCount > 0) {
	                thisObj.unitCountdown = new countdown(unitTime);
	                thisObj.sumCountHtmlObj.innerHTML = unitCount;
                } else {
                    thisObj.sumCountHtmlObj.innerHTML = 0;
                }
            }
                        
            if (unitCount > 0) {
                thisObj.totalTimeHtmlObj.innerHTML = totalTimestring;
            } else {
                thisObj.totalTimeHtmlObj.innerHTML =  LocalizationStrings.status.ready;
                
                if (!tb_is_open()) {
                    reload_page(reloadPage);
                }
            }
        }
				
        if ((thisObj.totalTimeHtmlObj)
            &&
            (thisObj.unitTimeHtmlObj)
            &&
            (thisObj.sumCountHtmlObj)
            ) {
            // countdown objekt
            thisObj.totalCountdown = new countdown(timeSum);
			thisObj.unitCountdown = new countdown(restTime);
			
			timerHandler.appendCallback(thisObj.updateCountdown);
			thisObj.updateCountdown();
        }
    }
}

/*
* Countdown reload
*/
reloaded = 0;
function reload_page(url)
{
	if(reloaded == 0)
	{
		location.href = url;
		reloaded++;
	}
}
