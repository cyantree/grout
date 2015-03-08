<?php
namespace Cyantree\Grout\Job;

class JobQueueResult
{
    /** @var Job[] */
    public $completed = array();

    /** @var FailedJobDetails[] */
    public $failed = array();
}
