<?php

// Deep Space Horror

const PTYP_LEVIATHAN = 22851;       // Планета - Космический монстр. Картинка получается из задания флота (origin) с этой планеты.

const GID_LEVI_AMOEBA = 22852;          // Planktonic Devourer
const GID_LEVI_GUARDIAN = 22853;        // Wandering Monolith
const GID_LEVI_JUGGERNAUT = 22854;      // Galactic Juggernaut 

// Подготовка к прыжку. После этого происходит перемещение левифана, специальная атака и новая подготовка.
const FTYP_LEVI_PREPARE_JUMP = 22855;

class DeepSpaceHorror extends GameMod {

    public function install() : void {
    }

    public function uninstall() : void {
    }

    public function init() : void {
    }

    public function get_planet_small_image(int $type, array &$img) : bool {
        if ($type == PTYP_LEVIATHAN) {

        }
        return false;
    }

    public function get_planet_image(int $type, array &$img) : bool {
        if ($type == PTYP_LEVIATHAN) {
            
        }
        return false;
    }

    public function get_object_image(int $id, array &$img) : bool {
        switch ($id) {
            case GID_LEVI_AMOEBA:
                $img['path'] = "mods/DeepSpaceHorror/img/amoeba.jpg";
                return true;
            case GID_LEVI_GUARDIAN:
                $img['path'] = "mods/DeepSpaceHorror/img/guardian.jpg";
                return true;
            case GID_LEVI_JUGGERNAUT:
                $img['path'] = "mods/DeepSpaceHorror/img/leviathan.jpg";
                return true;
        }
        return false;
    }
}

?>