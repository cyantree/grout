<?php
namespace Cyantree\Grout\Task;

use Cyantree\Grout\Task\TaskManager;
use Cyantree\Grout\Tools\StringTools;

class Task
{
    public $executeAt = null;

    public $estimatedDuration = 0;

    public $id;

    public $priority = 5;

    /** @var TaskManager */
    public $manager;

    public function execute()
    {

    }

    public function onError()
    {

    }

    protected function _query($createNewID = true)
    {
        if ($createNewID) {
            $this->id = StringTools::random(8);
        }
    }

    protected function _finish()
    {
        if ($this->manager) {
            $this->manager->finishTask($this);
        }
    }
}