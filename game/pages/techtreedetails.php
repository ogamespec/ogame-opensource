<?php

// Technologies (details).

class Techtreedetails extends Page {

    private array $tree = array ();
    private array $filter = array ();

    private int $reclevel = -1;
    private int $maxreclevel = -1;

    public function controller () : bool {
        return true;
    }

    public function view () : void {
        global $requirements;
        global $session;
        global $GlobalUser;
        global $aktplanet;

        $id = intval($_GET['tid']);
        if (!isset($requirements[$id])) return;

        echo "<center> \n";
        echo "<table width=270> \n";
        echo "<tr> \n";
        echo "<td class=c align=center nowrap> \n";
        echo va( loca("TECHTREE_COND_FOR"), "<a href=\"index.php?page=infos&session=$session&gid=$id\">'".loca("NAME_$id")."'</a>") . "</td> \n";
        echo "</tr> \n";

        $this->walk_tree ( $requirements[$id], $id );

        if ( $this->maxreclevel == 0 ) echo "<tr><td class=l align=center>".loca("TECHTREE_COND_NO")."</td></tr> ";

        for ($i=$this->maxreclevel-1,$n=0; $i>=0; $i--,$n++)
        {
            echo "<tr><td class=c>".($n+1)."</td></tr>";

            foreach ( $this->tree[$i] as $v=>$level) 
            {
                $this->filter[$v] = 0;
                if ($this->filter[$v] >= $level) continue;
                $color = "#00ff00";
                if ( !$this->MeetRequirement ( $GlobalUser, $aktplanet, $v, $level ) ) $color = "#ff0000";

                echo "<tr>\n";
                echo "    <td class=l align=center> \n";
                echo "    <table width=\"100%\" border=0> \n";
                echo "    <tr> \n";
                echo "        <td align=left> <font color=\"$color\"> ".loca("NAME_$v")." ".va(loca("TECHTREE_LEVEL"),$level)." </font> </td> \n";
                echo "        <td align=right> <a href=\"index.php?page=techtreedetails&session=$session&tid=$v\">[i]</a> </td> \n";
                echo "    </tr> \n";
                echo "    </td> \n";
                echo "    </table> \n";
                echo "</tr>";

                if ($this->filter[$v] < $level) $this->filter[$v] = $level;
            }
        }

        echo "</table> \n";
        echo "</center>";

        echo "<br><br><br><br>\n";
    }

    private function walk_tree (array $arr, int $id) : void
    {
        global $requirements;
        $this->reclevel++;
        if ($this->reclevel >= $this->maxreclevel) $this->maxreclevel = $this->reclevel;
        if ($arr == null) { $this->reclevel--; return; }

        foreach ($arr as $i=>$level) {
            if ( !key_exists ($this->reclevel, $this->tree) ) $this->tree[$this->reclevel] = array ();
            $this->tree[$this->reclevel][$i] = 0;
            if ($this->tree[$this->reclevel][$i] < $level) $this->tree[$this->reclevel][$i] = $level;
        }
        foreach ($arr as $i=>$level) {
            $this->walk_tree ( $requirements[$i], $i );
        }
        $this->reclevel--;
    }

    private function MeetRequirement ( array $user, array $planet, int $id, int $level ) : bool
    {
        if (IsResearch($id)) return $user[$id] >= $level;
        else return $planet[$id] >= $level;
    }
}

?>