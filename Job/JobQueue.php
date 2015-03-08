<?php
namespace Cyantree\Grout\Job;

use Cyantree\Grout\Job\Drivers\DriverBase;

class JobQueue
{
    /** @var DriverBase */
    private $driver;

    private $queueIsProcessing = false;
    private $jobsToBeQueued;

    public function __construct(DriverBase $driver)
    {
        $this->driver = $driver;
    }

    public function process($maxDuration = 0)
    {
        $this->queueIsProcessing = true;

        $result = new JobQueueResult();
        $stopAt = null;

        if ($maxDuration) {
            $stopAt = microtime(true) + $maxDuration;
        }

        if ($stopAt !== null && microtime(true) > $stopAt) {
            return $result;
        }

        $stopRequested = false;

        while (!$stopRequested && ($jobs = $this->driver->getJobs())) {
            $completedJobs = array();

            foreach ($jobs as $job) {
                if ($stopAt !== null && (microtime(true) + $job->estimatedDuration) > $stopAt) {
                    $stopRequested = true;
                    break;
                }

                $exceptions = null;

                try {
                    $job->queue = $this;
                    $job->execute();

                } catch (\Exception $e) {
                    $exceptions = array($e);

                    try {
                        $job->onError();

                    } catch (\Exception $e) {
                        $exceptions[] = $e;
                    }
                }

                $job->queue = null;

                if ($exceptions) {
                    $result->failed[] = new FailedJobDetails($job, $exceptions);

                } else {
                    $result->completed[] = $job;
                }

                $completedJobs[] = $job;
            }

            if ($completedJobs) {
                $this->driver->completeJobs($completedJobs);
            }
        }

        $this->queueIsProcessing = false;

        if ($this->jobsToBeQueued !== null) {
            $this->queue($this->jobsToBeQueued);
        }

        return $result;
    }

    public function queue($jobOrJobs)
    {
        if ($jobOrJobs === null) {
            return;
        }

        if (!is_array($jobOrJobs)) {
            $jobOrJobs = array($jobOrJobs);
        }

        if (!$jobOrJobs) {
            return;
        }

        if ($this->queueIsProcessing) {
            if ($this->jobsToBeQueued === null) {
                $this->jobsToBeQueued = $jobOrJobs;

            } else {
                foreach ($jobOrJobs as $job) {
                    $this->jobsToBeQueued[] = $job;
                }
            }

            return;
        }

        /** @var Job[] $jobOrJobs */

        foreach ($jobOrJobs as $job) {
            $job->onQueue();
        }

        $this->driver->queueJobs($jobOrJobs);
    }
}
