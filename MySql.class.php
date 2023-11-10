<?php

//include('IDatabase.class.php');

/**************************************************************************************
Author: Everaldo MENOUT
Date  : 2023-10-05
		Utility classes to manage bussiness rules, database connectivity and logging
All rigths are reserved to the author ( Everaldo MENOUT)
NO ONE IS ALLOWED TO CHANGE THIS SOURCE CODE WITHOUT PERMISSION OF THE AUTHOR
 ***************************************************************************************/
class MySql implements IDatabase
{

	public $mysqli = null;
	private $connected = FALSE;
	private static $instance = null;

	// <============= Database parameters ==============>
	const HOST = 'localhost';
	const USER = 'root';
	const PASS =  'eves1981';
	const DB = 'madapi';
	// <================================================>

	private function __construct()
	{
		$this->mysqli = new mysqli(self::HOST, self::USER, self::PASS, self::DB);
		if ($this->mysqli->connect_errno != 0) {
			$this->connected = FALSE;
			echo '+[ERR]: Unable to connect to the DB server: ' . $this->mysqli->connect_error;
			throw new Exception('Exeption: Cannot connect to DB server');
		} else {
			$this->connected = TRUE;
			$this->mysqli->autocommit(TRUE);
		}
	}

	public static function getInstance()
	{
		if (self::$instance == null)
			self::$instance = new Mysql();
		return self::$instance;
	}

	public function isConnected()
    {
        return $this->connected;
    }
    
    public function getDbType()
    {
        return DatabaseType::MYSQL;
    }

	public function executeNonQuery($query)
	{
		if (!$this->isConnected())
			throw new Exception("+[ERR]: Your are not connected to the MySQL Database server " . self::HOST);
		$this->mysqli->query($query);
		if ($this->mysqli->errno != 0) {
			echo "+[ERR]: Your query $query contains error:" . $this->mysqli->error;
			return false;
		}
		return $this->mysqli->affected_rows > 0;
	}

	public function executeQuery($query)
	{
		if (! $this->isConnected())
			throw new Exception("+[ERR]: Your are not connected to Database server ".self::HOST);

		$rows = array();
		$result = $this->mysqli->query($query);
		if ($this->mysqli->errno != 0) {
			echo  "+[ERR]: Your query $query contains error: " . $this->mysqli->error;
			return false;
		}
		if (!$result) {
			echo "+[ERR] executeQuery: query: $query";
			return false;
		}
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$rows[] = $row;
		}
		if ($result)
			$result->close();
		return $rows;
	}

	public function setAutoCommit($valBool)
	{
		$this->mysqli->autocommit($valBool);
	}

	public function commit()
	{
		$this->mysqli->commit();
	}

	public function rollback()
	{
		$this->mysqli->rollback();
	}

	public function close()
	{
		if ($this->isConnected()) {
			$this->mysqli->close();
			$this->connected = FALSE;
			//$this->logger->close ();
		} else
			echo 'WARNING: You are trying to close a connection already closed previously.';
	}
}
