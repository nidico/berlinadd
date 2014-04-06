<?php
//includes
include_once 'templates/progress.php';
include_once 'templates/status.php';

//init model
include_once 'includes/model.php';
$model = new Model();

//header template
include 'templates/header.php';
?>
<br>

<?php
if(isset($_GET['oid']) && isset($_GET['sid'])) {
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
					<?php echo $number['number']; ?>
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