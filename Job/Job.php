<?php
namespace Cyantree\Grout\Job;

abstract class Job
{
    public $estimatedDuration = 0;

    public $internalId;

    /** @var int 1 (low) - 9 (high) */
    public $priority = 5;

    /** @var JobQueue */
    public $queue;

    abstract public function execute();

    public function onQueue()
    {

    }

    public function onError()
    {

    }
}
