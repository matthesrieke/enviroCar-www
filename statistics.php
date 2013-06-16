<?php

require_once('db_conf.php');

	$query = 'SELECT max(speed) as max_speed, min(speed) as min_speed, max(co2) as max_co2, min(co2) as min_co2 from tracks';
	$result = query($query);
	
	$json = array();
	$json['statistics'] = array();
		
		while($row = pg_fetch_assoc($result)){
			$speed = array();
			$speed['max'] = floatval($row['max_speed']);
			$speed['min'] = floatval($row['min_speed']);
			$speed['phenomenon']['name'] = 'Speed';
			$speed['phenomenon']['unit'] = 'km/h';
			
			$co2 = array();
			$co2['max'] = floatval($row['max_co2']);
			$co2['min'] = floatval($row['min_co2']);
			$co2['phenomenon']['name'] = 'CO2';
			$co2['phenomenon']['unit'] = 'g/s';

			array_push($json['statistics'],$speed);
			array_push($json['statistics'],$co2);
		}
		
	echo json_encode($json);
	
	
	
//helper function to ease postgresql queries
function query($query) {
	global $dbhost, $dbname, $dbuser, $dbpw;
	//connect with db
	$dbconn = pg_connect("host=".$dbhost." dbname=".$dbname." user=".$dbuser." password=".$dbpw)
				or die('Verbindungsaufbau fehlgeschlagen: ' . pg_last_error());
	$results = pg_query($query) or die(utf8_decode(pg_last_error()));
	pg_close($dbconn);
	return $results;
}
?>
