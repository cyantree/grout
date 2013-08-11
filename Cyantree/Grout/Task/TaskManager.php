<?php
namespace Cyantree\Grout\Task;

use Cyantree\Grout\Tools\StringTools;

class TaskManager
{
    public $directory;

    public $timestampStopAt = 0;

    public $unlockAfterProcessing = true;

    public function queryTask($t)
    {
        if ($t->id === null) {
            $t->id = StringTools::random(16);
        }

        file_put_contents($this->directory . $t->priority . '_' . $t->id . '.tsk', serialize($t));

        /** @var $t Task */
        $t->manager = $this;
    }

    public function processQuery($stopAt = null)
    {
        if(!$stopAt){
            $stopAt = $this->timestampStopAt;
        }

        if (is_file($this->directory . 'lock.lck') && filemtime($this->directory . 'lock.lck') > time()) return;

        $id = StringTools::random(8);
        file_put_contents($this->directory . 'lock.lck', $id);
        touch($this->directory . 'lock.lck', $this->timestampStopAt);

        sleep(1);

        // Race condition occurred
        if (file_get_contents($this->directory . 'lock.lck') != $id) return;

        $tasks = glob($this->directory . '*.tsk');
        if ($tasks) {
            foreach ($tasks as $taskFile) {
                $time = microtime(true);

                /** @var $task Task */
                $task = unserialize(file_get_contents($taskFile));
                if ($task->executeAt !== null && $task->executeAt > $time) continue;

                $task->manager = $this;

                if ($time + $task->estimatedDuration > $stopAt) break;


                if (preg_match('!active_[0-9]+_[a-zA-Z0-9]+\.tsk$!', $taskFile)) {
                    unlink($this->directory . 'active_' . $task->priority . '_' . $task->id . '.tsk');
                    $task->onError();
                } else {
                    rename($taskFile, $this->directory . 'active_' . $task->priority . '_' . $task->id . '.tsk');
                    $task->execute();
                }
            }
        }

        if ($this->unlockAfterProcessing) unlink($this->directory . 'lock.lck');
    }

    public function finishTask($task)
    {
        unlink($this->directory . 'active_' . $task->priority . '_' . $task->id . '.tsk');
    }
}