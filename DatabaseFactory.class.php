<?php

interface IDatabase{

  public function isConnected();
  public function executeQuery($query);
  public function executeNonQuery($query);
  public function setAutoCommit($vBool);
  public function commit();
  public function rollback();
  public function close();
  public function getDbType();
}


class DatabaseType
{
    public const MYSQL = 0;
    public const ORACLE = 1;
}

class DatabaseFactory
{

    private function __construct()
    {
        //empty
    }

    public static function getDatabase(int $dbType): IDatabase
    {
        //echo "\$dbname = $dbname\n";
        if ($dbType === DatabaseType::MYSQL) {
            include_once('MySql.class.php');
            return MySql::getInstance();
        } else if ($dbType === DatabaseType::ORACLE) {
            include_once('Oracle.class.php');
            return Oracle::getInstance();
        } else
            return null;
    }
}
