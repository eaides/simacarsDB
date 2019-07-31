# updateDB.py
# update sqlite3 DB for SIM-ACARS by fixed data (discover by X-Plane Espanol
#
# Ernesto Aides
# version 2.0.0
#

from __future__ import print_function
from shutil import copyfile
import sqlite3
import sys
import os

dbSimAcars = 'SIM_ACARS.DB3'
bckp = 'SIM_ACARS_BCKP.DB3'
dbFix = 'FIX_SIM_ACARS.DB3'

try:
  copyfile(dbSimAcars, bckp)
except:
  print ("No puedo hacer backup del archivo base de datos!")
  sys.exit("Asegurese de estar en la carpeta de SIMACARS correcta")

fix_ok = False
if os.path.exists(dbFix):
  # path exists
  if os.path.isfile(dbFix): # is it a file or a dir?
    # also works when file is a link and the target is writable
    if os.access(dbFix, os.R_OK):
      fix_ok = True
    else:
      sys.exit(dbFix + " no puede ser leido")
  else:
    sys.exit(dbFix + " no es un archivo valido")
else:
  sys.exit("El archivo base de datos FIX " + dbFix + " no se encuentra." + "Asegurese de estar en la carpeta de SIMACARS correcta")

try:
  connBckp = sqlite3.connect(bckp)
  cB = connBckp.cursor()

  # attach dbSimAcars
  attachDatabaseDbSimAcars = "ATTACH DATABASE ? AS dbSimAcars"
  dbSpecDbSimAcars  = (dbSimAcars,)
  cB.execute(attachDatabaseDbSimAcars,dbSpecDbSimAcars)

  # attach dbFix
  attachDatabaseDbFix = "ATTACH DATABASE ? AS dbFix"
  dbSpecBdFix  = (dbFix,)
  cB.execute(attachDatabaseDbFix,dbSpecBdFix)

except:
  sys.exit("error al acceder a la base de datos " + bckp)

#try:
#  connDst = sqlite3.connect(dbSimAcars)
#  cD = connDst.cursor()
#except:
#  sys.exit("error al acceder a la base de datos " + dbSimAcars)

#try:
#  connFrm = sqlite3.connect(dbFix)
#  cF = connFrm.cursor()
#except:
#  sys.exit("error al acceder a la base de datos FIX " + dbFix)

def removeFromDest():
  print ("Removing all airports from " + dbSimAcars)
  query = 'DELETE FROM dbSimAcars.airports';
  try:
    cB.execute(query)
  except:
    print ("Error al ejecutar consulta a la base de datos:")
    sys.exit(query)
  connBckp.commit()

def copyTables():
  print ("Copy from " + dbFix + " to " + dbSimAcars)
  query = 'INSERT INTO dbSimAcars.airports SELECT * FROM dbFix.airports';
  try:
    cB.execute(query)
  except:
    print ("Error al ejecutar consulta a la base de datos:")
    sys.exit(query)
  connBckp.commit()

def close_all():
  detachDatabaseDbSimAcars = "DETACH DATABASE dbSimAcars"
  cB.execute(detachDatabaseDbSimAcars)
  detachDatabaseDbFix = "DETACH DATABASE dbFix"
  cB.execute(detachDatabaseDbFix)
  connBckp.commit()
  cB.close()
  connBckp.close()

def sync_tables():
  removeFromDest()
  print ("")
  copyTables()

sync_tables()
close_all()

print ("")
sys.exit("FIX aplicado correctamente")
