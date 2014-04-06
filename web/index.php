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
			<a href="?bip=<?php echo $bezirk['bid']; ?>"><?php echo $bezirk['name']; ?></a>
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

<?php
//footer template
include 'templates/footer.php';
?>