<?php
/**
 * Created by PhpStorm.
 * User: ernesto
 * Date: 28/07/19
 * Time: 08:42
 */


class sync_XPLE_dbs
{
    protected $dbHost = '';
    protected $dbName = '';
    protected $dbUser = '';
    protected $dbPass = '';

    protected $vam_airports = 'airports';

    protected $sqliteDB = './little_navmap_xp11.sqlite';
    protected $sqliteTable = 'airport';
    protected $lite = false;

    protected $sqliteFIX = './FIX_SIM_ACARS.sqlite';
    protected $sqliteORI = './ORIGINAL_SIM_ACARS.DB3';
    protected $sqliteSACTable = 'airports';

    protected $webExists = array();
    protected $webExistsErrors = array();
    protected $webNotExists = array();
    protected $webNotExistsClosed = array();

    protected $justSpain = false;    // todo false for all the world, true only spain
    protected $errorDist = 20;
    protected $errorAlt = 50;

    public function __construct ($dbHost='',$dbName='',$dbUser='',$dbPass='')
    {
        set_time_limit(500);
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $bdOK = $this->dbOK();
        if ($bdOK)
        {
            try {
                $conn = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);
            }
            catch (Exception $e)
            {
                $this->echoo('Error: can not connect to mysql DB!');
                $this->echoo($e->getMessage());
                die($this->echoo('', false));
            }

            if (!$conn)
            {
                $this->echoo('Error: can not connect to mysql DB!');
                die($this->echoo('', false));
            }
            mysqli_close($conn);

            try {
                $db = new SQLite3($this->sqliteDB);
                if (!$db)
                {
                    $this->echoo('Error: can not connect to sqlite DB! '.$this->sqliteDB);
                    die($this->echoo('', false));
                }
            }
            catch (Exception $e)
            {
                $this->echoo('Error: can not connect to sqlite DB! '.$this->sqliteDB . ' or '.$this->sqliteFIX);
                $this->echoo($e->getMessage());
                die($this->echoo('', false));
            }
            $this->lite = $db;

            if (!file_exists($this->sqliteFIX)) {
                @copy($this->sqliteORI, $this->sqliteFIX);
            }
            $dbFix = new SQLite3($this->sqliteFIX);
            if (!$dbFix)
            {
                $this->echoo('Error: can open sqlite '.$this->sqliteFIX);
                die($this->echoo('', false));
            }
            else
            {
                $dropTables = array();
                $tablesquery = $dbFix->query("SELECT name FROM sqlite_master WHERE type='table';");
                while ($table = $tablesquery->fetchArray(SQLITE3_ASSOC)) {
                    $table_name = $table['name'];
                    if ($table_name != $this->sqliteSACTable) {
                        $dropTables[] = $table_name;
                    }
                }
                foreach($dropTables as$table_name )
                {
                    $rdrop = $dbFix->query("DROP TABLE ".$table_name);
                }
                $dbFix->query("COMMIT");
            }
            $this->liteFix = $dbFix;
        }
    }

    /**
     * @return mysqli
     */
    protected function getCon()
    {
        set_time_limit(500);
        $conn = mysqli_connect($this->dbHost,$this->dbUser,$this->dbPass,$this->dbName);
        if (!$conn)
        {
            $this->echoo('Error: can not connect to DB!');
            die($this->echoo('', false));
        }
        return $conn;
    }

    /**
     * @return bool|SQLite3
     */
    protected function getLite()
    {
        set_time_limit(500);
        return $this->lite;
    }

    /**
     * @return string
     */
    protected function getNL()
    {
        set_time_limit(500);
        $nl = '<br>';
        if ($this->is_cli())
        {
            $nl = "\n";
        }
        return $nl;
    }

    /**
     * @param $msg
     * @param bool $doEcho
     * @return string
     */
    protected function echoo($msg, $doEcho=true)
    {
        set_time_limit(500);
        $nl = $this->getNL();
        $msg = $msg . $nl;
        if ($doEcho)
        {
            echo $msg;
        }
        return $msg;
    }

    protected function is_cli()
    {
        set_time_limit(500);
        if (empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function dbOK()
    {
        set_time_limit(500);
        if (empty($this->dbHost) || empty($this->dbName) || empty($this->dbUser) || empty($this->dbPass) || empty($this->vam_airports))
        {
            $this->echoo('Error: No DB connection defined!');
            die($this->echoo('', false));
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function makePrimaryKey()
    {
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;
        $qry = "
            SHOW INDEXES FROM {$table} WHERE Key_name = 'PRIMARY'
        ";

        mysqli_query($con,$qry);
        $rows = mysqli_affected_rows($con);
        if ($rows == 0)
        {
            $qry = "
              ALTER TABLE `{$table}` ADD PRIMARY KEY (`id`) USING BTREE;
            ";
            mysqli_query($con,$qry);
        }
        return true;
    }

    protected function makeIdentUnique()
    {
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;

        $qry_count = "
          SELECT `ident`, COUNT(*) AS qty FROM `{$table}` GROUP BY `ident` having qty > 1
        ";
        mysqli_query($con,$qry_count);
        $rows = mysqli_affected_rows($con);
        if ($rows <= 0)
        {
            $qry = "
                SHOW INDEXES FROM {$table} WHERE Key_name = 'ident'
            ";
            mysqli_query($con,$qry);
            $rows = mysqli_affected_rows($con);
            if ($rows == 0)
            {
                // not exists index for column `ident`
                $qry = "
                  ALTER TABLE `{$table}` ADD UNIQUE `ident` (`ident`) USING BTREE;
                ";
                mysqli_query($con,$qry);
            }
            else
            {
                // already exists index for column `ident`, ensure that is unique
                $qry = "
                  ALTER TABLE `{$table}` DROP INDEX `ident`, ADD UNIQUE `ident` (`ident`) USING BTREE;
                ";
                mysqli_query($con,$qry);
            }
        }
    }

    protected function testLite()
    {
        if (!$this->lite) return false;
        $db = $this->lite;
        $table = $this->sqliteTable;

        $results = $db->query("SELECT ident, `name`, country FROM {$table} WHERE country='spain'");
        while ($row = $results->fetchArray()) {
            $this->echoo($row[0]. ': ' .$row[1]. ' - ' .$row[2]);
        }
    }

    /**
     * @param bool $useFix
     * @return bool
     */
    protected function readAirportsFromWeb($useFix=false)
    {
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;

        $qr = "
          SELECT `ident` FROM `{$table}`
        ";
        $result = mysqli_query($con,$qr);
        if (!$result) return false;

        while ($row = $result->fetch_assoc())
        {
            $ident = strtoupper($row['ident']);
            $this->webExists[$ident] = $ident;
        }
    }

    /**
     * @param bool $useFix
     * @return bool
     */
    protected function readAirportsFromWebDBWithErrors($useFix=false)
    {
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;

        if (!$this->lite) return false;
        $db = $this->lite;
        $tableLite = $this->sqliteTable;

        $qr = "
          SELECT * FROM `{$table}`
        ";
        $result = mysqli_query($con,$qr);
        if (!$result) return false;

        while ($row = $result->fetch_assoc())
        {
            $ident = strtoupper($row['ident']);
            $name =  $row['name'];
            $country = trim($row['iso_country']);
            if ($this->justSpain && strtolower($country)!='es')
            {
                continue;
            }
            $altitude = intval($row['elevation_ft']);
            $lonx = floatval($row['longitude_deg']);
            $laty = floatval($row['latitude_deg']);

            // $this->echoo($row['ident'].': '.$row['name']);
            // read from lite
            $resultLite = $db->query("SELECT * FROM {$tableLite} WHERE `ident`=\"{$ident}\" LIMIT 1");
            if ($resultLite)
            while ($rowL = $resultLite->fetchArray()) {
                $altitudeL = intval($rowL['altitude']);
                $lonxL = floatval($rowL['lonx']);
                $latyL = floatval($rowL['laty']);
                $dist = $this->distance($laty,$lonx,$latyL,$lonxL);
                $alt = $altitude - $altitudeL;
                $dist = abs($dist);
                $alt = abs($alt);
                if ($dist > $this->errorDist || $alt > $this->errorAlt) {
                    $this->webExistsErrors[$ident] = $rowL;
                    // $this->echoo($ident .': '.$name);
                }
            }
        }
        return true;
    }

    /**
     * @param bool $useFix
     * @return bool
     */
    protected function readAirportsFromWebDBNotExists($useFix=false)
    {
        set_time_limit(500);
        if (!$this->lite) return false;
        $db = $this->lite;
        $tableLite = $this->sqliteTable;

        $resultLite = $db->query("SELECT * FROM {$tableLite}");
        if ($resultLite)
        {
            while ($rowL = $resultLite->fetchArray()) {
                $country = trim($rowL['country']);
                if ($this->justSpain && strtolower($country)!='spain')
                {
                    continue;
                }
                $ident = strtoupper($rowL['ident']);
                $is_closed = $rowL['is_closed'];
                if (!array_key_exists($ident, $this->webExists)) {
                    if (intval($is_closed)==1)
                    {
                        $this->webNotExistsClosed[$ident] = $rowL;
                    }
                    else
                    {
                        $this->webNotExists[$ident] = $rowL;
                    }
                    // $this->echoo($ident .': '.$rowL[3]);
                }
            }
        }
        return true;
    }

    /**
     * @param $lat1
     * @param $lon1
     * @param $lat2
     * @param $lon2
     * @param string $unit
     * @return float|int
     */
    protected function distance($lat1, $lon1, $lat2, $lon2, $unit='M') {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        }
        else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);
            if ($unit == "K") {
                return ($miles * 1.609344);
            } else if ($unit == "N") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }

    /**
     * @param bool $useFix
     * @return bool
     */
    protected function fixDBVamWeb($useFix=false)
    {
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;
        $table_bckp = $this->vam_airports . '_bckp';

        if (count($this->webExistsErrors)+count($this->webNotExists)+count($this->webNotExistsClosed)==0)
        {
            return false;
        }

        if (!$useFix)
        {
            // backup airports table
            $qry1 = "
          CREATE TABLE IF NOT EXISTS {$table_bckp} LIKE {$table};
        ";
            mysqli_query($con,$qry1);

            $do_backup = true;
            $qry2 = "
            SELECT COUNT(*) AS qty FROM {$table_bckp};
        ";
            $result = mysqli_query($con,$qry2);
            if ($result)
            {
                $row = $result->fetch_assoc();
                if ($row['qty'])
                {
                    $do_backup = false;
                }
            }

            if ($do_backup)
            {
                $qry3 = "
                INSERT {$table_bckp} SELECT * FROM {$table};
            ";
                mysqli_query($con,$qry3);
                $test = "
                SELECT count(*) as qty FROM {$table}
            ";
                $result = mysqli_query($con,$test);
                if (!$result) return false;
                $test_bckp = "
                SELECT count(*) as qty FROM {$table_bckp}
            ";
                $result_bckp = mysqli_query($con,$test_bckp);
                if (!$result_bckp)
                {
                    return false;
                }
                $row = $result->fetch_assoc();
                $row_bckp = $result_bckp->fetch_assoc();
                if ($row['qty']!=$row_bckp['qty'])
                {
                    return false;
                }
            }
        }

        $this->VAMMissing($table, $useFix);
        $this->VAMError($table, $useFix);
    }

    /**
     * @param $table
     * @param $useFix
     * @return bool
     */
    protected function VAMMissing($table, $useFix)
    {
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;

        if (count($this->webNotExists)+count($this->webNotExistsClosed)==0)
        {
            return false;
        }

        $qry = "
          SELECT MAX(id) themax FROM {$table}
        ";
        $result = mysqli_query($con, $qry);
        if (!$result) return false;
        $row = $result->fetch_assoc();
        $themax = $row['themax'];

        $nonExists = array_merge($this->webNotExists,$this->webNotExistsClosed);
        foreach($nonExists as $ident => $data)
        {
            $themax++;
            if ($data['rating']==1) $type='small_airport';
            if ($data['rating']==2) $type='small_airport';
            if ($data['rating']==3) $type='small_airport';
            if ($data['rating']==4) $type='medium_airport';
            if ($data['rating']==5) $type='large_airport';
            $qry = "
                INSERT IGNORE INTO `{$table}` 
                (`id`, `ident`, 
                 `type`, `name`, 
                 `latitude_deg`, `longitude_deg`, `elevation_ft`,
                 `continent`, `iso_country`, `iso_region`,
                 `municipality`, `scheduled_service`, `gps_code`, 
                 `iata_code`, `local_code`, `home_link`,
                  
                 `wikipedia_link`, `keywords`) 
                 VALUES ({$themax}, \"{$data['ident']}\", 
                 \"{$type}\", \"{$data['name']}\", 
                 \"{$data['laty']}\", \"{$data['lonx']}\", \"{$data['altitude']}\",
                 \"\", \"{$data['country']}\", \"{$data['region']}\",
                 \"{$data['city']}\", \"\", \"{$data['ident']}\",
                 \"\", \"\", \"\", 
                 \"\", \"\");
            ";
            $res = mysqli_query($con, $qry);
            if (!$res)
            {
                $themax--;
            }
        }
        return true;
    }

    /**
     * @param $table
     * @param $useFix
     * @return bool
     */
    protected function VAMError($table, $useFix)
    {
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;

        if (count($this->webExistsErrors)==0)
        {
            return false;
        }

        $withErrors = $this->webExistsErrors;
        foreach($withErrors as $ident => $data)
        {
            $ident6 = substr($ident,0,6);
            $qry = "
              UPDATE `{$table}` SET 
                `name` = \"{$data['name']}\",
                `latitude_deg` = {$data['laty']},
                `longitude_deg` = {$data['lonx']},
                `gps_code` = \"{$ident6}\",
                `elevation_ft` = {$data['altitude']}
                WHERE `airports`.`ident` = \"{$data['ident']}\";
            ";
            $res = mysqli_query($con, $qry);
//            if (!$res)
//            {
//                $this->echoo(mysqli_error($con));
//                $this->echoo($qry);
//            }
        }
        return true;
    }

    public function doSync()
    {
        $this->dbOK();
        $this->makePrimaryKey();
        $this->makeIdentUnique();

        // $this->testLite();

        $this->readAirportsFromWeb();
        $this->readAirportsFromWebDBWithErrors();
        $this->readAirportsFromWebDBNotExists();

        $this->echoo('VAM DB Web');
        $this->echoo('Airports with errors: '.count($this->webExistsErrors).' of '.count($this->webExists));
        $this->echoo('Missing Airports: '.count($this->webNotExists).' of '.count($this->webExists));
        $this->echoo('Missing Airports (closed): '.count($this->webNotExistsClosed).' of '.count($this->webExists));
        $this->echoo('Fixing... ');
        $this->fixDBVamWeb();

        $this->echoo('');
        $this->echoo('');

        $this->readAirportsFromWeb(true);   // todo
        $this->readAirportsFromWebDBWithErrors(true);   // todo
        $this->readAirportsFromWebDBNotExists(true);    // todo

        $this->echoo('FIX sqlite');
        $this->echoo('Airports with errors: '.count($this->webExistsErrors).' of '.count($this->webExists));
        $this->echoo('Missing Airports: '.count($this->webNotExists).' of '.count($this->webExists));
        $this->echoo('Missing Airports (closed): '.count($this->webNotExistsClosed).' of '.count($this->webExists));
        $this->echoo('Fixing... ');
        $this->fixDBVamWeb(true);   // todo

        $this->echoo('');
        $this->echoo('');

        $this->echoo('Sync Finished OK!');
        return 0;

    }
}
