<?php

// デバッグ用の文字列。ときどき母国語で表示されることがあります。

// debug.php

$LOCA["jp"]["DEBUG_ERROR"] = "エラーが発生しました";
$LOCA["jp"]["DEBUG_ERROR_INFO1"] = "緊急プログラムを終了します。";
$LOCA["jp"]["DEBUG_ERROR_INFO2"] = "サポートに連絡するか、「エラー」セクションのフォーラムをご覧ください。";
$LOCA["jp"]["DEBUG_SECURITY_BREACH"] = "セキュリティ違反: ";

// page.php

$LOCA["jp"]["DEBUG_PAGE_INFO"] = "ページ生成時間: %f 秒<br>SQLクエリ数: %d<br>";

$LOCA["jp"]["DEBUG_MANI_SESSION"] = "公開セッションの改ざん";
$LOCA["jp"]["DEBUG_PAYMENT_MANI_COUPON"] = "クーポンコードの改ざん";

// ハッキング試行のデバッグメッセージ。
// ハッキング試行のメモ付きデバッグレポートとして表示されます

$LOCA["jp"]["HACK_ADMIN_PAGE"] = "通常ユーザーによる管理パネルへのアクセス試行。";
$LOCA["jp"]["HACK_SELECT_PLANET"] = "他プレイヤーの惑星または特別な銀河オブジェクトの選択。";
$LOCA["jp"]["HACK_SQL_INJECTION"] = "SQLインジェクションの可能性（URIリクエストまたはGET/POSTパラメータに特定のキーワードが含まれています）。";

// queue.php

$LOCA["jp"]["DEBUG_QUEUE_UNKNOWN"] = "queue: グローバルキューの不明なタスクタイプ: ";
$LOCA["jp"]["DEBUG_QUEUE_CANCEL_RESEARCH_FOREIGN"] = "研究をキャンセルできません -#1-、プレイヤー #2、他惑星 #3 で開始";
$LOCA["jp"]["DEBUG_QUEUE_RESEARCH_COMPLETE"] = "研究 #1 レベル #2 はユーザー #3 に対して完了しました。";
$LOCA["jp"]["DEBUG_QUEUE_OLD_SCORE_SAVED"] = "古いポイントが保存されました、タイムスタンプ #1";
$LOCA["jp"]["DEBUG_QUEUE_CLEAN_PLANETS"] = "破壊された惑星の掃除 (#1)";

// userlogs

$LOCA["jp"]["DEBUG_LOG_BUILD"] = "惑星 #3 で建物 #1 をレベル #2 に建設";
$LOCA["jp"]["DEBUG_LOG_DEMOLISH"] = "惑星 #3 で建物 #1 をレベル #2 に解体";
$LOCA["jp"]["DEBUG_LOG_BUILD_CANCEL"] = "建設キャンセル #1 #2、スロット (#3) 惑星 #4";
$LOCA["jp"]["DEBUG_LOG_DEFENSE"] = "惑星 #3 で防衛 #1 (#2) の建設開始";
$LOCA["jp"]["DEBUG_LOG_SHIPYARD"] = "惑星 #3 で艦隊 #1 (#2) の建造開始";
$LOCA["jp"]["DEBUG_LOG_RESEARCH"] = "惑星 #2 で研究 #1 を開始";
$LOCA["jp"]["DEBUG_LOG_RESEARCH_CANCEL"] = "惑星 #2 で研究 #1 をキャンセル";
$LOCA["jp"]["DEBUG_LOG_FLEET_SEND1"] = "艦隊派遣 #1: ";
$LOCA["jp"]["DEBUG_LOG_FLEET_SEND2"] = "飛行時間: #1、待機: #2、デューテリウム消費: #3、ACS: #4";
$LOCA["jp"]["DEBUG_LOG_FLEET_SEND_AJAX1"] = "艦隊派遣 #1 (AJAX): ";
$LOCA["jp"]["DEBUG_LOG_FLEET_SEND_AJAX2"] = "飛行時間: #1、デューテリウム消費: #2";
$LOCA["jp"]["DEBUG_LOG_FLEET_RECALL"] = "艦隊召還 #1: ";

?>
