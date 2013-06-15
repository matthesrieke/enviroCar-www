<?
require_once('db_conf.php');

$distanceValue = 20; //meters

$roadQuery = "select ST_AsText(the_geom) as multiline,* from roads where name like 'Weseler Straße' and (ref = 'B 219' or ref = 'B 54' or ref = 'B 54;B 219')";

$roads = query($roadQuery);
$roads = pg_fetch_all($roads);

$trackIds = array('51bb0ec3e4b0e636c1b25e5d','51b34f75e4b01748637f310d','51b3200ce4b01748637ece27','51b3200ae4b01748637ecb7b');
$tracks = array();


foreach($trackIds as $trackId){
	$track = get_request('http://giv-car.uni-muenster.de:8080/dev/rest/tracks/'.$trackId);
	if($track['status'] == 200){
		$track = json_decode($track['response'],true);
		array_push($tracks, $track);
	}
}

foreach($roads as $road){
	$road = splitMultiline($road);
	foreach($road['coordinates'] as $coord){
		$measurements = array();
		foreach($tracks as $track){
			foreach($track['features'] as $trackFeature){
				$distance = getDistance($trackFeature['geometry']['coordinates'][1], $trackFeature['geometry']['coordinates'][0], $coord[1], $coord[0]);
				if($distance < $distanceValue) array_push($measurements, $trackFeature);
			}
		}
		$measurementCount = sizeof($measurements);
		if($measurementCount > 0){
			if($measurementCount == 1){
				insertToDb($road['osm_id'], $coord, $measurements[0]['properties']['phenomenons']['Speed']['value'], $measurementCount);
			} 
			else if($measurementCount > 1){
				$speed = 0;
				$sumDi = 0;
				foreach($measurements as $m){
					$sumDi += 1/getDistance($m['geometry']['coordinates'][1], $m['geometry']['coordinates'][0], $coord[1], $coord[0]);
				}
				if($sumDi > 0){
					foreach($measurements as $m){
						if(isset($m['properties']['phenomenons']['Speed']['value'])){
							$di = getDistance($m['geometry']['coordinates'][1], $m['geometry']['coordinates'][0], $coord[1], $coord[0]);
							if($di > 0){							
								$speed += (1/$di) * ($m['properties']['phenomenons']['Speed']['value']/$sumDi);
							}
						}
					}
					insertToDb($road['osm_id'], $coord, $speed, $measurementCount);
				}
				

			}
		}
		$measurements = null;
	}
	
}

function insertToDb($osm_id, $coords, $speed, $count){
$query = "INSERT into tracks(speed, measurements, osm_id, the_geom) VALUES(".$speed.", ".$count.", ".$osm_id.", ST_SetSRID(ST_MakePoint(".$coords[0].",".$coords[1]."), 4326))";
query($query);
}



function splitMultiline($road){
	$coordinates = substr($road["multiline"], 17, -2);
	$coordinates = explode(",", $coordinates);
	
	for($i = 0; $i < sizeof($coordinates); $i++){
		$coordinates[$i] = explode(" ", $coordinates[$i]);
	}
	$road["coordinates"] = $coordinates;
	return $road;
}


//great circle distance between two points in meter
function getDistance($latitude1, $longitude1, $latitude2, $longitude2) {  
    $earth_radius = 6371;  
      
    $dLat = deg2rad($latitude2 - $latitude1);  
    $dLon = deg2rad($longitude2 - $longitude1);  
      
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);  
    $c = 2 * asin(sqrt($a));  
    $d = $earth_radius * $c;  
      
    return $d*1000;  
} 

function get_request($uri){
    $ch = curl_init($uri);

    curl_setopt_array($ch, array( 
        CURLOPT_RETURNTRANSFER  =>true,
        CURLOPT_VERBOSE     => 0,
    ));

    //Performs the curl GET request
    $out = curl_exec($ch);
    //Returns the HTTP status codes 
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return array("status" => $http_status, "response" => $out);
}


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