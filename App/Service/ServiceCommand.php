<?php
namespace Cyantree\Grout\App\Service;

use Cyantree\Grout\App\App;
use Cyantree\Grout\App\Task;
use Cyantree\Grout\Filter\ArrayFilter;

class ServiceCommand
{
    /** @var ArrayFilter */
    public $data;

    /** @var ServiceResult */
    public $result;

    /** @var Task */
    public $task;

    /** @var App */
    public $app;

    public function execute()
    {

    }

    public function onError()
    {

    }
}
