<?php

// インストールスクリプト

$LOCA["jp"]["INSTALL_TITLE"] = "OGame インストール";
$LOCA["jp"]["INSTALL_TIP"] = "ラベル名にマウスオーバーするとヒントが表示されます";

$LOCA["jp"]["INSTALL_STARTPAGE"] = "スタートページのアドレス";
$LOCA["jp"]["INSTALL_DB"] = "ユニバースデータベース設定";
$LOCA["jp"]["INSTALL_DB_HOST"] = "ホスト";
$LOCA["jp"]["INSTALL_DB_USER"] = "ユーザー";
$LOCA["jp"]["INSTALL_DB_PASS"] = "パスワード";
$LOCA["jp"]["INSTALL_DB_NAME"] = "DB名";
$LOCA["jp"]["INSTALL_DB_PREFIX"] = "テーブル接頭辞";
$LOCA["jp"]["INSTALL_DB_SECRET"] = "秘密の言葉";

$LOCA["jp"]["INSTALL_MDB"] = "マスターデータベース設定";
$LOCA["jp"]["INSTALL_MDB_TIP"] = "中央データベースは別のサーバー（通常はスタートページと同じ場所）に置くことができ、すべてのユニバース、クーポン、その他の一般情報を保存します。";
$LOCA["jp"]["INSTALL_MDB_ENABLE"] = "このユニバースをマスターデータベースに追加しますか？";
$LOCA["jp"]["INSTALL_MDB_HOST"] = "ホスト";
$LOCA["jp"]["INSTALL_MDB_USER"] = "ユーザー";
$LOCA["jp"]["INSTALL_MDB_PASS"] = "パスワード";
$LOCA["jp"]["INSTALL_MDB_NAME"] = "DB名";

$LOCA["jp"]["INSTALL_UNI"] = "ユニバース設定";
$LOCA["jp"]["INSTALL_UNI_NUM"] = "ユニバース番号";
$LOCA["jp"]["INSTALL_UNI_SPEED"] = "スピード";
$LOCA["jp"]["INSTALL_UNI_FLEETSPEED"] = "艦隊速度";
$LOCA["jp"]["INSTALL_UNI_G"] = "銀河の数";
$LOCA["jp"]["INSTALL_UNI_S"] = "星系の数";
$LOCA["jp"]["INSTALL_UNI_USERS"] = "最大ユーザー数";
$LOCA["jp"]["INSTALL_UNI_START_DM"] = "初期ダークマター量";
$LOCA["jp"]["INSTALL_UNI_ACS"] = "ACS";
$LOCA["jp"]["INSTALL_UNI_FID"] = "デブリ化する艦隊 (%)";
$LOCA["jp"]["INSTALL_UNI_DID"] = "デブリ化する防御 (%)";
$LOCA["jp"]["INSTALL_UNI_RAPID"] = "速射";
$LOCA["jp"]["INSTALL_UNI_MOONS"] = "月とデス・スター";
$LOCA["jp"]["INSTALL_UNI_BATTLE"] = "戦闘エンジンへのパス";
$LOCA["jp"]["INSTALL_UNI_PHP_BATTLE"] = "PHPベースの戦闘エンジンを使用する";
$LOCA["jp"]["INSTALL_UNI_BATTLE_MAX"] = "片側の最大ユニット数";
$LOCA["jp"]["INSTALL_UNI_FORCE_LANG"] = "ユニバースの言語を強制する";
$LOCA["jp"]["INSTALL_MAX_WERF"] = "造船所の注文における最大ユニット数";
$LOCA["jp"]["INSTALL_FEED_AGE"] = "コマンダー向けRSS/Atom更新間隔（分）";

$LOCA["jp"]["INSTALL_ADMIN"] = "管理者アカウント";
$LOCA["jp"]["INSTALL_ADMIN_EMAIL"] = "Eメール";
$LOCA["jp"]["INSTALL_ADMIN_PASS"] = "パスワード";

$LOCA["jp"]["INSTALL_TIP1"] = "共通のテーブル接頭辞を使用すると、1つのデータベースで複数のユニバースを運用できます";
$LOCA["jp"]["INSTALL_TIP2"] = "パスワードとセッションの生成に使用されます";
$LOCA["jp"]["INSTALL_TIP3"] = "ユニバース番号はページタイトルと左メニューの上部に表示されます。";
$LOCA["jp"]["INSTALL_TIP4"] = "スピードは資源生産、建物と研究の所要時間、最低休暇モード期間に影響します。";
$LOCA["jp"]["INSTALL_TIP5"] = "艦隊速度は飛行時間のみに影響します";
$LOCA["jp"]["INSTALL_TIP6"] = "最大アカウント数。空きができるまで登録はブロックされます。";
$LOCA["jp"]["INSTALL_TIP7"] = "最大ACS招待数。最大ACS艦隊スロット = N * 4（Nは最大ACS招待数）。N = 0の場合、ACSは無効です。";
$LOCA["jp"]["INSTALL_TIP8"] = "戦闘後に艦隊構造の指定%がデブリになります。0 - 艦隊のデブリ化を無効。";
$LOCA["jp"]["INSTALL_TIP9"] = "戦闘後に防御構造の指定%がデブリになります。0 - 防御のデブリ化を無効。";
$LOCA["jp"]["INSTALL_TIP10"] = "艦船は追加射撃を行うチャンスがあります ";
$LOCA["jp"]["INSTALL_TIP11"] = "組み込みのPHPエンジンを使用する場合、外部の戦闘エンジンをコンパイルしてパスを指定する必要はありません。";

$LOCA["jp"]["INSTALL_INSTALL"] = "インストール";

$LOCA["jp"]["INSTALL_ERROR1"] = "設定ファイルを保存できません。";
$LOCA["jp"]["INSTALL_DONE"] = "インストール完了。設定ファイルが作成されました。";

$LOCA["jp"]["INSTALL_EXTERNAL_LINKS"] = "外部リンク";
$LOCA["jp"]["INSTALL_EXTERNAL_LINKS_TIP"] = "外部リンクが指定されていない場合（空文字列）、メニューの対応する項目は表示されません";

// エラーメッセージ

$LOCA["jp"]["INSTALL_ERR_REQUIRED"] = "\"#1\" フィールドは空にできません。";
$LOCA["jp"]["INSTALL_ERR_INT"] = "\"#1\" フィールドは整数である必要があります。";
$LOCA["jp"]["INSTALL_ERR_MIN"] = "\"#1\" フィールドは #2 以上である必要があります。";
$LOCA["jp"]["INSTALL_ERR_MAX"] = "\"#1\" フィールドは #2 を超えてはなりません。";
$LOCA["jp"]["INSTALL_ERR_EMAIL"] = "\"#1\" フィールドは有効なメールアドレスである必要があります。";
$LOCA["jp"]["INSTALL_ERR_PASS_LONG"] = "管理者パスワードは8文字以上である必要があります。";
$LOCA["jp"]["INSTALL_ERR_PREFIX"] = "テーブル接頭辞にはラテン文字、数字、アンダースコアのみ使用できます。";
$LOCA["jp"]["INSTALL_ERR_URL"] = "\"#1\" フィールドは有効なURLまたはホスト名である必要があります。";
$LOCA["jp"]["INSTALL_ERR_DBCONNECT"] = "#1 に接続できません: #2";
$LOCA["jp"]["INSTALL_ERR_DB_SELECT"] = "#1 を選択できません: #2";
$LOCA["jp"]["INSTALL_ERR_DB_UNI"] = "ユニバースデータベース";
$LOCA["jp"]["INSTALL_ERR_DB_MDB"] = "マスターデータベース";

?>
