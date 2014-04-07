fs = require('fs');

//open output file
var file;
function openFile(filename) {
	file = fs.createWriteStream(filename, {'flags': 'w'});
}
exports.openFile = openFile;

//write address
function writeAddress(postcode, street, housenumber, city, suburb, country) {
	//remove commas
	postcode = postcode.replace(",", "");
	street = street.replace(",", "");
	housenumber = housenumber.replace(",", "");
	city = city.replace(",", "");
	suburb = suburb.replace(",", "");
	country = country.replace(",", "");
	
	//write to file
	var output = postcode + "," + street + "," + housenumber + "," + city + "," + suburb + "," + country;
	file.write(output + "\n");
	console.log(output);
}
exports.writeAddress = writeAddress;