<?php
namespace Cyantree\Grout;

class Logging
{
    private $_tracks = array();
    public $file = 'log.txt';

    public $maxFilesize = 1000000;
    public $truncateOnMaxFilesize = 20000;

    private $_measurements = array();
    private $_measurementCounter = 0;
    private $_startTime;
    private $_id;

    public function startMeasurement($id = null)
    {
        if ($id === null) {
            $id = $this->_measurementCounter++;
        }
        $this->_measurements[$id] = microtime(true);

        return $id;
    }

    public function stopMeasurement($id, $log)
    {
        self::log($log, microtime(true) - $this->_measurements[$id]);
        unset($this->_measurements[$id]);
    }

    public function start($id = '_START_', $startTime = null)
    {
        $this->_id = mt_rand(1000, 9999);

        $this->_startTime = $startTime ? $startTime : microtime(true);
        self::log($id);
    }

    public function log($text, $duration = null, $time = null)
    {
        array_push($this->_tracks, $time ? $time : microtime(true), $text, $duration);
    }

    public function stop($text = '_STOP_')
    {
        self::log($text);

        $start = $this->_startTime;

        $count = (count($this->_tracks) / 3) >> 0;

        $i = 0;

        if ($this->maxFilesize && file_exists($this->file) && filesize($this->file) > $this->maxFilesize) {
            $data = substr(file_get_contents($this->file), $this->truncateOnMaxFilesize);
            $f = fopen($this->file, 'w');
            fwrite($f, $data);
        } else
            $f = fopen($this->file, 'a');

        fwrite($f, $this->_id . ': ' . date('Y-m-d H:i:s', $this->_startTime) . ' - ' . $this->_tracks[1] . chr(10));

        while ($i++ < $count - 1) {
            $duration = $this->_tracks[3 * $i + 2];
            if (!$duration) {
                $duration = $this->_tracks[3 * $i] - $this->_tracks[3 * ($i - 1)];
                $durationFlag = 'm';
            } else $durationFlag = 'c';

            fwrite($f, $this->_id . ': ' . self::_formatTime($duration) . $durationFlag . ': ' . self::_formatTime($this->_tracks[3 * $i] - $start) . ': ' . $this->_tracks[3 * $i + 1] . chr(10));
        }

        fwrite($f, chr(10));

        fclose($f);
    }

    private function _formatTime($time)
    {
        return str_pad(((($time * 10000) >> 0) / 10000), 7, '0');
    }
}