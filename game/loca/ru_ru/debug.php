<?php

// Различные отладочные строки. Иногда их приятно видеть на родном языке.

// debug.php

$LOCA["ru"]["DEBUG_ERROR"] = "Произошла ошибка";
$LOCA["ru"]["DEBUG_ERROR_INFO1"] = "Аварийное завершение программы.";
$LOCA["ru"]["DEBUG_ERROR_INFO2"] = "Обратитесь в Службу поддержки или на форум, в раздел \"Ошибки\".";
$LOCA["ru"]["DEBUG_SECURITY_BREACH"] = "Нарушение безопасности: ";

// page.php

$LOCA["ru"]["DEBUG_PAGE_INFO"] = "Страница сгенерирована за %f секунд<br>Количество SQL запросов: %d<br>";

$LOCA["ru"]["DEBUG_MANI_SESSION"] = "Манипулирование публичной сессией";
$LOCA["ru"]["DEBUG_PAYMENT_MANI_COUPON"] = "Манипулирование кодом купона";

// Сообщения о попытках взлома игры.
// Выводятся в debug с пометкой HACKING ATTEMPT

$LOCA["ru"]["HACK_ADMIN_PAGE"] = "Попытка открытия админ панели обычным пользователем.";
$LOCA["ru"]["HACK_SELECT_PLANET"] = "Выбор чужой планеты или выбор специального объекта Галактики.";
$LOCA["ru"]["HACK_SQL_INJECTION"] = "Возможно попытка SQL-инъекции (наличие ключевых слов в запросе).";

// queue.php

$LOCA["ru"]["DEBUG_QUEUE_UNKNOWN"] = "queue: Неизвестный тип задания для глобальной очереди: ";
$LOCA["ru"]["DEBUG_QUEUE_CANCEL_RESEARCH_FOREIGN"] = "Невозможно отменить исследование -#1-, игрока #2, запущенное на чужой планете #3";
$LOCA["ru"]["DEBUG_QUEUE_RESEARCH_COMPLETE"] = "Исследование #1 уровня #2 для пользователя #3 завершено.";
$LOCA["ru"]["DEBUG_QUEUE_OLD_SCORE_SAVED"] = "Старые очки сохранены, таймстамп #1";
$LOCA["ru"]["DEBUG_QUEUE_CLEAN_PLANETS"] = "Чистка уничтоженных планет (#1)";

// userlogs

$LOCA["ru"]["DEBUG_LOG_BUILD"] = "Постройка #1 #2 на планете #3";
$LOCA["ru"]["DEBUG_LOG_DEMOLISH"] = "Снос #1 #2 на планете #3";
$LOCA["ru"]["DEBUG_LOG_BUILD_CANCEL"] = "Отмена строительства #1 #2, слот (#3) на планете #4";
$LOCA["ru"]["DEBUG_LOG_DEFENSE"] = "Запустить постройку обороны #1 (#2) на планете #3";
$LOCA["ru"]["DEBUG_LOG_SHIPYARD"] = "Запустить постройку флота #1 (#2) на планете #3";
$LOCA["ru"]["DEBUG_LOG_RESEARCH"] = "Запустить исследование #1 на планете #2";
$LOCA["ru"]["DEBUG_LOG_RESEARCH_CANCEL"] = "Отменить исследование #1 на планете #2";
$LOCA["ru"]["DEBUG_LOG_FLEET_SEND1"] = "Отправка флота #1: ";
$LOCA["ru"]["DEBUG_LOG_FLEET_SEND2"] = "Время полёта: #1, удержание: #2, затраты дейтерия: #3, союз: #4";
$LOCA["ru"]["DEBUG_LOG_FLEET_SEND_AJAX1"] = "Отправка флота #1 (AJAX): ";
$LOCA["ru"]["DEBUG_LOG_FLEET_SEND_AJAX2"] = "Время полёта: #1, затраты дейтерия: #2";
$LOCA["ru"]["DEBUG_LOG_FLEET_RECALL"] = "Отзыв флота #1: ";

?>