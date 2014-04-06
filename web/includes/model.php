<?php
class Model {
	private $db;
	
	function __construct() {
		$this->db = new mysqli('127.0.0.1', 'root', 'root', 'berlinadd');
		$this->db->query("SET NAMES 'utf8'");
	}
	
	//get total
	function getTotal() {
		$ary = array();
		
		//get total
		$sql = 'SELECT COUNT(*) num
				FROM numbers';
		$res = $this->db->query($sql);
		$row = $res->fetch_assoc();
		$ary['num'] = $row['num'];
		
		//get in_osm 1 and 2
		for($i = 1; $i <= 2; $i++) {
			$sql = 'SELECT COUNT(*) num 
					FROM numbers
					WHERE in_osm = ' . $i;
			$res = $this->db->query($sql);
			while($row = $res->fetch_assoc()) {
				$ary['in_osm_' . $i] = $row['num'];
			}
		}
		return $ary;
	}
	
	//get bezirke
	function getBezirke() {
		//get total
		$bezirke = array();
		$sql = 'SELECT n.bid, b.bezirk_name, COUNT(*) num 
				FROM bezirke b 
				LEFT JOIN numbers n 
					ON b.bid = n.bid
				GROUP BY b.bid
				ORDER BY b.bid ASC';
		$res = $this->db->query($sql);
		while($row = $res->fetch_assoc()) {
			$bezirke[$row['bid']] = array(
				'name' => $row['bezirk_name'],
				'bid' => $row['bid'],
				'num' => $row['num'],
				'in_osm_1' => 0,
				'in_osm_2' => 0
			);
		}
		
		//get in_osm 1 and 2
		for($i = 1; $i <= 2; $i++) {
			$sql = 'SELECT bid, COUNT(*) num 
					FROM numbers n
					WHERE in_osm = ' . $i . ' 
					GROUP BY bid';
			$res = $this->db->query($sql);
			while($row = $res->fetch_assoc()) {
				$bezirke[$row['bid']]['in_osm_' . $i] = $row['num'];
			}
		}
		return $bezirke;
	}
	
	//get postcodes
	function getPostcodes() {
		//get total
		$postcodes = array();
		$sql = 'SELECT n.pid, p.postcode, COUNT(*) num 
				FROM postcodes p 
				LEFT JOIN numbers n 
					ON p.pid = n.pid
				GROUP BY p.pid
				ORDER BY p.postcode ASC';
		$res = $this->db->query($sql);
		while($row = $res->fetch_assoc()) {
			$postcodes[$row['pid']] = array(
				'name' => $row['postcode'],
				'pid' => $row['pid'],
				'num' => $row['num'],
				'in_osm_1' => 0,
				'in_osm_2' => 0
			);
		}
		
		//get in_osm 1 and 2
		for($i = 1; $i <= 2; $i++) {
			$sql = 'SELECT pid, COUNT(*) num 
					FROM numbers n
					WHERE in_osm = ' . $i . ' 
					GROUP BY pid';
			$res = $this->db->query($sql);
			while($row = $res->fetch_assoc()) {
				$postcodes[$row['pid']]['in_osm_' . $i] = $row['num'];
			}
		}
		return $postcodes;
	}
	
	//get ortsteile
	function getOrtsteile($bid) {
		//get total
		$ortsteile = array();
		$sql = 'SELECT n.oid, o.ortsteil_name, COUNT(*) num 
				FROM ortsteile o 
				LEFT JOIN numbers n 
					ON o.oid = n.oid
				WHERE n.bid = ' . $bid . '
				GROUP BY o.oid
				ORDER BY o.oid ASC';
		$res = $this->db->query($sql);
		while($row = $res->fetch_assoc()) {
			$ortsteile[$row['oid']] = array(
				'name' => $row['ortsteil_name'],
				'oid' => $row['oid'],
				'num' => $row['num'],
				'in_osm_1' => 0,
				'in_osm_2' => 0
			);
		}
		
		//get in_osm 1 and 2
		for($i = 1; $i <= 2; $i++) {
			$sql = 'SELECT oid, COUNT(*) num 
					FROM numbers n
					WHERE in_osm = ' . $i . ' 
						AND bid = ' . $bid . '
					GROUP BY oid';
			$res = $this->db->query($sql);
			while($row = $res->fetch_assoc()) {
				$ortsteile[$row['oid']]['in_osm_' . $i] = $row['num'];
			}
		}
		return $ortsteile;
	}
	
	//get bezirk by bid
	function getBezirk($bid) {
		$sql = 'SELECT bezirk_name
				FROM bezirke
				WHERE bid = ' . $bid;
		$res = $this->db->query($sql);
		if($row = $res->fetch_assoc()) {
			return $row['bezirk_name'];
		} else {
			return false;
		}
	}
	
	//get streets for oid
	function getStreetsForOid($oid) {
		//get total
		$streets = array();
		$sql = 'SELECT s.sid, s.street_name, COUNT(*) num
				FROM numbers n
				LEFT JOIN streets s
				ON s.sid = n.sid
				WHERE n.oid = ' . $oid . '
				GROUP BY n.sid
				ORDER BY s.street_name';
		$res = $this->db->query($sql);
		while($row = $res->fetch_assoc()) {
			$streets[$row['sid']] = array(
				'name' => $row['street_name'],
				'sid' => $row['sid'],
				'num' => $row['num'],
				'in_osm_1' => 0,
				'in_osm_2' => 0
			);
		}
		
		//get in_osm 1 and 2
		for($i = 1; $i <= 2; $i++) {
			$sql = 'SELECT sid, COUNT(*) num
					FROM numbers
					WHERE oid = ' . $oid . '
						AND in_osm = ' . $i . '
					GROUP BY sid';
			$res = $this->db->query($sql);
			while($row = $res->fetch_assoc()) {
				$streets[$row['sid']]['in_osm_' . $i] = $row['num'];
			}
		}
		return $streets;
	}
	
	//get ortsteil by oid
	function getOrtsteil($oid) {
		$sql = 'SELECT ortsteil_name
				FROM ortsteile
				WHERE oid = ' . $oid;
		$res = $this->db->query($sql);
		if($row = $res->fetch_assoc()) {
			return $row['ortsteil_name'];
		} else {
			return false;
		}
	}
	
	//get streets for pid
	function getStreetsForPid($pid) {
		//get total
		$streets = array();
		$sql = 'SELECT s.sid, s.street_name, COUNT(*) num
				FROM numbers n
				LEFT JOIN streets s
				ON s.sid = n.sid
				WHERE n.pid = ' . $pid . '
				GROUP BY n.sid
				ORDER BY s.street_name';
		$res = $this->db->query($sql);
		while($row = $res->fetch_assoc()) {
			$streets[$row['sid']] = array(
				'name' => $row['street_name'],
				'sid' => $row['sid'],
				'num' => $row['num'],
				'in_osm_1' => 0,
				'in_osm_2' => 0
			);
		}
		
		//get in_osm 1 and 2
		for($i = 1; $i <= 2; $i++) {
			$sql = 'SELECT sid, COUNT(*) num
					FROM numbers
					WHERE pid = ' . $pid . '
						AND in_osm = ' . $i . '
					GROUP BY sid';
			$res = $this->db->query($sql);
			while($row = $res->fetch_assoc()) {
				$streets[$row['sid']]['in_osm_' . $i] = $row['num'];
			}
		}
		return $streets;
	}
	
	//get postcode by pid
	function getPostcode($pid) {
		$sql = 'SELECT postcode
				FROM postcodes
				WHERE pid = ' . $pid;
		$res = $this->db->query($sql);
		if($row = $res->fetch_assoc()) {
			return $row['postcode'];
		} else {
			return false;
		}
	}
}