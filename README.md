The original project can be found at [berlinadd.morbz.de](http://berlinadd.morbz.de).

Setup
--------------
* Install git, zip, node.js, php, mysql, proj-bin

* Get project files  
`git clone --recursive https://github.com/MorbZ/berlinadd`

* Import official addresses  
run `scripts/download.sh`  
Create 'berlinadd' MySQL table  
Set correct user and password in scripts/db.php  
run `php scripts/loadAdressFile.php`

* Update from OSM data  
`npm install protobufjs`  
run `scripts/update.py`

* Publish  
Link webserver to 'web' directory  
Set correct user and password in web/includes/model.php  