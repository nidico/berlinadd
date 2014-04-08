import urllib2, sys, os, hashlib

def file_get_contents(filename):
    with open(filename) as f:
        return f.read()
        
def md5_for_file(filename, block_size=2**20):
	try:
		f = open(filename,'rb')
		md5 = hashlib.md5()
		while True:
			data = f.read(block_size)
			if not data:
				break
			md5.update(data)
		return md5.hexdigest()
	except:
		print "Could not open file for checking MD5"
		sys.exit(1)

def write_to_file(filename, str):
	f = open(filename,'w')
	f.write(str)
	f.close()

print "Loading current MD5..."
md5_url = "http://download.geofabrik.de/europe/germany/berlin-latest.osm.pbf.md5"
try:
	md5_site = urllib2.urlopen(md5_url)
	md5_text = md5_site.read()
except:
	print "Error: Cannot load MD5"
	sys.exit(1)
md5_strings = md5_text.split(' ');
md5 = md5_strings[0]
print md5

print "Compare with our current MD5..."
last_md5 = ''
try:
	last_md5 = file_get_contents('data/md5.txt')
except:
	print "No current MD5 file found."

if md5 == last_md5:
	print "Current MD5 is newest. Exit."
	sys.exit(1)
else:
	print "New MD5 found. Downloading."

print "Downloading new file..."
os.system('wget http://download.geofabrik.de/europe/germany/berlin-latest.osm.pbf -O data/berlin-latest.osm.pbf')

print "Check file MD5..."
file_md5 = md5_for_file('data/berlin-latest.osm.pbf')
if file_md5 != md5:
	print "Wrong MD5 for downloaded file. Exit."
	sys.exit(1)
write_to_file('data/md5.txt', md5)

print "Extract addresses..."
os.system('node parseOsm.js')
os.system('node parseOsmInterpolation.js')

print "Import addresses..."
os.system('php loadOsmFile.php')