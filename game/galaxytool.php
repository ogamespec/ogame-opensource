<?php

// Обновление встроенной галабазы.
// Обновление происходит каждую неделю, после чистки фантомных ПО.

function GalaxyToolUpdateGalaxy ()
{
    global $db_prefix;

    $list = array ();
    $query = "SELECT * FROM ".$db_prefix."planets WHERE type < 20000 AND type <> 10002;";
    $result = dbquery ( $query );
    $rows = dbrows ( $result );
    while ($rows--)
    {
        $planet = dbarray ( $result );
        if ( $planet['type'] == 10000 && (($planet['m'] + $planet['k']) < 300) ) continue;
        $list[ $planet['planet_id'] ] = array ();
        $list[ $planet['planet_id'] ]['g'] = $planet['g'];
        $list[ $planet['planet_id'] ]['s'] = $planet['s'];
        $list[ $planet['planet_id'] ]['p'] = $planet['p'];
        $list[ $planet['planet_id'] ]['type'] = $planet['type'];
        $list[ $planet['planet_id'] ]['name'] = $planet['name'];
        $list[ $planet['planet_id'] ]['owner_id'] = $planet['owner_id'];
    }

    $text = serialize ( $list );
    $f = fopen ( "galaxytool/galaxy.txt", "w" );
    fwrite ( $f, $text );
    fclose ( $f );
}

function GalaxyToolUpdateStats ()
{
}

function GalaxyToolUpdateAllyStats ()
{
}

function GalaxyToolReplaceOldStats ()
{
}

function GalaxyToolReplaceOldAllyStats ()
{
}

function GalaxyToolUpdate ()
{
    GalaxyToolUpdateGalaxy ();
    GalaxyToolReplaceOldStats ();
    GalaxyToolReplaceOldAllyStats ();
    GalaxyToolUpdateStats ();
    GalaxyToolUpdateAllyStats ();
}

?>