<?php

namespace SoundDetective\Util;

abstract class Stream
{
    protected $data = [];
    protected $dataLength = 0;
    protected $bufferSize = 2000;

    public function clear()
    {
        $this->data = [];
    }

    public function setBufferSize($size)
    {
        $this->bufferSize = $size;
    }

    abstract public function add($obj);
    abstract public function commit();
}

