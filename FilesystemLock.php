<?php
namespace Cyantree\Grout;

class FilesystemLock
{
    public $directory;

    function __construct($directory = null)
    {
        $this->directory = $directory;
    }


    public function lock($expiresOn)
    {
        $exists = is_dir($this->directory);
        $valid = $exists && filemtime($this->directory) > time();

        if ($valid) {
            return false;
        }

        // Lock exists but has been expired
        if ($exists) {
            // Delete current lock
            $deleteDir = @rmdir($this->directory);

            if (!$deleteDir) {
                return false;
            }
        }

        $makeDir = @mkdir($this->directory, 0777, true);

        // Race condition
        if (!$makeDir) {
            return false;
        }

        touch($this->directory, $expiresOn);

        return true;
    }

    public function release()
    {
        @rmdir($this->directory);
    }
}