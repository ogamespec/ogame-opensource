<?php

include ("geoip.inc");

function CountryCodeFromIP ($ipAddr)
{
    $handle = geoip_open("GeoIP.dat", GEOIP_STANDARD);
    $cc = geoip_country_code_by_addr($handle, $ipAddr);
    geoip_close($handle);
    return strtolower ( $cc );
}

?>