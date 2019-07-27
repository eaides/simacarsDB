# updateDB.py
# update sqlite3 DB for SIM-ACARS by fixed data (discover by X-Plane Espanol
#
# Ernesto Aides
# version 1.1.3
#

from __future__ import print_function
from shutil import copyfile
import sqlite3
import sys

src = 'SIM_ACARS.DB3'
dst = 'SIM_ACARS_BCKP.DB3'
xpl = 'little_navmap_xp11.sqlite'

xpl_list = dict()
sac_list = dict()
missing_list = dict()

max = 0;

new_airports = { 
	"LECH":{
		"ident":'LECH',
		"type":'small_airport',
		"name":'Castellon-Costa Azahar',
		"latitude_deg":'40.20547187410759',
		"longitude_deg":'0.06071754395631501',
		"elevation_ft":'1181',
		"continent":'EU',
		"iso_country":'ES',
		"iso_region":'ES-AR',
		"municipality":'Castellon',
		"scheduled_service":'no',
		"gps_code":'LECH',
		"iata_code":'',
		"local_code":'',
		"home_link":'',
		"wikipedia_link":'',
		"keywords":''
	}
}
			
try:
	conn = sqlite3.connect(src)
	c = conn.cursor()
except:
	sys.exit("error al acceder a la base de datos SIMACARS")

try:
	conn2 = sqlite3.connect(xpl)
	c2 = conn2.cursor()
except:
	sys.exit("error al acceder a la base de datos littlenavmap xplane")
			
def read_from_xpl():
    query = "SELECT `ident`, `name` FROM `airport` WHERE `ident` LIKE \"LE%\""
    for row in c2.execute(query):
#        print (row[0])
        ident = row[0]
        name = row[1]
        dict = {"ident" : ident, "name" : name }
        xpl_list[ident] = dict
		
def read_drom_sac():
    query = "SELECT `ident`,`name` FROM `airports` WHERE `ident` LIKE \"LE%\""
    for row in c.execute(query):
#        print (row[0])
        ident = row[0]
        name = row[1]
        dict = {"ident" : ident, "name" : name }
        sac_list[ident] = dict
	
def compare():
    for key, val in xpl_list.items():
#	    print(val["name"])
#       print (key)
        ident = key
        name = val["name"]
#       print (ident)
#       print (name)
        if ident in sac_list:
		    dummy = ident
        else:
            print (ident, " - " , name)
		
read_from_xpl()
read_drom_sac()
compare()

# print (xpl_list['LE04']["name"])
# print (sac_list['LE85']["name"])

conn.commit()
c.close()
conn.close()

conn2.commit()
c2.close()
conn2.close()

