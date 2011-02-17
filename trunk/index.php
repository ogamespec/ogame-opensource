<?

require_once "loca.php";

//loca_reset ( "localhost", "toor", "qwerty", "ogame" );

loca_init ( "localhost", "toor", "qwerty", "ogame", "ru" );
//loca_add ("LOCA_BLABLABLA", "1");
//loca_add ("LOCA_BLABLABLA", "2");
//loca_add ("LOCA_BLABLABLA", "LATS!");

echo loca("LOCA_BLABLABLA");

?>

OK.