<?php
function showStatus($status) {
	//get color
	$color = '';
	switch($status['in_osm']) {
		case 0:
			$color = 'red';
			break;
		case 1:
			$color = 'green';
			break;
		case 2:
			$color = 'orange';
			break;
	}
?>
	<div style="width: 100%; min-height: 20px; background-color: <?php echo $color; ?>">
	</div>
<?php
}