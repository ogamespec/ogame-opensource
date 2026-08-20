<?php

// My Alliance Menu

// ⚠️Important! This game feature involves a rich interaction with input from the user.
// You need to pay a lot of attention to the security of the input data (size and content checks).

// The alliance sub-pages (member list, ranks, settings, circular, misc) are
// plain functions (PageAlly_*), not methods of the Allianzen class. They were
// included by the page in the pre-MVC layout; the controller still calls them,
// so include the files here.
include "allianzen_members.php";
include "allianzen_ranks.php";
include "allianzen_settings.php";
include "allianzen_circular.php";
include "allianzen_misc.php";

class Allianzen extends Page {

    private string $SearchResults = "";
    private array $ally = [];
    private int $action = 0;
    private bool $ally_created = false;

    public function controller () : bool {
        global $GlobalUser;
        global $session;
        global $PageError;

        $this->SearchResults = "";

        // Handle POST requests for alliance creation/search
        if ( $GlobalUser['ally_id'] == 0 && key_exists('a', $_GET) ) {
            $weiter = 0;
            if (key_exists('weiter', $_GET) && $_GET['weiter'] == 1) $weiter = 1;

            if ( $_GET['a'] == 1 && $weiter == 1 ) {
                $_POST['tag'] = str_replace ( "\"", "", $_POST['tag']);
                $_POST['tag'] = str_replace ( "'", "", $_POST['tag']);
                $_POST['name'] = str_replace ( "\"", "", $_POST['name']);
                $_POST['name'] = str_replace ( "'", "", $_POST['name']);

                if (mb_strlen ($_POST['tag'], "UTF-8")  < 3) $PageError = loca("ALLY_FOUND_ERROR_TAG");
                else if (mb_strlen ($_POST['name'], "UTF-8")  < 3) $PageError = loca("ALLY_FOUND_ERROR_NAME");
                else if (IsAllyTagExist ($_POST['tag'])) $PageError = va(loca("ALLY_FOUND_ERROR_EXISTS"), $_POST['tag']);
                else {
                    CreateAlly ($GlobalUser['player_id'], $_POST['tag'], $_POST['name']);
                    // The classic page rendered an inline success confirmation
                    // (ALLY_FOUND_SUCCESS + confirm form) and exited; keep that
                    // instead of redirecting away.
                    $this->ally_created = true;
                }
            }
            else if ( $_GET['a'] == 2 ) {
                if ( key_exists ('suchtext', $_POST) && $_POST['suchtext'] !== "" ) {
                    $result = SearchAllyTag ($_POST['suchtext']);
                    $this->AllyPage_SearchResult ($result);
                }
            }
        }

        // Handle alliance actions (the PageAlly_* functions below both process
        // POST requests and render the sub-page, so they must run inside the
        // page content, i.e. from view(), not from here).
        if ( $GlobalUser['ally_id'] != 0 && key_exists ('a', $_GET) ) {
            // Remember which sub-page to render in view(). Actions that need
            // POST processing are handled there too, since the PageAlly_*
            // functions are view functions in the pre-MVC layout.
            $this->action = intval($_GET['a']);
        }

        return true;
    }

    public function view () : void {
        global $GlobalUser;
        global $GlobalUni;
        global $session;
        global $searchmap;

        $SearchResults = $this->SearchResults;

        if ( $GlobalUser['ally_id'] != 0 && $this->action > 0 ) {
            // The PageAlly_* functions read the alliance via the global $ally
            // (like the pre-MVC layout did), so load it into $GLOBALS.
            $GLOBALS['ally'] = LoadAlly ($GlobalUser['ally_id']);
            // The pre-MVC page output these scripts before calling the
            // sub-page functions (which may output them again); keep the same
            // output.
            echo "<script src=\"js/cntchar.js\" type=\"text/javascript\"></script><script src=\"js/win.js\" type=\"text/javascript\"></script>\n";
            switch ( $this->action ) {
                case 3:  PageAlly_Leave (); break;
                case 4:  PageAlly_MemberList (); break;
                case 5:  PageAlly_Settings (); break;
                case 6:  PageAlly_Ranks (); break;
                case 7:  PageAlly_MemberSettings (); break;
                case 9:  PageAlly_ChangeTag (); break;
                case 10: PageAlly_ChangeName (); break;
                case 11: PageAlly_Settings (); break;
                case 12: PageAlly_Dismiss (); break;
                case 13: PageAlly_MemberSettings (); break;
                case 15: PageAlly_Ranks (); break;
                case 16: PageAlly_MemberSettings (); break;
                case 17: AllyPage_CircularMessage (); break;
                case 18: AllyPage_Takeover (); break;
                default: $this->AllyPage_Home ();
            }
            return;
        }

        echo "<script src=\"js/cntchar.js\" type=\"text/javascript\"></script><script src=\"js/win.js\" type=\"text/javascript\"></script>\n";

        // Alliance successfully created: render the inline success page (like
        // the classic page did before exiting).
        if ( $this->ally_created ) {
            echo "<br/><p>".va(loca("ALLY_FOUND_SUCCESS"), $_POST['name'], $_POST['tag'])."</p>\n";
            echo "<form method=\"post\" action=\"index.php?page=allianzen&session=".$_GET['session']."\">\n";
            echo "<input type=\"submit\" value=\"".loca("ALLY_FOUND_CONFIRM")."\"/></form><br/><br/><br/><br/>\n";
            return;
        }

        if ( $GlobalUser['ally_id'] == 0 ) {
            $app_id = GetUserApplication ($GlobalUser['player_id']);
            if ( $app_id > 0 ) {
                $this->AllyPage_Already ($app_id);
            }
            else {
                if ( key_exists ('a', $_GET) && $_GET['a'] == 1 ) {
                    $tag = "";
                    $name = "";
                    if (key_exists('tag', $_POST)) $tag = $_POST['tag'];
                    if (key_exists('name', $_POST)) $name = $_POST['name'];
                    $this->AllyPage_CreateAlly ( $tag, $name );
                }
                else if ( key_exists ('a', $_GET) && $_GET['a'] == 2 ) {
                    $search_text = "";
                    if (key_exists('suchtext', $_POST)) $search_text = $_POST['suchtext'];
                    $this->AllyPage_Search ( $search_text, $SearchResults );
                }
                else $this->AllyPage_NoAlly ();
            }
        }
        else {
            $this->ally = LoadAlly ($GlobalUser['ally_id']);
            $this->AllyPage_Home ();
        }
    }

    // User is not a member of any alliance, display a menu to create/search for alliances.
    private function AllyPage_NoAlly () : void {
        global $session;
        echo "<table width=519>\n";
        echo "<tr><td class=c colspan=2>".loca("ALLY_ALLY")."</td></tr>\n";
        echo "<tr><th><a href=\"index.php?page=allianzen&session=".$_GET['session']."&a=1\">".loca("ALLY_FOUND_OWN")."</a></th>\n";
        echo "<th><a href=\"index.php?page=allianzen&session=".$_GET['session']."&a=2\">".loca("ALLY_FIND_OTHER")."</a></th></tr>\n";
        echo "</table><br><br><br><br><br>\n";
    }

    // Found your own alliance.
    private function AllyPage_CreateAlly (string $tag, string $name) : void {
        global $session;
        echo "<form action=\"index.php?page=allianzen&session=".$_GET['session']."&a=1&weiter=1\" method=POST>\n";
        echo "<table width=519>\n";
        echo "<tr><td class=c colspan=2>".loca("ALLY_FOUND_ALLY")."</td></tr>\n";
        echo "<tr><th>".loca("ALLY_FOUND_TAG")."</th><th><input type=text name=\"tag\" size=8 maxlength=8 value=\"$tag\"></th></tr>\n";
        echo "<tr><th>".loca("ALLY_FOUND_NAME")."</th><th><input type=text name=\"name\" size=20 maxlength=30 value=\"$name\"></th></tr>\n";
        echo "<tr><th colspan=2><input type=submit value=\"".loca("ALLY_FOUND_SUBMIT")."\"></th></tr></table></form><br><br><br><br>\n";
    }

    // Searching for alliances.
    private function AllyPage_Search (string $text, string $results="") : void {
        global $session;
        echo "<table width=519>\n";
        echo "<tr><td class=c colspan=2>".loca("ALLY_FIND_ALLY")."</td></tr>\n";
        echo "<tr><th>".loca("ALLY_FIND_HEAD")."</th><th>\n";
        echo "<form action=\"index.php?page=allianzen&session=".$_GET['session']."&a=2\" method=POST>\n";
        echo "<input type=text name=suchtext value=\"$text\"><input type=submit value=\"".loca("ALLY_FIND_SUBMIT")."\">\n";
        echo "</th></tr></form></table><br>\n";
        echo "$results\n";
        echo "<br><br><br>\n";
    }

    // Display a table of results.
    private function AllyPage_SearchResult (mixed $result) : void {
        global $session;
        $this->SearchResults = "";
        $rows = dbrows ($result);
        if ($rows == 0) return;
        $this->SearchResults .= "<table width=519>\n";
        $this->SearchResults .= "<tr><td class=c colspan=3>".loca("ALLY_FIND_RESULT")."</th></tr>\n";
        $this->SearchResults .= "<tr><th><center>".loca("ALLY_FIND_TAG")."</center></th><th><center>".loca("ALLY_FIND_NAME")."</center></th><th><center>".loca("ALLY_FIND_MEMBERS")."</center></th></tr>\n";
        if ($rows > 30) $rows = 30;
        for ($i=0; $i<$rows; $i++)
        {
            $ally = dbarray ($result);
            $enum = EnumerateAlly ($ally['ally_id']);
            $players = dbrows ($enum);
            $this->SearchResults .= "<tr><th><center>[<a href=\"index.php?page=bewerben&session=".$_GET['session']."&allyid=".$ally['ally_id']."\">".$ally['tag']."</a>]</center></th>\n";
            $this->SearchResults .= "<th><center>".$ally['name']."</center></th>\n";
            $this->SearchResults .= "<th><center>".$players."</center></th></tr>\n";
        }
        $this->SearchResults .= "</table><br>\n";
    }

    // The user has already applied to the alliance.
    private function AllyPage_Already (int $app_id) : void {
        global $session;
        $app = LoadApplication ($app_id);
        $ally = LoadAlly ( $app['ally_id'] );

        if ( method () === "POST" ) {
            if ( key_exists ( 'bcancel', $_POST ) ) RemoveApplication ( $app['app_id'] );
        }

        ?>
        <table width=519>
        <form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
        <tr><td class=c colspan=2><?=loca("ALLY_APPLY");?></td></tr>
        <tr><th colspan=2><?=va(loca("ALLY_APPLY_ALREADY"), $ally['tag']);?></th></tr>
        <tr><th colspan=2><input type=submit name="bcancel" value="<?=loca("ALLY_APPLY_WITHDRAW");?>"></th></tr>
        </table></form><br><br><br><br>
        <?php
    }

    private function AllyPage_Home () : void {
        global $GlobalUser;
        global $session;
        $ally = $this->ally;

        $now = time ();
        $members = CountAllyMembers ( $ally['ally_id'] );
        $rank = LoadRank ( $GlobalUser['ally_id'], $GlobalUser['allyrank'] );

        $result = EnumApplications ( $ally['ally_id'] );
        $apps = dbrows ($result);

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<?php
        if ( $ally['imglogo'] !== "" ) 
        {
?>
<tr><th colspan=2><img src="/game/img/preload.gif" class="reloadimage" title="pic.php?url=<?=$ally['imglogo'];?>"></td></tr>
<?php
        }
?>
<table width=519>
<tr><td class=c colspan=2><?=loca("ALLY_MAIN_HEAD");?></td></tr>
<tr><th><?=loca("ALLY_MAIN_TAG");?></th><th><?=$ally['tag'];?>
<?php
    if ( $now < $ally['tag_until'] ) echo " (".va(loca("ALLY_MAIN_PREV"), $ally['old_tag']).")";
?>
</th></tr>
<tr><th><?=loca("ALLY_MAIN_NAME");?></th><th><?=$ally['name'];?>
<?php
    if ( $now < $ally['name_until'] ) echo " (".va(loca("ALLY_MAIN_PREV"), $ally['old_name']).")";
?>
</th></tr>
<tr><th><?=loca("ALLY_MAIN_MEMBERS");?></th><th><?=$members;?>
<?php
    if ( $rank['rights'] & ARANK_R_MEMBERS ) echo " (<a href=\"index.php?page=allianzen&session=$session&a=4\">".loca("ALLY_MAIN_MEMBERS_LINK")."</a>)";
?>
</th></tr>
<tr><th><?=loca("ALLY_MAIN_RANK");?></th><th><?=$rank['name'];?>
<?php
    if ( $rank['rights'] & ARANK_W_MEMBERS ) echo " (<a href=\"index.php?page=allianzen&session=$session&a=5\">".loca("ALLY_MAIN_SETTINGS_LINK")."</a>)";
?>
</th></tr>
<?php
    if ( $apps > 0 )
    {
?>
<tr><th><?=loca("ALLY_MAIN_APPS");?></th><th><a href="index.php?page=bewerbungen&session=<?=$session;?>"><?=va(loca("ALLY_MAIN_APP_COUNT"), $apps);?></a></th></tr>
<?php
    }
?>
<?php
    if ( $rank['rights'] & ARANK_CIRCULAR )
    {
?>
<tr><th><?=loca("ALLY_MAIN_CIRCULAR");?></th><th><a href="index.php?page=allianzen&session=<?=$session;?>&a=17"><?=loca("ALLY_MAIN_CIRCULAR_LINK");?></a></th></tr>
<?php
    }
?>
<tr><th colspan=2 height=100><?=bb($ally['exttext']);?></th></tr>
<tr><th><?=loca("ALLY_MAIN_HOMEPAGE");?></th><th><a href="redir.php?url=<?=$ally['homepage'];?>" target="_blank"><?=$ally['homepage'];?></a></th></tr>
<tr><td class=c colspan=2><?=loca("ALLY_MAIN_INTTEXT");?></th></tr><tr><th colspan=2 height=100><?=bb($ally['inttext']);?></th></tr>
</table><br>
<?php
    if ( $GlobalUser['allyrank'] != 0 )    // Do not show the Founder the dialog of leaving the alliance.
    {
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=3" method=POST>
<tr><td class=c colspan=2><?=loca("ALLY_MAIN_LEAVE");?></td></tr><tr><th colspan=2><input type=submit value="<?=loca("ALLY_MAIN_LEAVE_SUBMIT");?>"></th></tr></table></form>
<?php
    }
    }
}
?>