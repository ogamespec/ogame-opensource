<?php

// Buddy list ("small alliance system")

class Buddy extends Page {

    private int $action = 0;

    public function controller () : bool {
        global $GlobalUser;

        $this->action = key_exists ('action', $_GET) ? intval($_GET['action']) : 0;

        // Process buddy actions
        if ( $this->action == 1 && key_exists('buddy_id', $_GET) && $_GET['buddy_id']) {
            $from = $GlobalUser['player_id'];
            $to = intval ($_GET['buddy_id']);
            if ($from != $to) {
                $buddy_id = AddBuddy ( $from, $to, $_POST['text']);
                if ($buddy_id == 0) $PageError = loca("BUDDY_ALREADY_SENT");
                else SendMessage ( $to, $GlobalUser['oname'], loca("BUDDY_REQUEST"), $_POST['text'], MTYP_PM );
            }
        }
        else if ( $this->action == 2 && key_exists('buddy_id', $_GET) && $_GET['buddy_id']) {
            $buddy_id = intval ($_GET['buddy_id']);
            $buddy = LoadBuddy ($buddy_id);
            AcceptBuddy ($buddy_id);
            SendMessage ( $buddy['request_from'], loca("BUDDY_LIST"), loca("BUDDY_CONFIRM"), va(loca("BUDDY_MSG_ADDED"), $GlobalUser['oname']), MTYP_PM);
        }
        else if ( $this->action == 3 && key_exists('buddy_id', $_GET) && $_GET['buddy_id']) {
            $buddy_id = intval ($_GET['buddy_id']);
            $buddy = LoadBuddy ($buddy_id);
            RemoveBuddy ($buddy_id);
            SendMessage ( $buddy['request_from'], loca("BUDDY_LIST"), loca("BUDDY_REQUEST"), va(loca("BUDDY_MSG_DECLINED"), $GlobalUser['oname']), MTYP_PM);
        }
        else if ( $this->action == 4 && key_exists('buddy_id', $_GET) && $_GET['buddy_id']) {
            $buddy_id = intval ($_GET['buddy_id']);
            $buddy = LoadBuddy ($buddy_id);
            if ( $buddy['request_from'] == $GlobalUser['player_id'] ) {
                RemoveBuddy ($buddy_id);
                SendMessage ( $buddy['request_to'], loca("BUDDY_LIST"), loca("BUDDY_REQUEST"), va (loca("BUDDY_MSG_RECALLED"), $GlobalUser['oname']), MTYP_PM );
            }
        }
        else if ( $this->action == 8 && key_exists('buddy_id', $_GET) && $_GET['buddy_id']) {
            $buddy_id = intval ($_GET['buddy_id']);
            $buddy = LoadBuddy ($buddy_id);
            if ($buddy['request_from'] == $GlobalUser['player_id'] ) {
                RemoveBuddy ($buddy_id);
                SendMessage ( $buddy['request_to'], loca("BUDDY_LIST"), loca("BUDDY_CONFIRM"), va (loca("BUDDY_MSG_DELETED"), $GlobalUser['oname']), MTYP_PM );
            }
            if ($buddy['request_to'] == $GlobalUser['player_id'] ) {
                RemoveBuddy ($buddy_id);
                SendMessage ( $buddy['request_from'], loca("BUDDY_LIST"), loca("BUDDY_CONFIRM"), va (loca("BUDDY_MSG_DELETED"), $GlobalUser['oname']), MTYP_PM );
            }
        }

        return true;
    }

    public function view () : void {
        global $GlobalUser;
        global $session;

        $action = $this->action;

        if ( $action == 5 ) $this->Buddy_Income ();
        else if ( $action == 6 ) $this->Buddy_Outcome ();
        else if ( $action == 7 ) $this->Buddy_Request ();
        else $this->Buddy_Home ();
    }

    private function Buddy_Home () : void {
        global $GlobalUser;
        global $session;
        $now = time ();

        echo "<table width=\"519\">\n";
        echo " <tr><td class=\"c\" colspan=\"6\">".loca("BUDDY_LIST")."</td></tr>\n";
        echo " <tr><th colspan=\"6\"><a href=?page=buddy&session=".$_GET['session']."&action=5>".loca("BUDDY_REQUESTS")."</a></th></tr>\n";
        echo " <tr><th colspan=\"6\"><a href=?page=buddy&session=".$_GET['session']."&action=6>".loca("BUDDY_YOUR_REQUESTS")."</a></th></tr>\n";
        echo " <tr>\n";
        echo "  <td class=\"c\"></td>\n";
        echo "  <td class=\"c\">".loca("BUDDY_NAME")."</td>\n";
        echo "  <td class=\"c\">".loca("BUDDY_ALLY")."</td>\n";
        echo "  <td class=\"c\">".loca("BUDDY_COORD")."</td>\n";
        echo "  <td class=\"c\">".loca("BUDDY_STATUS")."</td>\n";
        echo "  <td class=\"c\"></td>\n";
        echo " </tr>\n";

        $result = EnumBuddy ($GlobalUser['player_id']);
        $num = dbrows ($result);
        if ($num)
        {
            $i = 1;
            while ($num--)
            {
                $buddy = dbarray ($result);
                $user_id = $buddy['request_from'] == $GlobalUser['player_id'] ? $buddy['request_to'] : $buddy['request_from'];
                $user = LoadUser ($user_id);
                $home = LoadPlanetById ($user['hplanetid']);
                echo "<tr>\n";
                echo " <th width=\"20\">$i</th>\n";
                echo " <th><a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\">".$user['oname']."</a></th>\n";
                echo "  <th><a href=ainfo.php?allyid=".$user['ally_id']." target='_ally'> ";
                if ($user['ally_id'] > 0)
                {
                    $ally = LoadAlly ($user['ally_id']);
                    echo $ally['tag'];
                    if ($user['allyrank'] == 0 ) echo "  (G)";
                }
                echo "</a></th>\n";
                echo "  <th><a href=\"index.php?page=galaxy&galaxy=".$home['g']."&system=".$home['s']."&position=".$home['p']."&session=$session\" >[".$home['g'].":".$home['s'].":".$home['p']."]</a></th>\n";
                echo " <th>\n";
                $min = floor ( ($now - $user['lastclick']) / 60 );
                if ( $min < 15 ) echo "    <font color=\"lime\">On</font>\n";
                else if ( $min < 60 ) echo "    <font color=\"yellow\">$min min</font>\n";
                else echo "    <font color=\"red\">Off</font>\n";
                echo " </th>\n";
                echo " <th><a href=\"?page=buddy&session=$session&action=8&buddy_id=".$buddy['buddy_id']."\">".loca("BUDDY_DELETE")."</a></th>\n";
                echo "</tr>\n";
                $i++;
            }
        }
        else echo " <tr><th colspan=\"6\">".loca("BUDDY_NONE")."</th></tr>\n";
        
        echo "</table>\n";
        echo "<br><br><br><br>\n";
    }

    private function Buddy_Income () : void {
        global $GlobalUser;
        global $session;

        echo "<table width=\"519\">\n";
        echo " <tr><td class=\"c\" colspan=\"6\">".loca("BUDDY_REQUESTS")."</td></tr>\n";
        echo " <tr><th colspan=\"6\"><a href=?page=buddy&session=".$_GET['session'].">".loca("BUDDY_BACK")."</a></th></tr>\n";
        echo " </table>\n";
        echo " <table width=\"519\">\n";
        echo "  <tr>\n";
        echo "   <th></th>\n";
        echo "   <th>".loca("BUDDY_USER")."</th>\n";
        echo "   <th>".loca("BUDDY_ALLY")."</th>\n";
        echo "   <th>".loca("BUDDY_COORD")."</th>\n";
        echo "   <th>".loca("BUDDY_TEXT")."</th>\n";
        echo "   <th></th>\n";
        echo "  </tr>\n";

        $result = EnumIncomeBuddy ($GlobalUser['player_id']);
        $num = dbrows ($result);
        if ($num)
        {
            $i = 1;
            while ($num--)
            {
                $buddy = dbarray ($result);
                $user = LoadUser ($buddy['request_from']);
                $home = LoadPlanetById ($user['hplanetid']);
                echo "  <tr>\n";
                echo " <th width=\"20\">$i</th>\n";
                echo "  <th><a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\">".$user['oname']."</a></th>\n";
                if ($user['ally_id'] > 0)
                {
                    $ally = LoadAlly ($user['ally_id']);
                    echo "    <th><a href=index.php?page=ainfo&session=".$_GET['session']."&allyid=".$user['ally_id']." target='_ally'> ";
                    echo $ally['tag'];
                    if ($user['allyrank'] == 0) echo "  (G)";
                    echo "</a></th>\n";
                }
                else echo "    <th><a href=index.php?page=allianzen&session=".$_GET['session'].">  </a></th>\n";
                echo "  <th><a href=\"index.php?page=galaxy&galaxy=".$home['g']."&system=".$home['s']."&position=".$home['p']."&session=".$_GET['session']."\" >[".$home['g'].":".$home['s'].":".$home['p']."]</a></th>\n";
                echo "  <th>".$buddy['text']."</th>\n";
                echo "    <th width=\"100\"><a href=?page=buddy&session=$session&action=2&buddy_id=".$buddy['buddy_id'].">".loca("BUDDY_APPLY")."</a>\n";
                echo "   <a href=?page=buddy&session=$session&action=3&buddy_id=".$buddy['buddy_id'].">".loca("BUDDY_DECLINE")."</a></th>\n";
                echo "  </tr>\n";
                $i++;
            }
        }
        else echo " <tr>   <th colspan=\"6\">".loca("BUDDY_NO_REQUESTS")."</th>  </tr>\n";

        echo " </table>\n";
        echo " <table width=\"519\">\n";
        echo "  <tr>\n";
        echo "   <td class=\"c\" colspan=\"6\"><a href=\"?page=buddy&session=<?=$session;?>\">".loca("BUDDY_BACK")."</a></td>\n";
        echo "  </tr>\n";
        echo " </table>\n";
        echo "<br><br><br><br>\n";
    }

    private function Buddy_Outcome () : void {
        global $GlobalUser;
        global $session;

        echo "<table width=\"519\">\n";
        echo " <tr><td class=\"c\" colspan=\"6\">".loca("BUDDY_YOUR_REQUESTS")."</td></tr>\n";

        $result = EnumOutcomeBuddy ($GlobalUser['player_id']);
        $num = dbrows ($result);
        if ($num)
        {
            $i = 1;
            echo " <tr>\n";
            echo " <th></th>\n";
            echo " <th>".loca("BUDDY_USER")."</th>\n";
            echo "  <th>".loca("BUDDY_ALLY")."</th>\n";
            echo "  <th>".loca("BUDDY_COORD")."</th>\n";
            echo "  <th>".loca("BUDDY_TEXT")."</th>\n";
            echo "  <th></th>\n";
            echo " </tr>\n";
            while ($num--)
            {
                $buddy = dbarray ($result);
                $userto = LoadUser ($buddy['request_to']);
                $home = LoadPlanetById ($userto['hplanetid']);
                echo "  <tr>\n";
                echo " <th width=\"20\">$i</th>\n";
                echo "  <th><a href=\"index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$userto['player_id']."\">".$userto['oname']."</a></th>\n";
                if ($userto['ally_id'] > 0)
                {
                    $ally = LoadAlly ($userto['ally_id']);
                    echo "    <th><a href=index.php?page=ainfo&session=".$_GET['session']."&allyid=".$userto['ally_id']." target='_ally'> ";
                    echo $ally['tag'];
                    if ($userto['allyrank'] == 0) echo "  (G)";
                    echo "</a></th>\n";
                }
                else echo "    <th><a href=index.php?page=allianzen&session=".$_GET['session'].">  </a></th>\n";
                echo "  <th><a href=\"index.php?page=galaxy&galaxy=".$home['g']."&system=".$home['s']."&position=".$home['p']."&session=".$_GET['session']."\" >[".$home['g'].":".$home['s'].":".$home['p']."]</a></th>\n";
                echo "  <th>".$buddy['text']."</th>\n";
                echo "    <th width=\"100\"><a href=?page=buddy&session=".$_GET['session']."&action=4&buddy_id=".$buddy['buddy_id'].">".loca("BUDDY_RECALL")."</a></th>\n";
                echo "  </tr>\n";
                $i++;
            }
        }
        else echo " <tr>   <th colspan=\"6\">".loca("BUDDY_NO_REQUESTS")."</th>  </tr>\n";

        echo " <tr>  <td class=\"c\" colspan=\"6\"><a href=\"?page=buddy&session=".$_GET['session']."\">".loca("BUDDY_BACK")."</a></td> </tr>\n";
        echo "</table><br><br><br><br>\n";
    }

    private function Buddy_Request () : void {
        global $GlobalUser;
        global $session;
        $user = LoadUser ( intval ($_GET['buddy_id']) );
        echo "<form action=\"?page=buddy&session=".$_GET['session']."&action=1&buddy_id=".intval($_GET['buddy_id'])."\" method=\"POST\">\n";
        echo "<table width=\"519\">\n";
        echo " <tr>\n<td class=\"c\" colspan=\"2\">".loca("BUDDY_REQUEST")."</td>\n</tr>\n";
        echo " <tr>\n<th>".loca("BUDDY_PLAYER")."</th>\n<th>".$user['oname']."</th>\n</tr>\n";
        echo " <tr>\n<th>".va(loca("BUDDY_TEXTLEN"), "<span id=\"cntChars\">0</span> / 5000")."</th>\n";
        echo " <th><textarea name=\"text\" cols=\"60\" rows=\"10\" onkeyup=\"javascript:cntchar(5000)\"></textarea></th>\n</tr>\n";
        echo "<tr> \n<td class=\"c\"><a href=\"?page=buddy&session=".$_GET['session']."\">".loca("BUDDY_BACK")."</a></td>\n";
        echo " <td class=\"c\"><input type=\"submit\" value=\"".loca("BUDDY_SEND")."\"></td></tr>\n";
        echo "</table>\n";
        echo "</form><br><br><br><br>\n";
    }
}
?>