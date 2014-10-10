<?php
namespace Cyantree\Grout\App\Service;

use Cyantree\Grout\App\Task;
use Cyantree\Grout\ErrorWrapper\PhpErrorException;
use Cyantree\Grout\ErrorWrapper\PhpWarningException;

class ServiceDriver
{
    public $commandNamespaces = array();

    /** @var Task */
    protected $task;

    /** @param $task Task */
    public function processTask($task)
    {
        $this->task = $task;
    }

    /** @param $task Task */
    public function processError($task)
    {
        $this->task = $task;

        $this->postResults(array(ServiceResult::createWithError('error', 'An unknown error occurred', '::GLOBAL::')));
    }

    public function postResults($results)
    {
    }

    protected function executeCommand($command, $data = null, $id = null)
    {
        try {
            if (!preg_match('!^[a-zA-Z0-9_/]+$!', $command)) {
                return ServiceResult::createWithError('error', 'Invalid command', $id);

            } else {
                $command = str_replace('/', '\\', $command);
                $found = false;

                $className = null;
                foreach ($this->commandNamespaces as $commandNamespace) {
                    $className = $commandNamespace . $command . 'Command';
                    if (class_exists($className)) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    /** @var ServiceCommand $c */
                    $c = new $className();
                    $c->task = $this->task;
                    $c->app = $this->task->app;
                    $c->data = $data;
                    $c->result = new ServiceResult($id);
                    $c->execute();

                    return $c->result;

                } else {
                    return ServiceResult::createWithError('error', 'Invalid command', $id);
                }
            }

        } catch (PhpWarningException $e) {
            $this->task->app->events->trigger('logException', $e);
            return ServiceResult::createWithError('error', 'An unknown error has occurred', $id);

        } catch (PhpErrorException $e) {
            $this->task->app->events->trigger('logException', $e);
            return ServiceResult::createWithError('error', 'An unknown error has occurred', $id);
        }
    }
}
