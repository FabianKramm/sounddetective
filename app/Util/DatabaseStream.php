<?php
namespace SoundDetective\Util;

use SoundDetective\Update\SoundDetectiveUpdateDB;
use SoundDetective\Util\Log;
use Exception;

class DatabaseStream extends Stream{
    const UPDATE = 0;
    const INSERT = 1;
    const DELETE = 2;

    private $tableName;
    private $columnNames;
    private $type;

    private $doNotUpdateColumns;

    /** @var SoundDetectiveUpdateDB */
    private $db;

    /** @var \SoundDetective\Util\Log */
    private $log;

    function __construct($tableName, $columnNames, $type = self::INSERT, $doNotUpdateColumns = NULL)
    {
        $this->tableName = $tableName;
        $this->columnNames = $columnNames;

        $this->type = $type;
        $this->doNotUpdateColumns = $doNotUpdateColumns;

        $this->log = Log::getInstance();
        $this->db = SoundDetectiveUpdateDB::getInstance();
    }

    public function add($obj)
    {
        if( is_array($this->columnNames) && count($obj) !== count($this->columnNames) )
        {
            throw new Exception("Wrong Dimensions in added Object!");
        }

        if( !is_array($this->columnNames) && is_array($obj) )
        {
            throw new Exception("Added Object is an Array, but no Array expected!");
        }

        $this->data[] = $obj;
        $this->dataLength++;

        if( $this->dataLength >= $this->bufferSize ){
            $this->commit();
        }
    }

    public function commit()
    {
        if( $this->dataLength > 0 ){
            if( $this->type == self::INSERT ){
                $this->db->massInsert($this->tableName, $this->columnNames, $this->data);
            }
            else if ( $this->type == self::UPDATE ){
                $this->db->massUpdate($this->tableName, $this->columnNames, $this->doNotUpdateColumns, $this->data);
            }
            else if ( $this->type == self::DELETE ){
                $this->db->massDelete($this->tableName, $this->columnNames, $this->data);
            }

            $this->data = [];
            $this->dataLength = 0;
        }
    }

}