<?php
include_once 'db.php';
include_once 'lib.php';

ignore_user_abort(true);
set_time_limit(0);

//set is updating
$sql = 'UPDATE stats
		SET value = 1
		WHERE stid = \'isUpdating\'';
$db->query($sql);

//reset database
$sql = 'UPDATE numbers
		SET warning_country = NULL,
			warning_street = NULL,
			warning_city = NULL,
			warning_mentioned = NULL,
			warning_interpolated = 0,
			in_osm = 0';
$db->query($sql);

//handle dedicated addresses
if (($handle = fopen("osm.csv", "r")) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ",", '"')) !== FALSE) {
    	$postcode = $row[0];
    	$street = $row[1];
    	$origHousenumber = $row[2];
    	$housenumber = $origHousenumber;
    	$city = $row[3];
    	$country = $row[5];
    	
    	//get pid
    	$pid = getPid($postcode);
    	if($pid == false) {
    		continue;
    	}
    	
    	//format housenumber
    	$housenumber = formatHousenumber($housenumber);
    	
    	//split housenumber
    	$splitHousenumbers = preg_split('/[\-,\/;]+/i', $housenumber);
    	$isMentioned = false;
    	$housenumbers = array();
    	if(count($splitHousenumbers) > 1) {
    		$isMentioned = true;
    		foreach($splitHousenumbers as $splitHousenumber) {
    			$housenumbers[] = formatHousenumber($splitHousenumber);
    		}
    	} else {
    		$housenumbers[] = $housenumber;
    	}
    	
    	//get street
    	$street_simple = simplifyStreet($street);
    	foreach($housenumbers as $housenumber) {
			$sql = 'SELECT sid, street_name
					FROM streets
					WHERE street_name_simple = \'' . $street_simple . '\'';
			$res = $db->query($sql);
			while($row2 = $res->fetch_assoc()) {
				//get number
				$sid = $row2['sid'];
				$sql = 'SELECT nid, in_osm, warning_mentioned
						FROM numbers
						WHERE pid = ' . $pid . '
							AND sid = ' . $sid . '
							AND number = \'' . $db->real_escape_string($housenumber) . '\'';
				$res2 = $db->query($sql);
				if($row3 = $res2->fetch_assoc()) {
					$nid = $row3['nid'];
				
					//is street name differing?
					if($row2['street_name'] != $street) {
						echo $row2['street_name'] . ' => ' . $street . "\n";
						insertWarning($nid, 'street', $street);
					}
				
					//city differing?
					if($city != 'Berlin') {
						echo 'City is: ' . $city . "\n";
						insertWarning($nid, 'city', $city);
					}
				
					//country differing?
					if($country != 'DE') {
						echo 'Country is: ' . $country . "\n";
						insertWarning($nid, 'country', $country);
					}
				
					//is mentioned
					if($isMentioned && $row3['in_osm'] == 0) {
						echo 'Mentioned: ' . $origHousenumber . "\n";
						$sql = 'UPDATE numbers
								SET warning_mentioned = \'' . $db->real_escape_string($origHousenumber) . '\'
								WHERE nid = ' . $nid;
						$db->query($sql);
					} elseif(!$isMentioned && !is_null($row3['warning_mentioned'])) {
						$sql = 'UPDATE numbers
								SET warning_mentioned = NULL
								WHERE nid = ' . $nid;
						$db->query($sql);
					}
			
					//update
					$sql = 'UPDATE numbers
							SET in_osm = 1
							WHERE nid = ' . $nid;
					$db->query($sql);
					break;
				}
			}  
    	}  	
    }
    fclose($handle);
}

//post processing
$sql = 'SELECT nid, warning_country, warning_street, warning_city, warning_mentioned
		FROM numbers';
$res = $db->query($sql);
while($row = $res->fetch_assoc()) {
	//check for warning
	if(!is_null($row['warning_country']) 
		|| !is_null($row['warning_street']) 
		|| !is_null($row['warning_city']) 
		|| !is_null($row['warning_mentioned'])
	) {
		$sql = 'UPDATE numbers
				SET in_osm = 2
				WHERE nid = ' . $row['nid'];
		$db->query($sql);
	}
}

//handle interpolated addresses
if (($handle = fopen("osm_int.csv", "r")) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
    	$postcode = $row[0];
    	$street = $row[1];
    	$housenumber = $row[2];
    	
    	//get pid
    	$pid = getPid($postcode);
    	if($pid == false) {
    		continue;
    	}
    	
    	//format housenumber
    	$housenumber = formatHousenumber($housenumber);
    	
    	//get street
    	$street_simple = simplifyStreet($street);
    	$sql = 'SELECT sid, street_name
    			FROM streets
    			WHERE street_name_simple = \'' . $street_simple . '\'';
    	$res = $db->query($sql);
    	while($row2 = $res->fetch_assoc()) {
    		//get number
    		$sid = $row2['sid'];
    		$sql = 'SELECT nid
    				FROM numbers
					WHERE pid = ' . $pid . '
						AND sid = ' . $sid . '
						AND number = \'' . $db->real_escape_string($housenumber) . '\'';
			$res2 = $db->query($sql);
			if($row3 = $res2->fetch_assoc()) {
				$nid = $row3['nid'];
			
				//update
				$sql = 'UPDATE numbers
						SET in_osm = 2,
							warning_interpolated = 1
						WHERE nid = ' . $nid . '
							AND in_osm = 0';
				$db->query($sql);
				break;
			}
    	}    	
    }
    fclose($handle);
}

//get postcode
function getPid($postcode) {
	global $db;
	$sql = 'SELECT pid
			FROM postcodes
			WHERE postcode = \'' . $db->real_escape_string($postcode) . '\'';
	$res = $db->query($sql);
	if($row = $res->fetch_assoc()) {
		return $row['pid'];
	}
	return false;
}

//format housenumber
function formatHousenumber($number) {
	$number = preg_replace('/\s+/', '', $number);
    $number = mb_strtoupper($number);
    return $number;
}

//insert warning
function insertWarning($nid, $warning, $value) {
	global $db;
	$sql = 'UPDATE numbers
			SET warning_' . $warning . ' = \'' . $db->real_escape_string($value) . '\'
			WHERE nid = ' . $nid;
	$db->query($sql);
}

//set is updating and time
$sql = 'UPDATE stats
		SET value = 0
		WHERE stid = \'isUpdating\'';
$db->query($sql);
$sql = 'UPDATE stats
		SET value = ' . time() . '
		WHERE stid = \'lastUpdate\'';
$db->query($sql);