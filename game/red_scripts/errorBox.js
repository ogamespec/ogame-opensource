var errorBoxYesHandler = 0;
var errorBoxNoHandler = 0;
var errorBoxOkHandler = 0;

function errorBoxAsArray(data)
{
	if(data["type"] == "notify") {
	    notifyBoxAsArray(data);
	} else if(data["type"] == "decision") {
	    errorBoxDecision(data);
	} else if(data["type"] == "fadeBox") {
	    fadeBox(data["text"], data["failed"]);
	} else if(data["type"] == "promotion") {
	    promotionBoxAsArray(data);
	}
}

function promotionBoxAsArray(data) {
    promotionBox(
        data["title"],
        data["text"],
        data["picture"],
        data["buttonOk"],
        String(data["okFunction"]),
        data["removeOpen"],
        data["modal"]
    );
}

function notifyBoxAsArray(data) {
    errorBoxNotify(
        data["title"], 
        data["text"], 
        data["buttonOk"], 
        String(data["okFunction"]), 
        data["removeOpen"],
        data["modal"]
    );
}

function fadeBox(message, failed, callback) {
    
    tb_remove();
        
    if (failed) {
        $("#fadeBoxStyle").attr("class", "failed");
    } else {
        $("#fadeBoxStyle").attr("class", "success");
    }
    $("#fadeBoxContent").html(message);
    $("#fadeBox").stop(false, true).show().fadeOut(5000, callback);
}

function decisionBoxAsArray(data) {
    errorBoxDecision(
        data["title"], 
        data["text"], 
        data["buttonOk"], 
        data["buttonNOk"], 
        String(data["okFunction"]), 
        String(data["nokFunction"]), 
        data["removeOpen"],
        data["modal"]
    );
}

function errorBoxDecision(head, content, yes, no, yesHandler, noHandler, removeOpen, modal)
{
    document.getElementById("errorBoxDecisionHead").innerHTML = head;
    document.getElementById("errorBoxDecisionContent").innerHTML = content;
    document.getElementById("errorBoxDecisionYes").innerHTML = yes;
    document.getElementById("errorBoxDecisionNo").innerHTML = no;

    if (yesHandler != null) {
        errorBoxYesHandler = yesHandler;
    }

    if (noHandler != null) {
        errorBoxNoHandler = noHandler;
    }

    if(removeOpen != null && removeOpen == true) {
        tb_remove_openNew('#TB_inline?height=200&width=400&inlineId=decisionTB&modal=true');
    } else {
        tb_open('#TB_inline?height=200&width=400&inlineId=decisionTB&modal=true');
    }
}
function errorBoxNotify(head, content, ok, okHandler, removeOpen, modal)
{    
	document.getElementById("errorBoxNotifyHead").innerHTML = head;
	document.getElementById("errorBoxNotifyContent").innerHTML = content;
	document.getElementById("errorBoxNotifyOk").innerHTML = ok;

	if(okHandler != null) {
		errorBoxOkHandler = okHandler;
	}
    
    if(removeOpen != null && removeOpen == true) {
        if (modal == true || modal == "true") {
            tb_remove_openNew('#TB_inline?height=200&width=400&inlineId=notifyTB&modal=true');
        } else {
            tb_remove_openNew('#TB_inline?height=200&width=400&inlineId=notifyTB');
        }
    } else {
        if (modal || modal == "true") {
            tb_open('#TB_inline?height=200&width=400&inlineId=notifyTB&modal=true');
        } else {
            tb_open('#TB_inline?height=200&width=400&inlineId=notifyTB');
        }
    }

}

function promotionBox(head, content, picture, yes, no, yesHandler, noHandler, removeOpen, modal)
{
	document.getElementById("promotionBoxHead").innerHTML = head;
	document.getElementById("promotionBoxText").innerHTML = content;
        document.getElementById("promotionBoxPicture").innerHTML = picture;
	document.getElementById("promotionBoxYes").innerHTML = yes;
        document.getElementById("promotionBoxNo").innerHTML = no;

        if (yesHandler != null) {
            errorBoxYesHandler = yesHandler;
        }

        if (noHandler != null) {
            errorBoxNoHandler = noHandler;
        }

    if(removeOpen != null && removeOpen == true) {
        if (modal == true || modal == "true") {
            tb_remove_openNew('#TB_inline?height=200&width=400&inlineId=notifyTB&modal=true');
        } else {
            tb_remove_openNew('#TB_inline?height=200&width=400&inlineId=promotion');
        }
    } else {
        if (modal || modal == "true") {
            tb_open('#TB_inline?height=200&width=400&inlineId=promotion&modal=true');
        } else {
            tb_open('#TB_inline?height=200&width=400&inlineId=promotion');
        }
    }

}

function closeErrorBox()
{
    tb_remove();
	errorBoxYesHandler = 0;
	errorBoxNoHandler = 0;
}
function handleErrorBoxClick(buttonType)
{
	if(buttonType=='ok')
	{
        if(typeof errorBoxOkHandler == "string" && $.isFunction(window[errorBoxOkHandler]))
        {
            window[errorBoxOkHandler]();
        }
		else if($.isFunction(errorBoxOkHandler))
		{
            errorBoxOkHandler();
		}
		else if(typeof errorBoxSubmitOk != "undefined" && $.isFunction(errorBoxSubmitOk))
		{
			errorBoxSubmitOk();
		}
		else
		{
			closeErrorBox();
		}
	}
	else if(buttonType=='yes')
	{
        if(typeof errorBoxYesHandler == "string" && $.isFunction(window[errorBoxYesHandler]))
        {
            window[errorBoxYesHandler]();
        }
		else if($.isFunction(errorBoxYesHandler))
		{
			errorBoxYesHandler();
		}
		else if(typeof errorBoxSubmitYes != "undefined" && $.isFunction(errorBoxSubmitYes))
		{
			errorBoxSubmitYes();
		}
		else
		{
			closeErrorBox();
		}
	}
	else if(buttonType=='no')
	{
        if(typeof errorBoxNoHandler == "string" && $.isFunction(window[errorBoxNoHandler]))
        {
            window[errorBoxNoHandler]();
        }
		else if($.isFunction(errorBoxNoHandler))
		{
			errorBoxNoHandler();
		}
		else if(typeof errorBoxSubmitNo != "undefined" && $.isFunction(errorBoxSubmitNo))
		{
			errorBoxSubmitNo();
		}
		else
		{
			closeErrorBox();
		}
	}
}
