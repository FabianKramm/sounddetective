<?php
namespace SoundDetective\Util;

class SafeFile{
    private $file;
    private $readOnly;

    function __construct($filePath, $readOnly = false)
    {
        $this->readOnly = $readOnly;

        if( $this->readOnly ){
            $this->file = fopen($filePath, "r");
        }
        else{
            $this->file = fopen($filePath, "c+");
        }

        if( !$this->file ){
            throw Exception("Couldn't open file: $filePath");
        }
    }

    public function truncateFile()
    {
        flock($this->file, LOCK_EX);
        ftruncate($this->file, 0);
        flock($this->file, LOCK_UN);
    }

    public function writeFile($content)
    {
        flock($this->file, LOCK_EX);
        fwrite($this->file, $content);
        fflush($this->file);
        flock($this->file, LOCK_UN);
    }

    public function readFile()
    {
        flock($this->file, LOCK_SH);
        $content = file_get_contents ($this->file);
        flock($this->file, LOCK_UN);

        return $content;
    }

    function __destruct()
    {
        fclose($this->file);
    }
}