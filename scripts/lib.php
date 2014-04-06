<?php
//for mb_strtolower and umlauts
mb_internal_encoding('UTF-8');  

//simplify street
function simplifyStreet($street) {
	//remove non alphanumeric
	$street = preg_replace( "/[^\p{L}|\p{N}]+/u", "", $street);
	
	//lowercase
	$street = mb_strtolower($street);
	
	//replace umlauts
	$street = mb_ereg_replace('ß', 'ss', $street);
	$street = mb_ereg_replace('ü', 'ue', $street);
	$street = mb_ereg_replace('ä', 'ae', $street);
	$street = mb_ereg_replace('ö', 'oe', $street);
	
	//replace straße with str to avoid abbreviations
	$street = mb_ereg_replace('strasse', 'str', $street);

	return $street;
}