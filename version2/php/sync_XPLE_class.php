<?php
/**
 * Created by PhpStorm.
 * User: ernesto
 * Date: 28/07/19
 * Time: 08:42
 */


class sync_XPLE_class
{
    protected $dbHost = '';
    protected $dbName = '';
    protected $dbUser = '';
    protected $dbPass = '';
    protected $dbCreateIndex = false;
    protected $airports = array();

    protected $vam_airports = 'airports';

    protected $sqliteDB = './little_navmap_xp11.sqlite';
    protected $sqliteTable = 'airport';
    protected $lite = false;

    protected $sqliteFIX = './FIX_SIM_ACARS.DB3';
    protected $sqliteORI = './ORIGINAL_SIM_ACARS.DB3';
    protected $sqliteSACTable = 'airports';
    protected $liteFix = false;

    protected $webExists = array();
    protected $webExistsErrors = array();
    protected $webNotExists = array();
    protected $webNotExistsClosed = array();

    protected $justSpain = false;    // false for all the world, true only spain
    protected $errorDist = 20;
    protected $errorAlt = 50;

    /**
     * sync_XPLE_class constructor.
     * @param string $dbHost
     * @param string $dbName
     * @param string $dbUser
     * @param string $dbPass
     * @param bool $dbCreateIndex
     * @param array $airports
     */
    public function __construct($dbHost = '', $dbName = '', $dbUser = '', $dbPass = '', $dbCreateIndex=false, $airports=array())
    {
        set_time_limit(500);
        if (!is_array($airports))
        {
            $airports = array();
        }
        $this->airports = $airports;

        $dbCreateIndex = (bool)$dbCreateIndex;
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $this->dbCreateIndex = $dbCreateIndex;

        $bdOK = $this->dbOK();
        if ($bdOK) {
            try {
                $conn = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);
            } catch (Exception $e) {
                $this->echoo('Error: can not connect to mysql DB!');
                $this->echoo($e->getMessage());
                die($this->echoo('', false));
            }

            if (!$conn) {
                $this->echoo('Error: can not connect to mysql DB!');
                die($this->echoo('', false));
            }
            mysqli_close($conn);

            try {
                $db = new SQLite3($this->sqliteDB);
                if (!$db) {
                    $this->echoo('Error: can not connect to sqlite DB! ' . $this->sqliteDB);
                    die($this->echoo('', false));
                }
            } catch (Exception $e) {
                $this->echoo('Error: can not connect to sqlite DB! ' . $this->sqliteDB . ' or ' . $this->sqliteFIX);
                $this->echoo($e->getMessage());
                die($this->echoo('', false));
            }
            $this->lite = $db;

            if (!file_exists($this->sqliteFIX)) {
                @copy($this->sqliteORI, $this->sqliteFIX);
            }
            $dbFix = new SQLite3($this->sqliteFIX);
            if (!$dbFix) {
                $this->echoo('Error: can open sqlite ' . $this->sqliteFIX);
                die($this->echoo('', false));
            } else {
                $dropTables = array();
                $tables_query = $dbFix->query("SELECT name FROM sqlite_master WHERE type='table';");
                while ($table = $tables_query->fetchArray(SQLITE3_ASSOC)) {
                    $table_name = $table['name'];
                    if ($table_name != $this->sqliteSACTable) {
                        $dropTables[] = $table_name;
                    }
                }
                foreach ($dropTables as $table_name) {
                    $dbFix->query("DROP TABLE " . $table_name);
                }
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
        $conn = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);
        if (!$conn) {
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
        if ($this->is_cli()) {
            $nl = "\n";
        }
        return $nl;
    }

    /**
     * @param $msg
     * @param bool $doEcho
     * @return string
     */
    public function echoo($msg, $doEcho = true)
    {
        set_time_limit(500);
        $nl = $this->getNL();
        $msg = $msg . $nl;
        if ($doEcho) {
            echo $msg;
        }
        return $msg;
    }

    /**
     * @return bool
     */
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
        if (empty($this->dbHost) || empty($this->dbName) || empty($this->dbUser) || empty($this->dbPass) || empty($this->vam_airports)) {
            $this->echoo('Error: No DB connection defined!');
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function makePrimaryKey()
    {
        if (!$this->dbCreateIndex)
        {
            return false;
        }
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;
        $qry = "
            SHOW INDEXES FROM {$table} WHERE Key_name = 'PRIMARY'
        ";

        mysqli_query($con, $qry);
        $rows = mysqli_affected_rows($con);
        if ($rows == 0) {
            $qry = "
              ALTER TABLE `{$table}` ADD PRIMARY KEY (`id`) USING BTREE;
            ";
            mysqli_query($con, $qry);
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function makeIdentUnique()
    {
        if (!$this->dbCreateIndex)
        {
            return false;
        }
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;

        $qry_count = "
          SELECT `ident`, COUNT(*) AS qty FROM `{$table}` GROUP BY `ident` having qty > 1
        ";
        mysqli_query($con, $qry_count);
        $rows = mysqli_affected_rows($con);
        if ($rows <= 0) {
            $qry = "
                SHOW INDEXES FROM {$table} WHERE Key_name = 'ident'
            ";
            mysqli_query($con, $qry);
            $rows = mysqli_affected_rows($con);
            if ($rows == 0) {
                // not exists index for column `ident`
                $qry = "
                  ALTER TABLE `{$table}` ADD UNIQUE `ident` (`ident`) USING BTREE;
                ";
                mysqli_query($con, $qry);
            } else {
                // already exists index for column `ident`, ensure that is unique
                $qry = "
                  ALTER TABLE `{$table}` DROP INDEX `ident`, ADD UNIQUE `ident` (`ident`) USING BTREE;
                ";
                mysqli_query($con, $qry);
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function testLite()
    {
        if (!$this->lite) return false;
        $db = $this->lite;
        $table = $this->sqliteTable;

        $results = $db->query("SELECT ident, `name`, country FROM {$table} WHERE country='spain'");
        while ($row = $results->fetchArray()) {
            $this->echoo($row[0] . ': ' . $row[1] . ' - ' . $row[2]);
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function readAirportsFromWeb()
    {
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;

        $qr = "
          SELECT `ident` FROM `{$table}`
        ";
        $result = mysqli_query($con, $qr);
        if (!$result) return false;

        while ($row = $result->fetch_assoc()) {
            $ident = strtoupper($row['ident']);
            $this->webExists[$ident] = $ident;
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function readAirportsFromLite()
    {
        set_time_limit(500);
        $con = $this->liteFix;
        if (!$con) return false;
        $table = $this->sqliteSACTable;

        $qr = "
          SELECT `ident` FROM `{$table}`
        ";
        $result = $con->query($qr);
        if (!$result) return false;
        while ($row = $result->fetchArray()) {
            $ident = strtoupper($row['ident']);
            $this->webExists[$ident] = $ident;
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function readAirportsFromWebDBWithErrors()
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
        $result = mysqli_query($con, $qr);
        if (!$result) return false;

        while ($row = $result->fetch_assoc()) {
            $ident = strtoupper($row['ident']);
            $ident2 = substr($ident, 0, 2);
            $country = trim($row['iso_country']);
            if ($ident == 'LEGO') continue;
            if ($this->justSpain && strtolower($country) != 'es' && $ident2 != 'LE') {
                continue;
            }
            $altitude = intval($row['elevation_ft']);
            $lonx = floatval($row['longitude_deg']);
            $laty = floatval($row['latitude_deg']);

            // read from lite
            $resultLite = $db->query("SELECT * FROM {$tableLite} WHERE `ident`=\"{$ident}\" LIMIT 1");
            if ($resultLite)
            {
                while ($rowL = $resultLite->fetchArray()) {
                    $altitudeL = intval($rowL['altitude']);
                    $lonxL = floatval($rowL['lonx']);
                    $latyL = floatval($rowL['laty']);
                    $dist = $this->distance($laty, $lonx, $latyL, $lonxL);
                    $alt = $altitude - $altitudeL;
                    $dist = abs($dist);
                    $alt = abs($alt);
                    if ($dist > $this->errorDist || $alt > $this->errorAlt) {
                        $this->webExistsErrors[$ident] = $rowL;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function readAirportsFromLiteDBWithErrors()
    {
        set_time_limit(500);
        $con = $this->liteFix;
        if (!$con) return false;
        $table = $this->sqliteSACTable;

        if (!$this->lite) return false;
        $db = $this->lite;
        $tableLite = $this->sqliteTable;

        $qr = "
          SELECT * FROM `{$table}`
        ";

        $result = $con->query($qr);
        if (!$result) return false;
        while ($row = $result->fetchArray()) {
            $ident = strtoupper($row['ident']);
            $ident2 = substr($ident, 0, 2);
            $country = trim($row['iso_country']);
            if ($ident == 'LEGO') continue;
            if ($this->justSpain && strtolower($country) != 'es' && $ident2 != 'LE') {
                continue;
            }
            $altitude = intval($row['elevation_ft']);
            $lonx = floatval($row['longitude_deg']);
            $laty = floatval($row['latitude_deg']);

            // read from lite
            $resultLite = $db->query("SELECT * FROM {$tableLite} WHERE `ident`=\"{$ident}\" LIMIT 1");
            if ($resultLite)
            {
                while ($rowL = $resultLite->fetchArray()) {
                    $altitudeL = intval($rowL['altitude']);
                    $lonxL = floatval($rowL['lonx']);
                    $latyL = floatval($rowL['laty']);
                    $dist = $this->distance($laty, $lonx, $latyL, $lonxL);
                    $alt = $altitude - $altitudeL;
                    $dist = abs($dist);
                    $alt = abs($alt);
                    if ($dist > $this->errorDist || $alt > $this->errorAlt) {
                        $this->webExistsErrors[$ident] = $rowL;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @brief: this is the same for web and lite: just compare arrays
     * @return bool
     */
    protected function readAirportsDBNotExists()
    {
        set_time_limit(500);
        if (!$this->lite) return false;
        $db = $this->lite;
        $tableLite = $this->sqliteTable;

        $resultLite = $db->query("SELECT * FROM {$tableLite}");
        if ($resultLite) {
            while ($rowL = $resultLite->fetchArray()) {
                $country = trim($rowL['country']);
                $ident = strtoupper($rowL['ident']);
                $ident2 = substr($ident, 0, 2);
                if ($ident == 'LEGO') continue;
                if ($this->justSpain && strtolower($country) != 'spain' && $ident2 != 'LE') {
                    continue;
                }
                $is_closed = $rowL['is_closed'];
                if (!array_key_exists($ident, $this->webExists)) {
                    if (intval($is_closed) == 1) {
                        $this->webNotExistsClosed[$ident] = $rowL;
                    } else {
                        $this->webNotExists[$ident] = $rowL;
                    }
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
    protected function distance($lat1, $lon1, $lat2, $lon2, $unit = 'M')
    {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        } else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);
            if ($unit == "K") {
                return abs($miles * 1.609344);
            } else if ($unit == "N") {
                return abs($miles * 0.8684);
            } else {
                return abs($miles);
            }
        }
    }

    protected function doMysqlBackup($con, $table_bckp, $table)
    {
        // backup airports table
        $qry1 = "
          CREATE TABLE IF NOT EXISTS {$table_bckp} LIKE {$table};
        ";
        mysqli_query($con, $qry1);

        $do_backup = true;
        $qry2 = "
            SELECT COUNT(*) AS qty FROM {$table_bckp};
        ";
        $result = mysqli_query($con, $qry2);
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row['qty']) {
                $do_backup = false;
            }
        }

        if ($do_backup) {
            $qry3 = "
                INSERT {$table_bckp} SELECT * FROM {$table};
            ";
            mysqli_query($con, $qry3);
            $test = "
                SELECT count(*) as qty FROM {$table}
            ";
            $result = mysqli_query($con, $test);
            if (!$result) return false;
            $test_bckp = "
                SELECT count(*) as qty FROM {$table_bckp}
            ";
            $result_bckp = mysqli_query($con, $test_bckp);
            if (!$result_bckp) {
                return false;
            }
            $row = $result->fetch_assoc();
            $row_bckp = $result_bckp->fetch_assoc();
            if ($row['qty'] != $row_bckp['qty']) {
                return false;
            }
        }
    }

    /**
     * @return bool
     */
    protected function fixDBVamWeb()
    {
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;
        $table_bckp = $this->vam_airports . '_bckp';

        if (count($this->webExistsErrors) + count($this->webNotExists) + count($this->webNotExistsClosed) == 0) {
            return false;
        }
        $this->doMysqlBackup($con, $table_bckp, $table);

        $rc1 = $this->fixVAMMissing($table, $con, false);
        $rc2 = $this->fixVAMError($table, $con, false);

        return $rc1 && $rc2;
    }

    /**
     * @return bool
     */
    protected function fixDBVamLite()
    {
        set_time_limit(500);
        $con = $this->liteFix;
        if (!$con) return false;
        $table = $this->sqliteSACTable;

        if (count($this->webExistsErrors) + count($this->webNotExists) + count($this->webNotExistsClosed) == 0) {
            return false;
        }

        $rc1 = $this->fixVAMMissing($table, $con, true);
        $rc2 = $this->fixVAMError($table, $con, true);

        return $rc1 && $rc2;
    }

    /**
     * @param $table
     * @param $con
     * @param bool $lite
     * @return bool|int
     */
    protected function getTheMax($table, $con, $lite=false)
    {
        set_time_limit(500);
        $theMaxId = 0;
        $qry = "
          SELECT MAX(id) themax FROM {$table}
        ";
        if ($lite) {
            /** @var SQLite3 $con */
            $resultLite = $con->query($qry);
            if ($resultLite)
            {
                while ($rowL = $resultLite->fetchArray()) {
                    $theMaxId = $rowL['themax'];
                    break;
                }
            }
        }
        else
        {
            $result = mysqli_query($con, $qry);
            if (!$result) return false;
            while($row = $result->fetch_assoc())
            {
                $theMaxId = $row['themax'];
                break;
            }
        }
        return $theMaxId;
    }

    /**
     * @param $table
     * @param $con
     * @param bool $lite
     * @return bool
     */
    protected function fixVAMMissing($table, $con, $lite=false)
    {
        set_time_limit(500);
        if (!$con) return false;

        if (count($this->webNotExists) + count($this->webNotExistsClosed) == 0) {
            return false;
        }

        $theMaxId = $this->getTheMax($table, $con, $lite);
        $ignore = '';
        if (!$lite) {
            $ignore = 'IGNORE';
        }

        $nonExists = array_merge($this->webNotExists, $this->webNotExistsClosed);
        foreach ($nonExists as $ident => $data) {
            $theMaxId++;
            $type = 'small_airport';
            if ($data['rating'] == 1) $type = 'small_airport';
            if ($data['rating'] == 2) $type = 'small_airport';
            if ($data['rating'] == 3) $type = 'small_airport';
            if ($data['rating'] == 4) $type = 'medium_airport';
            if ($data['rating'] == 5) $type = 'large_airport';
            $qry = "
                INSERT {$ignore} INTO `{$table}` 
                (`id`, `ident`, 
                 `type`, `name`, 
                 `latitude_deg`, `longitude_deg`, `elevation_ft`,
                 `continent`, `iso_country`, `iso_region`,
                 `municipality`, `scheduled_service`, `gps_code`, 
                 `iata_code`, `local_code`, `home_link`,
                 `wikipedia_link`, `keywords`) 
                 VALUES ({$theMaxId}, \"{$data['ident']}\", 
                 \"{$type}\", \"{$data['name']}\", 
                 \"{$data['laty']}\", \"{$data['lonx']}\", \"{$data['altitude']}\",
                 \"\", \"{$data['country']}\", \"{$data['region']}\",
                 \"{$data['city']}\", \"\", \"{$data['ident']}\",
                 \"\", \"\", \"\", 
                 \"\", \"\");
            ";
            if ($lite)
            {
                /** @var SQLite3 $con */
                $res = $con->query($qry);
            }
            else
            {
                /** @var mysqli $con */
                $res = mysqli_query($con, $qry);
            }
            if (!$res) {
                $theMaxId--;
            }
        }
        return true;
    }

    /**
     * @param $table
     * @param $con
     * @param bool $lite
     * @return bool
     */
    protected function fixVAMError($table, $con, $lite=false)
    {
        set_time_limit(500);
        if (!$con) return false;

        if (count($this->webExistsErrors) == 0) {
            return false;
        }

        $withErrors = $this->webExistsErrors;
        foreach ($withErrors as $ident => $data) {
            $ident6 = substr($ident, 0, 6);
            $qry = "
              UPDATE `{$table}` SET 
                `name` = \"{$data['name']}\",
                `latitude_deg` = {$data['laty']},
                `longitude_deg` = {$data['lonx']},
                `gps_code` = \"{$ident6}\",
                `elevation_ft` = {$data['altitude']}
                WHERE `airports`.`ident` = \"{$data['ident']}\";
            ";
            if ($lite)
            {
                /** @var SQLite3 $con */
                $con->query($qry);
            }
            else
            {
                /** @var mysqli $con */
                mysqli_query($con, $qry);
            }
        }
        return true;
    }

    /**
     * @param $airports
     * @return bool
     */
    protected function syncAiports($airports) {
        if (!is_array($airports) || empty($airports))
        {
            return false;
        }

        // fixDB VAM web
        set_time_limit(500);
        $con = $this->getCon();
        if (!$con) return false;
        $table = $this->vam_airports;
        $table_bckp = $this->vam_airports . '_bckp';

        $this->doMysqlBackup($con, $table_bckp, $table);

        $rc1 = $this->syncAiportsWork($airports, $con, $table, false);

        // fixDB sqLite FIX
        set_time_limit(500);
        $con = $this->liteFix;
        if (!$con) return false;
        $table = $this->sqliteSACTable;

        $rc2 = $this->syncAiportsWork($airports, $con, $table, true);

        return $rc1 && $rc2;
    }

    /**
     * @param $airports
     * @param $con
     * @param $table
     * @param bool $lite
     * @return bool
     */
    protected function syncAiportsWork($airports, $con, $table, $lite=false) {
        if (!is_array($airports) || empty($airports))
        {
            return false;
        }
        $theMaxId = $this->getTheMax($table, $con, $lite);

        set_time_limit(500);
        foreach($airports as $ident => $data) {
            $ignore = '';
            if (!$lite) {
                $ignore = 'IGNORE';
            }

            // search if $ident exists
            $ident_exists = false;
            $qry_count = "
              SELECT COUNT(*) AS qty FROM {$table} WHERE `ident` = \"{$ident}\";
            ";
            if ($lite) {
                /** @var SQLite3 $con */
                $result = $con->query($qry_count);
            } else {
                /** @var mysqli $con */
                $result = mysqli_query($con, $qry_count);
            }
            if ($result) {
                $row = array('qty' => 0);
                if ($lite) {
                    while ($row = $result->fetchArray()) {
                        break;
                    }
                } else {
                    while ($row = $result->fetch_assoc()) {
                        break;
                    }
                }
                if ($row['qty']) {
                    $ident_exists = true;
                }
            }

            if ($ident_exists) {
                // update
                $qry = "
                  UPDATE `{$table}` SET 
                    `name` = \"{$data['name']}\",
                    `type` = \"{$data['type']}\",
                    `latitude_deg` = {$data['latitude_deg']},
                    `longitude_deg` = {$data['longitude_deg']},
                    `elevation_ft` = {$data['elevation_ft']},
                    `continent` = \"{$data['continent']}\", 
                    `iso_country` = \"{$data['iso_country']}\", 
                    `iso_region` = \"{$data['iso_region']}\",
                    `municipality` = \"{$data['municipality']}\",
                    `scheduled_service` = \"{$data['scheduled_service']}\",
                    `gps_code` = \"{$data['gps_code']}\",
                    `iata_code` = \"{$data['iata_code']}\",
                    `local_code` = \"{$data['local_code']}\",
                    `home_link` = \"{$data['home_link']}\",
                    `wikipedia_link` = \"{$data['wikipedia_link']}\",
                    `keywords` = \"{$data['keywords']}\"
                    WHERE `airports`.`ident` = \"{$ident}\";
                ";
                if ($lite) {
                    /** @var SQLite3 $con */
                    $con->query($qry);
                } else {
                    /** @var mysqli $con */
                    $res = mysqli_query($con, $qry);
                }

            } else {
                // insert
                $theMaxId++;
                $qry = "
                    INSERT {$ignore} INTO `{$table}` 
                    (`id`, `ident`, 
                     `type`, `name`, 
                     `latitude_deg`, `longitude_deg`, `elevation_ft`,
                     `continent`, `iso_country`, `iso_region`,
                     `municipality`, `scheduled_service`, `gps_code`, 
                     `iata_code`, `local_code`, `home_link`,
                     `wikipedia_link`, `keywords`) 
                     VALUES ({$theMaxId}, \"{$ident}\", 
                     \"{$data['type']}\", \"{$data['name']}\", 
                     \"{$data['latitude_deg']}\", \"{$data['longitude_deg']}\", \"{$data['elevation_ft']}\",
                     \"{$data['continent']}\", \"{$data['iso_country']}\", \"{$data['iso_region']}\",
                     \"{$data['municipality']}\", \"{$data['scheduled_service']}\", \"{$data['gps_code']}\",
                     \"{$data['iata_code']}\", \"{$data['local_code']}\", \"{$data['home_link']}\", 
                     \"{$data['wikipedia_link']}\", \"{$data['keywords']}\");
                ";
                if ($lite) {
                    /** @var SQLite3 $con */
                    $res = $con->query($qry);
                } else {
                    /** @var mysqli $con */
                    $res = mysqli_query($con, $qry);
                }
                if (!$res) {
                    $theMaxId--;
                }
            }
        }
        return true;
    }

    /**
     * @param bool $justSpain
     */
    public function setJustSpain($justSpain=false)
    {
        $justSpain = (bool) $justSpain;
        $this->justSpain = $justSpain;
    }

    /**
     * @return bool
     */
    public function doSync()
    {
        set_time_limit(500);

        $dbOK = $this->dbOK();
        if (!$dbOK) return false;

        $this->makePrimaryKey();
        $this->makeIdentUnique();

        if (!empty($this->airports))
        {
            $this->echoo('Set VAM DB and sqLite by fixed defined airports:');
            echo('Fixing... ');
            $fixed = $this->syncAiports($this->airports);
            if ($fixed) $this->echoo('Done!');
            else  $this->echoo('Not fixed needed ');
            $this->echoo('');
            $this->echoo('');
        }

        // $this->testLite();

        $this->readAirportsFromWeb();
        $this->readAirportsFromWebDBWithErrors();
        $this->readAirportsDBNotExists();

        $this->echoo('VAM DB Web:');
        $this->echoo('Airports with errors: ' . count($this->webExistsErrors) . ' of ' . count($this->webExists));
        $this->echoo('Missing Airports: ' . count($this->webNotExists) . ' of ' . count($this->webExists));
        $this->echoo('Missing Airports (closed): ' . count($this->webNotExistsClosed) . ' of ' . count($this->webExists));
        echo('Fixing... ');
        $fixed = $this->fixDBVamWeb();
        if ($fixed) $this->echoo('Done!');
            else  $this->echoo('Not fixed needed ');

        $this->echoo('');
        $this->echoo('');

        $this->webExists = array();
        $this->webExistsErrors = array();
        $this->webNotExists = array();
        $this->webNotExistsClosed = array();

        $this->readAirportsFromLite();
        $this->readAirportsFromLiteDBWithErrors();
        $this->readAirportsDBNotExists();

        $this->echoo('FIX sqlite:');
        $this->echoo('Airports with errors: ' . count($this->webExistsErrors) . ' of ' . count($this->webExists));
        $this->echoo('Missing Airports: ' . count($this->webNotExists) . ' of ' . count($this->webExists));
        $this->echoo('Missing Airports (closed): ' . count($this->webNotExistsClosed) . ' of ' . count($this->webExists));
        echo('Fixing... ');
        $fixed = $this->fixDBVamLite();
        if ($fixed) $this->echoo('Done!');
        else  $this->echoo('Not fixed needed ');

        $this->echoo('');
        $this->echoo('');

        $this->echoo('Sync Finished OK!');

        return true;
    }
}
