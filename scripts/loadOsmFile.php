<?php
include_once 'db.php';
include_once 'lib.php';

//load ortsteile
$ortsteile = array();
if (($handle = fopen("osm.csv", "r")) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
    	$postcode = $db->real_escape_string($row[0]);
    	$street = $db->real_escape_string($row[1]);
    	$housenumber = $db->real_escape_string($row[2]);
    	$city = $row[3];
    	$country = $row[5];
    	
    	//get postcode
    	$sql = 'SELECT pid
    			FROM postcodes
    			WHERE postcode = \'' . $postcode . '\'';
    	$res = $db->query($sql);
    	if($row2 = $res->fetch_assoc()) {
    		$pid = $row2['pid'];
    	} else {
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
    		$in_osm = 1;
    		
    		//is street name differing?
    		if($row2['street_name'] != $street) {
    			$in_osm = 2;
    		}
    		
    		//get number
    		$sid = $row2['sid'];
    		$sql = 'SELECT nid, note
    				FROM numbers
					WHERE pid = ' . $pid . '
						AND sid = ' . $sid . '
						AND number = \'' . $housenumber . '\'';
			$res2 = $db->query($sql);
			if($row3 = $res2->fetch_assoc()) {
				//city differing?
				if($city != 'Berlin') {
					echo 'City is: ' . $city . "\n";
					$in_osm = 2;
				}
				
				//country differing?
				if($country != 'DE') {
					echo 'Country is: ' . $country . "\n";
					$in_osm = 2;
				}
			
				//update
				$sql = 'UPDATE numbers
						SET in_osm = ' . $in_osm . '
						WHERE nid = ' . $row3['nid'];
				$db->query($sql);
				if($in_osm == 2) {
					echo $row2['street_name'] . ' => ' . $street . ' ' . $pid . ' ' . $sid . ' ' . $housenumber . ' ' . $in_osm . "\n";
				}
				break;
			}
    	}    	
    }
    fclose($handle);
}