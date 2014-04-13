<?php
namespace Cyantree\Grout\App\Service\Drivers;

use Cyantree\Grout\App\Service\ServiceDriver;
use Cyantree\Grout\App\Service\ServiceResult;
use Cyantree\Grout\App\Task;
use Cyantree\Grout\App\Types\ContentType;
use Cyantree\Grout\Filter\ArrayFilter;

class JsonDriver extends ServiceDriver
{
    public $contentType = ContentType::TYPE_PLAIN;
    public $maxPackageSize = 1048576;

    /** @param $task Task */
    public function processTask($task)
    {
        parent::processTask($task);

        $data = $task->request->post->get('commands');

        if(!is_string($data)){
            $this->postResults(array(ServiceResult::createWithError('error', 'No data', '::GLOBAL::')));
            return;
        }

        if(strlen($data) > $this->maxPackageSize){
            $this->postResults(array(ServiceResult::createWithError('error', 'Data too large', '::GLOBAL::')));
            return;
        }

        $data = json_decode($data, true);

        if(json_last_error() != JSON_ERROR_NONE || !is_array($data)){
            $this->postResults(array(ServiceResult::createWithError('error', 'Invalid data', '::GLOBAL::')));
            return;
        }

        $results = array();

        $f = new ArrayFilter();
        foreach($data as $command){
            if (!is_array($command)) {
                $this->postResults(array(ServiceResult::createWithError('error', 'Invalid data', '::GLOBAL::')));
                return;
            }

            $f->setData($command);

            $id = $f->get('id');
            $command = $f->get('command');
            $data = $f->asFilter('data');

            $results[] = $this->_executeCommand($command, $data, $id);
        }

        $this->postResults($results);
    }

    private function _stringifyResults($results){
        return json_encode($results);
    }

    public function postResults($results)
    {
        $this->_task->response->postContent(json_encode($results), ContentType::TYPE_PLAIN_UTF8);
    }
}