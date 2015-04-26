<?php

// Обновление встроенной галабазы.
// Обновление происходит каждую неделю, после чистки фантомных ПО.

function GalaxyToolUpdateGalaxy ()
{
    global $db_prefix;

    $list = array ();
    $query = "SELECT * FROM ".$db_prefix."planets WHERE type < 20000 AND type <> 10002 ORDER BY planet_id ASC";
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
    global $db_prefix;

    $week = time() - 604800;
    $week3 = time() - 604800*3;

    $list = array ();
    $query = "SELECT * FROM ".$db_prefix."users WHERE place1 < 1000 AND admin = 0 ORDER BY player_id ASC";    // только игроков из топ1000 и не админов
    $result = dbquery ( $query );
    $rows = dbrows ( $result );
    while ($rows--)
    {
        $user = dbarray ( $result );
        $list[ $user['player_id'] ] = array ();
        $list[ $user['player_id'] ]['name'] = $user['oname'];
        $list[ $user['player_id'] ]['i'] = $user['lastclick'] <= $week ? 1 : 0;
        $list[ $user['player_id'] ]['b'] = $user['banned'] ? 1 : 0;
        $list[ $user['player_id'] ]['iI'] = $user['lastclick'] <= $week3 ? 1 : 0;
        $list[ $user['player_id'] ]['v'] = $user['vacation'] ? 1 : 0;
        $list[ $user['player_id'] ]['points'] = $user['score1'];
        $list[ $user['player_id'] ]['fpoints'] = $user['score2'];
        $list[ $user['player_id'] ]['rpoints'] = $user['score3'];
        $list[ $user['player_id'] ]['ally_id'] = $user['ally_id'];
    }

    $text = serialize ( $list );
    $f = fopen ( "galaxytool/statistics.txt", "w" );
    fwrite ( $f, $text );
    fclose ( $f );
}

function GalaxyToolUpdateAllyStats ()
{
    global $db_prefix;

    $list = array ();
    $query = "SELECT * FROM ".$db_prefix."ally ORDER BY ally_id ASC";
    $result = dbquery ( $query );
    $rows = dbrows ( $result );
    while ($rows--)
    {
        $ally = dbarray ( $result );
        $list[ $ally['ally_id'] ] = array ();
        $list[ $ally['ally_id'] ]['name'] = $ally['tag'];
    }

    $text = serialize ( $list );
    $f = fopen ( "galaxytool/ally_statistics.txt", "w" );
    fwrite ( $f, $text );
    fclose ( $f );
}

function GalaxyToolReplaceOldStats ()
{
    if ( file_exists('galaxytool/statistics.txt') ) $current = file_get_contents( 'galaxytool/statistics.txt' );
    else $current = array ();
    file_put_contents( 'galaxytool/statistics_old.txt' , $current);
}

function GalaxyToolReplaceOldAllyStats ()
{
    if ( file_exists('galaxytool/ally_statistics.txt')) $current = file_get_contents( 'galaxytool/ally_statistics.txt' );
    else $current = array ();
    file_put_contents( 'galaxytool/ally_statistics_old.txt' , $current);
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