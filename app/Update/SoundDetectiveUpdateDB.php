<?php
namespace SoundDetective\Update;

use SoundDetective\Util\SoundDetectiveDB;

class SoundDetectiveUpdateDB extends SoundDetectiveDB{
    function __construct($host = NULL, $username = NULL, $password = NULL, $db = NULL, $port = NULL, $charset = 'utf8'){
        parent::__construct($host, $username, $password, $db, $port, $charset);

        $this->log->write("Update Database initialized!");
    }

    public static function getInstance()
    {
        if (self::$_instance == null) {
            return new SoundDetectiveUpdateDB(Config::db_host, Config::db_user, Config::db_password, Config::db_name, Config::db_port);
        }
        else {
            return self::$_instance;
        }
    }
}