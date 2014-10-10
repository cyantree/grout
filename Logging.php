<?php
namespace Cyantree\Grout;

class Logging
{
    private $tracks = array();
    public $file = 'log.txt';

    public $maxFilesize = 1000000;

    private $measurements = array();
    private $measurementCounter = 0;
    private $startTime;
    private $id;

    public function startMeasurement($id = null)
    {
        if ($id === null) {
            $id = $this->measurementCounter++;
        }
        $this->measurements[$id] = microtime(true);

        return $id;
    }

    public function stopMeasurement($id, $log)
    {
        self::log($log, microtime(true) - $this->measurements[$id]);
        unset($this->measurements[$id]);
    }

    public function start($id = '_START_', $startTime = null)
    {
        $this->id = mt_rand(1000, 9999);

        $this->startTime = $startTime ? $startTime : microtime(true);
        self::log($id);
    }

    public function log($text, $duration = null, $time = null)
    {
        array_push($this->tracks, $time ? $time : microtime(true), $text, $duration);
    }

    public function stop($text = '_STOP_')
    {
        self::log($text);

        $start = $this->startTime;

        $count = (count($this->tracks) / 3) >> 0;

        $i = 0;

        if ($this->maxFilesize && file_exists($this->file) && filesize($this->file) > $this->maxFilesize) {
            $f = fopen($this->file, 'w');

        } else {
            $f = fopen($this->file, 'a');
        }

        fwrite($f, $this->id . ': ' . date('Y-m-d H:i:s', $this->startTime) . ' - ' . $this->tracks[1] . chr(10));

        while ($i++ < $count - 1) {
            $duration = $this->tracks[3 * $i + 2];
            if (!$duration) {
                $duration = $this->tracks[3 * $i] - $this->tracks[3 * ($i - 1)];
                $durationFlag = 'm';

            } else {
                $durationFlag = 'c';
            }

            fwrite(
                $f,
                $this->id . ': ' . self::formatTime($duration) . $durationFlag
                . ': ' . self::formatTime($this->tracks[3 * $i] - $start)
                . ': ' . $this->tracks[3 * $i + 1] . chr(10)
            );
        }

        fwrite($f, chr(10));

        fclose($f);
    }

    private function formatTime($time)
    {
        return str_pad(((($time * 10000) >> 0) / 10000), 7, '0');
    }
}
