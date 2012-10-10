/*
Beispiel HTML, es wird das Inhalts-Div (id="tooltipBody") ausgetauscht und das gesamtelement (id="tooltip") positioniert

    <div id="tooltip" class="tooltip" style="display: none; position: absolute; left: 1055px; top: 299px;">
        <div class="tooltipHeader"/>
        <div id="tooltipBody" class="tooltipBody">Inhalt</div>
        <div class="tooltipFooter"/>
    </div>
*/

function tooltip(tipObj) {

    var positionLeft = -30; // Position relativ zum Mauszeiger
    var positionTop = -5;
    var appearanceTime = 250; // Millisekunden bis zum erscheinen
    
    var thisObj = this;
    thisObj.sticky = false;
    if (tipObj.className=='tooltip_sticky'){
    	thisObj.sticky = true; // Tooltipp bleibt stehen, wenn man sich innerhalb des tooltipps bewegt
    	positionLeft = -30; // Position relativ zum Mauszeiger
    	positionTop = -5;
    	appearanceTime = 250; // Millisekunden bis zum erscheinen
        
        thisObj.tooltippLayerObj = getElementByIdWithCache('tooltip');
        thisObj.tooltippTextObj = getElementByIdWithCache('TTWrapper');
    
    } else {
        thisObj.sticky = false; // Tooltipp bleibt stehen, wenn man sich innerhalb des tooltipps bewegt
        positionLeft = 0; // Position relativ zum Mauszeiger
        positionTop = 10;
        appearanceTime = 250; // Millisekunden bis zum erscheinen

        thisObj.tooltippLayerObj = getElementByIdWithCache('tooltipPlain');
        thisObj.tooltippTextObj = getElementByIdWithCache('tooltipBodyPlain');
    }

    thisObj.tipObj = tipObj;

    // interne flags
    thisObj.stickyActivated = false;
    
    this.waitForTooltip = function() {
        thisObj.timeout = setTimeout(thisObj.showTooltip, appearanceTime);
    }
    this.showTooltip = function() {

        thisObj.tooltippTextObj.innerHTML = thisObj.tipObj.innerHTML;
        thisObj.tooltippLayerObj.style.position = 'absolute';
        // verstecken
        thisObj.tooltippLayerObj.style.left = '-1000px';
        thisObj.tooltippLayerObj.style.top = '-1000px';
        // als block zum abmessen erzeugen
        thisObj.tooltippLayerObj.style.display = 'block';
        // position ausrechnen
        posX = (thisObj.xPos + positionLeft);
        posY = (thisObj.yPos + positionTop);
        // screen ausmessen
        if (window.innerWidth) { // DOM kompatible
            screenX = window.innerWidth + window.pageXOffset - thisObj.tooltippLayerObj.offsetWidth - 20;
            screenY = window.innerHeight + window.pageYOffset - thisObj.tooltippLayerObj.offsetHeight - 20;
        } else if(document.documentElement.clientWidth) { // IE
            screenX = document.documentElement.clientWidth + document.documentElement.scrollLeft - thisObj.tooltippLayerObj.offsetWidth - 20;
            screenY = document.documentElement.clientHeight + document.documentElement.scrollTop - thisObj.tooltippLayerObj.offsetHeight - 20;
        } else {
            screenX = 1000;
            screenY = 700;
        }
        if (posX > screenX) {
            posX = thisObj.xPos - thisObj.tooltippLayerObj.offsetWidth;
        }
        if (posY > screenY) {
            posY = thisObj.yPos - thisObj.tooltippLayerObj.offsetHeight;
        }
        // positionieren
        thisObj.tooltippLayerObj.style.left = posX +'px';
        thisObj.tooltippLayerObj.style.top = posY +'px';
                
        if (thisObj.sticky) {
            if (!thisObj.stickyActivated) {
                thisObj.stickyActivated = true;
                addListener(thisObj.tooltippLayerObj, 'mouseover', function(ev) {
                    thisObj.tooltippLayerObj.style.display = 'block';
                });
                addListener(thisObj.tooltippLayerObj, 'mouseout', function(ev) {
                    thisObj.hideTooltip();
                });
            }
        }
    }
    this.hideTooltip = function() {
        if (thisObj.timeout) {
            window.clearTimeout(thisObj.timeout);
        }
        thisObj.tooltippLayerObj.style.display = 'none';
    }
    this.getIEBody = function() {
        return (window.document.compatMode == "CSS1Compat") ?
            window.document.documentElement : window.document.body || null;
    }

    thisObj.IEBody = thisObj.getIEBody();      

    addListener(thisObj.tipObj.parentNode, 'mouseover', function(ev) {
        thisObj.waitForTooltip();
    });
    
    	
    addListener(thisObj.tipObj.parentNode, 'mousemove', function(ev) {
        thisObj.xPos    =  isNaN(ev.pageX)?parseInt(window.event.clientX +thisObj.IEBody.scrollLeft):parseInt(ev.pageX);
        thisObj.yPos    =  isNaN(ev.pageY)?parseInt(window.event.clientY +thisObj.IEBody.scrollTop)+10:parseInt(ev.pageY)+10;
    }); 
    
    	   
    addListener(thisObj.tipObj.parentNode, 'mouseout', function() {
        thisObj.hideTooltip();
    });
}

