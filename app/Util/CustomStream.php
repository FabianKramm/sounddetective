<?php
namespace SoundDetective\Util;

class CustomStream extends Stream{
    private $commitFunc;

    function __construct($commitFunc)
    {
        $this->commitFunc = $commitFunc;
    }

    public function add($obj)
    {
        $this->data[] = $obj;
        $this->dataLength++;

        if( $this->dataLength >= $this->bufferSize ){
            $this->commit();
        }
    }

    public function commit()
    {
        if( $this->dataLength > 0 ){
            call_user_func($this->commitFunc, $this->data);

            $this->data = [];
            $this->dataLength = 0;
        }
    }

}