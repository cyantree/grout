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

        $file = $this->directory . str_pad($t->priority, 3, '0', STR_PAD_LEFT) . '_' . $t->id . '.tsk';
        file_put_contents($file, serialize($t));

        if ($t->executeAt) {
            touch($file, $t->executeAt);
        }

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

                if (filemtime($taskFile) > $time) {
                    continue;
                }

                /** @var $task Task */
                $task = unserialize(file_get_contents($taskFile));
                $task->manager = $this;
                $task->onUnserialize();

                if ($time + $task->estimatedDuration > $stopAt) {
                    break;
                }

                $basename = basename($taskFile);

                if (preg_match('!^active_[0-9]+_[a-zA-Z0-9]+\.tsk$!', $basename)) {
                    if ($this->keepFailedTasks) {
                        rename($taskFile, $this->directory . 'error_' . str_pad($task->priority, 3, '0', STR_PAD_LEFT) . '_' . $task->id . '.tsk');

                    } else {
                        unlink($taskFile);
                    }

                    $task->onError();

                } else if (!preg_match('!^error_[0-9]+_[a-zA-Z0-9]+\.tsk$!', $basename)) {

                    $newTaskFile = $this->directory . 'active_' . str_pad($task->priority, 3, '0', STR_PAD_LEFT) . '_' . $task->id . '.tsk';
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