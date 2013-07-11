<?
require_once('db_conf.php');

$distanceValue = 20; //meters

//Select osm roads (excluding small roads like footways)
$roadQuery = "SELECT ST_AsText(the_geom) as multiline,* FROM roads WHERE the_geom && ST_MakeEnvelope(7.55582,51.979171, 7.680447, 51.930612, 4326) and type != 'cycleway' and type != 'path' and type != 'footway' and type != 'pedestrian' and type != 'unclassified' and type != 'steps' and type != 'track' and type != 'service'";

$roads = query($roadQuery);
$roads = pg_fetch_all($roads);

$tracks = array();

//Get all tracks within the requested boundingbox from enviroCar
$tracksInBB = get_request('https://giv-car.uni-muenster.de/stable/rest/tracks?bbox=7.55582,51.930612,7.680447,51.979171');
if($tracksInBB['status'] == 200){
	$tracksInBB = json_decode($tracksInBB['response'], true);
	
	//Fetch tracks from enviroCar
	foreach($tracksInBB['tracks'] as $trackId){
		echo $trackId['id'].' ';
		$track = get_request('https://giv-car.uni-muenster.de/stable/rest/tracks/'.$trackId['id']);
		if($track['status'] == 200){
			$track = json_decode($track['response'],true);
			array_push($tracks, $track);
		}
	}
}

$currentRoad = 0;
$roadCount = sizeof($roads);

//Iterate over all roads
foreach($roads as $road){
	echo 'Road: '.$currentRoad.'/'.$roadCount.' ';
	$currentRoad += 1;
	$road = splitMultiline($road);
	$segment = 0;
	
	//Iterate over all road segments of a road
	foreach($road['coordinates'] as $coord){
		$measurements = array();
		
		//Calculate the distance for every measurement
		foreach($tracks as $track){
			foreach($track['features'] as $trackFeature){
				$distance = getDistance($trackFeature['geometry']['coordinates'][1], $trackFeature['geometry']['coordinates'][0], $coord[1], $coord[0]);
				if($distance < $distanceValue) array_push($measurements, $trackFeature);
			}
		}
		$measurementCount = sizeof($measurements);
		//If there are nearby measurements, start aggregating
		if($measurementCount > 0){
		
			//if only one measurement exists it can be directly inserted into the database
			if($measurementCount == 1){
                if(isset($measurements[0]['properties']['phenomenons']['Speed']['value']) && isset($measurements[0]['properties']['phenomenons']['CO2']['value'])){
                    insertToDb($road['osm_id'], $coord, $measurements[0]['properties']['phenomenons']['Speed']['value'],$measurements[0]['properties']['phenomenons']['CO2']['value'], $measurementCount, $segment);
                }
			} 
			
			else if($measurementCount > 1){
				$speed = 0;
				$co2 = 0;
				$sumDi = 0;
				
				//Calculate the sum of distances 
				foreach($measurements as $m){
					$sumDi += 1/getDistance($m['geometry']['coordinates'][1], $m['geometry']['coordinates'][0], $coord[1], $coord[0]);
				}
				
				//Calculate Inverse distance weighting
				if($sumDi > 0){
					foreach($measurements as $m){
						if(isset($m['properties']['phenomenons']['Speed']['value'])){
							$di = getDistance($m['geometry']['coordinates'][1], $m['geometry']['coordinates'][0], $coord[1], $coord[0]);
							if($di > 0){							
								$speed += (1/$di) * ($m['properties']['phenomenons']['Speed']['value']/$sumDi);
							}
						}if(isset($m['properties']['phenomenons']['CO2']['value'])){
							$di = getDistance($m['geometry']['coordinates'][1], $m['geometry']['coordinates'][0], $coord[1], $coord[0]);
							if($di > 0){							
								$co2 += (1/$di) * ($m['properties']['phenomenons']['CO2']['value']/$sumDi);
							}
						}
					}
					//Insert aggregated values into the database
					insertToDb($road['osm_id'], $coord, $speed, $co2, $measurementCount, $segment);
				}
				

			}
		}
		$measurements = null;
		$segment += 1;
	}
	
}

//Inserts the aggregated values into the database. If the values road segment already exists, the values will be updated
function insertToDb($osm_id, $coords, $speed, $co2, $count, $segment){
    if(isset($osm_id) && isset($coords) && isset($speed) && isset($co2) && isset($count) && isset($segment)){
$query = "UPDATE tracks SET speed = ".$speed.", co2= ".$co2.", measurements = ".$count." WHERE osm_id = ".$osm_id." AND road_segment = ".$segment.";
INSERT into tracks(speed, co2, measurements, osm_id, the_geom, road_segment) 
	SELECT ".$speed.", ".$co2.", ".$count.", ".$osm_id.", ST_SetSRID(ST_MakePoint(".$coords[0].",".$coords[1]."), 4326),".$segment." WHERE NOT EXISTS (SELECT 1 from tracks where osm_id = ".$osm_id." AND road_segment=".$segment.");";
query($query);
}
}


//Splits the OSM Multiline into single points
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

//HTTP get request (used to fetch the enviroCar data)
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