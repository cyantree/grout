<?php
namespace Cyantree\Grout\App\Service;

use Cyantree\Grout\App\Task;
use Cyantree\Grout\ErrorWrapper\PhpErrorException;
use Cyantree\Grout\ErrorWrapper\PhpWarningException;

class ServiceDriver
{
    public $commandNamespaces = array();

    /** @var Task */
    protected $_task;

    /** @param $task Task */
    public function processTask($task)
    {
        $this->_task = $task;
    }

    /** @param $task Task */
    public function processError($task)
    {
        $this->_task = $task;

        $this->postResults(array(ServiceResult::createWithError('error', 'An unknown error occurred', '::GLOBAL::')));
    }

    public function postResults($results)
    {
    }

    protected function _executeCommand($command, $data = null, $id = null)
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
                    $c->task = $this->_task;
                    $c->app = $this->_task->app;
                    $c->data = $data;
                    $c->result = new ServiceResult($id);
                    $c->execute();

                    return $c->result;

                } else {
                    return ServiceResult::createWithError('error', 'Invalid command', $id);
                }
            }

        } catch (PhpWarningException $e) {
            $this->_task->app->events->trigger('logException', $e);
            return ServiceResult::createWithError('error', 'An unknown error has occurred', $id);

        } catch (PhpErrorException $e) {
            $this->_task->app->events->trigger('logException', $e);
            return ServiceResult::createWithError('error', 'An unknown error has occurred', $id);
        }
    }
}
