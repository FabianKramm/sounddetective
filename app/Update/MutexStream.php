<?php
namespace SoundDetective\Update;

use Exception;
use Memcached;

class MutexIsLockedException extends Exception { }

class MutexStream {
    protected $id;
    protected $memcached;

    protected $fillFunc = NULL;

    protected $hasEnded;
    protected $expires_in = 7200;

    protected $maxWait = 10000;
    private $lastLocked = FALSE;

    function __construct($id, $fillFunc)
    {
        $this->id = $id;
        $this->memcached = new Memcached;
        $this->memcached->addServer('localhost', 11211) or die ("Could not connect to Memcached");

        $this->fillFunc = $fillFunc;
        $this->hasEnded = $this->memcached->get("MutexStream_" . $this->id . "_ended");
    }

    protected function fillStream()
    {
        $this->hasEnded = $this->memcached->get("MutexStream_" . $this->id . "_ended");

        if( $this->hasEnded ){
            return [];
        }

        $data = call_user_func($this->fillFunc);

        if( count($data) == 0 ){
            $this->hasEnded = TRUE;
            $this->memcached->set("MutexStream_" . $this->id . "_ended", TRUE, time() + $this->expires_in);
            return [];
        }

        return $data;
    }

    public function getIdentifier()
    {
        return $this->id;
    }

    public function reset()
    {
        $this->memcached->delete("MutexStream_" . $this->id);
        $this->memcached->delete("MutexStream_" . $this->id . "_mutex");
        $this->memcached->delete("MutexStream_" . $this->id . "_ended");
    }
    
    public function lock()
    {
        $startTime = microtime(true);

        do {
            if( !$this->memcached->get("MutexStream_" . $this->id . "_mutex") ){
                $this->memcached->set("MutexStream_" . $this->id . "_mutex", TRUE, time() + $this->expires_in);
                $this->lastLocked = TRUE;
                return TRUE;
            }

            usleep(100000); // 100 millis
        } while(intval((microtime(true) - $startTime) * 1000) < $this->maxWait);

        throw new MutexIsLockedException("Mutex is locked");
    }
    
    public function unlock($FORCE = FALSE)
    {
        if( $this->lastLocked || $FORCE ){
            $this->lastLocked = FALSE;
            $this->memcached->set("MutexStream_" . $this->id . "_mutex", FALSE, time() + $this->expires_in);

            return TRUE;
        }
        else{
            return FALSE;
        }
    }

    /*
     * Careful returned amount can be < amount
     */
    public function getData($amount, $LOCK = TRUE)
    {
        if( $this->hasEnded ){
            return [];
        }

        if($LOCK){
            $this->lock();
        }

        $data = $this->memcached->get("MutexStream_" . $this->id);

        if( !$data || count($data) == 0 ){
            $data = $this->fillStream();

            if( count($data) == 0 ){
                return $data;
            }
        }

        $retArr = array_slice($data, 0, $amount);
        $newArr = (count($data) > $amount) ? array_slice($data, $amount) : [];

        $this->memcached->set("MutexStream_" . $this->id, $newArr, time() + $this->expires_in);

        if($LOCK){
            $this->unlock();
        }

        return $retArr;
    }

    public function isFinished() {
        return $this->hasEnded;
    }
}