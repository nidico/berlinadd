<?php
include_once 'db.php';
include_once 'lib.php';

//handle dedicated addresses
if (($handle = fopen("osm.csv", "r")) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
    	$postcode = $row[0];
    	$street = $row[1];
    	$housenumber = $row[2];
    	$city = $row[3];
    	$country = $row[5];
    	
    	//get pid
    	$pid = getPid($postcode);
    	if($pid == false) {
    		continue;
    	}
    	
    	//format housenumber
    	$housenumber = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $housenumber);
    	$housenumber = mb_strtoupper($housenumber);
    	
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
			
				//update
				$sql = 'UPDATE numbers
						SET in_osm = 1
						WHERE nid = ' . $nid . '
							AND in_osm = 0';
				$db->query($sql);
				break;
			}
    	}    	
    }
    fclose($handle);
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
    	$housenumber = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $housenumber);
    	$housenumber = mb_strtoupper($housenumber);
    	
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

//insert warning
function insertWarning($nid, $warning, $value) {
	global $db;
	$sql = 'UPDATE numbers
			SET warning_' . $warning . ' = \'' . $db->real_escape_string($value) . '\',
				in_osm = 2
			WHERE nid = ' . $nid;
	$db->query($sql);
}