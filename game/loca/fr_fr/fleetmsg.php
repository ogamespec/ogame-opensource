<?php

// Divers messages relatifs à la flotte (par exemple, Transport, Recyclage, etc.). C'est un véritable désordre. Le même désordre existait dans la version 0.84 de vanilla.
// TODO

// From/Subj
$LOCA["fr"]["FLEET_MESSAGE_FROM"] = "Командование флотом";
$LOCA["fr"]["FLEET_MESSAGE_RETURN"] = "Возвращение флота";
$LOCA["fr"]["FLEET_MESSAGE_HOLD"] = "Удержание флота";
$LOCA["fr"]["FLEET_MESSAGE_INTEL"] = "Разведданные";
$LOCA["fr"]["FLEET_MESSAGE_FLEET"] = "Флот ";
$LOCA["fr"]["FLEET_MESSAGE_OBSERVE"] = "Наблюдение";
$LOCA["fr"]["FLEET_MESSAGE_ARRIVE"] = "Достижение планеты";
$LOCA["fr"]["FLEET_MESSAGE_TRADE"] = "Чужой флот доставляет сырьё";
$LOCA["fr"]["FLEET_MESSAGE_SPY"] = "Шпионаж";
$LOCA["fr"]["FLEET_MESSAGE_BATTLE"] = "Боевой доклад";

$LOCA["fr"]["FLEET_TRANSPORT_OWN"] = "Ваш флот достигает планеты (\n#1\n) и доставляет свой груз:.\n<br/>\n" .
				"#2 металла, #3 кристалла и #4 дейтерия.\n<br/>\n";
$LOCA["fr"]["FLEET_TRANSPORT_OTHER"] = "Чужой флот игрока #1 доставляет на Вашу планету #2\n#3\n<br/>\n" .
					"#4 металла, #5 кристалла и #6 дейтерия\n<br/>\n" .
                    "Прежде у Вас было #7 металла, #8 кристалла и #9 дейтерия.\n<br/>\n" .
                    "Теперь же у Вас #10 металла, #11 кристалла и #12 дейтерия.\n<br/>\n";

$LOCA["fr"]["FLEET_RECYCLE"] = "Переработчики в количестве #1 штук обладают общей грузоподъёмностью в #2. " .
	"Поле обломков содержит #3 металла и #4 кристалла. " .
	"Добыто #5 металла и #6 кристалла.";

$LOCA["fr"]["FLEET_RETURN"] = "Один из Ваших флотов ( #1 ), отправленных с #2, достигает #3 #4 . ";
$LOCA["fr"]["FLEET_RETURN_RES"] = "Флот доставляет #1 металла, #2 кристалла и #3 дейтерия<br>";

$LOCA["fr"]["FLEET_DEPLOY"] = "\nОдин из Ваших флотов (#1) достиг #2\n#3\n. ";
$LOCA["fr"]["FLEET_DEPLOY_RES"] = "Флот доставляет #1 металла, #2 кристалла и #3 дейтерия\n<br/>\n";

$LOCA["fr"]["FLEET_COLONIZE"] = "\nФлот достигает заданных координат\n#1\n";
$LOCA["fr"]["FLEET_COLONIZE_MAX"] = ", и устанавливает, что эта планета пригодна для колонизации. Вскоре после начала освоения планеты поступает сообщение о беспорядках на главной планете, так как империя становится слишком большой и люди возвращаются обратно.\n";
$LOCA["fr"]["FLEET_COLONIZE_SUCCESS"] = ", находит там новую планету и сразу же начинает её освоение.\n";
$LOCA["fr"]["FLEET_COLONIZE_FAIL"] = ", но не находит там пригодной для колонизации планеты. В подавленном состоянии поселенцы возвращаются обратно.\n";
$LOCA["fr"]["FLEET_COLONIZE_FROM"] = "Поселенцы";
$LOCA["fr"]["FLEET_COLONIZE_SUBJ"] = "Доклад поселенцев";

$LOCA["fr"]["FLEET_SPY_OTHER"] = "\nЧужой флот с планеты #1\n#2\nбыл обнаружен вблизи от планеты #3\n#4\n. Шанс на защиту от шпионажа: #5 %\n";

?>