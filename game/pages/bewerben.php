<?php

// Apply for an alliance.

class Bewerben extends Page {

    private ?array $ally = null;
    private string $template = "";
    private bool $submitted = false;

    public function controller () : bool {
        global $GlobalUser;
        global $session;

        if ( ! $GlobalUser['validated'] ) Error ( loca("ALLY_APPU_NOT_ACTIVATED") );

        $ally_id = intval($_GET['allyid'] ?? 0);
        $ally = LoadAlly ($ally_id);
        $this->ally = is_array ($ally) ? $ally : null;

        if ( key_exists('weiter', $_POST) && $_POST['weiter'] === loca("ALLY_APPU_TEMPLATE") || $this->ally['insertapp']) {
            $this->template = $this->ally['apptext'];
            if ($this->template === "") $this->template = loca("ALLY_APPU_TEMPLATE_MISSING");
        }

        if ( method() === "POST" && key_exists('weiter', $_POST) && $_POST['weiter'] === loca("ALLY_APPU_SUBMIT") && $this->ally['open'] ) {
            $text = $_POST['text'];
            $text = addslashes ( $text );
            AddApplication ( $this->ally['ally_id'], $GlobalUser['player_id'], $text );
            // The pre-MVC page rendered a "submitted" block here instead of
            // redirecting, so mark the application as submitted and let view()
            // render the confirmation.
            $this->submitted = true;
        }

        return true;
    }

    public function view () : void {
        global $GlobalUser;
        global $session;

        $maxchars = 6000;
        $ally = $this->ally;
        $template = $this->template;

        if ( $this->submitted ) {
            ?>
            <h1><?=loca("ALLY_APPU_REG");?></h1>
            <table width=519>
            <form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
            <tr><th colspan=2><?=loca("ALLY_APPU_SUBMITTED");?></th></tr>
            <tr><th colspan=2><input type=submit value="<?=loca("ALLY_APPU_OK");?>"></th></tr>
            </table></form></center><br><br><br><br>
            <?php
            return;
        }

        if ( $ally['open'] ) {
            ?>
            <h1><?=loca("ALLY_APPU_REG");?></h1>
            <table width=519>
            <form action="index.php?page=bewerben&session=<?=$session;?>&allyid=<?=$ally['ally_id'];?>" method=POST>
            <tr><td class=c colspan=2><?=va(loca("ALLY_APPU_TITLE"), $ally['tag']);?></td></tr>
            <tr><th><?=va(loca("ALLY_APPU_TEXT"), "<span id=\"cntChars\">0</span>", $maxchars);?></th><th><textarea name="text" cols=40 rows=10 onkeyup="javascript:cntchar(<?=$maxchars;?>)"><?=$template;?></textarea></th></tr>
            <tr><th><?=loca("ALLY_APPU_HINT");?></th><th><input type=submit name="weiter" value="<?=loca("ALLY_APPU_TEMPLATE");?>"></th></tr>
            <tr><th colspan=2><input type=submit name="weiter" value="<?=loca("ALLY_APPU_SUBMIT");?>"></th></tr>
            </table></form></center><br><br><br><br>
            <?php
        }
        else {
            ?>
            <h1><?=loca("ALLY_APPU_REG");?></h1>
            <table width=519>
            <form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
            <tr><td class=c><?=va(loca("ALLY_APPU_FORBIDDEN"), $ally['tag']);?></td></tr>
            <tr><th><?=loca("ALLY_APPU_CLOSED");?></th></th></tr>
            <tr><th><input type=submit value="<?=loca("ALLY_APPU_BACK");?>"></th></tr></table></form></center><br><br><br><br>
            <?php
        }
    }
}
?>