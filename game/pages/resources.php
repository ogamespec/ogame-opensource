<?php

// Resource Settings.

class Resources extends Page {

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

    public function view () : void {

        global $GlobalUni;
        global $GlobalUser;
        global $aktplanet;

        $prem = PremiumStatus ($GlobalUser);
        if ( $prem['geologist'] )
        {
            $geologe_text = "<img border=\"0\" src=\"img/geologe_ikon.gif\" alt=\"".loca("PREM_GEOLOGE")."\" onmouseover='return overlib(\"<font color=#ffffff>".loca("PREM_GEOLOGE")."</font>\", WIDTH, 80);' onmouseout='return nd();' width=\"20\" height=\"20\">";
        }
        else
        {
            $geologe_text = "&nbsp;";
        }
        if ( $prem['engineer'] )
        {
            $engineer_text = "<img border=\"0\" src=\"img/ingenieur_ikon.gif\" alt=\"".loca("PREM_ENGINEER")."\" onmouseover='return overlib(\"<font color=#ffffff>".loca("PREM_ENGINEER")."</font>\", WIDTH, 80);' onmouseout='return nd();' width=\"20\" height=\"20\">";
        }
        else
        {
            $engineer_text = "&nbsp;";
        }

        $speed = $GlobalUni['speed'];
        $planet = $aktplanet;

        $m_total = (20*$speed);
        $k_total = (10*$speed);
        $d_total = 0;

        echo "<center> \n";
        echo "<br> \n";
        echo "<br> \n";
        echo va(loca("RES_FACTOR")." ", round($aktplanet['factor'],2))."\n";

        // Not known for what, but it's in the original game.
        $count = 0;
        $result = EnumPlanets ();
        $rows = dbrows ($result);
        while ($rows--)
        {
            $pl = dbarray ($result);
            if ( $pl['type'] != PTYP_MOON ) $count++;
        }
        if ( $count > MAX_PLANET ) echo "<br><font color=#ff000>".loca("RES_INFO")."</font>";

        echo "<form action=\"index.php?page=resources&session=".$_GET['session']."\" method=\"post\" id='ressourcen'> \n";
        echo "<input type=hidden name='screen' id='screen'> \n";
        echo "<table width=\"550\"> \n";

        echo "  <tr> \n";
        echo "    <td class=\"c\" colspan=\"6\"> \n";
        echo "    ".loca("RES_PROD")." &quot;".$aktplanet['name']."&quot;\n";
        echo "    </td> \n";
        echo "  </tr>\n";

        echo "  <tr> \n";
        echo "   <th colspan=\"2\"></th>    <th>".loca("NAME_".GID_RC_METAL)."</th>    <th>".loca("NAME_".GID_RC_CRYSTAL)."</th>    <th>".loca("NAME_".GID_RC_DEUTERIUM)."</th>    <th>".loca("NAME_".GID_RC_ENERGY)."</th> \n";
        echo "  </tr>\n";

        // Natural production
        echo "  <tr> \n";
        echo "   <th colspan=\"2\">".loca("RES_NATURAL")."</th> \n";
        echo "   <td class=\"k\">".(20*$speed)."</td>    <td class=\"k\">".(10*$speed)."</td>    <td class=\"k\">0</td>    <td class=\"k\">0</td> \n";
        echo "  </tr>\n";

        // Metal mine
        if ($aktplanet[GID_B_METAL_MINE]) {
            $m_hourly = $planet['prod_with_bonus'][GID_B_METAL_MINE];
            $m_total += $m_hourly;
            $m_cons = $planet['cons_with_bonus'][GID_B_METAL_MINE];
            $m_cons0 = round ($m_cons * $planet['factor']);
            $color1 = $m_hourly ? "<font color='00FF00'>" : "";
            $color2 = $m_cons ? "<font color='FF0000'>" : "";
            echo "  <tr> \n";
            echo "<th>".loca("NAME_1")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_METAL_MINE]).")</th><th>".$geologe_text."</th>   <th> \n";
            echo "    <font color=\"#FFFFFF\">        $color1".$this->nicenum2($m_hourly)."</font>   <th> \n";
            echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
            echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
            echo "    <font color=\"#FFFFFF\">        $color2".$this->nicenum2($m_cons0)."/".$this->nicenum2($m_cons)."</th> \n";
            $this->prod_select (GID_B_METAL_MINE, $planet);
            echo "  </tr>\n";
        }

        // Crystal mine
        if ($aktplanet[GID_B_CRYS_MINE]) {
            $k_hourly = $planet['prod_with_bonus'][GID_B_CRYS_MINE];
            $k_total += $k_hourly;
            $k_cons = $planet['cons_with_bonus'][GID_B_CRYS_MINE];
            $k_cons0 = round ($k_cons * $planet['factor']);
            $color1 = $k_hourly ? "<font color='00FF00'>" : "";
            $color2 = $k_cons ? "<font color='FF0000'>" : "";
            echo "  <tr> \n";
            echo "<th>".loca("NAME_2")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_CRYS_MINE]).")</th><th>".$geologe_text."</th>   <th> \n";
            echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
            echo "    <font color=\"#FFFFFF\">        $color1".$this->nicenum2($k_hourly)."</font>   <th> \n";
            echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
            echo "    <font color=\"#FFFFFF\">        $color2".$this->nicenum2($k_cons0)."/".$this->nicenum2($k_cons)."</th> \n";
            $this->prod_select (GID_B_CRYS_MINE, $planet);
            echo "  </tr>\n";
        }

        // Deuterium synthesizer
        if ($aktplanet[GID_B_DEUT_SYNTH]) {
            $d_hourly = $planet['prod_with_bonus'][GID_B_DEUT_SYNTH];
            $d_total += $d_hourly;
            $d_cons = $planet['cons_with_bonus'][GID_B_DEUT_SYNTH];
            $d_cons0 = round ($d_cons * $planet['factor']);
            $color1 = $d_hourly ? "<font color='00FF00'>" : "";
            $color2 = $d_cons ? "<font color='FF0000'>" : "";
            echo "  <tr> \n";
            echo "<th>".loca("NAME_3")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_DEUT_SYNTH]).")</th><th>".$geologe_text."</th>   <th> \n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       $color1".$this->nicenum2($d_hourly)."</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       $color2".$this->nicenum2($d_cons0)."/".$this->nicenum2($d_cons)."</th>\n";
            $this->prod_select (GID_B_DEUT_SYNTH, $planet);
            echo "  </tr>\n";
        }

        // Solar Plant
        if ($aktplanet[GID_B_SOLAR]) {
            $s_prod = $planet['prod_with_bonus'][GID_B_SOLAR];
            $color = $s_prod ? "<font color='00FF00'>" : "";
            echo "  <tr> \n";
            echo "<th>".loca("NAME_4")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_SOLAR]).")</th><th>".$engineer_text."</th>   <th> \n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       $color".$this->nicenum2($s_prod)."</th>\n";
            $this->prod_select (GID_B_SOLAR, $planet);
            echo "  </tr>\n";
        }

        // Fusion Reactor
        if ($aktplanet[GID_B_FUSION]) {
            $f_prod = $planet['prod_with_bonus'][GID_B_FUSION];
            $f_cons = - $planet['cons_with_bonus'][GID_B_FUSION];
            $d_total += $f_cons;
            $color1 = $f_cons ? "<font color='FF0000'>" : "";
            $color2 = $f_prod ? "<font color='00FF00'>" : "";
            echo "  <tr> \n";
            echo "<th>".loca("NAME_12")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_FUSION]).")</th><th>".$engineer_text."</th>   <th> \n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       $color1".$this->nicenum2($f_cons)."</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       $color2".$this->nicenum2($f_prod)."</th>\n";
            $this->prod_select (GID_B_FUSION, $planet);
            echo "  </tr>\n";
        }

        // Solar satellites
        if ($aktplanet[GID_F_SAT]) {
            $ss_prod = $planet['prod_with_bonus'][GID_F_SAT];
            $color = $ss_prod ? "<font color='00FF00'>" : "";
            echo "  <tr> \n";
            echo "<th>".loca("NAME_212")." (".va(loca("RES_AMOUNT"), $aktplanet[GID_F_SAT]).")</th><th>".$engineer_text."</th>   <th> \n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
            echo "    <font color=\"#FFFFFF\">       $color".$this->nicenum2($ss_prod)."</th>\n";
            $this->prod_select (GID_F_SAT, $planet);
            echo "  </tr>\n";
        }

        // Storages
        echo "    <tr>   <tr> \n";
        echo "    <th colspan=\"2\">".loca("RES_CAPACITY")."</th> \n";
        echo "    <td class=\"k\"><font color=\"#00ff00\">".$this->nicenum2($planet['mmax']/1000)."k</font></td> \n";
        echo "    <td class=\"k\"><font color=\"#00ff00\">".$this->nicenum2($planet['kmax']/1000)."k</font></td> \n";
        echo "    <td class=\"k\"><font color=\"#00ff00\">".$this->nicenum2($planet['dmax']/1000)."k</font></td> \n";
        echo "    <td class=\"k\"><font color=\"#00ff00\">-</font></td> \n";
        echo "    <td class=\"k\"> \n";
        echo "    <input type=\"submit\" name=\"action\" value=\"".loca("RES_CALCULATE")."\"></td> \n";
        echo "  </tr> \n";
        echo "  <tr>     <th colspan=\"6\" height=\"4\"></th>   </tr> \n";

        // Total production
        echo "  <tr> \n";
        echo "    <th colspan=\"2\">".loca("RES_PER_HOUR")."</th> \n";
        echo "    <td class=\"k\">".$this->rgnum($m_total)."</td> \n";
        echo "    <td class=\"k\">".$this->rgnum($k_total)."</td> \n";
        echo "    <td class=\"k\">".$this->rgnum($d_total)."</td> \n";
        echo "    <td class=\"k\">".$this->rgnum($planet['e'])."</td> \n";
        echo "  </tr> \n";

        echo "  <tr> \n";
        echo "    <th colspan=\"2\">".loca("RES_PER_DAY")."</th> \n";
        echo "    <td class=\"k\">".$this->rgnum($m_total*24)."</td> \n";
        echo "    <td class=\"k\">".$this->rgnum($k_total*24)."</td> \n";
        echo "    <td class=\"k\">".$this->rgnum($d_total*24)."</td> \n";
        echo "    <td class=\"k\">".$this->rgnum($planet['e'])."</td> \n";
        echo "  </tr> \n";

        echo "  <tr> \n";
        echo "    <th colspan=\"2\">".loca("RES_PER_WEEK")."</th> \n";
        echo "    <td class=\"k\">".$this->rgnum($m_total*24*7)."</td> \n";
        echo "    <td class=\"k\">".$this->rgnum($k_total*24*7)."</td> \n";
        echo "    <td class=\"k\">".$this->rgnum($d_total*24*7)."</td> \n";
        echo "    <td class=\"k\">".$this->rgnum($planet['e'])."</td> \n";
        echo "  </tr>\n";

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
        echo"  <th> <select name=\"last$id\" size=\"1\">\n";
        $prod = $this->get_prod ( $id, $planet );
        for ($i=10; $i>=0; $i--)
        {
            $selected = "";
            if (10*$i == $prod) $selected = "selected";
            echo"      <option value=\"".(10*$i)."\" $selected>".(10*$i)."%</option>\n";
        }
        echo"        </select>\n";
        echo"   </th>\n";
    }

    private function nicenum2 (float|int $num) : string    // for debugging. the Germans messed up with rounding, I don't know where they call floor, ceil and round.
    {
        return nicenum(round($num));
        //return $num;
    }

    private function rgnum (float|int $num) : string
    {
        if ( $num > 0 ) return "<font color=\"#00ff00\">".$this->nicenum2($num)."</font>";
        else return "<font color=\"#ff0000\">".$this->nicenum2($num)."</font>";
    }
}
?>