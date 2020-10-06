<?php

class Db
{
    // jy_walk db
    private static $db_instance;

    public static function getDbInstance () {
        if (self::$db_instance === null) {
            self::$db_instance = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
            self::$db_instance->exec("SET time_zone = '+8:00'");
            self::$db_instance->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }
        return self::$db_instance;
    }
}