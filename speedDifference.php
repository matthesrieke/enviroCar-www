<?php

require_once('db_conf.php');

	$query = 'select avg(maxspeed) as maxspeed, roads.osm_id, ST_AsText(roads.the_geom) as multiline, avg(tracks.speed) as avg_speed  from roads, tracks where roads.osm_id = tracks.osm_id and roads.maxspeed != 0 group by roads.osm_id, roads.the_geom';
	$result = query($query);
	
	$json = array();
	$json['type'] = 'FeatureCollection';
	$json['features'] = array();
		
		while($row = pg_fetch_assoc($result)){
			$road = splitMultiline($row['multiline']);
			foreach($road['coordinates'] as $coord){
				$point = array();
				$point['type'] = 'Feature';
				$point['properties'] = array();
				$point['properties']['speed_difference'] = floatval($row['maxspeed']- $row['avg_speed']);
				$point['properties']['max_speed'] = floatval($row['maxspeed']);
				$point['properties']['avg_speed'] = floatval($row['avg_speed']);
				$point['properties']['osm_id'] = $row['osm_id'];
				$point['geometry'] = array();
				$point['geometry']['type'] = 'Point';
				$point['geometry']['coordinates'] = array();
				array_push($point['geometry']['coordinates'],floatval($coord[0]));
				array_push($point['geometry']['coordinates'],floatval($coord[1]));
				array_push($json['features'],$point);
			}
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


//Splits the OSM Multiline into single points
function splitMultiline($road){
	$coordinates = substr($road, 17, -2);
	$coordinates = explode(",", $coordinates);
	
	for($i = 0; $i < sizeof($coordinates); $i++){
		$coordinates[$i] = explode(" ", $coordinates[$i]);
	}
	$road = null;
	$road["coordinates"] = $coordinates;
	return $road;
}
?>
