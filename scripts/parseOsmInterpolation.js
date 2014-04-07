pbfParser = require('./osm-read/lib/pbfParser.js');
addressCsv = require('./addressCsv.js');

//open output file
addressCsv.openFile('osm_int.csv');

//process
var ways = [];
var nodeRefs = {};
var nodes = {};
var fileIn = 'data/berlin-latest.osm.pbf';
parse1();

//first parse
function parse1() {
	pbfParser.parse({
		filePath: fileIn,
		endDocument: function(){
			parse2();
		},
		node: function(node){
		},
		way: function(way){
			if(way.tags['addr:interpolation'] != undefined) {
				ways.push(way);
				for(var i = 0; i < way.nodeRefs.length; i++) {
					var nodeRef = way.nodeRefs[i];
					nodeRefs[nodeRef] = true;
				};
				console.log(way);
			}
		},
		error: function(msg){
			console.error('error: ' + msg);
			throw msg;
		}
	});
}

//second parse
function parse2() {
	pbfParser.parse({
		filePath: fileIn,
		endDocument: function(){
			process();
		},
		node: function(node){
			if(nodeRefs[node.id] != undefined) {
				nodes[node.id] = node;
				console.log(node);
			}
		},
		way: function(way){
		},
		error: function(msg){
			console.error('error: ' + msg);
			throw msg;
		}
	});
};

//process
function process() {
	//add full nodes to ways
	for(var i = 0; i < ways.length; i++) {
		var way = ways[i];
		way.nodes = [];
		for(j = 0; j < way.nodeRefs.length; j++) {
			var nodeRef = way.nodeRefs[j];
			var node = nodes[nodeRef];
			if(node.tags['addr:housenumber'] != undefined) {
				way.nodes.push(node);
			}
		};
		processWay(way);
	};
}

//process way
function processWay(way) {
	console.log("----------");
	
	//enough nodes
	if(way.nodes.length < 2) {
		console.log("SKIP WAY: less than two address nodes");
		return;
	}
	
	//make tag groups
	var tagGroups = [];
	tagGroups.push(way.tags);
	for(var i = 0; i < way.nodes.length; i++) {
		tagGroups.push(way.nodes[i].tags);
	};
	
	//get postcode and street from tags
	var postcode = {};
	var street = {};
	for(var i = 0; i < tagGroups.length; i++) {
		tags = tagGroups[i];
		
		//postcode
		if(tags['addr:postcode'] != undefined) {
			postcode[tags['addr:postcode']] = true;
		}
		
		//street
		if(tags['addr:street'] != undefined) {
			street[tags['addr:street']] = true;
		}
	};
	
	//check for unique
	if(Object.keys(postcode).length != 1) {
		console.log("SKIP WAY: no or multiple postcodes");
		return;
	}
	if(Object.keys(street).length != 1) {
		console.log("SKIP WAY: no or multiple street");
		return;
	}
	var street = Object.keys(street)[0];
	var postcode = Object.keys(postcode)[0];
	console.log(street + ", " + postcode);
	
	//interpolation type
	var type = way.tags['addr:interpolation'];
	if(type != 'odd' && type != 'even' && type != 'all' && type != 'alphabetic') {
		console.log("SKIP WAY: interpolation type is unknown");
		return;
	}
	console.log(type);
	
	//iterate nodes
	var numbers = {};
	for(var i = 0; i < way.nodes.length - 1; i++) {
		num1 = way.nodes[i].tags['addr:housenumber'];
		num2 = way.nodes[i+1].tags['addr:housenumber'];
		tmpNumbers = getIntervalNumbers(num1, num2, type);
		for(var j = 0; j < tmpNumbers.length; j++) {
			var number = tmpNumbers[j];
			numbers[number] = true;
		};
	}
	
	//log
	var str = '--- ';
	for(var i = 0; i < way.nodes.length; i++) {
		str += way.nodes[i].tags['addr:housenumber'] + ' --- ';
	};
	console.log(str);
	
	//write to file
	var numberKeys = Object.keys(numbers);
	console.log(numberKeys);
	for(var i = 0; i < numberKeys.length; i++) {
		var number = numberKeys[i];
		addressCsv.writeAddress(postcode, street, number, '', '', '');
	}
}

//get numbers for interval
function getIntervalNumbers(num1, num2, type) {
	//get real type
	var realType;
	if(type == 'all' || type == 'odd' || type == 'even') {
		realType = 'num';
	} else {
		realType = 'alpha';
	}
	
	//special case, marked as 'all' but alphabetic
	if(parseInt(num1) == parseInt(num2)) {
		realType = 'alpha';
	}

	//interpolate
	numbers = [];
	if(realType == 'num') {
		//check if both are numbers
		if(isNaN(num1) || isNaN(num2)) {
			return numbers;
		}
		
		//sort
		num1 = parseInt(num1);
		num2 = parseInt(num2);
		var num_min = Math.min(num1, num2);
		var num_max = Math.max(num1, num2);
		
		//check parity
		if(type == 'odd' && (!isOdd(num1) || !isOdd(num2))) {
			return numbers;
		}
		if(type == 'even' && (!isEven(num1) || !isEven(num2))) {
			return numbers;
		}
	
		//iterate numbers
		for(var i = num_min; i <= num_max; i++) {
			if(
				(type == 'odd' && isOdd(i)) ||
				(type == 'even' && isEven(i)) ||
				type == 'all'
			) {
				numbers.push(i);
			}
		}
	} else { //realType = alpha
		//check that we have the same number
		var number = parseInt(num1);
		if(parseInt(num2) != number || isNaN(number)) {
			return numbers;
		}
		
		//get letters
		var letter1 = getLetter(number, num1);
		var letter2 = getLetter(number, num2);
		
		//at least one must have a letter, both no more than one letter
		if(letter1.length > 1 || letter2.length > 1 || 
			(letter1.length == 0 && letter2.length == 0)
		) {
			return numbers;
		}
		
		//add raw number
		if(letter1.length == 0 || letter2.length == 0) {
			numbers.push(number);
		}
		
		//get char codes
		var code1 = letter1.length == 0 ? 97 : letter1.charCodeAt(0);
		var code2 = letter2.length == 0 ? 97 : letter2.charCodeAt(0);
		
		//sort codes
		var code_min = Math.min(code1, code2);
		var code_max = Math.max(code1, code2);
		
		//iterate letters
		for(var i = code_min; i <= code_max; i++) {
			var letter = String.fromCharCode(i);
			numbers.push(number + letter);
		}
	}	
	return numbers;
}

//get letter
function getLetter(number, str) {
	var regex = new RegExp(number, 'g');
	var letter = str.replace(regex, '');
	return letter.toLowerCase();
}

//is odd
function isOdd(num) {
	return num % 2 == 1;
}

//is even
function isEven(num) {
	return num % 2 == 0;
}