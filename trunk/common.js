
function changeAction(type) {
    if(type != "register" && document.loginForm.universe.value == '') {
        alert('<?php echo loca("LOGIN_NOTCHOSEN");?>');
    }
    else {
        if(type == "login") {
            var url = "http://" + document.loginForm.universe.value + "/game/reg/login2.php";
            document.loginForm.action = url;
        }
        else if (type=="getpw") {
            var url = "http://" + document.loginForm.universe.value + "/game/reg/mail.php";
            document.loginForm.action = url;
            document.loginForm.submit();
        }
        else if(type == "register") {
            var url = "http://" + document.registerForm.universe.value + "/game/reg/newredirect.php";
            document.registerForm.action = url;
        }
    }
}

function printMessage(code, div) {
    var textclass = "";
    
    if (div == null) {
        div = "statustext";
    }
    switch (code) {
        case "0":
            text = "<?php echo loca("ERROR_0");?>";
            textclass = "fine"; 
            break;
        case "101":
            text = "<?php echo loca("ERROR_101");?>";
            textclass = "warning"; 
            break;
        case "102":
            text = "<?php echo loca("ERROR_102");?>";
            textclass = "warning"; 
            break;
        case "103":
            text = "<?php echo loca("ERROR_103");?>";
            textclass = "warning"; 
            break;
        case "104":
            text = "<?php echo loca("ERROR_104");?>";
            textclass = "warning"; 
            break;
        case "105":
            text = "<?php echo loca("ERROR_105");?>";
            textclass = "fine"; 
            break;
        case "106":
            text = "<?php echo loca("ERROR_106");?>";
            textclass = "fine"; 
            break;
        case "107":
            text = "<?php echo loca("ERROR_107");?>";
            textclass = "warning"; 
            break;
        case "108":
            text = "<?php echo loca("ERROR_108");?>";
            textclass = "warning"; 
            break;
        case "201":
            text = "<?php echo loca("TIP_201");?>";
            break;
        case "202":
            text = "<?php echo loca("TIP_202");?>";
            break;
        case "203":
            text = "<?php echo loca("TIP_203");?>";
            break;
        case "204":
            text = "<?php echo loca("TIP_204");?>";
            break;
        case "205":
            text = "<?php echo loca("TIP_205");?>";
            break;
        default:
            text = code;
            break;
    }
    
    if (textclass != "") {
        text = "<span class='" + textclass + "'>" + text + "</span>";
    }
    document.getElementById(div).innerHTML = text;
}
