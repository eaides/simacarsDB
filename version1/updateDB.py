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

try:
	copyfile(src, dst)
except:
	print ("No puedo hacer backup del archivo base de datos!")
	sys.exit("Asegurese de estar en la carpeta de SIMACARS correcta")
	
max = 0;
new_airports_icao = {'LECH':0, 'LERJ':0, 'LETL':0}
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
	},
	"LERJ":{
		"ident":'LERJ',
		"type":'small_airport',
		"name":'Logrono-Agoncillo Airport',
		"latitude_deg":'42.4609534888',
		"longitude_deg":'-2.32223510742',
		"elevation_ft":'1161',
		"continent":'EU',
		"iso_country":'ES',
		"iso_region":'ES-LO',
		"municipality":'Logrono',
		"scheduled_service":'yes',
		"gps_code":'LERJ',
		"iata_code":'RJL',
		"local_code":'',
		"home_link":'',
		"wikipedia_link":'',
		"keywords":''
	},
		"LERJ":{
		"ident":'LERJ',
		"type":'small_airport',
		"name":'Logrono-Agoncillo Airport',
		"latitude_deg":'42.4609534888',
		"longitude_deg":'-2.32223510742',
		"elevation_ft":'1161',
		"continent":'EU',
		"iso_country":'ES',
		"iso_region":'ES-LO',
		"municipality":'Logrono',
		"scheduled_service":'yes',
		"gps_code":'LERJ',
		"iata_code":'RJL',
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
	sys.exit("error al acceder a la base de datos")
			
 
def do_insert_update():
	global new_airports_icao
	global max
	for icao in new_airports_icao:
		id = int(new_airports_icao[icao])
		print (' ', end='')
		print (icao, end='')
		print (' ', end='')
		print (id)
		query = '';
		if id > 0:
			query = "UPDATE \"airports\" SET ";
			query = query + "\"ident\" = '" + new_airports[icao]['ident'] + "', "
			query = query + "\"type\" = '" + new_airports[icao]['type'] + "', "
			query = query + "\"name\" = '" + new_airports[icao]['name'] + "', "
			query = query + "\"latitude_deg\" = '" + new_airports[icao]['latitude_deg'] + "', "
			query = query + "\"longitude_deg\" = '" + new_airports[icao]['longitude_deg'] + "', "
			query = query + "\"elevation_ft\" = '" + new_airports[icao]['elevation_ft'] + "', "
			query = query + "\"continent\" = '" + new_airports[icao]['continent'] + "', "
			query = query + "\"iso_country\" = '" + new_airports[icao]['iso_country'] + "', "
			query = query + "\"iso_region\" = '" + new_airports[icao]['iso_region'] + "', "
			query = query + "\"municipality\" = '" + new_airports[icao]['municipality'] + "', "
			query = query + "\"scheduled_service\" = '" + new_airports[icao]['scheduled_service'] + "', "
			query = query + "\"gps_code\" = '" + new_airports[icao]['gps_code'] + "', "
			query = query + "\"iata_code\" = '" + new_airports[icao]['iata_code'] + "', "
			query = query + "\"local_code\" = '" + new_airports[icao]['local_code'] + "', "
			query = query + "\"home_link\" = '" + new_airports[icao]['home_link'] + "', "
			query = query + "\"wikipedia_link\" = '" + new_airports[icao]['wikipedia_link'] + "', "
			query = query + "\"keywords\" = '" + new_airports[icao]['keywords'] + "'"
			query = query + " WHERE \"id\" = '" + str(id) + "'"
		else:
			max = max + 1
			query = "INSERT INTO \"airports\" (\"id\", \"ident\", \"type\", \"name\", \"latitude_deg\", \"longitude_deg\", \"elevation_ft\", \"continent\", \"iso_country\", \"iso_region\", \"municipality\", \"scheduled_service\", \"gps_code\", \"iata_code\", \"local_code\", \"home_link\", \"wikipedia_link\", \"keywords\") VALUES ("
			query = query + "'" + str(max) + "', "
			query = query + "'" + new_airports[icao]['ident'] + "', "
			query = query + "'" + new_airports[icao]['type'] + "', "
			query = query + "'" + new_airports[icao]['name'] + "', "
			query = query + "'" + new_airports[icao]['latitude_deg'] + "', "
			query = query + "'" + new_airports[icao]['longitude_deg'] + "', "
			query = query + "'" + new_airports[icao]['elevation_ft'] + "', "
			query = query + "'" + new_airports[icao]['continent'] + "', "
			query = query + "'" + new_airports[icao]['iso_country'] + "', "
			query = query + "'" + new_airports[icao]['iso_region'] + "', "
			query = query + "'" + new_airports[icao]['municipality'] + "', "
			query = query + "'" + new_airports[icao]['scheduled_service'] + "', "
			query = query + "'" + new_airports[icao]['gps_code'] + "', "
			query = query + "'" + new_airports[icao]['iata_code'] + "', "
			query = query + "'" + new_airports[icao]['local_code'] + "', "
			query = query + "'" + new_airports[icao]['home_link'] + "', "
			query = query + "'" + new_airports[icao]['wikipedia_link'] + "', "
			query = query + "'" + new_airports[icao]['keywords'] + "')"
					
		print (query)
		try:
			c.execute(query)
		except:
			sys.exit("error al ejecutar consulta a la base de datos")

		
def leer_maximo_and_airports():
	global new_airports_icao
	global new_airports
	global max

	try:
		c.execute('SELECT id,ident FROM airports')
		data = c.fetchall()
	except:
		sys.exit("error al ejecutar consulta a la base de datos")

	for row in data:
		the_id = int(row[0])
		icao = row[1]
		if the_id > max:
			max = the_id
			
		if icao in new_airports_icao:
			new_airports_icao[icao] = the_id
			
	print ('max id:', end=' ')
	print (max)
	print ('')
		
leer_maximo_and_airports()
do_insert_update()

conn.commit()
c.close()
conn.close()


