<?php

class Admin_GalaxyTool extends Page {

    public function controller () : bool {
        global $db_prefix;
        global $GlobalUni;
        global $GlobalUser;
        global $now;

        if ( method () === "POST" && $GlobalUser['admin'] >= 2 ) {

            $days = min (30, max (1, intval($_POST['galaxytool_update'])));
            $when = $now + $days * 24 * 60 * 60;

            $query = "UPDATE ".$db_prefix."queue SET end = $when WHERE type = '".QTYP_GALAXY_TOOL."'";
            dbquery ($query);
            $query = "UPDATE ".$db_prefix."uni SET galaxytool_update = $days";
            dbquery ($query);

            $GlobalUni = LoadUniverse ();   // reload uni
        }
        return true;
    }

    public function view () : void {
        global $session;
        global $GlobalUni;

        $unitab = $GlobalUni;
?>
<table >
<form action="index.php?page=admin&session=<?=$session;?>&mode=GalaxyTool" method="POST" >
<tr><td class=c colspan=2><?=loca("GALATOOL_TITLE");?></td></tr>
<tr><th><?=loca("ADMIN_GALATOOL_UPDATE_PERIOD");?></th><th><input type="text" name="galaxytool_update" maxlength="10" size="10" value="<?=$unitab['galaxytool_update'];?>" /></th></tr>
<tr><th colspan=2><input type="submit" value="<?=loca("ADMIN_GALATOOL_SUBMIT");?>" /></th></tr>

</form>
</table>
<?php

    }
}

?>