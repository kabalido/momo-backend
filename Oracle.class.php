<?php

/**************************************************************************************
Author: Everaldo MENOUT
Date  : 2018-12-20
		Utility classes to manage bussiness rules, database connectivity and logging
All rigths are reserved to the author ( Everaldo MENOUT)
NO ONE IS ALLOWED TO CHANGE THIS SOURCE CODE WITHOUT PERMISSION OF THE AUTHOR
 ***************************************************************************************/
//include('IDatabase.class.php');

class Oracle implements IDatabase
{

    public $conn = null;
    //public $logger = null;
    private $connected = false;
    private static $instance = null;
    private static $autoCommit = OCI_COMMIT_ON_SUCCESS;

    // <============= Database parameters ==============>
    #const TNSNAME = 'ODA1_REGSUBS';
    const TNSNAME = 'TABS';
    const USER = 'tabs';
    const PASS =  'tabsprod';
    // <================================================>

    private function __construct()
    {
        //$this->logger = new Logging ( 'DataAccess_error', false );
        $this->conn = @oci_connect(self::USER, self::PASS, self::TNSNAME);
        if (!$this->conn) {
            $this->connected = false;
            $e = oci_error();
            throw new Exception($e['message']);
            //echo "+[ERR]: Unable to connect to the DB server.\nError: " . $e['message'];
            //echo "\n";
        } else {
            $this->connected = true;
        }
    }

    public static function getInstance()
    {
        if (self::$instance == null)
            self::$instance = new Oracle();
        return self::$instance;
    }

    public function isConnected()
    {
        return $this->connected;
    }
    
    public function getDbType()
    {
        return DatabaseType::ORACLE;
    }

    public function executeNonQuery($query)
    {
        if (!$this->isConnected()) {
            echo "+[ERR]: Can't execute current query because you're not connected to the Oracle databse.\n";
            return false;
        }
        $stid = @oci_parse($this->conn, $query);
        if (!$stid) {
            $e = oci_error($stid);
            echo  "+[ERR]: Your query $query contains error:" . $e['message'] . "\n";
            return false;
        }
        $status = @oci_execute($stid, self::$autoCommit);
        //echo "AutoCommit: ".self::$autoCommit;
        if (!$status) {
            $e = oci_error($stid);
            echo  "+[ERR] Can't execute: $query | Error: " . $e['message'] . "\n";
            return false;
        }
        oci_free_statement($stid);
        return $status;
    }

    public function executeQuery($query)
    {
        if (!$this->isConnected()) {
            echo "+[ERR]: Can't execute current query because you're not connected to the Oracle databse.\n";
            return false;
        }
        $rows = [];
        $stid = @oci_parse($this->conn, $query);
        if (!$stid) {
            $e = oci_error($stid);
            echo  "+[ERR]: Your query $query contains error:" . $e['message'] . "\n";
            return false;
        }
        $status = @oci_execute($stid);
        if (!$status) {
            $e = oci_error($stid);
            echo  "+[ERR] Can't execute: $query | Error: " . $e['message'] . "\n";
            return false;
        }
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $rows[] = $row;
        }
        oci_free_statement($stid);
        return $rows;
    }

    public function setAutoCommit($val)
    {
        if ($val)
            self::$autoCommit =  OCI_COMMIT_ON_SUCCESS;
        else
            self::$autoCommit = OCI_NO_AUTO_COMMIT;
    }

    public function commit()
    {
        oci_commit($this->conn);
    }
    public function rollback()
    {
        oci_rollback($this->conn);
    }

    public function close()
    {
        if ($this->isConnected()) {
            oci_close($this->conn);
            $this->connected = false;
        } else {
            echo "+[WARN]: You are trying to close a connection already closed previously, or not opened.\n";
        }
    }
}
