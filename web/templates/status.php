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
	<div style="width: 100%; min-height: 16px; padding: 2px; font-size: 0.8em; text-align: center; background-color: <?php echo $color; ?>">
	<?php if(isset($status['warning'])) { ?>
		<?php echo $status['warning']; ?>
	<?php } ?>
	</div>
<?php
}