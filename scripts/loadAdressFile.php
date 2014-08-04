<?php
include_once 'db.php';
include_once 'lib.php';

ignore_user_abort(true);
set_time_limit(0);

//format tables
$sql = '
DROP TABLE IF EXISTS bezirke;
CREATE TABLE bezirke (
	bid INT PRIMARY KEY,
	bezirk_name VARCHAR(255)
) DEFAULT CHARSET utf8;

DROP TABLE IF EXISTS ortsteile;
CREATE TABLE ortsteile (
	oid INT PRIMARY KEY,
	bid INT,
	ortsteil_name VARCHAR(255),
	INDEX(bid)
) DEFAULT CHARSET utf8;

DROP TABLE IF EXISTS streets;
CREATE TABLE streets (
	sid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	street_name VARCHAR(255),
	street_name_simple VARCHAR(255),
	INDEX(street_name),
	INDEX(street_name_simple)
) DEFAULT CHARSET utf8;

DROP TABLE IF EXISTS postcodes;
CREATE TABLE postcodes (
	pid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	postcode CHAR(5),
	INDEX(postcode)
) DEFAULT CHARSET utf8;

DROP TABLE IF EXISTS numbers;
CREATE TABLE numbers (
	nid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	sid INT,
	bid INT,
	oid INT,
	pid INT,
	number VARCHAR(255),
	lat DECIMAL(8, 6),
	lon DECIMAL(9, 6),
	warning_country VARCHAR(255),
	warning_street VARCHAR(255),
	warning_city VARCHAR(255),
	warning_mentioned VARCHAR(255),
	warning_interpolated TINYINT DEFAULT 0,
	in_osm TINYINT DEFAULT 0,
	INDEX(sid),
	INDEX bid_in_osm (bid, in_osm),
	INDEX(oid),
	INDEX pid_in_osm (pid, in_osm),
	INDEX(in_osm)
) DEFAULT CHARSET utf8;

DROP TABLE IF EXISTS stats;
CREATE TABLE stats (
	stid VARCHAR(255) NOT NULL PRIMARY KEY,
	value VARCHAR(255)
) DEFAULT CHARSET utf8;
INSERT INTO stats (stid, value) VALUES(\'isUpdating\', \'1\');
INSERT INTO stats (stid, value) VALUES(\'lastUpdate\', \'0\');
';
$db->multi_query($sql);
while ($db->next_result()) {;} // flush multi_queries

//load bezirke
$bezirke = array();
if (($handle = fopen("data/bezirke.csv", "r")) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
    	$bid = intval($row[0]);
    	$bezirk_name = $row[1];
    	$bezirke[$bid] = $bezirk_name;
    	echo $bid . ' ' . $bezirk_name . "\n";
    }
    fclose($handle);
}

foreach($bezirke as $k => $v) {
	$sql = 'INSERT INTO bezirke (bid, bezirk_name)
			VALUES (' . $k . ', \'' . $v . '\')';
	$db->query($sql);
}

//load ortsteile
$ortsteile = array();
if (($handle = fopen("data/ortsteile.csv", "r")) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
    	$oid = $row[0];
    	$ortsteil_name = $row[1];
    	$ortsteile[$oid] = $ortsteil_name;
    	echo $oid . ' ' . $ortsteil_name . "\n";
    }
    fclose($handle);
}

foreach($ortsteile as $k => $v) {
	//get bezirk
	$bid = substr($k, 0, 2);
	
	//insert
	$sql = 'INSERT INTO ortsteile (oid, bid, ortsteil_name)
			VALUES (' . $k . ', ' . $bid . ', \'' . $v . '\')';
	$db->query($sql);
}

//load address file
$numberCache = array();
if (($handle = fopen("data/HKO_EPSG3068_2013-12-13.txt", "r")) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
    	//utf8 encode
    	foreach($row as $k => $v) {
    		$row[$k] = utf8_encode($v);
    	}
    
    	//get data
        $quality = $row[2];
        $bid = intval($row[6]);
        $oid = intval($row[7]);
        $numberInt = $db->real_escape_string($row[9]);
        $numberExt = $db->real_escape_string($row[10]);
        $number = $numberInt . $numberExt;
        $coord1 = $db->real_escape_string($row[11]);
        $coord2 = $db->real_escape_string($row[12]);
        $coords = array($coord1, $coord2);
        $street = $db->real_escape_string($row[13]);
        $postcode = $db->real_escape_string($row[14]);

        // Don't include planned building
        // see http://fbinter.stadt-berlin.de/fb_daten/beschreibung/sachdaten/s_hauskoordinaten.html
        // Qualitaetsangaben A (exakte Hauskoordinate) und R (Koordinate auf dem Grundstueck, geplantes Gebaeude)
        if ($quality == 'R') {
            continue;
        }

        //wrong ortsteil numbers
        if($oid < 100) {
        	continue;
        }
        
        //get street
        $sid = 0;
        $sql = 'SELECT sid
        		FROM streets
        		WHERE street_name = \'' . $street . '\'';
        $res = $db->query($sql);
        if($row = $res->fetch_assoc()) {
        	$sid = $row['sid'];
        } else {
        	//insert
        	$streetSimple = simplifyStreet($street);
        	$sql = 'INSERT INTO streets (street_name, street_name_simple)
        			VALUES (\'' . $street . '\', \'' . $streetSimple . '\')';
        	$db->query($sql);
        	$sid = $db->insert_id;
        }
        
        //get postcode
        $pid = 0;
        $sql = 'SELECT pid
        		FROM postcodes
        		WHERE postcode = \'' . $postcode . '\'';
        $res = $db->query($sql);
        if($row = $res->fetch_assoc()) {
        	$pid = $row['pid'];
        } else {
        	//insert
        	$sql = 'INSERT INTO postcodes (postcode)
        			VALUES (\'' . $postcode . '\')';
        	$db->query($sql);
        	$pid = $db->insert_id;
        }
        
        //insert number
        $sql = 'INSERT INTO numbers (sid, bid, oid, pid, number)
        		VALUES (' . $sid . ', ' . $bid . ', ' . $oid . ', ' . $pid . ', \'' . $number . '\')';
        $db->query($sql);
        echo 'Insert: ' . $pid . ' ' . $sid . ' ' . $postcode . ' ' . $street . ' ' . $number . "\n";
        
        //add to number cache
        $nid = $db->insert_id;
        $numberCache[$nid] = $coords;
        if(count($numberCache) >= 1000) {
        	addCoords();
        }
    }
    fclose($handle);
}
addCoords();

//add coords to numbers
function addCoords() {
	global $numberCache, $db;
	
	echo 'Convert coords..' . "\n";
	
	//empty?
	if(count($numberCache) == 0) {
		return;
	}
	
	//convert
	$newCoords = convert($numberCache);
	$i = 0;
	foreach($numberCache as $nid => $coord) {
		$sql = 'UPDATE numbers
				SET lat = ' . $newCoords[$i][0] . ',
					lon = ' . $newCoords[$i][1] . '
				WHERE nid = ' . $nid;
		$db->query($sql);
		$i++;
	}
	$numberCache = array();
}

//convert from EPSG:3068 to lat lon
function convert($ary) {
	//open process
	$descriptorspec = array(
		0 => array("pipe","r"),
		1 => array("pipe","w"),
		2 => array("pipe", "w")
	);
	$cmd = 'cs2cs -f "%.6f" -v +init=epsg:3068 +to +init=epsg:4326';
	$process = proc_open($cmd, $descriptorspec, $pipes);
	if(is_resource($process)) {
		//write coords
		$out = '';
		foreach($ary as $coords) {
			$coord1 = str_replace(',', '.', $coords[0]);
			$coord2 = str_replace(',', '.', $coords[1]);
			$coord = $coord1 . ' ' . $coord2 . "\n";
			fwrite($pipes[0], $coord);
		}
		fclose($pipes[0]);
		
		//read output
		$out = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		proc_close($process);
		
		//parse coords
		$lines = explode("\n", $out);
		$newAry = array();
		foreach($lines as $line) {
			//skip line?
			if(mb_substr($line, 0, 1) == '#' || strlen($line) == 0) {
				continue;
			}
			
			//parse coords
			$parts = preg_split('/\s+/', $line);
			$newAry[] = array($parts[1], $parts[0]);
		}
		return $newAry;
	}
}