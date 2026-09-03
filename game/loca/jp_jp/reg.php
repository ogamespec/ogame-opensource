<?php

// 登録、ログイン、パスワード回復

$LOCA["jp"]["REG_MAIL_TITLE"] = "レビュー";
$LOCA["jp"]["REG_MAIL_SEND"] = "パスワード送信";
$LOCA["jp"]["REG_MAIL_NOTE"] = "有効なゲーム内住所を入力してください。";
$LOCA["jp"]["REG_MAIL_EMAIL"] = "E-Mail:";
$LOCA["jp"]["REG_MAIL_SUBMIT"] = "データ送信";

$LOCA["jp"]["REG_FORGOT_TITLE"] = "#1 パスワードの送信";
$LOCA["jp"]["REG_FORGOT_ERROR"] = "パーマネントアドレスが間違っています。";
$LOCA["jp"]["REG_FORGOT_OK"] = "パスワードは #1.";
$LOCA["jp"]["REG_FORGOT_SUBJ"] = "#1のパスワード";
$LOCA["jp"]["REG_FORGOT_MAIL"] = "#1、ようこそ！\n\n" .
"パスワード#3を入力しなければ、#2の宇宙には戻れない。\n\n" .
"パスワードは、アカウントプロファイルに記載されたアドレスにのみ送信されます。\n\n" .
"パスワード回復をご注文されていない場合は、このメールを無視してください。\n\n" .
"幸運を祈る,\n\n" .
"あなたのO#5チーム.";

// ページ読み込み時のセッションエラー

$LOCA["jp"]["REG_SESSION_INVALID"] = "セッションは無効だ。";
$LOCA["jp"]["REG_SESSION_ERROR"] = "エラーが発生しました";
$LOCA["jp"]["REG_SESSION_ERROR_BODY"] = "    <br /><br />
    セッションは無効だ。<br/><br/>これにはいくつかの理由がある： 
<br>- 同じアカウントに複数回ログインした; 
<br>- 前回ログイン時からIPアドレスが変更されている; 
<br>- AOLまたはプロキシ経由でインターネットにアクセスしている。アカウントの設定メニューでIP認証をオフにしてください。    
    <br /><br />
";

$LOCA["jp"]["REG_NOT_ACTIVATED"] = "あなたのゲームアカウントはまだ有効化されていません。 に行く <a href=index.php?page=options&session=#1>設定</a>, Eメールアドレスを入力し、アクティベーションリンクを受け取る。";
$LOCA["jp"]["REG_PENDING_DELETE"] = "あなたのアカウントは削除されました。削除日 #1";

// 選手がアカウントを有効化せずに書き込みをしようとした。
$LOCA["jp"]["REG_NOT_ACTIVATED_MESSAGE"] = "この機能は、アカウントの有効化後にのみ使用できます。";

// errorpage

$LOCA["jp"]["REG_ERROR"] = "エラー";
$LOCA["jp"]["REG_ERROR_21"] = "あなたはニックネーム#2で宇宙#1に入ろうとした。";
$LOCA["jp"]["REG_ERROR_22"] = "このアカウントが存在しないか、パスワードの入力が間違っています。 ";
$LOCA["jp"]["REG_ERROR_23"] = "入る <a href='#1'>正しいパスワード</a> または <a href='mail.php'>パスワード回復</a>.";
$LOCA["jp"]["REG_ERROR_24"] = "を作成することもできる <a href='new.php'>新規アカウント</a>.";
$LOCA["jp"]["REG_ERROR_31"] = "このアカウントは#1にロックされています <a href=../pranger.php>これ</a>.<br> ご不明な点がございましたら、ブロックされた方にお問い合わせください <a href='#'>オペレーター</a>.<br><br>警告：司令塔のステータスはブロックされても終了しない！";

// new.php

$LOCA["jp"]["REG_NEW_ERROR_AGB"] = "ゲームを開始するには、基本規定に同意する必要があります！";
$LOCA["jp"]["REG_NEW_ERROR_IP"] = "1つのipiからの登録は10分につき1回まで！";
$LOCA["jp"]["REG_NEW_ERROR_CHARS"] = "名前#1に無効な文字が含まれているか、文字数が少なすぎるか多すぎる！";
$LOCA["jp"]["REG_NEW_ERROR_EXISTS"] = "名前#1はすでに存在する";
$LOCA["jp"]["REG_NEW_ERROR_EMAIL"] = "アドレス#1は無効です！";
$LOCA["jp"]["REG_NEW_ERROR_EMAIL_EXISTS"] = "アドレス#1はすでに存在する！";
$LOCA["jp"]["REG_NEW_ERROR_MAX_PLAYERS"] = "最大到達選手数 (#1)!";
$LOCA["jp"]["REG_NEW_TITLE"] = "#2 ユニバース #1 登録";
$LOCA["jp"]["REG_NEW_SUCCESS"] = "登録はうまくいった！";
$LOCA["jp"]["REG_NEW_TEXT"] = "おめでとう, <span class='fine'>ユニバース #1</span>!<br /><br />あなたは#6への登録に成功しました (<span class='fine'>#2</span>). <br />\n".
            "あなたが受け取るのは <span class='fine'>#3</span> パスワードといくつかの重要なリンクが記載された電子メール。<br />\n".
            "プレイするには、以下の方法でログインする必要があります <a href='#4'>ホームページ</a>.<br />\n".
            "この後の写真で、正しいやり方をご覧いただきたい。<br /><br />\n" .
            "<center><a href='#5' style='text-decoration: underline;font-size: large;'>行こう！</a></center><br /><br /> \n" .
            "幸運を祈る<br /> \n" .
            "あなたの#6チーム</th>";
$LOCA["jp"]["REG_NEW_UNI"] = "ユニバース #1";
$LOCA["jp"]["REG_NEW_CHOOSE_UNI"] = "宇宙を選ぶ";
$LOCA["jp"]["REG_NEW_NAME"] = "名前を入力";
$LOCA["jp"]["REG_NEW_PASSWORD"] = "そしてパスワードが送られた！";
$LOCA["jp"]["REG_NEW_ERROR"] = "エラー";
$LOCA["jp"]["REG_NEW_PLAYER_INFO"] = "選手データ";
$LOCA["jp"]["REG_NEW_PLAYER_NAME"] = "ゲーム内名称";
$LOCA["jp"]["REG_NEW_PLAYER_EMAIL"] = "メールアドレス";
$LOCA["jp"]["REG_NEW_ACCEPT"] = "私もそう思う";
$LOCA["jp"]["REG_NEW_AGB"] = "基本規定";
$LOCA["jp"]["REG_NEW_SUBMIT"] = "登録する";
$LOCA["jp"]["REG_NEW_INFO"] = "インフォメーション";

$LOCA["jp"]["REG_NEW_MESSAGE_0"] = "OK";
$LOCA["jp"]["REG_NEW_MESSAGE_101"] = "そのような名前はすでに存在する！";
$LOCA["jp"]["REG_NEW_MESSAGE_102"] = "このアドレスはすでに使用されている！";
$LOCA["jp"]["REG_NEW_MESSAGE_103"] = "名前は3文字以上20文字以内でなければならない！";
$LOCA["jp"]["REG_NEW_MESSAGE_104"] = "アドレスが無効です！";
$LOCA["jp"]["REG_NEW_MESSAGE_105"] = "選手名で結構です";
$LOCA["jp"]["REG_NEW_MESSAGE_106"] = "住所が必要です";
$LOCA["jp"]["REG_NEW_MESSAGE_107"] = "アドレスが無効です！";
$LOCA["jp"]["REG_NEW_MESSAGE_108"] = "1つのipiからの登録は10分につき1回まで！";
$LOCA["jp"]["REG_NEW_MESSAGE_109"] = "プレイヤー数が上限に達しました！";
$LOCA["jp"]["REG_NEW_MESSAGE_201"] = "試合中の名前 <br />これはゲーム内でのあなたのキャラクターの名前です。同じ宇宙で同じ名前はありません。";
$LOCA["jp"]["REG_NEW_MESSAGE_202"] = "Eメール <br />パスワードはこのアドレスに送信されます。間違ったアドレスや無効なアドレスを入力した場合、プレーすることができません。";
$LOCA["jp"]["REG_NEW_MESSAGE_203"] = "";
$LOCA["jp"]["REG_NEW_MESSAGE_204"] = "ゲームを開始するには、基本レギュレーションに同意する必要があります。";

// user.php

$LOCA["jp"]["REG_GREET_MAIL_SUBJ"] = "#1へようこそ ";
$LOCA["jp"]["REG_GREET_MAIL_BODY"] = "ご挨拶 #1,\n\n" .
            "あなたは#7ユニバースの#2に自分の帝国を作ることを決めた！\n\n" .
            "アカウントを有効化するには、このリンクをクリックしてください：\n" .
            "#3\n\n" .
            "あなたの試合の詳細：\n" .
            "ゲーム名: #4\n" .
            "パスワード: #5\n" .
            "ユニバース: #6\n\n\n";
$LOCA["jp"]["REG_GREET_MAIL_BOARD"] = "他の皇帝からの助けやアドバイスが必要な場合は、私たちのフォーラムで見つけることができます (#1).\n\n";
$LOCA["jp"]["REG_GREET_MAIL_TUTORIAL"] = "ここでは（#1）、新参者ができるだけ早くゲームを理解できるよう、選手やチームメンバーが集めたすべての情報を紹介する。\n\n";
$LOCA["jp"]["REG_GREET_MAIL_FOOTER"] = "我々は、あなた方の帝国建設が成功し、今後の戦いで幸運がもたらされることを祈っている！\n\nあなたの#1チーム";

$LOCA["jp"]["REG_CHANGE_MAIL_SUBJ"] = "ゲーム内のメールアドレスが変更されました ";
$LOCA["jp"]["REG_CHANGE_MAIL_BODY"] = "ご挨拶 #1,\n\n" .
            "設定により、#2ユニバースのアカウントの一時的なメールアドレスが#3に変更されました。\n" .
            "1週間以内に変更しなければ、永久に残ってしまう。\n\n" .
            "問題なくプレーを続けるために、以下のリンクから新しいメールアドレスを確認してください：\n\n" .
            "#4\n\n" .
            "あなたの#5チーム";

$LOCA["jp"]["REG_GREET_MSG_SUBJ"] = "#1へようこそ！";
$LOCA["jp"]["REG_GREET_MSG_TEXT"] = "ようこそ[b]#3[/b]へ !\n" .
        "\n" .
        "まずは鉱山を開発する必要がある。\n" .
        "これは \"Buildings\" メニューでできる。\n" .
        "金属鉱山を選択し、建設をクリックする。\n" .
        "今は試合に慣れる時間がある。\n" .
        "ゲームのヘルプは以下のリンクからご覧いただけます： \n" .
        "[url=#1/]チュートリアル[/url]\n" .
        "[url=#2/]フォーラム[/url]\n" .
        "\n" .
        "その間に、あなたの鉱山はもう建設されているはずだ。\n" .
        "鉱山を操業するにはエネルギーが必要なので、それを得るために太陽光発電所を建設する。\n" .
        "これを行うには、もう一度建物メニューに行き、発電所をクリックする。\n" .
        "開発の進捗状況を確認するには、技術メニューに進んでください。\n" .
        "さて、あなたの宇宙を駆け巡る勝利の行進が始まった..。幸運を祈る。\n";

// logout

$LOCA["jp"]["REG_LOGOUT"] = "また会おう";

?>