pbfParser = require('./osm-read/lib/pbfParser.js');
fs = require('fs');

//open output file
var file = fs.createWriteStream('osm.csv', {'flags': 'w'});

//parse osm file
pbfParser.parse({
	filePath: 'data/berlin-latest.osm.pbf',
	endDocument: function(){
		
	},
	node: function(node){
		addr(node);
	},
	
	way: function(way){
		addr(way);
	},
	
	error: function(msg){
		console.error('error: ' + msg);
		throw msg;
	}
});

function addr(data) {
	//postcode
	if(data.tags['addr:postcode'] != undefined) {
		var postcode = data.tags['addr:postcode'].replace(",", "");
	} else {
		return;
	}
	
	//street
	if(data.tags['addr:street'] != undefined) {
		var street = data.tags['addr:street'].replace(",", "");
	} else {
		return;
	}
	
	//housenumber
	if(data.tags['addr:housenumber'] != undefined) {
		var housenumber = data.tags['addr:housenumber'].replace(",", "");
	} else {
		return;
	}
	
	//city
	var city = '';
	if(data.tags['addr:city'] != undefined) {
		city = data.tags['addr:city'].replace(",", "");
	}
	
	//country
	var country = '';
	if(data.tags['addr:country'] != undefined) {
		country = data.tags['addr:country'].replace(",", "");
	}
	
	//suburb
	var suburb = '';
	if(data.tags['addr:suburb'] != undefined) {
		suburb = data.tags['addr:suburb'].replace(",", "");
	}
		
	//generate output
	var output = postcode + "," + street + "," + housenumber + "," + city + "," + suburb + "," + country;
	file.write(output + "\n");
	console.log(output);
}