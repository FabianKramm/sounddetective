<?php
namespace SoundDetective\Util;

class Log{
    private $logString;
    const maxAmountLogEntries = 1024;

    private static $instance;

    function __construct()
    {
        $this->logString = [];
    }

    public static function getInstance()
    {
        if( self::$instance == null ){
            self::$instance = new Log();
        }

        return self::$instance;
    }

    public function clear()
    {
        $this->logString = [];
    }

    public function write($string)
    {
        if( count($this->logString) == self::maxAmountLogEntries ){
            array_shift($this->logString);
        }

        $t = microtime(true);
        $micro = sprintf("%06d",($t - floor($t)) * 1000000);

        $this->logString[] = date('H:i:s.') . "$micro: " . $string;
    }

    public function getLastEntry()
    {
        $size = count($this->logString);

        return ( $size ) ? $this->logString[$size - 1] : "";
    }

    public function getLog($join = PHP_EOL)
    {
        return implode($join, $this->logString);
    }

}