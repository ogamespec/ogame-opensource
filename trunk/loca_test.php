<?

require_once "loca.php";

$res = loca_init ( "localhost", "toor", "qwerty", "ogame", "ru", "OGame Startpage" );
if ($res == false) {
    loca_reset ( "localhost", "toor", "qwerty", "ogame" );

    loca_init ( "localhost", "toor", "qwerty", "ogame", "ru", "DUMMY" );
    loca_add_project ( "OGame Startpage" );
    loca_close ();

    loca_init ( "localhost", "toor", "qwerty", "ogame", "ru", "OGame Startpage" );

    loca_add ("LOCA_BLABLABLA", "1");
    loca_add ("LOCA_BLABLABLA", "2");
    loca_add ("LOCA_BLABLABLA", "LATS!");
}

echo loca("LOCA_BLABLABLA");

?>

OK.