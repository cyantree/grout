<?php
namespace Cyantree\Grout\Job;

class FailedJobDetails
{
    /** @var Job */
    public $job;

    /** @var \Exception[] */
    public $exceptions;

    function __construct(Job $job, $exceptions)
    {
        $this->job = $job;
        $this->exceptions = $exceptions;
    }
}
