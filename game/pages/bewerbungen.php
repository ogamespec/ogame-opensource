<?php

// Buddy applications list

class Bewerbungen extends Page {

    public function controller () : bool {
        global $GlobalUser;

        // Process GET action
        if ( key_exists ('action', $_GET) ) {
            if ( $_GET['action'] == 1 && key_exists('buddy_id', $_GET) ) {
                $buddy_id = intval ($_GET['buddy_id']);
                $buddy = LoadBuddy ($buddy_id);
                if ( $buddy['request_to'] == $GlobalUser['player_id'] ) {
                    AcceptBuddy ($buddy_id);
                    SendMessage ( $buddy['request_from'], loca("BUDDY_LIST"), loca("BUDDY_CONFIRM"), va(loca("BUDDY_MSG_ADDED"), $GlobalUser['oname']), MTYP_PM);
                }
            }
            else if ( $_GET['action'] == 2 && key_exists('buddy_id', $_GET) ) {
                $buddy_id = intval ($_GET['buddy_id']);
                $buddy = LoadBuddy ($buddy_id);
                if ( $buddy['request_to'] == $GlobalUser['player_id'] ) {
                    RemoveBuddy ($buddy_id);
                    SendMessage ( $buddy['request_from'], loca("BUDDY_LIST"), loca("BUDDY_REQUEST"), va(loca("BUDDY_MSG_DECLINED"), $GlobalUser['oname']), MTYP_PM);
                }
            }
            else if ( $_GET['action'] == 3 && key_exists('buddy_id', $_GET) ) {
                $buddy_id = intval ($_GET['buddy_id']);
                $buddy = LoadBuddy ($buddy_id);
                if ( $buddy['request_from'] == $GlobalUser['player_id'] ) {
                    RemoveBuddy ($buddy_id);
                    SendMessage ( $buddy['request_to'], loca("BUDDY_LIST"), loca("BUDDY_REQUEST"), va (loca("BUDDY_MSG_RECALLED"), $GlobalUser['oname']), MTYP_PM );
                }
            }
        }

        return true;
    }

    public function view () : void {
        global $GlobalUser;
        global $session;

        echo "<table width=\"519\">\n";
        echo " <tr><td class=\"c\" colspan=\"6\">".loca("BUDDY_APPLICATIONS")."</td></tr>\n";

        $result = EnumApplications ($GlobalUser['player_id']);
        $num = dbrows ($result);
        if ($num)
        {
            echo " <tr>\n";
            echo " <th></th>\n";
            echo " <th>".loca("BUDDY_USER")."</th>\n";
            echo "  <th>".loca("BUDDY_ALLY")."</th>\n";
            echo "  <th>".loca("BUDDY_COORD")."</th>\n";
            echo "  <th>".loca("BUDDY_TEXT")."</th>\n";
            echo "  <th></th>\n";
            echo " </tr>\n";
            $i = 1;
            while ($num--)
            {
                $app = dbarray ($result);
                $userfrom = LoadUser ($app['player_id']);
                $home = LoadPlanetById ($userfrom['hplanetid']);
                echo "  <tr>\n";
                echo " <th width=\"20\">$i</th>\n";
                echo "  <th><a href=\"index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$userfrom['player_id']."\">".$userfrom['oname']."</a></th>\n";
                if ($userfrom['ally_id'] > 0)
                {
                    $ally = LoadAlly ($userfrom['ally_id']);
                    echo "    <th><a href=index.php?page=ainfo&session=".$_GET['session']."&allyid=".$userfrom['ally_id']." target='_ally'> ";
                    echo $ally['tag'];
                    if ($userfrom['allyrank'] == 0) echo "  (G)";
                    echo "</a></th>\n";
                }
                else echo "    <th><a href=index.php?page=allianzen&session=".$_GET['session'].">  </a></th>\n";
                echo "  <th><a href=\"index.php?page=galaxy&galaxy=".$home['g']."&system=".$home['s']."&position=".$home['p']."&session=".$_GET['session']."\" >[".$home['g'].":".$home['s'].":".$home['p']."]</a></th>\n";
                echo "  <th>".$app['text']."</th>\n";
                echo "    <th width=\"100\"><a href=?page=bewerbungen&session=".$_GET['session']."&action=1&buddy_id=".$app['app_id'].">".loca("BUDDY_APPLY")."</a>\n";
                echo "   <a href=?page=bewerbungen&session=".$_GET['session']."&action=2&buddy_id=".$app['app_id'].">".loca("BUDDY_DECLINE")."</a></th>\n";
                echo "  </tr>\n";
                $i++;
            }
        }
        else echo " <tr>   <th colspan=\"6\">".loca("BUDDY_NO_APPLICATIONS")."</th>  </tr>\n";

        echo " <tr>  <td class=\"c\" colspan=\"6\"><a href=\"?page=buddy&session=".$_GET['session']."\">".loca("BUDDY_BACK")."</a></td> </tr>\n";
        echo "</table><br><br><br><br>\n";
    }
}
?>
