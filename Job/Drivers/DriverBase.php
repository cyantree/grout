<?php
namespace Cyantree\Grout\Job\Drivers;

use Cyantree\Grout\Job\Job;

abstract class DriverBase
{
    /**
     * @param $jobs Job[]
     */
    abstract public function queueJobs($jobs);

    /**
     * @param $jobs Job[]
     */
    abstract public function completeJobs($jobs);

    /** @return Job[] */
    abstract public function getJobs();
}
