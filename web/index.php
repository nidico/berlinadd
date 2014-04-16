<?php
//includes
include_once 'templates/progress.php';
include_once 'templates/status.php';

//header template
include 'templates/header.php';
?>
<br>

<?php
if(isset($_GET['nid'])) {
	$nid = intval($_GET['nid']);
	$number = $model->getNumber($nid);
	if($number === false) { ?>
		<h2>Fehler</h2>
	<?php } else { ?>
		<table style="width: 350px; margin: 0 auto;">
			<tr>
				<td>Bezirk:</td>
				<td>
					<a href="?bid=<?php echo $number['bid']; ?>">
						<?php echo $number['bezirk_name']; ?>
					</a>
				</td>
			</tr>
			<tr>
				<td>Ortsteil:</td>
				<td>
					<a href="?oid=<?php echo $number['oid']; ?>">
						<?php echo $number['ortsteil_name']; ?>
					</a>
				</td>
			</tr>
			<tr>
				<td>PLZ:</td>
				<td>
					<a href="?pid=<?php echo $number['pid']; ?>">
						<?php echo $number['postcode']; ?>
					</a>
				</td>
			</tr>
			<tr>
				<td>Straße:</td>
				<td><?php echo $number['street_name']; ?></td>
			</tr>
			<tr>
				<td>Nummer:</td>
				<td><?php echo $number['number']; ?></td>
			</tr>
		</table>
		
		<br>
		<div id="map" style="width: 500px; height: 500px; margin: 0 auto;"></div>
		<script>
			var lat = <?php echo $number['lat']; ?>;
			var lon = <?php echo $number['lon']; ?>;

			var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
			var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
			var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 19, attribution: osmAttrib});		

			map = new L.Map('map');
			map.setView(new L.LatLng(lat, lon), 17);
			map.addLayer(osm);
		
			L.marker([lat, lon]).addTo(map);
		 </script>
	<?php }
} elseif(isset($_GET['pid']) && isset($_GET['sid'])) {
	$pid = intval($_GET['pid']);
	$sid = intval($_GET['sid']);
	$postcode = $model->getPostcode($pid);
	$street = $model->getStreet($sid);
	if($postcode === false || $street === false) { ?>
		<h2>Fehler</h2>
	<?php
	} else { ?>
		<h2>Hausnummern in <?php echo $street; ?></h2>
		<h3>In PLZ <?php echo $postcode; ?></h3>
	
		<table style="width: 370px; margin: 0 auto;">
		<?php
		//get numbers
		$numbers = $model->getNumbersForPidAndSid($pid, $sid);
		foreach($numbers as $number) { ?>
			<tr>
				<td>
					<a href="?nid=<?php echo $number['nid']; ?>">
						<?php echo $number['number']; ?>
					</a>
				</td>
				<td style="width: 300px;">
					<?php showStatus($number['status']); ?>
				</td>
			</tr>
		
		<?php
		}
		?>
		</table>
	<?php
	}
} elseif(isset($_GET['oid']) && isset($_GET['sid'])) {
	$oid = intval($_GET['oid']);
	$sid = intval($_GET['sid']);
	$ortsteil = $model->getOrtsteil($oid);
	$street = $model->getStreet($sid);
	if($ortsteil === false || $street === false) { ?>
		<h2>Fehler</h2>
	<?php
	} else { ?>
		<h2>Hausnummern in <?php echo $street; ?></h2>
		<h3>In Ortsteil <?php echo $ortsteil; ?></h3>
	
		<table style="width: 370px; margin: 0 auto;">
		<?php
		//get numbers
		$numbers = $model->getNumbersForOidAndSid($oid, $sid);
		foreach($numbers as $number) { ?>
			<tr>
				<td>
					<a href="?nid=<?php echo $number['nid']; ?>">
						<?php echo $number['number']; ?>
					</a>
				</td>
				<td style="width: 300px;">
					<?php showStatus($number['status']); ?>
				</td>
			</tr>
		
		<?php
		}
		?>
		</table>
	<?php
	}
} elseif(isset($_GET['bid'])) {
	$bid = intval($_GET['bid']);
	$bezirk = $model->getBezirk($bid);
	if($bezirk === false) {?>
		<h2>Fehler</h2>
	<?php } else { ?>
		<h2>Bezirk <?php echo $bezirk; ?></h2>
		
		<table>
		<?php
		//get ortsteile
		$ortsteile = $model->getOrtsteile($bid);
		foreach($ortsteile as $ortsteil) { ?>
			<tr>
				<td>
					<a href="?oid=<?php echo $ortsteil['oid']; ?>"><?php echo $ortsteil['name']; ?></a>
				</td>
				<td style="width: 300px;"><?php showProgress($ortsteil['in_osm_1'], $ortsteil['in_osm_2'], $ortsteil['num']); ?></td>
			</tr>
		<?php
		}
		?>
		</table>
	<?php
	}
} elseif(isset($_GET['oid'])) {
	$oid = intval($_GET['oid']);
	$ortsteil = $model->getOrtsteil($oid);
	if($ortsteil === false) {?>
		<h2>Fehler</h2>
	<?php } else { ?>
		<h2>Ortsteil <?php echo $ortsteil; ?></h2>
		
		<table>
		<?php
		//get streets
		$streets = $model->getStreetsForOid($oid);
		foreach($streets as $street) { ?>
			<tr>
				<td>
					<a href="?oid=<?php echo $oid; ?>&sid=<?php echo $street['sid']; ?>"><?php echo $street['name']; ?></a>
				</td>
				<td style="width: 300px;"><?php showProgress($street['in_osm_1'], $street['in_osm_2'], $street['num']); ?></td>
			</tr>
		<?php
		}
		?>
		</table>
	<?php
	}
} elseif(isset($_GET['pid'])) {
	$pid = intval($_GET['pid']);
	$postcode = $model->getPostcode($pid);
	if($postcode === false) {?>
		<h2>Fehler</h2>
	<?php } else { ?>
		<h2>PLZ <?php echo $postcode; ?></h2>
		
		<table>
		<?php
		//get streets
		$streets = $model->getStreetsForPid($pid);
		foreach($streets as $street) { ?>
			<tr>
				<td>
					<a href="?pid=<?php echo $pid; ?>&sid=<?php echo $street['sid']; ?>"><?php echo $street['name']; ?></a>
				</td>
				<td style="width: 300px;"><?php showProgress($street['in_osm_1'], $street['in_osm_2'], $street['num']); ?></td>
			</tr>
		<?php
		}
		?>
		</table>
	<?php
	}
} else { 
?>

<h2>Berlin Gesamt</h2>
<?php
//get total
$total = $model->getTotal();
showProgress($total['in_osm_1'], $total['in_osm_2'], $total['num']);
?>
<br>

<h2>Bezirk</h2>
<table>
<?php
//get bezirke
$bezirke = $model->getBezirke();
foreach($bezirke as $bezirk) { ?>
	<tr>
		<td>
			<a href="?bid=<?php echo $bezirk['bid']; ?>"><?php echo $bezirk['name']; ?></a>
		</td>
		<td style="width: 300px;"><?php showProgress($bezirk['in_osm_1'], $bezirk['in_osm_2'], $bezirk['num']); ?></td>
	</tr>
<?php
}
?>
</table>
<br>

<h2>PLZ</h2>
<table>
<?php
//get postcodes
$postcodes = $model->getPostcodes();
foreach($postcodes as $postcode) { ?>
	<tr>
		<td>
			<a href="?pid=<?php echo $postcode['pid']; ?>"><?php echo $postcode['name']; ?></a>
		</td>
		<td style="width: 300px;"><?php showProgress($postcode['in_osm_1'], $postcode['in_osm_2'], $postcode['num']); ?></td>
	</tr>
<?php
}
?>
</table>

<?php } ?>

<?php
//footer template
include 'templates/footer.php';
?>