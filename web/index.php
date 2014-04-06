<?php
//includes
include_once 'templates/progress.php';

//init model
include_once 'includes/model.php';
$model = new Model();

//header template
include 'templates/header.php';
?>
<br>

<?php
if(isset($_GET['bid'])) {
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
					<a href="?oid=<?php echo $ortsteil['oid']; ?>"><?php echo $street['name']; ?></a>
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