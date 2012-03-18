function initHighscoreCluetip () {
    $.fn.cluetip.defaults.clickThrough = true;
    $('*.tipsHighscoreNav').cluetip("destroy").cluetip({
        splitTitle: '|',
        showTitle: false,
        width: 150,
        positionBy: 'auto',
        leftOffset: 20,
        topOffset: 15,
        cluezIndex: 9997,
        hoverIntent: {
            sensitivity:  1,
            interval:     250,
            timeout:      400
        }
    });
}

function initCluetip () {
    $.fn.cluetip.defaults.clickThrough = true;
    $('*.tipsStandard').cluetip("destroy").cluetip({
        splitTitle: '|', 
        showTitle: false,
        width: 150,
        positionBy: 'auto',
        leftOffset: 20,
        topOffset: 15,
        cluezIndex: 9997,
        hoverIntent: {  
            sensitivity:  1,
            interval:     250,
            timeout:      400       
        }
    });

    $('*.tipsStandardMax').cluetip("destroy").cluetip({
        splitTitle: '|', 
        showTitle: false,
        width: 'auto',
        truncate: 0,
        positionBy: 'auto',
        leftOffset: 20,
        topOffset: 15,
        cluezIndex: 9997,
        hoverIntent: {  
            sensitivity:  1,
            interval:     250,
            timeout:      400       
        }
    });
    
    $('*.tipsTitle').cluetip("destroy").cluetip({
        splitTitle: '|',
        showTitle: true,
        cluetipClass: 'event',
        positionBy: 'mouse', 
        leftOffset: 15,
        topOffset: 10,
        cluezIndex: 9997,
        width: 'auto'
    });
    
    $('*.tipsTitleAdvice').cluetip("destroy").cluetip({
        splitTitle: '|',
        showTitle: true,
        cluetipClass: 'advice',
        positionBy: 'auto', 
        leftOffset: 25,
        topOffset: 25
    }); 
    
    $('*.tipsTitleSmall').cluetip("destroy").cluetip({
        splitTitle: '|',
        showTitle: true,
        cluetipClass: 'impact',
        positionBy: 'fixed', 
        width: 125,
        leftOffset: 10,
        topOffset: 10
    });
	
    $('*.tipsTitleArrowClose').cluetip("destroy").cluetip({
        width: 250,
        closePosition: 'title',
        closeText: '<img src="/cdn/img/navigation/close-details.jpg">',
        cluetipClass: 'event',
        positionBy: 'mouse',
        sticky: true
    });
	 	
    $('*.tipsLocal').cluetip("destroy").cluetip({
        local: true,
        showTitle: false,
        width: 150,
        positionBy: 'auto',
        leftOffset: 20,
        topOffset: 15,
        cluezIndex: 9997,
        hoverIntent: {  
            sensitivity:  1,
            interval:     250,
            timeout:      400       
        }
    });
    
    $('*.tipsTechinfoDetails').cluetip("destroy").cluetip({
        local: true,
        width: 250,
        showTitle: false,
        leftOffset: 5
    }); 
    
    $('*.tipsDemolishcosts').cluetip("destroy").cluetip({
        local: true,
        cluetipClass: 'impact',
        width: 150,
        positionBy: 'bottomTop', 
        leftOffset: 15,
        topOffset: 15       
    });

    $('*.tipsTitleArrowCloseFleet').cluetip("destroy").cluetip({
        local: true,
        width: 200,
        showTitle: true,
        leftOffset: 5,
        closePosition: 'title',
        closeText: '<img src="/cdn/img/navigation/close-details.jpg">',
        cluetipClass: 'event',
        positionBy: 'fixed',
        topOffset: 0,
        sticky: true,
        arrows: true
    });
    
    $('*.tipsGalaxy').cluetip("destroy").cluetip({
        local: true,
        cluetipClass: 'galaxy',
        width: 250,
        showTitle: false,
        delayedClose: 500,
        mouseOutClose: true,
        hoverIntent: false,
        clickThrough: false,
        sticky: true
    }); 
    $('*.tipsPlayer').cluetip("destroy").cluetip({
        splitTitle: '|',
        showTitle: true,
        cluetipClass: 'player',
        positionBy: 'mouse', 
        leftOffset: 15,
        topOffset: 10,
        cluezIndex: 9997,
        width: 'auto',
        sticky: true,
        delayedClose: 500,
        hoverIntent: false,
        clickThrough: true,
        mouseOutClose: true
    });
}