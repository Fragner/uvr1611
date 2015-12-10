<?php
include_once("lib/backend/uvr1611.inc.php");
include_once("lib/error.inc.php");
include_once("lib/backend/piko-connection.inc.php");

$debug = 0;
if (PHP_SAPI === 'cli'){
//if ($argc > 1) {
//debug --> echo only when an additional input received
	$debug = 1;
}

try {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json; charset=utf-8');
	
	
	
	if(isset($_GET["date"]) && $_GET["date"] < time()) {
		$date = $_GET["date"];
		// connect to database
		$database = Database::getInstance();
		echo preg_replace('/"(-?\d+\.?\d*)"/', '$1', json_encode($database->queryLatest($date)));
	}
	else 
	{
		$data = load_cache("uvr1611_latest", Config::getInstance()->app->latestcache);
	
		if(!$data)
		{
			//UVR1611
			try{
				$uvr = Uvr1611::getInstance();
				$latest = $uvr->getLatest();
				$latest["info"]["cached"] = false;
			}
			catch (Exception $e) {
				if ($debug > 0) {
					echo "latest.php - No connection to BL-Net --> UVR1611!\n";	
				}
			}		
			//PIKO
			getPikoData();
			//	
			$data = json_encode($latest);
			save_cache($latest,"uvr1611_latest");			
		}	
		echo $data;
	}
}
catch(Exception $e) {
	sendAjaxError($e);
}

function save_cache($data, $key) {
	$temp = sys_get_temp_dir();
	$key = md5($key);
	$data["info"]["cached"] = true;
	$data = serialize(json_encode($data));
	file_put_contents("$temp/$key", $data);
}

function load_cache($key, $expire) {
	$temp = sys_get_temp_dir();
	$key = md5($key);
	$path = "$temp/$key";
	if(file_exists($path))
	{
		if(time() < (filemtime($path) + $expire)) {
			return unserialize(file_get_contents($path));
		}
		unlink($path);
	}
	return false;
}

function getPikoData(){
	//PIKO
	global $latest;
	global $debug;
	try{
		$piko = Piko5::getInstance();				
		if ($piko->fetchData()){			
			 $myAData = $piko-> getArrValues();			
			 $frame = $myAData["frame"];
			/* must be convertet to string, 
			   otherwise in the schema the values will not be shown */				 
			 $latest[$frame] = $myAData;
			if ($debug > 0) {
				echo "latest.php - connection to PIKO!\n";	
			}		
		} else {
			if ($debug > 0) {
				echo "latest.php - No connection to PIKO!\n";	
			}		
		}
	}	
	catch (Exception $e) {
		if ($debug > 0) {
			echo "latest.php - No connection to PIKO!\n";	
		}		
	}
}
