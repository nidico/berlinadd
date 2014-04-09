<?php
//init model
include_once 'includes/model.php';
$model = new Model();
?>
<!DOCTYPE html>
<html>
	<head>
		<title>OSM Berlin Address Progress</title>
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" /> 
		<style type="text/css">
		* {
			font-family: verdana, arial;
		}
		h1,h2,h3 {
			text-align: center;
		}
		h1 {
			font-size: 1.4em;
			margin: 10px;
		}
		h2 {
			font-size: 1.2em;
			margin: 5px;
		}
		h3 {
			font-size: 0.9em;
			margin: 5px;
		}
		a {
			color: #000;
			text-decoration: underline;
		}
		hr {
			margin: 20px 10px;
		}
		table {
			width: 100%;
		}
		</style>
	</head>
	<body>
		<div style="margin: 0 auto; width: 550px; background-color: #eee; padding: 20px 70px; padding-top: 5px;">
			<a href="/">
				<h1>OSM Berlin Address Progress</h1>
			</a>
			<?php if($model->isUpdating()) { ?>
				<div style="color: red; text-align: center; font-size: 0.8em;">
					Daten werden gerade aktualisiert...
				</div>
			<?php } ?>