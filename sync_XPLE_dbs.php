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

    protected $sqliteDB = 'little_navmap_xp11.sqlite';
    protected $sqliteTable = 'airport';
    protected $lite = false;

    public function __construct ($dbHost='',$dbName='',$dbUser='',$dbPass='')
    {
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $bdOK = $this->dbOK();
        if ($bdOK)
        {
            $conn = mysqli_connect($this->dbHost,$this->dbUser,$this->dbPass,$this->dbName);
            if (!$conn)
            {
                $this->echoo('Error: can not connect to DB!');
                die($this->echoo('', false));
            }
            mysqli_close($conn);

            $db = new SQLite3($this->sqliteDB);
            if (!$db)
            {
                $this->echoo('Error: can not connect to sqlite DB!');
                die($this->echoo('', false));
            }
            $this->lite = $db;
        }
    }

    /**
     * @return mysqli
     */
    protected function getCon()
    {
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
        return $this->lite;
    }

    /**
     * @return string
     */
    protected function getNL()
    {
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

    // todo delete this function after test
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

    public function doSync()
    {
        $this->dbOK();
        $this->makePrimaryKey();
        $this->makeIdentUnique();

        $this->testLite();      // todo delete this line after test

        $this->echoo('All OK');
        die();

    }

}
