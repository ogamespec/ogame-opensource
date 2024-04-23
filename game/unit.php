<?php

// Fleet and Defense Parameters.
$UnitParam = array (        // structure, shield, attack, cargo capacity, speed, consumption
    GID_F_SC => array ( 4000, 10, 5, 5000, 5000, 10 ),
    GID_F_LC => array ( 12000, 25, 5, 25000, 7500, 50 ),
    GID_F_LF => array ( 4000, 10, 50, 50, 12500, 20 ),
    GID_F_HF => array ( 10000, 25, 150, 100, 10000, 75 ),
    GID_F_CRUISER => array ( 27000, 50, 400, 800, 15000, 300 ),
    GID_F_BATTLESHIP => array ( 60000, 200, 1000, 1500, 10000, 500 ),
    GID_F_COLON => array ( 30000, 100, 50, 7500, 2500, 1000 ),
    GID_F_RECYCLER => array ( 16000, 10, 1, 20000, 2000, 300 ),
    GID_F_PROBE => array ( 1000, 0, 0, 5, 100000000, 1 ),
    GID_F_BOMBER => array ( 75000, 500, 1000, 500, 4000, 1000 ),
    GID_F_SAT => array ( 2000, 1, 1, 0, 0, 0 ),
    GID_F_DESTRO => array ( 110000, 500, 2000, 2000, 5000, 1000 ),
    GID_F_DEATHSTAR => array ( 9000000, 50000, 200000, 1000000, 100, 1 ),
    GID_F_BATTLECRUISER => array ( 70000, 400, 700, 750, 10000, 250 ),

    GID_D_RL => array ( 2000, 20, 80, 0, 0, 0 ),
    GID_D_LL => array ( 2000, 25, 100, 0, 0, 0 ),
    GID_D_HL => array ( 8000, 100, 250, 0, 0, 0 ),
    GID_D_GAUSS => array ( 35000, 200, 1100, 0, 0, 0 ),
    GID_D_ION => array ( 8000, 500, 150, 0, 0, 0 ),
    GID_D_PLASMA => array ( 100000, 300, 3000, 0, 0, 0 ),
    GID_D_SDOME => array ( 20000, 2000, 1, 0, 0, 0 ),
    GID_D_LDOME => array ( 100000, 10000, 1, 0, 0, 0 ),

    GID_D_ABM => array ( 8000, 1, 1, 0, 0, 0 ),
    GID_D_IPM => array ( 15000, 1, 12000, 0, 0, 0 ),
);

// Rapid-fire settings.
$RapidFire = array (
    GID_F_SC => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_LC => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_LF => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_HF => array ( GID_F_SC => 3, GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_CRUISER => array ( GID_F_LF => 6, GID_F_PROBE => 5, GID_F_SAT => 5, GID_D_RL => 10 ),
    GID_F_BATTLESHIP => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_COLON => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_RECYCLER => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_PROBE => array ( ),
    GID_F_BOMBER => array ( GID_F_PROBE => 5, GID_F_SAT => 5, GID_D_RL => 20, GID_D_LL => 20, GID_D_HL => 10, GID_D_ION => 10 ),
    GID_F_SAT => array ( ),
    GID_F_DESTRO => array ( GID_F_PROBE => 5, GID_F_SAT => 5, GID_F_BATTLECRUISER => 2, GID_D_LL => 10 ),
    GID_F_DEATHSTAR => array ( GID_F_SC => 250, GID_F_LC => 250, GID_F_LF => 200, GID_F_HF => 100, GID_F_CRUISER => 33, GID_F_BATTLESHIP => 30, 
        GID_F_COLON => 250, GID_F_RECYCLER => 250, GID_F_PROBE => 1250, GID_F_BOMBER => 25, GID_F_SAT => 1250, GID_F_DESTRO => 5, GID_F_BATTLECRUISER => 15, 
        GID_D_RL => 200, GID_D_LL => 200, GID_D_HL => 100, GID_D_GAUSS => 50, GID_D_ION => 100 ),
    GID_F_BATTLECRUISER => array ( GID_F_SC => 3, GID_F_LC => 3, GID_F_HF => 4, GID_F_CRUISER => 4, GID_F_BATTLESHIP => 7, GID_F_PROBE => 5, GID_F_SAT => 5 ),
    // The defense doesn't feature rapid-fire
    GID_D_RL => array ( ),
    GID_D_LL => array ( ),
    GID_D_HL => array ( ),
    GID_D_GAUSS => array ( ),
    GID_D_ION => array ( ),
    GID_D_PLASMA => array ( ),
    GID_D_SDOME => array ( ),
    GID_D_LDOME => array ( ),
);

?>