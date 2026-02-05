<?php

// Resource Settings.

class Resources extends Page {

    private bool $debug_no_rounding = false;

    public function controller () : bool {
        global $GlobalUser;
        global $db_prefix;
        global $aktplanet;

        // POST requests processing (you cannot change energy settings in VM)
        if ( method () === "POST" && !$GlobalUser['vacation'] )
        {
            global $PlanetProd;

            foreach ($PlanetProd as $gid=>$prod) {

                $exist = key_exists ( 'last'.$gid, $_POST ) ? true : false;
                $last = key_exists ( 'last'.$gid, $_POST ) ? intval($_POST['last'.$gid]) : 0;

                // Checking for incorrect parameters.
                if ($last > 100) Error ( "resources: Attempt to set prod settings to more than 100%" );
                if ( $last < 0 ) $last = 0;        // It should not be < 0.

                // Make multiples of 10.
                $last = round ($last / 10) * 10 / 100;

                $planet_id = $aktplanet['planet_id'];
                if ( $exist ) {
                    $query = "UPDATE ".$db_prefix."planets SET ";
                    $query .= "prod$gid = $last ";
                    $query .= "WHERE planet_id = $planet_id";
                    dbquery ($query);
                }
            }

            $aktplanet = GetUpdatePlanet ( $GlobalUser['aktplanet'], time() );    // reload the planet.
            if ($aktplanet == null) {
                Error ("Can't get aktplanet");
            }
        }
        return true;
    }

    private function GetResourceBonus (array $planet, int $gid, int $rc, bool $produce) : string {

        global $GlobalUser;
        $prem = PremiumStatus ($GlobalUser);

        // First, take into account the bonuses offered by the original mechanics of 0.84

        $bonuses = [];

        switch ($rc) {
            case GID_RC_METAL:
            case GID_RC_CRYSTAL:
            case GID_RC_DEUTERIUM:
                if ($gid == GID_B_METAL_MINE || $gid == GID_B_CRYS_MINE || $gid == GID_B_DEUT_SYNTH) {
                    $prem = PremiumStatus ($GlobalUser);
                    if ($prem['geologist']) {
                        $bonus = [];
                        $bonus['img'] = "img/geologe_ikon.gif";
                        $bonus['alt'] = loca("PREM_GEOLOGE");
                        $bonus['overlib'] = "<font color=#ffffff>".loca("PREM_GEOLOGE")."</font>";
                        $bonus['width'] = 80;
                        $bonuses[] = $bonus;
                    }
                }
                break;

            case GID_RC_ENERGY:
                if ($gid == GID_B_SOLAR || $gid == GID_B_FUSION || $gid == GID_F_SAT) {
                    $prem = PremiumStatus ($GlobalUser);
                    if ($prem['engineer']) {
                        $bonus = [];
                        $bonus['img'] = "img/ingenieur_ikon.gif";
                        $bonus['alt'] = loca("PREM_ENGINEER");
                        $bonus['overlib'] = "<font color=#ffffff>".loca("PREM_ENGINEER")."</font>";
                        $bonus['width'] = 80;
                        $bonuses[] = $bonus;
                    }
                }
                break;
        }

        // Get visual bonuses from modifications

        $param = [];
        $param['user'] = $GlobalUser;
        $param['planet'] = $planet;
        $param['gid'] = $gid;
        $param['rc'] = $rc;
        $param['produce'] = $produce;
        ModsExecArrRef ('page_resources_get_bonus', $param, $bonuses);

        $text = "";
        foreach ($bonuses as $i=>$bonus) {

            $text .= "<img border=\"0\" src=\"".$bonus['img']."\" alt=\"".$bonus['alt']."\" ";
            $text .= "onmouseover='return overlib(\"".$bonus['overlib']."\", WIDTH, ".$bonus['width'].");' ";
            $text .= "onmouseout='return nd();' width=\"20\" height=\"20\">";
        }

        return $text === "" ? "&nbsp;" : $text;
    }

    public function view () : void {

        global $GlobalUni;
        global $GlobalUser;
        global $aktplanet;
        global $resourcemap;
        global $PlanetProd;
        global $naturalProduction;
        global $resourcesWithNonZeroDerivative;

        $speed = $GlobalUni['speed'];
        $planet = $aktplanet;

        // Get a list of resource types to display their production
        $reslist = [];
        foreach ($resourcemap as $i=>$rc) {

            // If the resource is found in production or consumption, add it to the list
            foreach ($PlanetProd as $ii=>$rules) {
                if (isset($rules['prod'][$rc]) || isset($rules['cons'][$rc])) {
                    $reslist[] = $rc;
                    break;
                }
            }
        }

        echo "<center> \n";
        echo "<br> \n";
        echo "<br> \n";
        echo va(loca("RES_FACTOR")." ", round($planet['factor'],2))."\n";

        // Not known for what, but it's in the original game.
        $count = 0;
        $result = EnumPlanets ();
        $rows = dbrows ($result);
        while ($rows--)
        {
            $pl = dbarray ($result);
            if ( $pl['type'] == PTYP_PLANET ) $count++;
        }
        if ( $count > MAX_PLANET ) echo "<br><font color=#ff000>".loca("RES_INFO")."</font>";

        echo "<form action=\"index.php?page=resources&session=".$_GET['session']."\" method=\"post\" id='ressourcen'> \n";
        echo "<input type=hidden name='screen' id='screen'> \n";
        echo "<table width=\"550\"> \n";

        echo "  <tr> \n";
        echo "    <td class=\"c\" colspan=\"6\"> \n";
        echo "    ".loca("RES_PROD")." &quot;".$planet['name']."&quot;\n";
        echo "    </td> \n";
        echo "  </tr>\n";

        // List resources
        echo "  <tr> \n";
        echo "   <th colspan=\"2\"></th>";
        foreach ($reslist as $i=>$rc) {
            echo "    <th>".loca("NAME_".$rc)."</th>";
        }
        echo "</th> \n";
        echo "  </tr>\n";

        // Natural production
        echo "  <tr> \n";
        echo "   <th colspan=\"2\">".loca("RES_NATURAL")."</th> \n";
        foreach ($reslist as $i=>$rc) {
            $val = (isset($naturalProduction[$rc]) ? $naturalProduction[$rc] : 0) * $speed;
            echo "    <td class=\"k\">".$val."</td>\n";
        }
        echo "  </tr>\n";

        foreach ($PlanetProd as $gid=>$rules) {

            if ($planet[$gid]) {

                echo "  <tr> \n";
                echo "<th>".loca("NAME_".$gid)." (".va(loca(IsBuilding($gid) ? "RES_LEVEL" : "RES_AMOUNT"), $planet[$gid]).")</th>";

                echo "<th>";
                foreach ($reslist as $i=>$rc) {
                    if (isset($rules['prod'][$rc])) {
                        echo $this->GetResourceBonus($planet, $gid, $rc, true);
                    }
                    if (isset($rules['cons'][$rc])) {
                        echo $this->GetResourceBonus($planet, $gid, $rc, false);
                    }
                }
                echo "</th>\n";

                foreach ($reslist as $i=>$rc) {

                    $val_with_bonus = 0;

                    $produced = false;
                    if (isset($rules['prod'][$rc]) && $planet['prod_with_bonus'][$gid] != 0) {
                        $val_with_bonus = $planet['prod_with_bonus'][$gid];
                        $produced = true;
                    }
                    else if (isset($rules['cons'][$rc]) && $planet['cons_with_bonus'][$gid] != 0) {
                        $val_with_bonus = - $planet['cons_with_bonus'][$gid];
                        $produced = false;
                    }

                    $color = $val_with_bonus > 0 ? '00FF00' : ($val_with_bonus < 0 ? 'FF0000' : 'FFFFFF');
                    $deriv = in_array ($rc, $resourcesWithNonZeroDerivative, true);
                    echo "   <th><font color=\"#$color\">";
                    if ($deriv) {
                        echo $this->nicenum2($val_with_bonus);
                    }
                    else {
                        if ($produced) {
                            echo $this->nicenum2($val_with_bonus);
                        }
                        else {
                            $val_factor = abs($val_with_bonus) * $planet['factor'];
                            echo $this->nicenum2(abs($val_factor))."/".$this->nicenum2(abs($val_with_bonus));
                        }
                    }
                    echo "</font></th>\n";
                }

                echo " \n";
                $this->prod_select ($gid, $planet);
                echo "  </tr>\n";
            }
        }

        // Storages
        echo "    <tr>   <tr> \n";
        echo "    <th colspan=\"2\">".loca("RES_CAPACITY")."</th> \n";
        $this->DisplayStorages ($planet, $reslist);
        echo "    <td class=\"k\"> \n";
        echo "    <input type=\"submit\" name=\"action\" value=\"".loca("RES_CALCULATE")."\"></td> \n";
        echo "  </tr> \n";
        echo "  <tr>     <th colspan=\"6\" height=\"4\"></th>   </tr> \n";

        // Total production
        $this->DisplayTotalProduction ($planet, $reslist, loca("RES_PER_HOUR"), 1);
        $this->DisplayTotalProduction ($planet, $reslist, loca("RES_PER_DAY"), 24);
        $this->DisplayTotalProduction ($planet, $reslist, loca("RES_PER_WEEK"), 24*7);

        echo "  </table> \n";

        echo "<br><br><br><br>\n";
    }

    private function get_prod (int $id, array|null $planet) : float
    {
        if ($planet == null) return 0;
        if (isset($planet['prod'.$id])) {
            return 100 * $planet['prod'.$id];
        }
        return 0;
    }

    private function prod_select (int $id, array|null $planet) : void
    {
        if ($planet == null) return;

        // The planet does not support the configuration of the specified resource producing facility.
        if (!isset($planet['prod'.$id])) {
            echo "<th>&nbsp;</th>\n";
            return;
        }

        echo "  <th> <select name=\"last$id\" size=\"1\">\n";
        $prod = $this->get_prod ( $id, $planet );
        for ($i=10; $i>=0; $i--)
        {
            $selected = "";
            if (10*$i == $prod) $selected = "selected";
            echo "      <option value=\"".(10*$i)."\" $selected>".(10*$i)."%</option>\n";
        }
        echo "        </select>\n";
        echo "   </th>\n";
    }

    private function nicenum2 (float|int $num) : string
    {
        // for debugging. the Germans messed up with rounding, I don't know where they call floor, ceil and round.
        if ($this->debug_no_rounding) return $num;
        return nicenum(round($num));
    }

    private function rgnum (float|int $num) : string
    {
        if ( $num > 0 ) return "<font color=\"#00ff00\">".$this->nicenum2($num)."</font>";
        else return "<font color=\"#ff0000\">".$this->nicenum2($num)."</font>";
    }

    private function DisplayTotalProduction (array $planet, array $reslist, string $head, int $hours) : void {

        global $resourcesWithNonZeroDerivative;         // used to determine the type (integral, constant)

        echo "  <tr> \n";
        echo "    <th colspan=\"2\">".$head."</th> \n";
        foreach ($reslist as $i=>$rc) {
            $factor = in_array ($rc, $resourcesWithNonZeroDerivative, true) ? $hours : 1;
            $total = $planet['balance'][$rc] * $factor;
            echo "    <td class=\"k\">".$this->rgnum($total)."</td> \n";
        }
        echo "  </tr> \n";
    }

    private function DisplayFacilityProduction () : void {

    }

    private function DisplayStorages (array $planet, array $reslist) : void {

        foreach ($reslist as $i=>$rc) {
            $val_str = isset($planet['max'.$rc]) ? $this->nicenum2($planet['max'.$rc]/1000) . "k" : '-';
            echo "    <td class=\"k\"><font color=\"#00ff00\">$val_str</font></td> \n";
        }
    }
}
?>