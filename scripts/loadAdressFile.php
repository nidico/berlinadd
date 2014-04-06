<?php
include_once 'db.php';
include_once 'lib.php';

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
if (($handle = fopen("data/HKO_EPSG5650_2013-12-13.txt", "r")) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
    	//utf8 encode
    	foreach($row as $k => $v) {
    		$row[$k] = utf8_encode($v);
    	}
    
    	//get data
        $bid = intval($row[6]);
        $oid = intval($row[7]);
        $numberInt = $db->real_escape_string($row[9]);
        $numberExt = $db->real_escape_string($row[10]);
        $number = $numberInt . $numberExt;
        $street = $db->real_escape_string($row[13]);
        $postcode = $db->real_escape_string($row[14]);
        
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
    }
    fclose($handle);
}