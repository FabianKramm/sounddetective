<?php
namespace SoundDetective\Util;

use SoundDetective\Util\Config;
use SoundDetective\Util\DB;
use SoundDetective\Util\Log;

class SoundDetectiveDB extends DB{
    protected $log;

    function __construct($host = NULL, $username = NULL, $password = NULL, $db = NULL, $port = NULL, $charset = 'utf8'){
        parent::__construct($host, $username, $password, $db, $port, $charset);

        $this->log = Log::getInstance();
    }

    public static function getInstance()
    {
        if (self::$_instance == null) {
            return new SoundDetectiveDB(Config::db_host, Config::db_user, Config::db_password, Config::db_name, Config::db_port);
        }
        else {
            return self::$_instance;
        }
    }

    public function massDelete($tableName, $keyName, $ids){
        if( count($ids) <= 0 ){
            return false;
        }

        $query = "DELETE FROM " . $tableName;
        $query .= " WHERE `" . $keyName . "` IN ('" . implode("','", $ids) . "')";

        $this->log->write($query);
        return $this->rawQuery($query);
    }

    public function massInsert($tableName, $columnNames, $data){
        if( count($data) <= 0 ){
            return false;
        }

        $query = "INSERT IGNORE INTO " . $tableName;

        $records = [];

        foreach ($data as $value){
            $t = "(";
            $is_first = true;

            foreach ($value as $_value) {
                if( !$is_first ){
                    $t .= ",";
                }
                else{
                    $is_first = false;
                }

                if( $_value === null ){
                    $t .= "NULL";
                }
                else{
                    $t .= "'" . $this->_mysqli->escape_string($_value) . "'";
                }
            }

            $t .= ")";
            $records[] = $t;
        }

        $query .= " (`" . join("`,`", $columnNames) . "`) VALUES " . join(",", $records);

        $this->log->write("INSERT IGNORE INTO " . $tableName);

        return $this->rawQuery($query);
    }

    /**
     * Overrides Rows with same primary id
     */
    public function massUpdate($tableName, $updateColumns, $notUpdateColumns, $data){
        if( count($data) <= 0 ){
            return false;
        }

        $query = "INSERT INTO " . $tableName;

        $records = [];

        $countUpdateColumns = count($updateColumns);
        $countNotUpdateColumns = count($notUpdateColumns);

        foreach ($data as $value){
            $t = "(";
            $is_first = true;

            foreach ($value as $_value) {
                if( !$is_first ){
                    $t .= ",";
                }
                else{
                    $is_first = false;
                }

                if( $_value === null ){
                    $t .= "NULL";
                }
                else{
                    $t .= "'" . $this->_mysqli->escape_string($_value) . "'";
                }
            }

            for($i=0;$i<$countNotUpdateColumns;$i++){
                $t .= ",'0'";
            }

            $t .= ")";
            $records[] = $t;
        }

        $query .= " (`" . join("`,`", array_merge($updateColumns, $notUpdateColumns)) . "`) VALUES " . join(",", $records);
        $query .= " ON DUPLICATE KEY UPDATE ";

        for ($i=0;$i<$countUpdateColumns;$i++) {
            $query .= $updateColumns[$i] . "=VALUES(" . $updateColumns[$i] . ")";

            if( $i + 1 < $countUpdateColumns ){
                $query .= ",";
            }
        }

        for ($i=0;$i<$countNotUpdateColumns;$i++) {
            $query .= "," . $notUpdateColumns[$i] . "=" . $notUpdateColumns[$i] . "";
        }

        $this->log->write("(UPDATE) INSERT INTO " . $tableName);
        return $this->rawQuery($query);
    }
}