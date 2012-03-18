function resourceTicker(config) {
    
    var thisObj = this;
    thisObj.config = config; 
    thisObj.htmlObj = document.getElementById(thisObj.config.valueElem);
    
    var localTime = new Date();
    thisObj.startTime = localTime.getTime(); // Script-Startzeit
    
    thisObj.updateResource = function() {
        var localTime = new Date().getTime(); 
        nrResource = thisObj.config.available + thisObj.config.production * (localTime-thisObj.startTime)/1000 ;
        nrResource = Math.max(0, Math.round(nrResource));

        // resourcen tickern (hoch ODER runter)
        if 
        (   
            nrResource < thisObj.config.available 
            || nrResource >= thisObj.config.available && nrResource < thisObj.config.limit[1]
        ) 
        {
            nrResource = gfNumberGetHumanReadable(nrResource);
            thisObj.htmlObj.innerHTML = nrResource;
            
	        if (nrResource >= thisObj.config.limit[1]) {
	            thisObj.htmlObj.className = "overmark";
	        } else if (nrResource >= thisObj.config.limit[1] * 0.9 && nrResource < thisObj.config.limit[1]) {
	            thisObj.htmlObj.className = "middlemark";
	        } 
        }
    }
    
    if(config.intervalObj) {
    	timerHandler.removeCallback(config.intervalObj);
    }

	config.intervalObj = timerHandler.appendCallback(thisObj.updateResource);
}    
