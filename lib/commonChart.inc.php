<?php
/**
 * Basic access for the charts
 *
 * @copyright  Copyright (c) Bertram Winter bertram.winter@gmail.com
 * @license    GPLv3 License
 */
include_once("lib/backend/logfile.php");
include_once("lib/config.inc.php");
$setUvr1611Data = false;
if (is_file("/home/pi/scripts/uvr1611/setUvr1611Data.php"))
{
	$setUvr1611Data = true;
	include_once("/home/pi/scripts/uvr1611/setUvr1611Data.php");	
}

$logfile = LogFile::getInstance();
$logfile->writeLogInfo("commonChart.inc.php - start!\n");
//get instance off logger

include_once("/var/www/myUvr1611DataLogger/lib/backend/uvr1611.inc.php");
include_once("/var/www/myUvr1611DataLogger/lib/backend/database.inc.php");
date_default_timezone_set("Europe/Berlin");

// set json header
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json; charset=utf-8');

// get date for chart
$date = date("Y-m-d");
if(isset($_GET["date"])) {
	$date = date("Y-m-d", strtotime($_GET["date"]));
}

// get chart id
$chartId = 1;
if(isset($_GET["id"])) {
	$chartId = $_GET["id"];
}

// get period
$period = 0;
if(isset($_GET["period"]) && $_GET["period"] == "week") {
	$period = 6;
}
else if(isset($_GET["period"]) && $_GET["period"] == "year") {
	$period = 364;
}

// get grouping
$grouping = "days";
if(isset($_GET["grouping"])) {
	$grouping = $_GET["grouping"];
}

// connect to database
$database = Database::getInstance();

$logfile->writeLogInfo("commonChart.inc.php - check date!\n");
// check if required date is today and last update is older than 10 minutes
// -> so we need to fetch new values
if($date == date("Y-m-d") && ($database->lastDataset() + Config::getInstance()->app->chartcache) < time()) {
	
		$logfile->writeLogInfo("commonChart.inc.php - date okay!\n");	
		$uvr = Uvr1611::getInstance();
		$data = Array();
	        $lastDatabaseValue = $database->lastDataset();
		try {
		$count = $uvr->startRead();
if ($count > 0) {
		$logfile->writeLogInfo("commonChart.inc.php - date okay - 2\n");			
		;

		$logfile->writeLogInfo("commonChart.inc.php - date okay - 3\n");					
		for($i=0; $i < $count; $i++) {
			$logfile->writeLogInfo("commonChart.inc.php - try fetchdata\n");					
			// fetch a set of dataframes and insert them into the database
			$value = $uvr->fetchData();
			$logfile->writeLogInfo("commonChart.inc.php - data fetched\n");						
			if($value !== false) {
		    	if(strtotime($value["frame1"]["date"]) < $lastDatabaseValue) {
		    		break;
		    	}
		    	$data[] = $value;
		    	if(count($data) == 64) {
	         		    $logfile->writeLogState("commonChart.inc.php - insertData ".$count."\n");
				    $database->insertData($data);
				    $data = Array();
			    }
		    }
		}
		$uvr->endRead();
		// insert all data into database
		$database->insertData($data);
		$database->updateTables();
		$logfile->writeLogState("commonChart.inc.php - insert ".$count." sets in Database should be done\n");
		if ($count == 4095) {
		//additional debug info
			$logfile->writeLogState("commonChart.inc.php - myCount:= ".$myCount." value of i:= ".$i."\n");
		}
		checkUvr1611State(1);
	} else {
		$logfile->writeLogError("commonChart.inc.php - getCount: $count \n");
		checkUvr1611State(0);
	}
	}
	catch (Exception $e) {
		$uvr->endRead(false);
		$logfile->writeLogError("commonChart.inc.php - exception: ".$e->getMessage()."\n");
		echo "{'error':'".$e->getMessage()."'}";
		checkUvr1611State(0);
	}
} 
else {
	$logfile->writeLogState("commonChart.inc.php - no entry in Database --> timegap too small\n");
}

function checkUvr1611State($stateOk=null){
	global $setUvr1611Data, $logfile;
	$uvr1611StateLog = '/mnt/RAMDisk/uvr1611State';
	$debug = 0;

	if (!file_exists ( $uvr1611StateLog)) {
		//create file with 'state' 0
		file_put_contents($uvr1611StateLog, 0);	
	}
	if ($stateOk) {
		//state is 1 --> data received, reset file content	
		file_put_contents($uvr1611StateLog, 0);
	} else {
		$content = file_get_contents($uvr1611StateLog);
		if ($content > 2) {
			if ($setUvr1611Data) {
				restartBL_Net();//setUvr1611Data.php
				$logfile->writeLogState("commonChart.inc.php - restart BL-Net\n");			
				//reset file content
				file_put_contents($uvr1611StateLog, 0);
			}
		} else {
			//increase counter
			$content ++;
			file_put_contents($uvr1611StateLog, $content);		
		}
	} 

	if ($debug) {
		echo "Content of ".$uvr1611StateLog." = ".file_get_contents($uvr1611StateLog)."\n";	
	}	
}
