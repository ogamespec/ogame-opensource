function reloadEventbox(data)
{
    if (data.length == 0) {
        return;
    }
    var evalData = $.parseJSON(data);
    var displayContent = false;
    var type = typeof evalData["eventText"];
	
    var actionSum = parseInt(evalData["friendly"]) + parseInt(evalData["neutral"]) + parseInt(evalData["hostile"]);
	
    if (actionSum > 0) {
        displayContent = true;
    }
	
    if (displayContent) {
        $("#eventFriendly").html(evalData["friendly"]);
        $("#eventNeutral").html(evalData["neutral"]);
        $("#eventHostile").html(evalData["hostile"]);

        $("#eventContent").html(evalData["eventText"]);
        $("#eventClass").attr("class", evalData["eventText"]);
    }

    if (type == "string" || type == "undefined") {
        $("#eventboxLoading").hide();
       
        if (displayContent) {
            $("#eventboxBlank").hide();
            $("#eventboxFilled").show();
	
            new simpleCountdown(getElementByIdWithCache("tempcounter"), evalData["eventTime"], initAjaxEventbox);
        } else {
            $("#eventboxBlank").show();
            $("#eventboxFilled").hide();
        }
    } 
}

function reloadResources(resources)
{
    var data = $.parseJSON(resources);

    reloadResourceTicker(resources);

    $("#resources_metal").html(data["metal"]["resources"]["actualFormat"]);
    $("#resources_metal").attr('class', data["metal"]["class"]);
    $("#metal_box").attr('title', data["metal"]["tooltip"]);

    $("#resources_crystal").html(data["crystal"]["resources"]["actualFormat"]);
    $("#resources_crystal").attr('class', data["crystal"]["class"]);
    $("#crystal_box").attr('title', data["crystal"]["tooltip"]);

    $("#resources_deuterium").html(data["deuterium"]["resources"]["actualFormat"]);
    $("#resources_deuterium").attr('class', data["deuterium"]["class"]);
    $("#deuterium_box").attr('title', data["deuterium"]["tooltip"]);

    $("#resources_energy").html(data["energy"]["resources"]["actualFormat"]);
    $("#resources_energy").attr('class', data["energy"]["class"]);
    $("#energy_box").attr('title', data["energy"]["tooltip"]);

    $("#resources_darkmatter").html(data["darkmatter"]["resources"]["actualFormat"]);
    $("#resources_darkmatter").attr('class', data["darkmatter"]["class"]);
    $("#darkmatter_box").attr('title', data["darkmatter"]["tooltip"]);

}

function reloadResourceTicker(resources)
{
    var data = $.parseJSON(resources);
    resourceTickerMetal.available = data["metal"]["resources"]["actual"];
    resourceTickerMetal.limit = [0, data["metal"]["resources"]["max"]];
    resourceTickerMetal.production = data["metal"]["resources"]["production"];

    resourceTickerCrystal.available = data["crystal"]["resources"]["actual"];
    resourceTickerCrystal.limit = [0, data["crystal"]["resources"]["max"]];
    resourceTickerCrystal.production = data["crystal"]["resources"]["production"];

    resourceTickerDeuterium.available = data["deuterium"]["resources"]["actual"];
    resourceTickerDeuterium.limit = [0, data["deuterium"]["resources"]["max"]];
    resourceTickerDeuterium.production = data["deuterium"]["resources"]["production"];


    if (!vacation) {
        metalTicker = new resourceTicker(resourceTickerMetal);
        crystalTicker = new resourceTicker(resourceTickerCrystal);
        deuteriumTicker = new resourceTicker(resourceTickerDeuterium);
    }
}

function reloadRightmenu(url)
{
    $.get(url, { }, displayRightmenu);
    
}

function displayRightmenu(data)
{
    $("#rechts").html(data);
    initCluetip();
}

function ajaxFormSubmit(form, url, okFunction)
{
    var params = $("#"+form+"").serialize();

    var successFunction = null;
    if(okFunction != null && typeof okFunction == "function")
    {
        successFunction = okFunction;
    }

    $.ajax({
        type: "POST",
        url: url,
        data: params,
        success: successFunction
    });
}