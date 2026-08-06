<?php

// Search page

class Suche extends Page {

    private string $search_text = "";
    private int $start = 1;
    private string $type = "player";

    public function controller () : bool {
        global $GlobalUser;

        $this->search_text = key_exists('search', $_REQUEST) ? $_REQUEST['search'] : "";
        $this->start = key_exists('start', $_REQUEST) ? intval($_REQUEST['start']) : 1;
        $this->type = key_exists('type', $_REQUEST) ? $_REQUEST['type'] : "player";

        return true;
    }

    public function view () : void {
        global $GlobalUser;
        global $db_prefix;
        global $session;

        $search_text = $this->search_text;
        $start = $this->start;
        $type = $this->type;

        if ($type === "player") {
            $result = SearchUser ($search_text, $start);
            $rows = dbrows ($result);
            ?>
            <table width="519">
            <tr><td class="c" colspan="4"><?=loca("SEARCH_RESULTS");?></td></tr>
            <tr><th><?=loca("SEARCH_NAME");?></th><th><?=loca("SEARCH_COORD");?></th><th><?=loca("SEARCH_ALLY");?></th><th><?=loca("SEARCH_ACTION");?></th></tr>
            <?php
            while ($rows--) {
                $user = dbarray ($result);
                $home = LoadPlanetById ($user['hplanetid']);
                echo "<tr><th><a href=\"index.php?page=galaxy&no_header=1&session=$session&p1=".$home['g']."&p2=".$home['s']."&p3=".$home['p']."\">".$user['oname']."</a></th>";
                echo "<th>[".$home['g'].":".$home['s'].":".$home['p']."]</th>";
                if ($user['ally_id'] > 0) {
                    $ally = LoadAlly ($user['ally_id']);
                    echo "<th><a href=ainfo.php?allyid=".$user['ally_id']." target='_ally'>".$ally['tag']."</a></th>";
                }
                else echo "<th></th>";
                echo "<th><a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\">".loca("SEARCH_MESSAGE")."</a></th>";
                echo "</tr>\n";
            }
            echo "</table><br><br><br><br>\n";
        }
        else if ($type === "ally") {
            $result = SearchAllyTag ($search_text);
            $rows = dbrows ($result);
            ?>
            <table width="519">
            <tr><td class="c" colspan="3"><?=loca("SEARCH_ALLY_RESULTS");?></td></tr>
            <tr><th><?=loca("SEARCH_ALLY_TAG");?></th><th><?=loca("SEARCH_ALLY_NAME");?></th><th><?=loca("SEARCH_ALLY_MEMBERS");?></th></tr>
            <?php
            while ($rows--) {
                $ally = dbarray ($result);
                $enum = EnumerateAlly ($ally['ally_id']);
                $players = dbrows ($enum);
                echo "<tr><th><a href=\"index.php?page=bewerben&session=$session&allyid=".$ally['ally_id']."\">".$ally['tag']."</a></th>";
                echo "<th>".$ally['name']."</th><th>".$players."</th></tr>\n";
            }
            echo "</table><br><br><br><br>\n";
        }
    }
}
?>
