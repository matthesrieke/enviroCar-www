<?php

require_once('db_conf.php');

	$query = 'SELECT ST_X(the_geom) as lat,ST_Y(the_geom) as lon, speed, osm_id from tracks';
	$result = query($query);
	
	$json = array();
	$json['type'] = 'FeatureCollection';
	$json['features'] = array();
		
		while($row = pg_fetch_assoc($result)){
			$point = array();
			$point['type'] = 'Feature';
			$point['properties'] = array();
			$point['properties']['speed'] = floatval($row['speed']);
			$point['properties']['co2'] = floatval($row['co2']);
			$point['properties']['osm_id'] = $row['osm_id'];
			$point['geometry'] = array();
			$point['geometry']['type'] = 'Point';
			$point['geometry']['coordinates'] = array();
			array_push($point['geometry']['coordinates'],floatval($row['lat']));
			array_push($point['geometry']['coordinates'],floatval($row['lon']));
			array_push($json['features'],$point);
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
