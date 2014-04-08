pbfParser = require('./osm-read/lib/pbfParser.js');
addressCsv = require('./addressCsv.js');

//open output file
addressCsv.openFile('osm.csv');

//parse osm file
pbfParser.parse({
	filePath: 'data/berlin-latest.osm.pbf',
	endDocument: function(){
		//process.exit(0);
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
		var postcode = data.tags['addr:postcode'];
	} else {
		return;
	}
	
	//street
	if(data.tags['addr:street'] != undefined) {
		var street = data.tags['addr:street'];
	} else {
		return;
	}
	
	//housenumber
	if(data.tags['addr:housenumber'] != undefined) {
		var housenumber = data.tags['addr:housenumber'];
	} else {
		return;
	}
	
	//city
	var city = '';
	if(data.tags['addr:city'] != undefined) {
		city = data.tags['addr:city'];
	}
	
	//country
	var country = '';
	if(data.tags['addr:country'] != undefined) {
		country = data.tags['addr:country'];
	}
	
	//suburb
	var suburb = '';
	if(data.tags['addr:suburb'] != undefined) {
		suburb = data.tags['addr:suburb'];
	}
		
	//write to file
	addressCsv.writeAddress(postcode, street, housenumber, city, suburb, country);
}