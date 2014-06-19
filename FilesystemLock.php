<?php
namespace Cyantree\Grout;

class FilesystemLock
{
    public $directory;

    function __construct($directory = null)
    {
        $this->directory = $directory;
    }

    private function _lock($lifetime)
    {
        $exists = is_dir($this->directory);
        $valid = $exists && (filemtime($this->directory) + $lifetime) > time();

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

        return true;
    }

    public function lock($lifetime, $waitDuration = 0, $waitInterval = .2)
    {
        if (!$waitDuration) {
            return $this->_lock($lifetime);
        }

        $waitStarted = microtime(true);

        while (!$this->_lock($lifetime)) {
            if (microtime(true) - $waitStarted > $waitDuration) {
                return false;
            }

            usleep($waitInterval * 1000000);
            clearstatcache(true, $this->directory);
        }

        return true;
    }

    public function release()
    {
        @rmdir($this->directory);
    }
}