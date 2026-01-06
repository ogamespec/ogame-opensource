<?php

class BogusMod implements GameMod
{
    public function install() {
        Debug ("BogusMod install.");
    }

    public function uninstall() {
        Debug ("BogusMod uninstall.");
    }

    public function init() {
        global $GlobalUni;
        loca_add ("bogusmod", $GlobalUni['lang'], __DIR__);
    }

    public function route() {
        if ( $_GET['page'] === "tipoftheday" ) {
            include __DIR__ . "/pages/tipoftheday.php";
            return true;
        }
        return false;
    }
}

?>