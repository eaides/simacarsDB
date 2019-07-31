----------------------------------------------------------------------------------------
1er PASO: script PHP para modificar las bases de datos
----------------------------------------------------------------------------------------

git clone git@github.com:eaides/simacarsDB.git
cd simacarsDB/version2/php
ls
	archivos que se deben tener:
		ORIGINAL_SIM_ACARS.DB3				la base de datos sqlite original del simacars (tal como se la baja de XPLE)
		little_navmap_xp11.sqlite			la base de datos (recortada en varias tablas) que genera el littlenavmap
												con todos los aeropuertos de xplane 11.35 (de stock)
		sync_XPLE.php						archivo principal del script
		sync_XPLE_airports.php				los datos completos de los 3 aeropuertos que conocemos con problemas (LECH, LERJ y LETL)
		sync_XPLE_class.php					define la clase principal que hace la sincronizacion
		sync_XPLE_dbConnection.php			defina la coneccion a la base de datos de VAM

nano sync_XPLE_dbConnection.php
	poner los valores de de coneccion a la base de datos de VAM
	asegurarse que la variable $disable este en 'false'		$disable = false;
	
subir con FTP todos los archivos al sitio de VAM
desde el browser, navegar a <VAM>/sync_XPLE.php

	ejemplo:	https://equisplanespain.com/va/vam/sync_XPLE.php
	
una vez terminado el script, se vera una salida como la siguiente:

Set VAM DB and sqLite by fixed defined airports:
Fixing... Done!


VAM DB Web:
Airports with errors: 3343 of 46172
Missing Airports: 8111 of 46172
Missing Airports (closed): 26 of 46172
Fixing... Done!


FIX sqlite:
Airports with errors: 3343 of 46172
Missing Airports: 8111 of 46172
Missing Airports (closed): 26 of 46172
Fixing... Done!


Sync Finished OK!

Synchronization OK


a) bajar con ftp el archivo generado FIX_SIM_ACARS.DB3
b) nano sync_XPLE_dbConnection.php
	asegurarse que la variable $disable este en 'true'		$disable = true;
c) subir por ftp el archivo modificado:	sync_XPLE_dbConnection.php

-Si se desea correr el script nuevamente, hay que volver a modificar la variable a false, subirlo por ftp y repetir el proceso

Que es lo que pasa en las bases de datos:
	a) en la base de datos mySql de la VAM:
		se crea una tabla de backup (por unica vez) llamada `airports_bckp` como backup de la tabla `airports`
		se insertan o modifican los datos a la tabla `airport` de los aeropeurtos definidos en sync_XPLE_airports.php 
			(esto es lo mismo que se hacia a mano directamente a la BD)
		se insertan todos los aeropuertos del little_navmap_xp11.sqlite tabla `airport` y que no existan en la BD de la VAM `airports`
		se corrigen todos los aeropuertos del little_navmap_xp11.sqlite tabla `airport` y que existan en la BD de la VAM `airports` y cuyos
			datos de coordenadas difieran en mas de 20 millas o mas de 50 pies de diferencias en las alturas del aeropuertos
			El campo type (large, medium o small) se trata de sacar de 2 tablas del little_navmap_xp11.sqlite llamadas airport_large y airport_medium
			si no se encuentra alli, se trata de adivinar por un valor llamdo rating (peroe sto no es preciso)
	b) se crea una base de datos sqlite llamada FIX_SIM_ACARS.DB3 que tiene una unica tabla llamada `airports` partiendo de la tabla del mismo nombre
		de la BD ORIGINAL_SIM_ACARS.DB3 y se repiten los pasos explicados arriba

Se puede verificar que (esto si ya no fue modificada a mano la tabla `airports` de la BD):
en la base de datos mySql de la VAM, el query
	SELECT * FROM `airports_bckp` WHERE `ident` IN ('LECH','LETL','LERJ')
retorna solo LECH pero con Calamocha	
	SELECT * FROM `airports` WHERE `ident` IN ('LECH','LETL','LERJ')
retorna los 3 aeropuertos y LECH figura como Castellon-Costa Azahar

Lo mismo ocurre si se inspecciona la FIX_SIM_ACARS.DB3 respecto a la ORIGINAL_SIM_ACARS.DB3


----------------------------------------------------------------------------------------
2do PASO: script python, para los integrantes de la VA
----------------------------------------------------------------------------------------

Los pilotos de la VA deben bajar 2 archivos:

1) el script python que se encuentra en simacarsDB\version2\python llamado updateDB_2.0.0.py (se lo puede renombrar a simplemente updateDB.py como hasta ahora)
2) la base de datos sqlite generada en el paso 1 por el scrip php:	FIX_SIM_ACARS.DB3

ambos archivos tal como ahora a la misma carpeta del simacars, al ejecutar el python lo que pasa es:

a) se crea la copia de seguridad SIM_ACARS_BCKP.DB3
b) se borran todos los datos de la tabla `airports` de la SIM_ACARS.DB3
c) se insertan todos los datos de la tabla `airports` pero desde la BD FIX_SIM_ACARS.DB3 (corregida)


