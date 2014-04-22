<?php
namespace Cyantree\Grout\Task;

use Cyantree\Grout\FilesystemLock;
use Cyantree\Grout\Task\Task;
use Cyantree\Grout\Tools\StringTools;

class TaskManager
{
    public $directory;

    public $timestampStopAt = 0;

    public $keepFailedTasks = false;

    public function queryTask(Task $t)
    {
        if ($t->id === null) {
            $t->id = StringTools::random(16);
        }

        $t->manager = null;

        $t->onSerialize();
        file_put_contents($this->directory . $t->priority . '_' . $t->id . '.tsk', serialize($t));

        /** @var $t Task */
        $t->manager = $this;
    }

    public function processQuery($stopAt = null, $useLock = true)
    {
        if(!$stopAt){
            $stopAt = $this->timestampStopAt;
        }

        $lock = null;

        if ($useLock) {
            $lock = new FilesystemLock($this->directory . 'lock.lck');

            if (!$lock->lock($stopAt)) {
                return;
            }
        }


        $tasks = glob($this->directory . '*.tsk');
        if ($tasks) {
            foreach ($tasks as $taskFile) {
                $time = microtime(true);

                /** @var $task Task */
                $task = unserialize(file_get_contents($taskFile));

                if ($task->executeAt !== null && $task->executeAt > $time) {
                    continue;
                }

                $task->manager = $this;
                $task->onUnserialize();

                if ($time + $task->estimatedDuration > $stopAt) {
                    break;
                }

                $basename = basename($taskFile);

                if (preg_match('!^active_[0-9]+_[a-zA-Z0-9]+\.tsk$!', $basename)) {
                    if ($this->keepFailedTasks) {
                        rename($taskFile, $this->directory . 'error_' . $task->priority . '_' . $task->id . '.tsk');

                    } else {
                        unlink($taskFile);
                    }

                    $task->onError();

                } else if (!preg_match('!^error_[0-9]+_[a-zA-Z0-9]+\.tsk$!', $basename)) {

                    $newTaskFile = $this->directory . 'active_' . $task->priority . '_' . $task->id . '.tsk';
                    rename($taskFile, $newTaskFile);

                    $task->execute();

                    unlink($newTaskFile);
                }
            }
        }

        if ($lock) {
            $lock->release();
        }
    }
}