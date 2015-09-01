<?php
include_once ("/var/www/myUvr1611DataLogger/lib/commonChart.inc.php");
include_once("lib/login/session.inc.php");
if($database->isProtected($chartId) == false || login_check()) {
    echo preg_replace('/"(-?\d+\.?\d*)"/', '$1', json_encode($database->queryAnalog($date,$chartId,$period)));
}
else {
	echo "{status: \"access denied\"}";
}
