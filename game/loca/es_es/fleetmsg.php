<?php

// Various fleet messages (e.g. Transport, Recycle, etc.). There is a complete mess. The same mess was also in vanilla version 0.84
// TODO: Take messages from the current official game.

// From/Subj
$LOCA["es"]["FLEET_MESSAGE_FROM"] = "Comando de la flota";
$LOCA["es"]["FLEET_MESSAGE_RETURN"] = "Regreso de la flota";
$LOCA["es"]["FLEET_MESSAGE_HOLD"] = "Flota en espera";
$LOCA["es"]["FLEET_MESSAGE_INTEL"] = "Inteligencia";
$LOCA["es"]["FLEET_MESSAGE_FLEET"] = "Flota ";
$LOCA["es"]["FLEET_MESSAGE_OBSERVE"] = "Observación";
$LOCA["es"]["FLEET_MESSAGE_ARRIVE"] = "Llegada al planeta";
$LOCA["es"]["FLEET_MESSAGE_TRADE"] = "Una flota extranjera entrega suministros";
$LOCA["es"]["FLEET_MESSAGE_SPY"] = "Espionaje";
$LOCA["es"]["FLEET_MESSAGE_BATTLE"] = "Informe de batalla";

$LOCA["es"]["FLEET_TRANSPORT_OWN"] = "Tu flota llega al planeta (\n#1\n) y entrega su carga:\n<br/>\n" .
				"#2 de metal, #3 de cristal y #4 de deuterio.\n<br/>\n";
$LOCA["es"]["FLEET_TRANSPORT_OTHER"] = "La flota del jugador #1 está entregando suministros a tu planeta #2\n#3\n<br/>\n" .
					"#4 de metal, #5 de cristal y #6 de deuterio\n<br/>\n" .
                    "Antes tenías #7 de metal, #8 de cristal y #9 de deuterio.\n<br/>\n" .
                    "Ahora tienes #10 de metal, #11 de cristal y #12 de deuterio.\n<br/>\n";

$LOCA["es"]["FLEET_RECYCLE"] = "Los #1 recicladores tienen una capacidad total de #2. " .
	"El campo de escombros contiene #3 de metal y #4 de cristal. " .
	"Se reciclaron #5 de metal y #6 de cristal.";

$LOCA["es"]["FLEET_RETURN"] = "Una de tus flotas ( #1 ), enviada desde #2, llega a #3 #4 . ";
$LOCA["es"]["FLEET_RETURN_RES"] = "La flota entrega #1 de metal, #2 de cristal y #3 de deuterio<br>";

$LOCA["es"]["FLEET_DEPLOY"] = "\nUna de tus flotas (#1) llegó a #2\n#3\n. ";
$LOCA["es"]["FLEET_DEPLOY_RES"] = "La flota entrega #1 de metal, #2 de cristal y #3 de deuterio\n<br/>\n";

$LOCA["es"]["FLEET_COLONIZE"] = "\nLa flota llega a las coordenadas establecidas\n#1\n";
$LOCA["es"]["FLEET_COLONIZE_MAX"] = ", y determina que este planeta es adecuado para la colonización. Poco después de que comience la exploración del planeta, se informa de disturbios en el planeta principal, ya que el imperio se vuelve demasiado grande y la gente regresa.\n";
$LOCA["es"]["FLEET_COLONIZE_SUCCESS"] = ", encuentra allí un nuevo planeta y comienza de inmediato a explorarlo.\n";
$LOCA["es"]["FLEET_COLONIZE_FAIL"] = ", pero no encuentra ningún planeta adecuado para la colonización. Los colonos regresan abatidos.\n";
$LOCA["es"]["FLEET_COLONIZE_FROM"] = "Colonos";
$LOCA["es"]["FLEET_COLONIZE_SUBJ"] = "Informe de los colonos";

$LOCA["es"]["FLEET_SPY_OTHER"] = "\nSe ha detectado una flota extranjera procedente del planeta #1\n#2\nen las inmediaciones del planeta #3\n#4\n. Probabilidad de defenderse del espionaje: #5 %\n";

?>
