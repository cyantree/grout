<?php
namespace Cyantree\Grout\Job\Drivers;

use Cyantree\Grout\Job\Job;
use Cyantree\Grout\Tools\StringTools;

class FilesystemDriver extends DriverBase
{
    private $directory;

    function __construct($directory)
    {
        $this->directory = $directory;
    }

    /** @return Job[] */
    public function getJobs()
    {
        $dir = opendir($this->directory);

        $jobs = array();

        while (($entry = readdir($dir)) !== false) {
            if (strrpos($entry, '.job') != strlen($entry) - 4) {
                continue;
            }

            /** @var Job $job */
            $job = unserialize(file_get_contents($this->directory . $entry));
            $job->internalId = $entry;

            $jobs[] = $job;
        }

        return $jobs;
    }

    /**
     * @param $jobs Job[]
     */
    public function queueJobs($jobs)
    {
        foreach ($jobs as $job) {
            do {
                $id = StringTools::random(32);
                $filePath = $this->directory . (10 - $job->priority) . '_' . $id . '.job';

            } while (is_file($filePath));

            $job->internalId = null;

            file_put_contents($filePath, serialize($job));
        }
    }

    /**
     * @param $jobs Job[]
     */
    public function completeJobs($jobs)
    {
        foreach ($jobs as $job) {
            unlink($this->directory . $job->internalId);
            $job->internalId = null;
        }
    }
}
