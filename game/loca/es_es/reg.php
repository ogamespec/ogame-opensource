<?php

// Registration, login, forgot email send

$LOCA["es"]["REG_MAIL_TITLE"] = "Visión general";
$LOCA["es"]["REG_MAIL_SEND"] = "Enviar contraseña";
$LOCA["es"]["REG_MAIL_NOTE"] = "Por favor, introduce tu dirección de correo electrónico";
$LOCA["es"]["REG_MAIL_EMAIL"] = "Correo electrónico:";
$LOCA["es"]["REG_MAIL_SUBMIT"] = "enviar datos de acceso";

$LOCA["es"]["REG_FORGOT_TITLE"] = "Enviar contraseña de #1";
$LOCA["es"]["REG_FORGOT_ERROR"] = "Esta dirección de correo electrónico no existe como dirección permanente o variable";
$LOCA["es"]["REG_FORGOT_OK"] = "Tu contraseña ha sido enviada a #1.";
$LOCA["es"]["REG_FORGOT_SUBJ"] = "contraseña de #1";
$LOCA["es"]["REG_FORGOT_MAIL"] = "Hola #1,\n\n" .
"tu contraseña para el Universo #2 de #5 es:\n\n" .
"#3\n\n" .
"Puedes iniciar sesión en #4 con estos datos de acceso.\n\n" .
"Solo enviamos contraseñas a la dirección de correo electrónico registrada en tu cuenta. Ignora este correo si no lo has solicitado.\n\n" .
"¡Te deseamos mucho éxito jugando a #5!\n\n" .
"Tu equipo de #5";

// Session error on page load

$LOCA["es"]["REG_SESSION_INVALID"] = "La sesión no es válida.";
$LOCA["es"]["REG_SESSION_ERROR"] = "Se ha producido un error";
$LOCA["es"]["REG_SESSION_ERROR_BODY"] = "    <br /><br />
    La sesión no es válida.<br/><br/>Esto puede deberse a varias razones: 
<br>- Has iniciado sesión en la misma cuenta varias veces; 
<br>- Tu dirección IP ha cambiado desde la última vez que iniciaste sesión; 
<br>- Estás accediendo a Internet a través de AOL o de un proxy. Desactiva la verificación de IP en el menú \"Ajustes\" de tu cuenta.    
    <br /><br />
";

$LOCA["es"]["REG_NOT_ACTIVATED"] = "Tu cuenta de juego aún no ha sido activada. Ve a <a href=index.php?page=options&session=#1>Ajustes</a>, introduce tu dirección de correo electrónico y recibirás un enlace de activación";
$LOCA["es"]["REG_PENDING_DELETE"] = "Tu cuenta ha sido marcada para su eliminación. Fecha de eliminación: #1";

// Player tries to write without account activation
$LOCA["es"]["REG_NOT_ACTIVATED_MESSAGE"] = "Esta función solo está disponible después de la activación de la cuenta.";

// errorpage

$LOCA["es"]["REG_ERROR"] = "Error";
$LOCA["es"]["REG_ERROR_21"] = "Has intentado entrar en el universo #1 con el apodo #2.";
$LOCA["es"]["REG_ERROR_22"] = "Esta cuenta no existe o has introducido tu contraseña incorrectamente. ";
$LOCA["es"]["REG_ERROR_23"] = "Introduce <a href='#1'>la contraseña correcta</a> o utiliza <a href='mail.php'>la recuperación de contraseña</a>.";
$LOCA["es"]["REG_ERROR_24"] = "También puedes crear una <a href='new.php'>cuenta nueva</a>.";
$LOCA["es"]["REG_ERROR_31"] = "Esta cuenta ha sido bloqueada hasta #1; consulta más detalles <a href=../pranger.php>aquí</a>.<br> Si tienes alguna pregunta, ponte en contacto con la persona que te bloqueó, el <a href='#'>operador</a>.<br><br>ADVERTENCIA: el estado de comandante no se cancela al ser bloqueado; la cancelación se realiza por separado!";

// new.php

$LOCA["es"]["REG_NEW_ERROR_AGB"] = "¡Para poder empezar a jugar debes aceptar las Normas Básicas!";
$LOCA["es"]["REG_NEW_ERROR_IP"] = "¡Registro desde una misma IP no más de una vez cada 10 minutos!";
$LOCA["es"]["REG_NEW_ERROR_CHARS"] = "¡El nombre #1 contiene caracteres no válidos o demasiados/pocos caracteres!";
$LOCA["es"]["REG_NEW_ERROR_EXISTS"] = "El nombre #1 ya existe";
$LOCA["es"]["REG_NEW_ERROR_EMAIL"] = "¡La dirección #1 no es válida!";
$LOCA["es"]["REG_NEW_ERROR_EMAIL_EXISTS"] = "¡La dirección #1 ya existe!";
$LOCA["es"]["REG_NEW_ERROR_MAX_PLAYERS"] = "¡Se ha alcanzado el número máximo de jugadores (#1)!";
$LOCA["es"]["REG_NEW_TITLE"] = "Registro en el Universo #1 de #2";
$LOCA["es"]["REG_NEW_SUCCESS"] = "¡El registro se ha completado con éxito!";
$LOCA["es"]["REG_NEW_TEXT"] = "¡Enhorabuena, <span class='fine'>Universo #1</span>!<br /><br />Te has registrado con éxito en #6. (<span class='fine'>#2</span>). <br />\n".
            "Pronto recibirás <span class='fine'>#3</span> un correo electrónico con una contraseña y algunos enlaces importantes.<br />\n".
            "Para poder jugar, debes iniciar sesión a través de la <a href='#4'>página de inicio</a>.<br />\n".
            "En la imagen siguiente verás cómo hacerlo correctamente.<br /><br />\n" .
            "<center><a href='#5' style='text-decoration: underline;font-size: large;'>¡Vamos!</a></center><br /><br /> \n" .
            "Buena suerte<br /> \n" .
            "Tu equipo de #6</th>";
$LOCA["es"]["REG_NEW_UNI"] = "Universo #1";
$LOCA["es"]["REG_NEW_CHOOSE_UNI"] = "Elige el universo";
$LOCA["es"]["REG_NEW_NAME"] = "Introduce el nombre";
$LOCA["es"]["REG_NEW_PASSWORD"] = "¡Y la contraseña enviada!";
$LOCA["es"]["REG_NEW_ERROR"] = "Error";
$LOCA["es"]["REG_NEW_PLAYER_INFO"] = "Información del jugador";
$LOCA["es"]["REG_NEW_PLAYER_NAME"] = "Nombre en el juego";
$LOCA["es"]["REG_NEW_PLAYER_EMAIL"] = "Correo electrónico";
$LOCA["es"]["REG_NEW_ACCEPT"] = "Estoy de acuerdo con";
$LOCA["es"]["REG_NEW_AGB"] = "Normas Básicas";
$LOCA["es"]["REG_NEW_SUBMIT"] = "Registrarse";
$LOCA["es"]["REG_NEW_INFO"] = "Información";

$LOCA["es"]["REG_NEW_MESSAGE_0"] = "OK";
$LOCA["es"]["REG_NEW_MESSAGE_101"] = "¡Ese nombre ya existe!";
$LOCA["es"]["REG_NEW_MESSAGE_102"] = "¡Esta dirección ya está en uso!";
$LOCA["es"]["REG_NEW_MESSAGE_103"] = "¡El nombre debe tener entre 3 y 20 caracteres!";
$LOCA["es"]["REG_NEW_MESSAGE_104"] = "¡La dirección no es válida!";
$LOCA["es"]["REG_NEW_MESSAGE_105"] = "El nombre del jugador es correcto";
$LOCA["es"]["REG_NEW_MESSAGE_106"] = "La dirección es correcta";
$LOCA["es"]["REG_NEW_MESSAGE_107"] = "¡La dirección no es válida!";
$LOCA["es"]["REG_NEW_MESSAGE_108"] = "¡Registro desde una misma IP no más de una vez cada 10 minutos!";
$LOCA["es"]["REG_NEW_MESSAGE_109"] = "¡Se ha alcanzado el número máximo de jugadores!";
$LOCA["es"]["REG_NEW_MESSAGE_201"] = "Nombre en el juego: <br />Este es el nombre de tu personaje en el juego. No puede haber dos nombres iguales en el mismo universo.";
$LOCA["es"]["REG_NEW_MESSAGE_202"] = "Correo electrónico: <br />Tu contraseña se enviará a esta dirección. Si introduces una dirección incorrecta o no válida, no podrás jugar.";
$LOCA["es"]["REG_NEW_MESSAGE_203"] = "";
$LOCA["es"]["REG_NEW_MESSAGE_204"] = "Para poder empezar a jugar debes aceptar las Normas Básicas.";

// user.php

$LOCA["es"]["REG_GREET_MAIL_SUBJ"] = "¡Bienvenido a #1!";
$LOCA["es"]["REG_GREET_MAIL_BODY"] = "Saludos #1,\n\n" .
            "¡Has decidido crear tu propio imperio en #2 del universo #7!\n\n" .
            "Haz clic en este enlace para activar tu cuenta:\n" .
            "#3\n\n" .
            "Tus credenciales de juego:\n" .
            "Nombre de jugador: #4\n" .
            "Contraseña: #5\n" .
            "Universo: #6\n\n\n";
$LOCA["es"]["REG_GREET_MAIL_BOARD"] = "Si necesitas ayuda o consejo de otros emperadores, puedes encontrar todo en nuestro foro (#1).\n\n";
$LOCA["es"]["REG_GREET_MAIL_TUTORIAL"] = "Aquí (#1) encontrarás toda la información recopilada por jugadores y miembros del equipo para ayudar a los nuevos jugadores a entender el juego lo antes posible.\n\n";
$LOCA["es"]["REG_GREET_MAIL_FOOTER"] = "¡Te deseamos éxito en la construcción de tu imperio y buena suerte en las próximas batallas!\n\nTu equipo de #1";

$LOCA["es"]["REG_CHANGE_MAIL_SUBJ"] = "Tu dirección de correo electrónico del juego ha sido cambiada ";
$LOCA["es"]["REG_CHANGE_MAIL_BODY"] = "Saludos #1,\n\n" .
            "La dirección de correo electrónico temporal de tu cuenta en el universo #2 ha sido cambiada en los ajustes a #3.\n" .
            "Si no la cambias en el plazo de una semana, se convertirá en permanente.\n\n" .
            "Confirma tu nueva dirección de correo electrónico mediante el siguiente enlace para seguir jugando sin problemas:\n\n" .
            "#4\n\n" .
            "Tu equipo de #5";

$LOCA["es"]["REG_GREET_MSG_SUBJ"] = "¡Bienvenido a #1!";
$LOCA["es"]["REG_GREET_MSG_TEXT"] = "¡Bienvenido a [b]#3[/b]!\n" .
        "\n" .
        "Primero necesitas desarrollar las minas.\n" .
        "Puedes hacerlo en el menú \"Edificios\".\n" .
        "Selecciona una mina de metal y pulsa \"construir\".\n" .
        "Ahora tienes algo de tiempo para familiarizarte con el juego.\n" .
        "Puedes encontrar ayuda para el juego en estos enlaces: \n" .
        "[url=#1/]Tutorial[/url]\n" .
        "[url=#2/]Foro[/url]\n" .
        "\n" .
        "Mientras tanto, tu mina ya debería estar construida.\n" .
        "Las minas necesitan energía para funcionar, así que construye una planta de energía solar para obtenerla.\n" .
        "Para ello, vuelve al menú \"Edificios\" y haz clic en la planta de energía.\n" .
        "Para ver hasta dónde has llegado en tu desarrollo, ve al menú \"Tecnología\".\n" .
        "Así que tu marcha victoriosa por el universo ha comenzado... ¡Buena suerte!\n";

// logout

$LOCA["es"]["REG_LOGOUT"] = "¡¡Hasta pronto!!";

?>
