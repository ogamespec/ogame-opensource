<?php

/** @var array $GlobalUser */
/** @var string $db_prefix */
/** @var array $fleetmap */

/**
 * Class FleetTemplates
 *
 * MVC implementation of the fleet templates page.
 * Allows users to create and manage standard fleet compositions.
 */
class FleetTemplates extends Page
{
    private array $fleetmap_nosat;
    private int $maxTemplates;

    /**
     * Controller method.
     * Processes GET/POST requests and prepares data for the view.
     *
     * @return bool Always returns true
     */
    public function controller() : bool
    {
        global $GlobalUser, $fleetmap, $db_prefix;

        $this->maxTemplates = $GlobalUser[GID_R_COMPUTER] + 1;
        $this->fleetmap_nosat = array_diff($fleetmap, [GID_F_SAT]);

        // Check commander premium status
        $prem = PremiumStatus($GlobalUser);
        if (!$prem['commander']) {
            MyGoto("overview");
            return false;
        }

        // POST request processing - save or update template
        if (method() === "POST" && key_exists('mode', $_POST) && $_POST['mode'] === "save") {
            $this->processSaveTemplate();
        }

        // GET request processing - delete template
        if (method() === "GET" && key_exists('mode', $_GET) && $_GET['mode'] === "delete") {
            $this->processDeleteTemplate();
        }

        return true;
    }

    /**
     * Process save or update fleet template.
     */
    private function processSaveTemplate() : void
    {
        global $GlobalUser, $db_prefix;

        $id = intval($_POST['template_id']);
        $name = SecureText($_POST['template_name']);
        $name = mb_substr($name, 0, 30);
        $now = time();

        if ($id) {
            // Update existing template
            $query = "SELECT * FROM " . $db_prefix . "template WHERE id = $id AND owner_id = " . $GlobalUser['player_id'] . " LIMIT 1";
            $result = dbquery($query);
            if (dbrows($result) > 0) {
                $query = "UPDATE " . $db_prefix . "template SET name='" . $name . "', date=$now";
                foreach ($this->fleetmap_nosat as $i => $gid) {
                    $query .= ", `$gid` =" . intval($_POST['ship'][$gid]) . " ";
                }
                $query .= "WHERE id = $id";
                dbquery($query);
            }
        } else {
            // Add new template
            $query = "SELECT * FROM " . $db_prefix . "template WHERE owner_id = " . $GlobalUser['player_id'];
            $result = dbquery($query);
            $rows = dbrows($result);

            if ($rows < $this->maxTemplates) {
                $temp = array('owner_id' => $GlobalUser['player_id'], 'name' => $name, 'date' => $now);
                foreach ($this->fleetmap_nosat as $i => $gid) {
                    $temp[$gid] = intval($_POST['ship'][$gid]);
                }
                AddDBRow($temp, 'template');
            }
        }
    }

    /**
     * Process delete fleet template.
     */
    private function processDeleteTemplate() : void
    {
        global $GlobalUser, $db_prefix;

        $id = intval($_GET['id']);
        $query = "SELECT * FROM " . $db_prefix . "template WHERE id = $id AND owner_id = " . $GlobalUser['player_id'] . " LIMIT 1";
        $result = dbquery($query);
        if (dbrows($result) > 0) {
            $query = "DELETE FROM " . $db_prefix . "template WHERE id = $id";
            dbquery($query);
        }
    }

    /**
     * View method.
     * Renders the fleet templates page with list and form.
     *
     * @return void
     */
    public function view() : void
    {
        global $GlobalUser, $fleetmap, $db_prefix, $session;
        ?>

        <script type="text/javascript">
        function show_input(id,name,s16,s17,s18,s19,s20,s21,s22,s23,s24,s25,s27,s28,s29){
            document.getElementById('input_field').style.visibility="visible";
            document.getElementsByName('template_id')[0].value=id;
            document.getElementsByName('template_name')[0].value=name;
            document.getElementsByName('ship[202]')[0].value=s16;
            document.getElementsByName('ship[203]')[0].value=s17;
            document.getElementsByName('ship[204]')[0].value=s18;
            document.getElementsByName('ship[205]')[0].value=s19;
            document.getElementsByName('ship[206]')[0].value=s20;
            document.getElementsByName('ship[207]')[0].value=s21;
            document.getElementsByName('ship[208]')[0].value=s22;
            document.getElementsByName('ship[209]')[0].value=s23;
            document.getElementsByName('ship[210]')[0].value=s24;
            document.getElementsByName('ship[211]')[0].value=s25;
            document.getElementsByName('ship[213]')[0].value=s27;
            document.getElementsByName('ship[214]')[0].value=s28;
            document.getElementsByName('ship[215]')[0].value=s29;
        }
        </script>

        <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

        <center>
            <table style='cellpadding=5px;' border=0>
                <tr>
                    <td class='c' colspan=4 width=517><?=va(loca("FLEET_TEMP_TITLE_MAX"), $this->maxTemplates);?></td>
                </tr>
                <tr>
                    <th width=60>#</th>
                    <th width=267><?=loca("FLEET_TEMP_NAME");?></th>
                    <th><?=loca("FLEET_TEMP_UPDATE");?></th>
                    <th><?=loca("FLEET_TEMP_DELETE");?></th>
                </tr>
                <?php
                $query = "SELECT * FROM " . $db_prefix . "template WHERE owner_id =" . $GlobalUser['player_id'] . " ORDER BY date DESC LIMIT " . $this->maxTemplates;
                $result = dbquery($query);
                $rows = dbrows($result);
                $count = 1;
                while ($rows--) {
                    $temp = dbarray($result);
                ?>
                <tr>
                    <th><?= $count; ?></th>
                    <th width=160>
                        <a href="#" onclick="show_input(<?= $temp['id']; ?>,'<?= $temp['name']; ?>',
                            <?= $temp['202']; ?>,<?= $temp['203']; ?>,<?= $temp['204']; ?>,<?= $temp['205']; ?>,<?= $temp['206']; ?>,
                            <?= $temp['207']; ?>,<?= $temp['208']; ?>,<?= $temp['209']; ?>,<?= $temp['210']; ?>,<?= $temp['211']; ?>,
                            <?= $temp['213']; ?>,<?= $temp['214']; ?>,<?= $temp['215']; ?>);">
                            <?= $temp['name']; ?>
                        </a>
                    </th>
                    <th width=80>
                        <a href="#" onclick="show_input(<?= $temp['id']; ?>,'<?= $temp['name']; ?>',
                            <?= $temp['202']; ?>,<?= $temp['203']; ?>,<?= $temp['204']; ?>,<?= $temp['205']; ?>,<?= $temp['206']; ?>,
                            <?= $temp['207']; ?>,<?= $temp['208']; ?>,<?= $temp['209']; ?>,<?= $temp['210']; ?>,<?= $temp['211']; ?>,
                            <?= $temp['213']; ?>,<?= $temp['214']; ?>,<?= $temp['215']; ?>);">O</a>
                    </th>
                    <th width=80>
                        <a href="index.php?page=fleet_templates&session=<?= $session; ?>&mode=delete&id=<?= $temp['id']; ?>">X</a>
                    </th>
                </tr>
                <?php
                    $count++;
                }
                ?>
                <th colspan=4 align=center>
                    <input type="button" name="send" value='<?= loca("FLEET_TEMP_CREATE"); ?>'
                           onclick="show_input(0,'',0,0,0,0,0,0,0,0,0,0,0,0,0,0)">
                </th>
            </table>
            <br>
            <div id='input_field' style='visibility:hidden;'>
                <form action='index.php?page=fleet_templates&session=<?= $session; ?>' method="POST">
                    <input type="hidden" name="mode" value="save">
                    <table style='cellpadding=5px;' border=0>
                        <tr>
                            <td class='c' colspan=2 width=517><?= loca("FLEET_TEMP_CREATE"); ?></td>
                        </tr>
                        <tr>
                            <th><?= loca("FLEET_TEMP_NAME"); ?></th>
                            <th>
                                <input name='template_name' size=20>
                                <input type="hidden" name='template_id' size=6>
                            </th>
                        </tr>
                        <?php
                        foreach ($this->fleetmap_nosat as $i => $gid) {
                            echo "<tr>\n";
                            echo "    <th>" . loca("NAME_$gid") . "</th>\n";
                            echo "    <th><input name='ship[$gid]' size=3></th>\n";
                            echo "    </tr>\n";
                        }
                        ?>
                        <th colspan=2 align=center>
                            <input type="submit" name="send" value='<?= loca("FLEET_TEMP_SAVE"); ?>'>
                        </th>
                    </tr>
                    </table>
                </form>
            </div>
            <br><br><br><br>
        </center>

        <?php
    }
}
